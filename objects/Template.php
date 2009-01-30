<?php

class Template {
    protected $Filename = "";

    protected $rawCode = null;
    protected $compiledCode = null;

    protected $cachedFile = null;

    protected $templateVariables = array();

    public function Template($filename) {
        global $CONFIG;

        if(!($this->Filename = realpath($CONFIG->Template['TemplateDir'].'/'.$filename))
                                                && !($this->Filename = realpath($filename))) {
            trigger_error(t("Template file \"%s\" not found", $filename), E_USER_ERROR); 
        }

        if($CONFIG->Template['UseCaching']) {
            $fileId = basename($this->Filename).'_'.sprintf('%x', crc32($this->Filename));

            $this->cachedFile = realpath($CONFIG->Template['CacheDir']).
                '/'.$fileId.'_'.filemtime($this->Filename);

            if(!file_exists($this->cachedFile)) {
                $this->compile();
                file_put_contents($this->cachedFile, $this->compiledCode);
            }

            foreach(glob(realpath($CONFIG->Template['CacheDir']).'/'.$fileId.'_*') as $oldFile) {
                if($oldFile != $this->cachedFile) {
                    unlink($oldFile);
                }
            }
        } else {
            $this->compile();
        }
    }

    public function __set($name, $value) {
        $this->templateVariables[$name] = $value;
    }

    public function __get($name) {
        if(array_key_exists($name, $this->templateVariables)) {
            return $this->templateVariables[$name];
        }

        trigger_error(t("Invalid property request %s", $name), E_USER_NOTICE);
        return null;
    }

    public function __isset($name) {
        return isset($this->templateVariables[$name]);
    }

    public function __unset($name) {
        unset($this->templateVariables[$name]);
    }

    public function compile() {
        global $CONFIG;

        $openTag = preg_quote($CONFIG->Template['OpenTag']);
        $closeTag = preg_quote($CONFIG->Template['CloseTag']);

        $this->rawCode = file_get_contents($this->Filename);
        $this->compiledCode = preg_replace_callback(
            '/'.$openTag.'(.*)'.$closeTag.'/U', array($this, 'compileCodeBlock'), $this->rawCode);
    }

    public function display() {
        global $CONFIG;
        $_TEMPLATE = $this->templateVariables;

        if($CONFIG->Template['UseCaching'] && $this->cachedFile) {
            include($this->cachedFile);
        } else {
            eval('?>'.$this->compiledCode);
        }
    }

    public function getSourceFile() {
        return $this->$Filename;
    }

    protected function executeTemplateVar(& $variable) {
        if($variable instanceof Template) {
            $variable->display();
        } else {
            echo($variable);
        }
    }

    protected function compileCodeBlock($code) {
        $sourcecode = trim(is_array($code) ? $code[1] : $code);

        if($sourcecode[0] == '$') {
            $variable = ($sourcecode[1] == '{' && $sourcecode[strlen($sourcecode)-1] == '}') ? 
                substr($sourcecode, 2, -1): substr($sourcecode, 1);
            $phpcode = '$this->executeTemplateVar($_TEMPLATE[\''.$variable.'\']);';
        } elseif($sourcecode[0] == '*' && $sourcecode[strlen($sourcecode)-1] == '*') {
            $phpcode = '/*'.substr($sourcecode, 1, -1).'*/';
        } elseif($sourcecode[0] == '"' && $sourcecode[strlen($sourcecode)-1] == '"') {
            $string = substr($sourcecode, 1, -1);
            preg_match_all('/\$\{?(\w*)\}?/', $string, $matches);
            $string = preg_replace('/\$\{?(\w*)\}?/', '%s', $string);

            if(empty($matches[1])) {
                $phpcode = 'echo t("'.$string.'");';
            } else {
                $variables = '$_TEMPLATE[\''.implode('\'], $_TEMPLATE[\'', $matches[1]).'\']';
                $phpcode = 'echo t("'.$string.'", '.$variables.');';
            }
        } else {
            if(!strpos($sourcecode, ' ')) {
                $keyword = $sourcecode;
            } else {
                list($keyword, $parameters) = explode(' ', $sourcecode, 2);
            }

            switch($keyword) {
                case 'if':
                case 'elif':
                    $parameters = preg_replace('/\$\{?(\w*)\}?/', '\$_TEMPLATE[\'${1}\']', $parameters);
                    $phpcode = (($keyword=='if')?'if':'elseif').' ('.$parameters.'):';
                    break;
                case 'else':
                    $phpcode = 'else:';
                    break;
                case '/if':
                    $phpcode = 'endif;';
                    break;
                case 'foreach':
                    $args = explode(' ', $parameters);
                    if(count($args) == 3 && $args[0][0] == '$' && 
                        $args[1] == 'as' && $args[2][0] == '$') {

                        $phpcode = 'foreach ($_TEMPLATE[\''.substr($args[0], 1).'\'] as '.
                                    '$_TEMPLATE[\''.substr($args[2], 1).'\']):';

                    } elseif(count($args) == 5 && $args[0][0] == '$' && 
                        $args[1] == 'as' && $args[2][0] == '$' &&
                        $args[3] == '=>' && $args[4][0] == '$') {

                        $phpcode = 'foreach ($_TEMPLATE[\''.substr($args[0], 1).'\'] as '.
                                    '$_TEMPLATE[\''.substr($args[2], 1).'\'] => '.
                                    '$_TEMPLATE[\''.substr($args[4], 1).'\']):';

                    } else {
                        trigger_error(t("Template parsing error in file: %s", $this->Filename), E_USER_WARNING); 
                    }
                    break;
                case '/foreach':
                    $phpcode = 'endforeach;';
                    break;
                case 'include':
                    $phpcode = '$tmpTemplate = new Template("'.$parameters.'"); $tmpTemplate->display();';
                    break;
                default:
                    trigger_error(t("Template parsing error in file: %s", $this->Filename), E_USER_WARNING); 
                    break;
            }
        }
        return isset($phpcode)?'<?php '.$phpcode.' ?>':(is_array($code) ? $code[1] : $code);
       
    }

}

?>