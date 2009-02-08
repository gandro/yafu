<?php

/* important paths */ 
define("CLASSPATH", "objects/");
define("CONFIGFILE", "yafu2.conf");

/* don't like it */
set_magic_quotes_runtime(0);

/* start initial setup */
if(!file_exists(CONFIGFILE) && file_exists("setup.php")) {
    include("setup.php");
    exit();
}

/* init error handling */
set_error_handler(array('ErrorHandler', 'newError'));
register_shutdown_function(array('ErrorHandler', 'flushErrorBuffer'));

/* require additional functions */
require_once("functions.php");

/* load config file */
$CONFIG = new Config(CONFIGFILE);

/* load language file */
$LANGUAGE = new Language();

/* set needed parameters */
if(empty($_GET)) {
    if($_SERVER['SCRIPT_NAME'] != $_SERVER['PHP_SELF']) {
        $requestedFileID = strtok(
            substr($_SERVER['PHP_SELF'], strlen($_SERVER['SCRIPT_NAME'])),
            '/'
        );

        $_GET = array('f' => $requestedFileID);
    } else {
        $_GET = array('a' => 'upload', 's' => 'file');
    }
}


foreach($_GET as $Command => $Parameter) {
    switch($Command) {
        case 'a':
        case 'f':
        case 'u':
            break 2;
        default:
            $Command = $Parameter = null;
    }
}

/* initalize main template, execpt for a valid file request */

if(!(isset($_GET['f']) && File::exists($_GET['f']))) {
    $mainTemplate = new Template("Index.html");
    $mainTemplate->httpRoot = getHttpRoot();
    $mainTemplate->Title = strip_tags($CONFIG->Core['Title']);
    $mainTemplate->HTMLTitle = $CONFIG->Core['Title'];
    ErrorHandler::setOutput($mainTemplate, 'Error');
}

/* important global used functions */

function __autoload($classname) {
    if($filepath = realpath(CLASSPATH.$classname.".php")) {
        require_once($filepath);
    }
}

function t($resetLanguage = false) {
    global $LANGUAGE;
    $argv = func_get_args();

    if(isset($LANGUAGE)) {
        $argv[0] = $LANGUAGE->translate($argv[0]);
    }
    return call_user_func_array('sprintf', $argv);
}

?>
