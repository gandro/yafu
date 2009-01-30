<?php

$_HOOKS = array();

abstract class Plugin {
    abstract public function initPlugin();

    public static $PluginName = "Untitled Plugin";
    public static $PluginAuthor = "nobody";
    public static $PluginVersion = 0;

    public static function releaseHook($hookName, $parameters) {
        global $_HOOKS;

        if(isset($_HOOKS[$hookName]) && is_array($_HOOKS[$hookName])) {
            foreach($_HOOKS[$hookName] as $callbackFunction) {
                call_user_func_array($callbackFunction, $parameters);
            }
        }
    }

    public static function loadPlugins() {
        //TODO!!!!
    }

    protected function registerHook($hookName, array $callbackFunction, $priority = 0) {
        global $_HOOKS;

        if($priority < -100 || $priority > 100) {
            trigger_error(t("Plugin \"%s\": Priority of hook \"%s\" is out of range", self::$PluginName, $hookName), E_USER_WARNING);
            $priority = ($priority<0) ? -100 : 100;
        }

        $fullCallback = array($this, $callbackFunction);
        if(isset($_HOOKS[$hookName]) && in_array($fullCallback, $_HOOKS[$hookName])) {
            trigger_error(t("Plugin \"%s\": Hook \"%s\" is already registered", self::$PluginName, $hookName), E_USER_WARNING);
            return false;
        }

        $_HOOKS[$hookName][$priority] = $fullCallback;
        krsort($_HOOKS[$hookName]);
        return true;
    }

    protected function unregisterHook($hookName, $callbackFunction) {
        global $_HOOKS;

        $fullCallback = array($this, $callbackFunction);
        if(isset($_HOOKS[$hookName]) && ($key = array_search($fullCallback, $_HOOKS[$hookName]))) {
            unset($_HOOKS[$hookName][$key]);
            return true;
        }

        return false;
    }
}

?>
