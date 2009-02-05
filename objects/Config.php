<?php

class Config {

   /*
    * Default settings - DO NOT EDIT!
    *
    * These can be overwritten in the configuration file "yafu2.conf".
    */

    public $Core = array(
            'Title' => "Yet Another File Upload <small>2.0</small>",
            'FilePool' => 'files/',
            'MaxFilesize' => 10485760,
            'MagicFile' => '/usr/share/misc/magic',
            'DebugInfo' => false,
            'TempFilePrefix' => 'auto',
            'SiPrefixes' => false,
            'ShortLinks' => false,
            'AllowFileUpload' => true,
            'AllowTextUpload' => true,
            'AllowLinkUpload' => true,
    );

    public $Language = array(
            'AutoDetect' => true,
            'Default' => 'en',
            'LanguageDir' => 'i18n/',
    );

    public $Template = array(
            'TemplateDir' => 'html/',
            'CacheDir' => '.cache/',
            'OpenTag' => '<!--[',
            'CloseTag' => ']-->',
            'UseCaching' => true,
            'ImagePath' => 'images/',
    );

    public $FileList = array(
            'UseCaching' => true,
            'IndexFile' => '.cachedFileList.php',
    );

    /* End of default settings */

    public function Config($filepath) {
        if(file_exists($filepath)) {
            $tmpArray = parse_ini_file($this->ConfigFilePath($filepath), true);
            if(!$tmpArray) {
                trigger_error("Cannot parse configuration file!", E_USER_ERROR);
            }
            foreach($tmpArray as $variable => $value) {
                if(is_array($value) && isset($this->$variable)) {
                    $this->$variable = array_merge($this->$variable, $value);
                } else {
                    $this->$variable = $value;
                }
            }
        } else {
            $this->ConfigFilePath($filepath);
        }
    }

    protected function ConfigFilePath($path = null) {
        static $configFile = null;

        if(isset($path) && is_string($path)) {
            if(!file_exists($path)) {
                touch($path);
            }
            $configFile = realpath($path);
        }
        return $configFile;
    }

    public function saveChanges($change = null) {
        static $hasChanged = null;

        $hasChanged = is_bool($change) ? $change : $hasChanged;
        return (boolean) $hasChanged;
    }

    protected function writeChanges() {
        if($this->saveChanges()) {
            $unassignedItems = $assignedItems = array();
            foreach($this as $variable => $value) {
                if(is_scalar($value)) {
                    $unassignedItems[$variable] = $value;
                } else {
                    $assignedItems[$variable] = $value;
                }
            }
            $allItems = array_merge($unassignedItems, $assignedItems);
            write_ini_file(
                $this->ConfigFilePath(),
                $allItems,
                true,
                "This file was generated by yafu2. See help files for more information."
            );
        }
    }

    public function __destruct() {
        $this->writeChanges();
    }

}

?>
