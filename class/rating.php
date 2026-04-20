<?php
/**
 * XcreateRating Class
 * Yıldızlı oylama sistemi (1-5 yıldız)
 *
 * Eren Yumak tarafından kodlanmıştır — Aymak
 */

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

class XcreateRatingHandler
{
    protected $db;
    protected $table;
    protected $tableExists = null;

    public function __construct($db)
    {
        $this->db    = $db;
        $this->table = $db->prefix('xcreate_ratings');
    }

    /**
     * Dışarıdan tablo durumunu sorgulayabilmek için (hata ayıklama)
     */
    /**
     * Dışarıdan tablo durumunu sorgulayabilmek için (hata ayıklama)
     */
    public function isTableReady()
    {
        return $this->ensureTable();
    }

    /**
     * Tablo var mı kontrol et; yoksa otomatik oluştur.
     */
    protected function ensureTable()
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        // Tablo var mı?
        $res = $this->db->query("SHOW TABLES LIKE '{$this->table}'");
        if ($res && $this->db->getRowsNum($res) > 0) {
            $this->tableExists = true;
            return true;
        }

        // Yoksa otomatik oluştur
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `rating_id`      int(11) unsigned NOT NULL AUTO_INCREMENT,
            `rating_item_id` int(11) unsigned NOT NULL,
            `rating_uid`     int(11) unsigned NOT NULL DEFAULT '0',
            `rating_ip`      varchar(45)      NOT NULL DEFAULT '',
            `rating_score`   tinyint(1)       NOT NULL DEFAULT '0',
            `rating_created` int(11)          NOT NULL DEFAULT '0',
            PRIMARY KEY (`rating_id`),
            UNIQUE KEY `unique_member_vote` (`rating_item_id`, `rating_uid`),
            KEY `rating_item_id` (`rating_item_id`),
            KEY `rating_uid`     (`rating_uid`),
            KEY `rating_ip`      (`rating_ip`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $result = $this->db->queryF($sql);
        $this->tableExists = (bool)$result;
        return $this->tableExists;
    }

    /**
     * Boş istatistik dizisi döner
     */
    protected function emptyStats()
    {
        return array(
            'count'        => 0,
            'total'        => 0,
            'average'      => 0.0,
            'average_str'  => '0.0',
            'stars_full'   => 0,
            'stars_half'   => 0,
            'stars_empty'  => 5,
            'distribution' => array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0),
            'dist_pct'     => array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0),
        );
    }

    /**
     * Bir item'ın özet istatistiklerini döner.
     *
     * @param int $item_id
     * @return array
     *   count, total, average, average_str,
     *   stars_full, stars_half, stars_empty,
     *   distribution[1..5], dist_pct[1..5]
     */
    public function getStats($item_id)
    {
        if (!$this->ensureTable()) {
            return $this->emptyStats();
        }

        $item_id = intval($item_id);

        $sql = "SELECT COUNT(*) AS cnt, COALESCE(SUM(rating_score),0) AS total,
                       COALESCE(AVG(rating_score),0) AS avg
                FROM {$this->table}
                WHERE rating_item_id = $item_id";

        $res = $this->db->query($sql);
        if (!$res) {
            return $this->emptyStats();
        }

        $row   = $this->db->fetchArray($res);
        $count = (int)$row['cnt'];
        $total = (int)$row['total'];
        $avg   = $count > 0 ? (float)$row['avg'] : 0.0;
        $average = round($avg, 1);

        // Yıldız hesabı
        $full = (int)floor($average);
        $frac = $average - $full;
        $half = 0;
        if ($frac >= 0.75) {
            $full++;
        } elseif ($frac >= 0.25) {
            $half = 1;
        }
        $empty = max(0, 5 - $full - $half);

        // Dağılım
        $dist     = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0);
        $dist_pct = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0);

        $sql2 = "SELECT rating_score, COUNT(*) AS cnt
                 FROM {$this->table}
                 WHERE rating_item_id = $item_id
                 GROUP BY rating_score";
        $res2 = $this->db->query($sql2);
        if ($res2) {
            while ($r = $this->db->fetchArray($res2)) {
                $key = intval($r['rating_score']);
                if (isset($dist[$key])) {
                    $dist[$key] = (int)$r['cnt'];
                }
            }
        }
        if ($count > 0) {
            foreach ($dist as $k => $v) {
                $dist_pct[$k] = round(($v / $count) * 100, 1);
            }
        }

        return array(
            'count'        => $count,
            'total'        => $total,
            'average'      => $average,
            'average_str'  => number_format($average, 1),
            'stars_full'   => $full,
            'stars_half'   => $half,
            'stars_empty'  => $empty,
            'distribution' => $dist,
            'dist_pct'     => $dist_pct,
        );
    }

    /**
     * Kullanıcının bu item'a daha önce oy verip vermediğini kontrol eder.
     *
     * @param int    $item_id
     * @param int    $uid     0 = misafir
     * @param string $ip
     * @return int  Önceki oyu (0 = hiç oy vermemiş)
     */
    public function getUserVote($item_id, $uid = 0, $ip = '')
    {
        if (!$this->ensureTable()) {
            return 0;
        }

        $item_id = intval($item_id);
        $uid     = intval($uid);

        if ($uid > 0) {
            $sql = "SELECT rating_score FROM {$this->table}
                    WHERE rating_item_id = $item_id AND rating_uid = $uid LIMIT 1";
        } else {
            $safe_ip = $this->db->escape($ip);
            $sql = "SELECT rating_score FROM {$this->table}
                    WHERE rating_item_id = $item_id AND rating_uid = 0 AND rating_ip = '$safe_ip' LIMIT 1";
        }

        $res = $this->db->query($sql);
        if (!$res) {
            return 0;
        }
        if ($row = $this->db->fetchArray($res)) {
            return (int)$row['rating_score'];
        }
        return 0;
    }

    /**
     * Oy kaydet veya güncelle.
     *
     * @param int    $item_id
     * @param int    $score   1-5
     * @param int    $uid     0 = misafir
     * @param string $ip
     * @return bool
     */
    public function saveVote($item_id, $score, $uid = 0, $ip = '')
    {
        if (!$this->ensureTable()) {
            return false;
        }

        $item_id = intval($item_id);
        $score   = max(1, min(5, intval($score)));
        $uid     = intval($uid);
        $safe_ip = $this->db->escape($ip);
        $now     = time();

        $existing = $this->getUserVote($item_id, $uid, $ip);

        if ($existing > 0) {
            if ($uid > 0) {
                $sql = "UPDATE {$this->table}
                        SET rating_score = $score, rating_created = $now
                        WHERE rating_item_id = $item_id AND rating_uid = $uid";
            } else {
                $sql = "UPDATE {$this->table}
                        SET rating_score = $score, rating_created = $now
                        WHERE rating_item_id = $item_id AND rating_uid = 0 AND rating_ip = '$safe_ip'";
            }
        } else {
            $sql = "INSERT INTO {$this->table}
                        (rating_item_id, rating_uid, rating_ip, rating_score, rating_created)
                    VALUES ($item_id, $uid, '$safe_ip', $score, $now)";
        }

        return (bool)$this->db->queryF($sql);
    }

    /**
     * Bir item'ın tüm oylarını sil (item silinince).
     */
    public function deleteByItem($item_id)
    {
        if (!$this->ensureTable()) {
            return true;
        }
        $item_id = intval($item_id);
        return (bool)$this->db->queryF("DELETE FROM {$this->table} WHERE rating_item_id = $item_id");
    }

    /**
     * Smarty için rating array'i döner.
     *
     * @param int    $item_id
     * @param int    $uid
     * @param string $ip
     * @return array
     */
    public function getRatingData($item_id, $uid = 0, $ip = '')
    {
        $stats     = $this->getStats($item_id);
        $user_vote = $this->getUserVote($item_id, $uid, $ip);

        return array_merge($stats, array(
            'item_id'    => $item_id,
            'user_vote'  => $user_vote,
            'user_voted' => ($user_vote > 0) ? 1 : 0,
        ));
    }
}
