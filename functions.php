<?php

function getHttpRoot() {
        static $httpRoot;

        if(!isset($httpRoot)) {
            $httpRoot = empty($_SERVER['HTTPS']) ? "http://" : "https://";
            $httpRoot .= $_SERVER['SERVER_NAME'];
            $httpRoot .= ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ?  ':'.$_SERVER['SERVER_PORT'] : '';
            $httpRoot .= ((dirname($_SERVER['SCRIPT_NAME']) != '/') ? dirname($_SERVER['SCRIPT_NAME']) : '').'/';
        }

        return $httpRoot;
}

function str_eval($str, $noUTF8 = false) {
    if(version_compare(PHP_VERSION, '5.3.0', '<') && get_magic_quotes_gpc()) {
        $str = stripslashes($str);
    }
    if(!$noUTF8 && !is_utf8($str)) {
        $str = utf8_encode($str);
    }
    return $str;
}

function str_html($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', false);
}

function str_extract($line) {
    $subString = null;
    $fullString = '';

    for($i=0; $i<strlen($line); $i++) {
        if(is_null($subString) && $line[$i] == '"') {
            $subString = '';
        } elseif(!is_null($subString)) {
            switch($line[$i]) {
                case '"':
                    $fullString .= $subString;
                    $subString = null;
                    break;
                case '\\':
                    if($line[$i+1] == '"') {
                        $subString .= $line[++$i];
                    } elseif($line[$i+1] == 'n') {
                        $subString .= "\n";
                        $i++;
                    } else {
                         $subString .= '\\';
                    }
                    break;
                default:
                    $subString .= $line[$i];
            }
        }
    }
    return $fullString;
}

function serializeToFile($struct, $filename) {
    return file_put_contents(
                $filename, 
                "<?php return unserialize(<<<END\n".
                serialize($struct).
                "\nEND\n); ?>",
                LOCK_EX
            ) !== false;
}

function unserializeFromFile($filename) {
    return include($filename);
}

function getScriptDuration() {
    global $startTime;

    return microtime(true)-$startTime;
}

function createTempFile() {
    global $CONFIG;

    if(strtolower($CONFIG->Core['TempFilePrefix']) == 'auto') {
        $path = sys_get_temp_dir();
        $prefix = 'yafu2_';
    } else {
        $path = dirname($CONFIG->Core['TempFilePrefix']);
        $prefix = basename($CONFIG->Core['TempFilePrefix']);
    }

    addFileForRemoval($path.'/'.$prefix.'*', ini_get("max_execution_time"));
    return tempnam($path, $prefix);
}

function addFileForRemoval($filePattern, $maxAge = 0) {
    static $fileList = array();

    if(is_string($filePattern)) {
            $fileList[$filePattern] = $maxAge;
    }

    return $fileList;
}

function removeLeftOverFiles() {
    foreach(addFileForRemoval(null) as $filePattern => $maxAge) {
        $maxTimestamp = time() - $maxAge;

        $filesToRemove = glob($filePattern);
        foreach((is_array($filesToRemove) ? $filesToRemove : array()) as $filename) {
            if(filemtime($filename) < $maxTimestamp) {
                unlink($filename);
            }
        }
    }
}

function is_utf8($str) {
        /* from http://www.php.net/manual/en/function.mb-detect-encoding.php#50087 */
        return preg_match('%^(?:
              [\x09\x0A\x0D\x20-\x7E]            # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
            |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
        )*$%xs', $str); 
}

function ascii_encode($str) {

    $str = is_utf8($str) ? str_replace(
        array('À','Á','Â','Ã','Ä','Å','Æ','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï',
              'Ð','Ñ','Ò','Ó','Ô','Õ','Ö','Ø','Ù','Ú','Û','Ü','Ý','Þ','ß','à',
              'á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï','ð',
              'ñ','ò','ó','ô','õ','ö','ø','ù','ú','û','ü','ý','ý','þ','ÿ','Ŕ','ŕ'),

        array('A','A','A','A','AE','A','AE','C','E','E','E','E','I','I','I','I',
              'D','N','O','O','O','O','OE','O','U','U','U','UE','Y','P','ss','a',
              'a','a','a','ae','a','ae','c','e','e','e','e','i','i','i','i','d',
              'n','o','o','o','o','oe','o','u','u','u','ue','y','y','p','y','R','r'),
    $str) : $str;

    for($i=0;$i<strlen($str);$i++) {
        $str[$i] = (ord($str[$i]) < 128) ? $str[$i] : '_';
    }
    return $str;
}

function write_ini_file($filename, $array, $process_sections = false, $startcomment = "") {
    if(!($iniFile = fopen(realpath($filename), 'w'))) {
        return false;
    }

    flock($iniFile, LOCK_EX);
    if(trim($startcomment) != "") {
        fwrite($iniFile, "; ".$startcomment."\n");
    }
    foreach($array as $section => $items) {
        if(is_array($items)) {
            if($process_sections) {
                if(ftell($iniFile) > 0) {
                    fwrite($iniFile, "\n");
                }
                fwrite($iniFile, "[$section]\n");
            }
        } else {
            $items = array($section => $items);
        }
        foreach($items as $key => $value) {
            if($value === '' || $value === false) {
                $value = 'no';
            } elseif($value === '1' || $value === true) {
                $value = 'yes';
            } elseif(is_null($value)) {
                $value = 'null';
            } elseif(!is_numeric($value)) {
                $value = "\"".addslashes($value)."\"";
            }
            fwrite($iniFile, $key." = ".$value."\n");
        }
    }
    return fclose($iniFile);
}

?>
