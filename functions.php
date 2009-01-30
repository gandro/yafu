<?php

function getHttpRoot() {
        $httpRoot = empty($_SERVER['HTTPS']) ? "http://" : "https://";
        $httpRoot .= $_SERVER['SERVER_NAME'];
        $httpRoot .= ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443) ?  ':'.$_SERVER['SERVER_PORT'] : '';
        $httpRoot .= ((dirname($_SERVER['PHP_SELF']) != '/') ? dirname($_SERVER['PHP_SELF']) : '').'/';

        return $httpRoot;
}

function str_eval($str) {
    if(version_compare(PHP_VERSION, '5.3.0', '<') && get_magic_quotes_gpc()) {
        $str = stripslashes($str);
    }
    if(!is_utf8($str)) {
        $str = utf8_encode($str);
    }
    return $str;
}

function str_html($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', false);
}

function createTempFile($create = true) {
    global $CONFIG;

    if(strtolower($CONFIG->Core['TempFilePrefix']) == 'auto') {
        $path = sys_get_temp_dir();
        $prefix = 'yafu2_';
    } else {
        $path = dirname($CONFIG->Core['TempFilePrefix']);
        $prefix = basename($CONFIG->Core['TempFilePrefix']);
    }

    if($create) {
        return tempnam($path, $prefix);
    } else {
        return $path.'/'.$prefix;
    }
}

function removeLeftOverFiles() {
    $maxTimestamp = time() - ini_get("max_execution_time");

    foreach (glob(getTempFile(false).'*') as $filename) {
        if(filemtime($filename) < $maxTimestamp) {
            unlink($filename);
        }
    }

}

function is_utf8($str) {
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

function write_ini_file($filename, $array, $process_sections = false, $startcomment = "") {
    $iniFile = fopen(realpath($filename), 'w');
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
