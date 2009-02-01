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
