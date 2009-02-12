<?php

class FileList implements Iterator {

    protected $fileList;

    public function FileList() {
        global $CONFIG;

        if($CONFIG->FileList['UseCaching']) {
            if($this->loadCachedList()) {
                if($this->isOldCache()) {
                    $this->updateFileList();
                    $this->writeCachedList();
                }
            } else {
                $this->createFileList();
                $this->writeCachedList();
            }
        } else {
            $this->createFileList();
        }
    }

    public function sortBy($property) {
        switch($property) {
            case 'Filename':
            case 'name':
                $callbackFunction = create_function('$a,$b',
                    'return strcasecmp($a->Filename, $b->Filename);'
                );
                break;
            case 'Mimetype':
            case 'type':
                $callbackFunction = create_function('$a,$b',
                    'return strcasecmp($a->Mimetype, $b->Mimetype);'
                );
                break;
            case 'Size':
            case 'size':
                $callbackFunction = create_function('$a,$b','
                    return ($a->Size == $b->Size)?0:(($a->Size < $b->Size)?-1:1);'
                );
                break;
            default:
                return;
        }
        usort($this->fileList, $callbackFunction);
    }

    public function searchFor($query) {
        /* TODO: Plugin hook */
        foreach($this->fileList as $index => $File) {
            if(stripos($File->Filename, $query) === false) {
                unset($this->fileList[$index]);
            }
        }
    }

    public function count() {
        return count($this->fileList);
    }

    protected function createFileList() {
        global $CONFIG;

        $this->fileList = array();

        if($filePoolDir = opendir($CONFIG->Core['FilePool'])) {
            while(($fileID = readdir($filePoolDir)) !== false) {
                if($fileID != '.' && $fileID != '..' && File::exists($fileID)) {
                    $this->fileList[$fileID] = new File($fileID);
                }
            }
        } else {
            trigger_error(t("Failed to open file pool directory!"), E_USER_ERROR);
        }
    }

    protected function updateFileList() {
        global $CONFIG;
        $changeLog = $CONFIG->Core['FilePool'].'/changeLog';

        $filesToAdd = file($changeLog, FILE_SKIP_EMPTY_LINES|FILE_IGNORE_NEW_LINES);

        foreach($filesToAdd as $fileID) {
            if(File::exists($fileID)) {
                $this->fileList[$fileID] = new File($fileID);
            }
        }
        unlink($changeLog);
    }

    protected function loadCachedList() {
        global $CONFIG;

        if(file_exists($CONFIG->FileList['IndexFile'])) {
            $this->fileList = unserializeFromFile($CONFIG->FileList['IndexFile']);
            return is_array($this->fileList);
        } else {
            return false;
        }
    }

    protected function writeCachedList() {
        global $CONFIG;

        if(!serializeToFile($this->fileList, $CONFIG->FileList['IndexFile'])) {
            trigger_error(t("Failed to cache file index!"), E_USER_WARNING);
        }
    }

    protected function isOldCache() {
        global $CONFIG;

        return file_exists($CONFIG->Core['FilePool'].'/changeLog');
    }

    public static function addToQueue($fileID) {
        global $CONFIG;

        return file_put_contents($CONFIG->Core['FilePool'].'/changeLog', 
                                $fileID.PHP_EOL, FILE_APPEND|LOCK_EX);
    }

    /* stupid wrappers for Iterator interface */

    public function rewind() {
        reset($this->fileList);
    }

    public function current() {
        return current($this->fileList);
    }

    public function key() {
        return key($this->fileList);
    }

    public function next() {
        return next($this->fileList);
    }

    public function valid() {
        return $this->current() instanceof File;
    }

}

?>
