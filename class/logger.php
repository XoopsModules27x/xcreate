<?php
/**
 * Xcreate Debug Logger
 */

class XcreateLogger {
    private static $logFile = null;
    
    public static function init() {
        if (self::$logFile === null) {
            $logDir = XOOPS_ROOT_PATH . '/modules/xcreate/logs/';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            self::$logFile = $logDir . 'debug_' . date('Y-m-d') . '.log';
        }
    }
    
    public static function log($message, $type = 'INFO') {
        self::init();
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type] $message\n";
        
        // Dosyaya yaz
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
        
        // Ayrıca PHP error_log'a da yaz
        error_log($message);
    }
    
    public static function logArray($label, $array) {
        self::log($label . ": " . print_r($array, true));
    }
    
    public static function getLogPath() {
        self::init();
        return self::$logFile;
    }
    
    public static function getLogContent($lines = 100) {
        self::init();
        if (!file_exists(self::$logFile)) {
            return "Log dosyası henüz oluşturulmadı.";
        }
        
        $content = file_get_contents(self::$logFile);
        $allLines = explode("\n", $content);
        $lastLines = array_slice($allLines, -$lines);
        return implode("\n", $lastLines);
    }
}

?>
