<?php
/**
 * Debug Log Viewer
 */

include_once '../../../include/cp_header.php';

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/logger.php';

$language = $GLOBALS['xoopsConfig']['language'];
foreach (['admin', 'main'] as $lf) {
    $path = XOOPS_ROOT_PATH . "/modules/xcreate/language/{$language}/{$lf}.php";
    include_once file_exists($path) ? $path : XOOPS_ROOT_PATH . "/modules/xcreate/language/english/{$lf}.php";
}

$op = isset($_GET['op']) ? $_GET['op'] : 'view';

switch ($op) {
    case 'clear':
        $logFile = XcreateLogger::getLogPath();
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        redirect_header('debug_log.php', 2, _AM_XCREATE_DEBUG_LOG_CLEARED);
        break;
        
    case 'download':
        $logFile = XcreateLogger::getLogPath();
        if (file_exists($logFile)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="xcreate_debug_' . date('Y-m-d') . '.log"');
            readfile($logFile);
            exit;
        }
        break;
        
    case 'view':
    default:
        xoops_cp_header();
        
        $adminObject = \Xmf\Module\Admin::getInstance();
        $adminObject->displayNavigation('debug_log.php');
        
        echo '<style>
        .log-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            font-family: "Courier New", monospace;
            font-size: 13px;
            line-height: 1.6;
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }
        .log-line {
            margin: 2px 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .log-line.info { color: #4ec9b0; }
        .log-line.error { color: #f48771; }
        .log-line.success { color: #b5cea8; }
        .log-line.warning { color: #dcdcaa; }
        .log-header {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .log-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .btn-log {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-log.refresh {
            background: #4CAF50;
            color: white;
        }
        .btn-log.download {
            background: #2196F3;
            color: white;
        }
        .btn-log.clear {
            background: #f44336;
            color: white;
        }
        </style>';
        
        echo '<div class="log-header">';
        echo '<h2>🔍 ' . _AM_XCREATE_DEBUG_LOG_TITLE . '</h2>';
        echo '<p><strong>' . _AM_XCREATE_DEBUG_LOG_FILE . '</strong> ' . XcreateLogger::getLogPath() . '</p>';
        echo '<p><strong>' . _AM_XCREATE_DEBUG_LOG_UPDATED . '</strong> ' . date('Y-m-d H:i:s') . '</p>';
        echo '</div>';
        
        echo '<div class="log-actions">';
        echo '<a href="debug_log.php?op=view" class="btn-log refresh" onclick="location.reload(); return false;">🔄 ' . _AM_XCREATE_DEBUG_LOG_REFRESH . '</a>';
        echo '<a href="debug_log.php?op=download" class="btn-log download">⬇️ ' . _AM_XCREATE_DEBUG_LOG_DOWNLOAD . '</a>';
        echo '<a href="debug_log.php?op=clear" class="btn-log clear" onclick="return confirm(\'' . addslashes(_AM_XCREATE_DEBUG_LOG_CLEAR_CONFIRM) . '\');">🗑️ ' . _AM_XCREATE_DEBUG_LOG_CLEAR . '</a>';
        echo '</div>';
        
        $logContent = XcreateLogger::getLogContent(500);
        
        echo '<div class="log-viewer">';
        
        if (empty($logContent) || $logContent == _AM_XCREATE_DEBUG_LOG_NOT_CREATED || $logContent == "Log dosyası henüz oluşturulmadı.") {
            echo '<div class="log-line warning">⚠️ ' . _AM_XCREATE_DEBUG_LOG_EMPTY . '</div>';
            echo '<div class="log-line info">💡 ' . _AM_XCREATE_DEBUG_LOG_HINT . '</div>';
        } else {
            $lines = explode("\n", $logContent);
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                $class = 'info';
                if (stripos($line, 'ERROR') !== false || stripos($line, 'FAILED') !== false) {
                    $class = 'error';
                } elseif (stripos($line, 'SUCCESS') !== false) {
                    $class = 'success';
                } elseif (stripos($line, 'WARNING') !== false) {
                    $class = 'warning';
                }
                
                echo '<div class="log-line ' . $class . '">' . htmlspecialchars($line) . '</div>';
            }
        }
        
        echo '</div>';
        
        echo '<script>
        // Auto-scroll to bottom
        var logViewer = document.querySelector(".log-viewer");
        if (logViewer) {
            logViewer.scrollTop = logViewer.scrollHeight;
        }
        
        // Auto-refresh every 5 seconds
        setTimeout(function() {
            location.reload();
        }, 5000);
        </script>';
        
        xoops_cp_footer();
        break;
}

?>
