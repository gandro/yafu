<?php 

class Language {

    protected $lookupTable = array();
    public $currentLanguage = null;

    public function Language($language = null) {
        global $CONFIG;

        $requestedLanguages = array();

        if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $CONFIG->Language['AutoDetect']) {

            /* Note: The following code is taken from http://vrac.adwain.org/:
             *
             *         Copyright © 2007 Xavier Lepaul <xavier AT lepaul DOT fr>
             *         License: Creative Commons Attribution 3.0
             *                  http://creativecommons.org/licenses/by/3.0/
             */

            foreach(explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']) as $language) {
                preg_match('/([A-Za-z]{1,8}[\-[A-Za-z]{1,8}]*)(;q=([0-9\.]+))?/', $language, $matches);
                if(count($matches) != 2 && count($matches) != 4) { continue; }
                $requestedLanguages[$matches[1]] = (count($matches)==2) ? 1 : $matches[3];
            }

            arsort($requestedLanguages);
            $requestedLanguages = array_keys($requestedLanguages);
        } else {
            $requestedLanguages[] = $language;
        }

        $requestedLanguages[] = $CONFIG->Language['Default'];
        $requestedLanguages = array_map(array('self', 'getValidLanguageCode'), $requestedLanguages);
        setlocale(LC_ALL, $requestedLanguages);

        $this->currentLanguage = self::getBestLanguage($requestedLanguages);

        if($this->currentLanguage) {
            setlocale(LC_ALL, $this->currentLanguage);
        }

        $this->registerContextRoot("core", $CONFIG->Language['CoreDir']);
    }

    public function translate($string, $context = null) {
        $searchContext = is_string($context) ? array($context) : array_keys($this->lookupTable);

        foreach($searchContext as $context) {
            if(isset($this->lookupTable[$context][$string])) {
                return $this->lookupTable[$context][$string];
            }
        }

        return $string;
    }

    public function registerContextRoot($contextName, $contextRootDir) {
        global $CONFIG;

        $languageFiles = glob($contextRootDir.'/'.$this->currentLanguage.
                        '/*'.$CONFIG->Language['Extension']);
        $languageFiles = ($languageFiles === false) ? array() : $languageFiles;

        foreach($languageFiles as $languageFile) {
            $contextName .= '.'.strtolower(pathinfo($languageFile, PATHINFO_FILENAME));
            if($CONFIG->Language['UseCaching']) {
                $cachedFile = $CONFIG->Language['CacheDir'].'/'.
                                $this->currentLanguage.'.'.$contextName.'.php';

                if(file_exists($cachedFile) && 
                    (filemtime($cachedFile) > filemtime($languageFile))
                ) {
                    $this->lookupTable[$contextName] = unserializeFromFile($cachedFile);
                    if(is_array($this->lookupTable[$contextName])) {
                        continue;
                    }
                } else {
                    $this->lookupTable[$contextName] = $this->loadLanguageFile($languageFile);
                    if(!serializeToFile($this->lookupTable[$contextName], $cachedFile)) {
                        trigger_error("Failed to write cache file: $cachedFile", E_USER_WARNING);
                    }
                    continue;
                }
            }

            $this->lookupTable[$contextName] = $this->loadLanguageFile($languageFile);
        }
    }

    public static function listLanguages() {
        global $CONFIG;

        $languageList = @parse_ini_file(realpath($CONFIG->Language['CoreDir']).'/alias.ini');
        if(!$languageList) {
            trigger_error("Failed to load language aliases: ".
                ErrorHandler::getLastError(), E_USER_WARNING);
        }
        return $languageList;
    }

    public static function getBestLanguage(array $sortedArray) {
        $availableLanguages = array_keys(self::listLanguages());
        $secondChoice = false;

        foreach($sortedArray as $searchedLanguage) {
            $searchedLanguage = self::getValidLanguageCode($searchedLanguage);
            foreach($availableLanguages as $languageIndex => $availableLanguage) {
                $availableLanguage = self::getValidLanguageCode($availableLanguage);
                if($searchedLanguage == $availableLanguage) {
                    return $availableLanguages[$languageIndex];
                } elseif(!$secondChoice && 
                    strtolower(strtok($searchedLanguage, '_')) == $availableLanguage
                ) {
                    $secondChoice = $availableLanguages[$languageIndex];
                }
            }
        }

        return $secondChoice;
    }

    public static function getValidLanguageCode($languageCode) {
            $languageCode = strtolower(strtr($languageCode, '-', '_'));
            if($subLanguageOffset = strpos($languageCode, '_')) {
                $languageCode = substr_replace(
                    $languageCode,
                    strtoupper(substr($languageCode, $subLanguageOffset)),
                    $subLanguageOffset
                );
            }
            return $languageCode;
    }

    protected function loadLanguageFile($filepath) {
        $stringArray = array();
        $languageFile = fopen($filepath, 'r');

        if(!$languageFile) {
            trigger_error("Cannot open language file $filepath", E_USER_WARNING);
            return null;
        }

        $lineCounter = 0;
        $currentId = null;
        while(!feof($languageFile)) {
            $lineCounter++;

            $currentLine = trim(fgets($languageFile));
            $currentString = str_extract($currentLine);

            if(empty($currentLine) || $currentLine[0] == '#') {
                continue;
            } elseif(substr($currentLine, 0, 3) == 'msg') {
                switch(strtok($currentLine, ' ')) {
                    case 'msgid':
                        $currentId = $currentString;
                        continue 2;
                    case 'msgstr':
                        $stringArray[$currentId] = $currentString;
                        continue 2;
                }
            } elseif(!is_null($currentId) && !empty($currentString)) {
                if(array_key_exists($currentId, $stringArray)) {
                    $stringArray[$currentId] .= $currentString;
                } else {
                    $currentId .= $currentString;
                }
                continue;
            }

            trigger_error("Syntax error in language file $filepath on line $lineCounter", E_USER_WARNING);
        }
        fclose($languageFile);

        return $stringArray;
    }
}

?>
