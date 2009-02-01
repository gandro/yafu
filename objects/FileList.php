<?php

class FileList implements Iterator {

    protected $fileList;

    public function FileList() {
        global $CONFIG;

        if($CONFIG->FileList['UseCaching']) {
            if(@filemtime($CONFIG->FileList['IndexFile']) < 
                filemtime($CONFIG->Core['FilePool'])) 
            {
                $this->refreshList();
                $this->writeCachedList();
            } else {
                $this->loadCachedList();
            }
        } else {
            $this->refreshList();
        }
    }

    public function sortBy($property) {

    }

    protected function refreshList() {
        global $CONFIG;
        $this->fileList = array();

        if($filePoolDir = opendir($CONFIG->Core['FilePool'])) {
            while(($fileID = readdir($filePoolDir)) !== false) {
                if($fileID != '.' && $fileID != '..' && File::exists($fileID)) {
                    $this->fileList[] = new File($fileID);
                }
            }
        } else {
            trigger_error(t("Failed to open file pool directory!"), E_USER_ERROR);
        }
    }

    protected function loadCachedList() {
        global $CONFIG;
        $this->fileList = require($CONFIG->FileList['IndexFile']);
    }

    protected function writeCachedList() {
        global $CONFIG;

        if(!($indexHandler = fopen($CONFIG->FileList['IndexFile'], 'w'))) {
            trigger_error(t("Failed to cache file index!"), E_USER_WARNING);
        }

        flock($indexHandler, LOCK_EX);
        fwrite($indexHandler, "<?php return unserialize(<<<END\n");
        fwrite($indexHandler, serialize($this->fileList));
        fwrite($indexHandler, "\nEND\n); ?>");
        flock($indexHandler,LOCK_UN);
        fclose($indexHandler);
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
