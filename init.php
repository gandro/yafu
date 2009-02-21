<?php
/* ready, steady... go! */
$startTime = microtime(true);

/* important paths */ 
define("CLASSPATH", "objects/");
define("CONFIGFILE", "yafu2.conf");

/* don't like it */
set_magic_quotes_runtime(0);

/* start initial setup */
if(file_exists("setup.php") && !file_exists(CONFIGFILE)) {
    include("setup.php");
    exit();
}

/* init error handling */
set_error_handler(array('ErrorHandler', 'newError'));
register_shutdown_function(array('ErrorHandler', 'dumpErrors'));

/* require additional functions */
require_once("functions.php");
register_shutdown_function('removeLeftOverFiles');

/* load config file */
$CONFIG = new Config(CONFIGFILE);

/* load language file */
$LANGUAGE = new Language();

/* load plugins */
$HOOKS = array();
Plugin::loadPlugins();

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
        case 'f': /*  file  */
        case 'a': /* action */
        case 'u': /* upload */
        case 'q': /*  query */
        case 'i': /*  info  */
            break 2;
        default:
            $Command = $Parameter = null;
    }
}

/* initialize main template */
if(isset($_POST['raw']) && $Command == 'u') {

    $mainTemplate = new Template("RawInfo.txt");
    $mainTemplate->setContentType("text/plain");
    ErrorHandler::setOutput($mainTemplate, 'ErrorMsg');
    ErrorHandler::$ignoreFatal = true;

} elseif(!($Command == 'f' && @File::exists($Parameter))) {

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

function t() {
    global $LANGUAGE;
    $argv = func_get_args();

    if(isset($LANGUAGE)) {
        $argv[0] = $LANGUAGE->translate($argv[0]);
    }
    return call_user_func_array('sprintf', $argv);
}

?>
