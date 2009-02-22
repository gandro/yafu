<?php 

class RawOutput {

    public static $currentErrorCode = 'server.unknown';

    public static function printFileInfo(File $File) {
        self::printArray(array(
            'url' => $File->getDownloadLink(),
            'type' => $File->Mimetype,
            'size' => $File->Size
        ));
        Plugin::triggerHook("RawFileInfo", array(&$File));
    }

    public static function printUploadInfo() {
        global $CONFIG;
        self::printArray(array(
            'maxsize' => $CONFIG->Core['MaxFilesize'],
            'file_upload' => (bool) $CONFIG->Core['AllowFileUpload'],
            'text_upload' => (bool) $CONFIG->Core['AllowTextUpload'],
            'link_upload' => (bool) $CONFIG->Core['AllowLinkUpload']
        ));
        Plugin::triggerHook("RawUploadInfo", array(&$File));
    }

    public static function printArray(array $output) {
        @header("Content-Type: text/plain; charset=utf-8");
        foreach($output as $key => $value) {
            if(is_bool($value)) {
                echo($key.": ".(($value) ? 'true' : 'false')."\n");
            } else {
                echo($key.": ".$value."\n");
            }
        }
    }

    public static function printError($errorNumber, $errorString) {

        switch($errorNumber) {
            default:
            case E_ERROR:
            case E_USER_ERROR:
                $errorType = 'error';
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $errorType = 'warning';
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $errorType = 'notice';
                break;
        }

        self::printArray(array(
            $errorType => self::$currentErrorCode,
            $errorType.'msg' => $errorString
        ));

        if($errorType == 'error') {
            exit(1);
        }
    }

}

?>
