<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Yet Another File Upload 2.0 Installation</title>
<base href="<!--[$httpRoot]-->" /> 
<meta http-equiv="content-type" content="text/html; charset=utf-8" /> 
<style type="text/css" media="screen"> 
body {
    font-family: LiberationSerif,Georgia,serif;
    margin: 5em auto;
    width: 40em;
    max-width: 100%;
}

a:link, a:visited { 
    color: #204a87;
    text-decoration: underline;
}

a:focus, a:hover, a:active { 
    color: #3c6e1a;
    text-decoration: underline;
}

h1, h2, h3, h4, h5 {
    color: #4F4F4A;
    border-bottom: thin solid;
}

p {
    margin-left: 1.5em;
}

form * p {
    margin: 1em;
}

input[type="text"] {
    width: 100%;
}

input#Title, select#Language {
    font-size: larger;
}

input#MaxFilesize {
    font-size: larger;
    width: 2em;
}

label.ImportantLabel {
    font-weight: bold;
    display: block;
    margin-bottom: 0.5em;
}

input.Next {
    float: right;
}

input.Previous {
    float: left;
}

form p small {
    display: block;
    color: #4F4F4A;
}

hr {
    background-color: #4F4F4A;
    border: 1px;
    height: 1px;
}

.Notice {
    background-color: #D9D9CF;
    background-image: url(images/notice.png);
    background-repeat: no-repeat;
    background-position: 1em center;
    padding: 1em 1em 1em 3em;
}

.Warning {
    background-color: #EBC026;
    background-image: url(images/warning.png);
    background-repeat: no-repeat;
    background-position: 1em center;
    padding: 1em 1em 1em 3em;
}

.Fatal {
    background-color: #BD4730;
    background-image: url(images/error.png);
    background-repeat: no-repeat;
    background-position: 1em center;
    padding: 1em 1em 1em 3em;
}

.Success {
    font-style:italic;
    color: #3C6E1A;
}

.Fail {
    font-weight: bold;
    color: #BD4730;
}
</style>
<link rel="shortcut icon" href="images/favicon.ico" />
</head>
<body>
<h1><small>Welcome to</small> Yet Another File Upload 2.0</h1>
<?php
ini_set("display_errors", TRUE);
error_reporting(E_ALL);

defined('CLASSPATH') || define("CLASSPATH", "objects/");
defined('CONFIGFILE') || define("CONFIGFILE", "yafu2.conf");

/* check the most important thing */
if(version_compare(PHP_VERSION, '5.2.8', '<')) : ?>
<div class="Fatal">
    <strong>Fatal Error:</strong>
    This script needs PHP 5.2.8 or higher! <br />
    You are using PHP version <?php echo(PHP_VERSION) ?>.
</div></body></html>
<?php exit();
elseif(file_exists(CONFIGFILE)) : ?>
<div class="Fatal">
    <strong>Fatal Error:</strong>
    Config file already exists. Refuse to start setup routine.
</div></body></html>
<?php exit();
endif;

require_once("functions.php");
require_once(CLASSPATH."Config.php");
require_once(CLASSPATH."Language.php");

$AllowedTitleTags = '<small> <big> <code> <del> <strong> <em> <sub> <sup>';

$CONFIG = new Config(CONFIGFILE);

switch (isset($_POST['p']) ? $_POST['p'] : 'introduction'):
    default:
    case 'introduction':
    /*************** Introduction ***************/
?>
    <h2>Introduction</h2>
    <p>
        Welcome the installation of <em>Yet Another File Upload 2.0.</em>
        This script will help you to configure your <abbr title="Yet Another File Upload 2.0">yafu2</abbr> installation. <br />
    </p>
    <p class="Notice">
        Read the instructions carefully.
    </p>
    <hr />
    <form action="<?php echo($_SERVER['PHP_SELF']) ?>"  method="post">
        <div>
            <input type="hidden" name="p" value="settings" />
            <input type="submit" value="Next" class="Next" />
        </div>
    </form>
<?php 
    break;
    case 'settings':
    $Title = str_html($CONFIG->Core['Title']);
    $MaxFilesize = $CONFIG->Core['MaxFilesize'] / 1048576;
    /*************** User Settings ***************/
?>
    <h2>Important settings</h2>
    <form action="<?php echo($_SERVER['PHP_SELF']) ?>"  method="post">
        <p>
            <label for="Title" class="ImportantLabel">Title of your upload:</label>
            <input type="text" name="title" id="Title" value="<?php echo($Title) ?>" />
            <small>Allowed HTML-Tags: <code><?php echo(str_html($AllowedTitleTags)) ?></code></small>
        </p>
        <hr />
        <p>
            <label for="MaxFilesize" class="ImportantLabel">Filesize limit for uploaded files:</label>
            <input type="text" name="maxfilesize" id="MaxFilesize" value="<?php echo($MaxFilesize) ?>" /><big> MiB</big>
            <small>1 MiB = 2<sup>20</sup> Bytes</small>
        </p>
        <hr />
        <p>
            <label for="Language" class="ImportantLabel">Default language:</label>
            <select name="language" size="1" id="Language">
            <?php foreach (Language::listLanguages() as $shortName => $longName): ?> 
                <option value="<?php echo($shortName) ?>" <?php 
                    if($shortName == $CONFIG->Language['Default']) : ?>selected="selected"<?php endif; ?>>
                    <?php echo(str_html($longName)) ?> 
                </option>
            <?php endforeach; ?> 
            </select>
            <input type="checkbox" name="autodedect" value="yes" id="AutoDedect" checked="checked"/>
            <label for="AutoDedect">Auto dedect language for users</label>
        </p>
        <hr />
        <div>
            <input type="hidden" name="p" value="saveconfig" />
            <input type="submit" value="Next" class="Next" />
        </div>
    </form>
    <form action="<?php echo($_SERVER['PHP_SELF']) ?>"  method="post">
        <div>
            <input type="hidden" name="p" value="introduction" />
            <input type="submit" value="Previous" class="Previous" />
        </div>
    </form>
<?php 
    break;
    case 'saveconfig':
    /*************** Save Settings ***************/

    $CONFIG->Core['Title'] = isset($_POST['title']) 
        ? strip_tags($_POST['title'], $AllowedTitleTags)
        : $CONFIG->Core['Title'];
    $CONFIG->Core['MaxFilesize'] = isset($_POST['maxfilesize']) 
        ? intval($_POST['maxfilesize']) * 1048576
        : $CONFIG->Core['MaxFilesize'];
    $CONFIG->Language['Default'] = isset($_POST['language']) 
        ? $_POST['language']
        : $CONFIG->Language['Default'];
    $CONFIG->Language['AutoDedect'] = (isset($_POST['autodedect']) && $_POST['autodedect'] == 'yes');

    function failed($msg = "Failed") { echo('<span class="Fail">'.$msg.'</span>'); }
    function success($msg = "Success") { echo('<span class="Success">'.$msg.'</span>'); }

    function return_bytes($val) {
        $val = trim($val); 
        $last = strtolower($val[strlen($val)-1]);
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return intval($val);
    }

    $CONFIG->saveChanges(true);
?>
    <h2>Final server-side checks</h2>
    <ul>
        <li>
            Trying to create temporary file...
            <?php 
                if(@createTempFile() == false) {
                    mkdir("tmp");
                    $CONFIG->Core['TempFilePrefix'] = realpath($tmp).'/yafu2_';
                    if(@createTempFile() == false) {
                        failed();
                    } else {
                        success();
                    }
                } else {
                    success();
                }
            ?> 
        </li>
        <li>
            Creating pool for uploaded files...
            <?php 
                if(!is_dir($CONFIG->Core['FilePool'])) {
                    mkdir($CONFIG->Core['FilePool']);
                }
                is_writeable($CONFIG->Core['FilePool']) 
                    ? success() 
                    : failed("Failed! No write permission.");
            ?> 
        </li>
        <li>
            Creating template cache directory...
            <?php 
                if(!is_dir($CONFIG->Template['CacheDir'])) {
                    mkdir($CONFIG->Template['CacheDir']);
                }
                if(is_writeable($CONFIG->Template['CacheDir'])) {
                    success();
                } else {
                    $CONFIG->Template['UseCaching'] = false;
                    failed("Failed! Caching disabled!");
                }
            ?> 
        </li>
        <li>
            Checking php.ini upload_max_filesize...
            <?php 
                if(return_bytes(ini_get('upload_max_filesize')) > $CONFIG->Core['MaxFilesize']) {
                    success();
                } else {
                    $CONFIG->Core['MaxFilesize'] = return_bytes(ini_get('upload_max_filesize'));
                    failed("Failed! Reducing max filesize limit!");
                }
            ?> 
        </li>
        <li>
            Checking php.ini post_max_size...
            <?php 
                if(return_bytes(ini_get('post_max_size')) > $CONFIG->Core['MaxFilesize']) {
                    success();
                } else {
                    $CONFIG->Core['MaxFilesize'] = return_bytes(ini_get('post_max_size'));
                    failed("Failed! Reducing max filesize limit!");
                }
            ?> 
        </li>
        <li>
            Checking php.ini file_uploads...
            <?php 
                if((bool) ini_get('file_uploads')) {
                    success();
                } else {
                    $CONFIG->Core['AllowFileUpload'] = false;
                    failed("Failed! Disabling file upload!");
                }
            ?> 
        </li>
        <li>
            Checking php.ini allow_url_fopen...
            <?php 
                if((bool) ini_get('allow_url_fopen')) {
                    success();
                } else {
                    $CONFIG->Core['AllowLinkUpload'] = false;
                    failed("Failed! Disabling link upload!");
                }
            ?> 
        </li>
    </ul>
    <h2>Congratulaions!</h2>
    <p>
        The installation of <em>Yet Another File Upload 2.0</em> is complete. 
        Please note any errors above, as they may disable certain essential features.
    </p>
    <hr />
    <form action="<?php echo($_SERVER['PHP_SELF']) ?>"  method="post">
        <div>
            <input type="hidden" name="p" value="settings" />
            <input type="submit" value="Previous" class="Previous" />
        </div>
    </form>
    <form action="<?php echo(getHttpRoot()) ?>"  method="post">
        <div>
            <input type="hidden" name="a" value="fileUpload" />
            <input type="submit" value="Quit" class="Next" />
        </div>
    </form>
<?php 
endswitch;
?>
</body>
</html>
