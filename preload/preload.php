<?php
/**
 * Xcreate Preload
 * Registers Smarty plugins
 */

class XcreatePreload extends XoopsPreloadItem
{
    public static function eventCoreHeaderStart($args)
    {
        global $xoopsTpl;
        
        if (isset($xoopsTpl) && is_object($xoopsTpl)) {
            // Register Smarty plugin
            $plugin_dir = XOOPS_ROOT_PATH . '/modules/xcreate/plugins';
            if (is_dir($plugin_dir)) {
                $xoopsTpl->plugins_dir[] = $plugin_dir;
            }
        }
    }
}

?>
