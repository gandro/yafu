<?php

class File {
    protected $FileID;

    public $Filename;
    public $Size;
    public $Mimetype;

    protected $AdditionalProperties;

    public function File($fileID) {
        global $CONFIG;

        $this->FileID = basename($fileID);

        if(isset($fileID) && is_dir($CONFIG->Core['FilePool'].'/'.$this->FileID)) {
            $fileRoot = realpath($CONFIG->Core['FilePool']).'/'.$this->FileID.'/';
            if(($infoArray = parse_ini_file($fileRoot.'info', true)) && is_readable($fileRoot.'data')) {
                $this->Filename = (string) $infoArray["Attributes"]["Filename"];
                $this->Size = (integer) $infoArray["Attributes"]["Size"];
                $this->Mimetype = (string) $infoArray["Attributes"]["Mimetype"];

                $this->AdditionalProperties = (array) $infoArray["AdditionalProperties"];
            } else {
                trigger_error(t("Corrupted file! ID: %s", $this->FileID), E_USER_ERROR);
            }
        } elseif(isset($fileID)) {
            trigger_error(t("File not found! ID: %s", $this->FileID), E_USER_ERROR);
        }

    }

    public static function create($fileID, $filename, $size, $mimetype, $additional = array()) {
            $newFile = new File(null);

            $newFile->FileID = basename($fileID);

            $newFile->Filename = basename($filename);
            $newFile->Size = intval($size);
            $newFile->Mimetype = (string) $mimetype;

            $newFile->AdditionalProperties = $additional;

            return $newFile;
    }

    public function save() {
        global $CONFIG;

        $fileRoot = realpath($CONFIG->Core['FilePool']).'/'.$this->FileID.'/';

        if(!is_writable($CONFIG->Core['FilePool'])) {
            trigger_error(t("File pool directory not writable!"), E_USER_ERROR);
        } elseif(!file_exists($fileRoot)) {
            mkdir($fileRoot);
            touch($fileRoot.'info');
            touch($fileRoot.'data');
        }

        touch($CONFIG->Core['FilePool']);

        $infoArray = array(
                "Attributes" => array(
                    "Filename" => (string) $this->Filename,
                    "Size" => (integer) $this->Size,
                    "Mimetype" => (string) $this->Mimetype),
                "AdditionalProperties" => (array) $this->AdditionalProperties
        );

        return write_ini_file($fileRoot.'info', $infoArray, true);
    }

    public function getDataPath() {
        global $CONFIG;

        return realpath($CONFIG->Core['FilePool'].'/'.$this->FileID.'/data');
    }

    public function delete() {
        global $CONFIG;

        if($this->exists($this->FileID)) {
            $fileRoot = realpath($CONFIG->Core['FilePool']).'/'.$this->FileID.'/';
            foreach(glob($fileRoot.'*') as $fileItem) {
                unlink($fileItem);
            }
            return rmdir($fileRoot);
        }
        return false;
    }

    public function isIdentical($file) {
        return (md5_file($file) === md5_file($this->getDataPath()));
    }

    public static function exists($fileID) {
        global $CONFIG;

        $fileRoot = realpath($CONFIG->Core['FilePool']).'/'.basename($fileID).'/';
        if(is_dir($fileRoot)) {
            if(is_file($fileRoot.'info') && is_file($fileRoot.'data')) {
                return true;
            } else {
                trigger_error(t("Corrupted file! ID: %s", $fileID), E_USER_WARNING);
            }
        }
        return false;
    }

    public function __set($name, $value) {
        if($name != 'FileID' && is_scalar($value)) {
            $this->AdditionalProperties[$name] = $value;
        }
    }

    public function __get($name) {
        if(array_key_exists($name, $this->AdditionalProperties)) {
            return $this->AdditionalProperties[$name];
        } elseif($name == 'FileID') {
            return $this->FileID;
        }

        trigger_error(t("Invalid property request %s", $name), E_USER_NOTICE);
        return null;
    }

    public function __isset($name) {
        return isset($this->AdditionalProperties[$name]);
    }

    public function __unset($name) {
        unset($this->AdditionalProperties[$name]);
    }
}

?>
