<?php

abstract class Plugin {
    abstract public function initPlugin();

    public static $PluginName = "Untitled Plugin";
    public static $PluginAuthor = "nobody";
    public static $PluginVersion = 0;

    public static $loadedPlugins = array();

    final public static function triggerHook($hookName, array $parameters) {
        global $HOOKS;

        $returnString = '';

        if(isset($HOOKS[$hookName]) && is_array($HOOKS[$hookName])) {
            foreach($HOOKS[$hookName] as $callbackFunction) {
                $returnString .= call_user_func_array($callbackFunction, $parameters);
            }
        }

        return $returnString;
    }

    final public static function loadPlugins() {
        global $CONFIG;

        $PluginRoot = $CONFIG->Plugin['PluginDir'];
        $activePlugins = explode(',', $CONFIG->Plugin['ActivePlugins']);
        $activePlugins = array_map('trim', $activePlugins);
        $activePlugins = array_filter($activePlugins);

        foreach($activePlugins as $Plugin) {
            if(
                (is_dir($PluginRoot.'/'.$Plugin)) &&
                (is_file($PluginRoot.'/'.$Plugin.'/'.$Plugin.'.php')) &&
                (include($PluginRoot.'/'.$Plugin.'/'.$Plugin.'.php')) &&
                (get_parent_class($Plugin) == 'Plugin')
            ) {
                self::$loadedPlugins[$Plugin] = new $Plugin();
                self::$loadedPlugins[$Plugin]->initPlugin();
            } else {
                trigger_error(t("Cannot load plugin %s", $Plugin), E_USER_WARNING);
            }
        }
    }

    final protected function registerHook($hookName, $callbackFunction, $priority = 0) {
        global $HOOKS;

        if($priority < -100 || $priority > 100) {
            trigger_error(t("Plugin \"%s\": Priority of hook \"%s\" is out of range", self::$PluginName, $hookName), E_USER_WARNING);
            $priority = ($priority<0) ? -100 : 100;
        }
        if(isset($HOOKS[$hookName]) && in_array($callbackFunction, $HOOKS[$hookName])) {
            trigger_error(t("Plugin \"%s\": Hook \"%s\" is already registered", self::$PluginName, $hookName), E_USER_WARNING);
            return false;
        }

        $HOOKS[$hookName][$priority] = $callbackFunction;
        krsort($HOOKS[$hookName]);
        return true;
    }

    final protected function unregisterHook($hookName, $callbackFunction) {
        global $HOOKS;

        $fullCallback = array($this, $callbackFunction);
        if(isset($HOOKS[$hookName]) && ($key = array_search($fullCallback, $HOOKS[$hookName]))) {
            unset($HOOKS[$hookName][$key]);
            return true;
        }

        return false;
    }
}

?>
