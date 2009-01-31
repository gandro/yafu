<?php

/* important paths */ 
define("CLASSPATH", "objects/");
define("CONFIGFILE", "yafu2.conf");

/* don't like it */
set_magic_quotes_runtime(0);

/* init error handling */
set_error_handler(array('ErrorHandler', 'newError'));
register_shutdown_function(array('ErrorHandler', 'flushErrorBuffer'));

/* require additional functions */
require_once("functions.php");

/* load config file */
$CONFIG = new Config(CONFIGFILE);

/* load language file */
t(true);

/* initalize main template, execpt for a valid file request */

if(!(isset($_GET['f']) && File::exists($_GET['f']))) {
    $mainTemplate = new Template("Index.html");
    $mainTemplate->httpRoot = getHttpRoot();
    $mainTemplate->Title = strip_tags($CONFIG->Core['Title']);
    $mainTemplate->HTMLTitle = $CONFIG->Core['Title'];
    ErrorHandler::setOutput($mainTemplate, 'Error');
}

/* set needed parameters */

$_GET = (empty($_GET)) ? array('a' => 'uploadFile') : $_GET;

/* important global used functions */

function __autoload($classname) {
    if($filepath = realpath(CLASSPATH.$classname.".php")) {
        require_once($filepath);
    }
}

function __errorhandler($errno, $errstr, $errfile, $errline) {
    echo($errstr."\n");
}

function t($resetLanguage = false) {
    static $currentLanguage = null;
    if(is_null($currentLanguage) || $resetLanguage) {
        global $CONFIG;
        if($CONFIG->Language['ForceDefault']) {
            $currentLanguage = new Language($CONFIG->Language['Default']);
        } else {
            $currentLanguage = new Language();
        }
    }
    $argv = func_get_args();
    $argv[0] = $currentLanguage->translate($argv[0]);
    return call_user_func_array('sprintf', $argv);
}

?>
