<?php

class ErrorHandler {
    private static $outputTemplate;
    private static $outputVariable;

    private static $outputBuffer = '';
    private static $lastErrorMessage = null;

    public static function newError($errno, $errstr, $errfile, $errline) {
        global $CONFIG;

        $errfile = (strpos($errfile, getcwd())===0) ? 
                        substr($errfile, strlen(getcwd())+1) : $errfile;

        $outputMessage = htmlspecialchars($errstr);
        $outputDebug = htmlspecialchars(t("From file \"%s\" on line %s.", $errfile, $errline));

        switch ($errno) {
            case E_NOTICE:
            case E_USER_NOTICE:
                $outputClass = 'ErrorNotice';
                $outputType = t("Notice:");
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $outputClass = 'ErrorWarning';
                $outputType = t("Warning:");
                break;
            case E_ERROR:
            case E_USER_ERROR:
            default:
                $outputClass = 'ErrorFatal';
                $outputType = t("Error:");
                break;
        }

        self::$lastErrorMessage = strip_tags(htmlspecialchars_decode($outputMessage));

        if(error_reporting() > 0) {
            $outputString = '<div class="'.$outputClass.'"><strong>'.$outputType.'</strong> '.$outputMessage.PHP_EOL.
                                (($CONFIG->Core['DebugInfo']) ? '<div class="ErrorDebug">'.$outputDebug.'</div>' : '').
                            '</div>';

            if(!(self::$outputTemplate instanceof Template)) {
                self::$outputBuffer .= $outputString;
            } else {
                self::$outputTemplate->{self::$outputVariable} .= $outputString;
            }

            if($errno == E_ERROR || $errno == E_USER_ERROR) {
                self::flushErrorBuffer();
                if(self::$outputTemplate instanceof Template) {
                    self::$outputTemplate->display();
                }
                exit(1);
            }
        }
    }

    public static function setOutput(Template $template, $variable) {
        self::$outputTemplate = $template;
        self::$outputVariable = $variable;
        self::$outputTemplate->{self::$outputVariable} = '';
        self::flushErrorBuffer();
    }

    public static function flushErrorBuffer() {
        if(self::$outputTemplate instanceof Template) {
            self::$outputTemplate->{self::$outputVariable} .= self::$outputBuffer;
        } elseif(!empty(self::$outputBuffer)) {
            echo(self::$outputBuffer);
        }
        self::$outputBuffer = '';
    }

    public static function dumpErrors() {
        if(!empty(self::$outputBuffer)) {
            self::flushErrorBuffer();
        }

        if(
            self::$outputTemplate instanceof Template &&
            !empty(self::$outputTemplate->{self::$outputVariable})
        ) {
            self::$outputTemplate->display();
        }
    }

    public static function getLastError() {
        return self::$lastErrorMessage;
    }
}
?>
