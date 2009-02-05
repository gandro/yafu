<?php 

class Language {

    protected $lookupTable = array();
    public $currentLanguage = "None";

    public function Language($language = null) {
        global $CONFIG;
        $languages = array();

        if(is_null($language) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $CONFIG->Language['AutoDetect']) {
            /* CC-BY 3.0 Xavier Lepaul <xavier AT lepaul DOT fr> 2007 */
            foreach(explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']) as $language) {
                preg_match('/([A-Za-z]{1,8}[\-[A-Za-z]{1,8}]*)(;q=([0-9\.]+))?/', $language, $matches);
                if(count($matches) != 2 && count($matches) != 4) { continue; }
                $languages[$matches[1]] = (count($matches)==2) ? 1 : $matches[3];
            }
            arsort($languages);
            $languages = array_keys($languages);
        } elseif(is_null($language) && isset($CONFIG->Language['Default'])) {
            $languages[] = $CONFIG->Language['Default'];
        } else {
            $languages[] = $language;
        }

        foreach($languages as $currentLanguage) {
            if($languageFile = $this->findLanguageFile($currentLanguage)) { break; }
        }

        if(is_null($languageFile)) {
            foreach($languages as $currentLanguage) {
                if($languageFile = $this->findLanguageFile($currentLanguage, true)) { break; }
            }
        }

        if(!is_null($languageFile)) {
            $this->loadLanguageFile($languageFile);
            setlocale(LC_ALL, $languages);
        }
    }

    public function translate($string) {
        $string = trim($string);

        if(isset($this->lookupTable[$string])) {
            return $this->lookupTable[$string];
        } else {
            return $string;
        }
    }

    public function findLanguageFile($language, $fuzzyHits = false) {
        global $CONFIG;
        $languageDir = realpath($CONFIG->Language['LanguageDir']).'/';
        $extension = '.lang';

        $language = strtolower(str_replace('_','-',$language));
        

        if(file_exists($languageDir.$language.$extension)) {
            return $languageDir.$language.$extension;
        }

        if(strpos($language, '-')) {
            list($language, $region) = explode('-', $language, 2);
            if(file_exists($languageDir.$language.$extension)) {
                return $languageDir.$language.$extension;
            }
        }

        if($fuzzyHits) {
            $languageList = glob($languageDir.$language.'-*'.$extension);
            if($languageList) {
                return $languageList[0];
            }
        }
        
        return null;
    }

    public static function listLanguages() {
        global $CONFIG;
        $languageDir = realpath($CONFIG->Language['LanguageDir']).'/';
        $extension = '.lang';
        $fileList = glob($languageDir.'*'.$extension);
        $languageList = array();

        if($fileList !== false) {
            foreach($fileList as $languageFileName) {
                $fullName = "Unnamed language";
                    $languageFile = fopen($languageFileName, 'r');
                    while (!feof($languageFile)) {
                        if(substr(($currentLine = fgets($languageFile)), 0, 2) == 'n:') {
                            $fullName = str_extract($currentLine);
                            break;
                        }
                    }
                $languageFileName = basename(substr($languageFileName, 0, strlen($extension)*-1));
                $languageList[$languageFileName] = $fullName;
            }
        }
        
        return $languageList;
    }

    protected function loadLanguageFile($filepath, $onlyFullname = false) {
        $languageFile = fopen($filepath, 'r');

        $currentString = null;
        $lineCounter = 0;
        while (!feof($languageFile)) {
            $currentLine = trim(fgets($languageFile));
            $lineCounter++;

            if(empty($currentLine)) { 
                continue;
            }

            switch($currentLine[0]) {
                case 's':
                    if($currentLine[1] == ':' && ($tmpString = str_extract(substr($currentLine, 2)))) {
                        $currentString = $tmpString;
                        continue 2;
                    }
                    break;
                case 't':
                    if($currentLine[1] == ':' && isset($currentString) && ($tmpString = str_extract(substr($currentLine, 2)))) {
                        $this->lookupTable[$currentString] = $tmpString;
                        $currentString = null;
                        continue 2;
                    }
                    break;
                case 'n':
                    if($currentLine[1] == ':' && ($tmpString = str_extract(substr($currentLine, 2)))) {
                        $this->currentLanguage = $tmpString;
                        continue 2;
                    }
                    break;
                case '#':
                    continue 2;
            }
            trigger_error("Syntax error in language file $filepath on line $lineCounter", E_USER_WARNING);
        }
        fclose($languageFile);
    }
}

?>
