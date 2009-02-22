<?php

class Upload {

    public static function uploadFromFile(array $fileArray) {

        self::checkFileArray($fileArray);

        $fileID = self::calculateFileID($fileArray['tmp_name']);
        $filename = strtr(str_eval($fileArray['name']), "\n", ' ');
        $size = intval($fileArray['size']);
        $mimetype = self::detectMimeType($fileArray['tmp_name'], $fileArray['type']);

        $uploadedFile = File::create($fileID, $filename, $size, $mimetype);

        if(!($uploadedFile->save() && move_uploaded_file($fileArray['tmp_name'], $uploadedFile->getDataPath()))) {
            RawOutput::$currentErrorCode = 'server.no_write';
            trigger_error(t("Failed to write file to server."), E_USER_ERROR);
            return null;
        }

        return $uploadedFile;
    }

    public static function uploadFromText($uploadedText, $name = null, $type = null, $rawContent = false) {
        global $CONFIG;

        $uploadedText = str_eval($uploadedText, $rawContent);

        $tmpFile = createTempFile();
        file_put_contents($tmpFile, $uploadedText);
        unset($uploadedText);

        $fileID = self::calculateFileID($tmpFile);
        $filename = empty($name) ? t("Textsnippet.txt") : str_eval($name);
        $size = filesize($tmpFile);
        $mimetype = self::detectMimeType($tmpFile, empty($type) ? "text/plain; charset=utf-8" : $type);

        if($size > $CONFIG->Core['MaxFilesize']) {
            RawOutput::$currentErrorCode = 'client.too_big';
            trigger_error(t("The uploaded file exceeds the filesize limit."), E_USER_ERROR);
        } elseif($size == 0) {
            RawOutput::$currentErrorCode = 'client.empty_file';
            trigger_error(t("The uploaded file is empty."), E_USER_ERROR);
        }

        $uploadedFile = File::create($fileID, $filename, $size, $mimetype);

        if(!($uploadedFile->save() && rename($tmpFile, $uploadedFile->getDataPath()))) {
            RawOutput::$currentErrorCode = 'server.no_write';
            trigger_error(t("Failed to write file to server."), E_USER_ERROR);
            return null;
        }

        return $uploadedFile;
    }

    public static function uploadFromLink($uploadedLink) {
        global $CONFIG;

        $uploadedLink = str_eval($uploadedLink);

        $protocol = parse_url($uploadedLink, PHP_URL_SCHEME);

        if(!empty($protocol) && !in_array($protocol, stream_get_wrappers())) {
            RawOutput::$currentErrorCode = 'client.invalid_url';
            trigger_error(t("The scheme \"%s\" is not supported!", $protocol), E_USER_ERROR);
            return null;
        }

        switch($protocol) {
            case 'http':
            case 'https':

                $uploadedLink =  strtr($uploadedLink, "\n\r\t", "   ");
                $uri = parse_url($uploadedLink);

                $httpContext = stream_context_create(array(
                    'http' => array(
                        'method' => "GET",
                        'user_agent' => "Yet Another File Upload 2 on ".$_SERVER['SERVER_NAME'],
                        'header' =>
                            "Referer: ".$uri['scheme'].'://'.$uri['host'].(isset($uri['port'])?':'.$uri['port']:'').'/'
                    )
                ));

                unset($uri);

                $urlHandler = @fopen($uploadedLink, 'r', false, $httpContext);
                if(!$urlHandler) {
                    RawOutput::$currentErrorCode = 'client.invalid_url';
                    trigger_error(t("Failed to open url \"%s\": %s", 
                        $uploadedLink, ErrorHandler::getLastError()), E_USER_ERROR);
                    return null;
                }

                $httpHeaders = stream_get_meta_data($urlHandler);
                $httpHeaders = $httpHeaders['wrapper_data'];

                foreach($httpHeaders as $header) {
                    if(!strpos($header, ':')) { continue; }
                    list($header, $parameter) = explode(':', $header, 2);
                    $header = trim(strtolower($header));

                    switch($header) {
                        case 'content-type':
                            $mimetype = $parameter;
                            break;
                        case 'content-length':
                            if($parameter > $CONFIG->Core['MaxFilesize']) {
                                RawOutput::$currentErrorCode = 'server.too_big';
                                trigger_error(t("The uploaded file exceeds the filesize limit."), E_USER_ERROR);
                                return null;
                            }
                            break;
                        case 'content-disposition':
                            if(preg_match('/.*filename="(?<filename>.*)".*/i', $parameter, $parameterArray)) {;
                                $filename = $parameterArray['filename'];
                            }
                            unset($parameterArray);
                            break;
                        case 'x-wormhole':
                            RawOutput::$currentErrorCode = 'client.invalid_url';
                            trigger_error(t("Wormhole Alert! Don't be silly."), E_USER_ERROR);
                            return null;
                            break;
                    }
                }
                break;
            case 'ftp':
            case 'ftps':
                if(!is_file($uploadedLink)) {
                    RawOutput::$currentErrorCode = 'client.invalid_url';
                    trigger_error(t("\"%s\" is not a file.", $uploadedLink), E_USER_ERROR);
                    return null;
                } elseif(filesize($uploadedLink) > $CONFIG->Core['MaxFilesize']) {
                    RawOutput::$currentErrorCode = 'client.too_big';
                    trigger_error(t("The uploaded file exceeds the filesize limit."), E_USER_ERROR);
                    return null;
                }

                $urlHandler = @fopen($uploadedLink, 'r');
                if(!$urlHandler) {
                    RawOutput::$currentErrorCode = 'client.invalid_url';
                    trigger_error(t("Failed to open url \"%s\": %s", 
                        $uploadedLink, ErrorHandler::getLastError()), E_USER_ERROR);
                    return null;
                }
                break;
            case 'data':
                $urlHandler = @fopen($uploadedLink, 'r');
                if(!$urlHandler) {
                    RawOutput::$currentErrorCode = 'client.invalid_url';
                    trigger_error(t("Failed to open url \"%s\": %s", 
                        $uploadedLink, ErrorHandler::getLastError()), E_USER_ERROR);
                    return null;
                }
                $metaData = stream_get_meta_data($urlHandler);
                $filename = t("Datasnippet");
                $mimetype = $metaData['mediatype'];
                break;
            case 'file':
                    RawOutput::$currentErrorCode = 'client.invalid_url';
                    trigger_error(t("Select \"hard disk\" as source to upload files from your computer."), E_USER_ERROR);
                    return null;
            case '':
            case false:
                    RawOutput::$currentErrorCode = 'client.invalid_url';
                    trigger_error(t("The string \"%s\" is not a valid url.", $uploadedLink), E_USER_ERROR);
                    return null;
            default:
                     RawOutput::$currentErrorCode = 'client.invalid_url';
                    trigger_error(t("The scheme \"%s\" is not supported!", $protocol), E_USER_ERROR);
                    return null;
        }


        $tmpFile = createTempFile();

        if(!($tmpHandler = fopen($tmpFile, 'w'))) {
            RawOutput::$currentErrorCode = 'server.no_write';
            trigger_error(t("Failed to write file to server."), E_USER_ERROR);
            return null;
        }

        if(!stream_copy_to_stream($urlHandler, $tmpHandler, $CONFIG->Core['MaxFilesize']+1)) {
            RawOutput::$currentErrorCode = 'client.invalid_url';
            trigger_error(t("Failed to download form url \"%s\".", $uploadedLink), E_USER_ERROR);
            return null;
        } elseif(connection_status() != 0 || connection_aborted() != 0) {

            /* Note: On most systems the connection_* functions seem broken.
             *       This is however a php related bug, so the script may 
             *       continues, regardless of the actual connection status.
             */

            fclose($urlHandler);
            fclose($tmpHandler);
            unlink($tmpFile);
            return null;
        }

        fclose($urlHandler);
        fclose($tmpHandler);

        $fileID = self::calculateFileID($tmpFile);

        $filename = !isset($filename) ? basename(parse_url($uploadedLink, PHP_URL_PATH)) : $filename;
        $filename = empty($filename) ? parse_url($uploadedLink, PHP_URL_HOST) : $filename;
        $filename = str_eval($filename);

        $size = filesize($tmpFile);
        $mimetype = self::detectMimeType($tmpFile, isset($mimetype) ? $mimetype : 'application/octet-stream');

        if($size > $CONFIG->Core['MaxFilesize']) {
            RawOutput::$currentErrorCode = 'client.too_big';
            trigger_error(t("The uploaded file exceeds the filesize limit."), E_USER_ERROR);
        } elseif($size == 0) {
            RawOutput::$currentErrorCode = 'client.empty_file';
            trigger_error(t("The uploaded file is empty."), E_USER_ERROR);
        }

        $uploadedFile = File::create($fileID, $filename, $size, $mimetype);

        if(!($uploadedFile->save() && rename($tmpFile, $uploadedFile->getDataPath()))) {
            RawOutput::$currentErrorCode = 'server.no_write';
            trigger_error(t("Failed to write file to server."), E_USER_ERROR);
            return null;
        }

        return $uploadedFile;
    }

    protected static function calculateFileID($filepath) {
        global $CONFIG;

        /* Note: The file id has to be as short as possible to avoid bloated
         *       urls. However, it has to be unique for each file to detect
         *       duplicate files, so we use a shortened notation of sha1 sums.
         *
         *       Format: <first 24bit of sha1sum><1+ byte random data>
         */

        $sha1Full = sha1_file($filepath);
        $sha1Short = substr($sha1Full,  0, 6); /* first 24bit */

        /* search for files with the same sha1 prefix, which may are duplicates  */
        $filesToCheck = glob($CONFIG->Core['FilePool'].'/'.$sha1Short.'*/data');
        $numberOfFilesToCheck = ($filesToCheck === false) ? 0 : count($filesToCheck);

        if(($numberOfFilesToCheck) > 0) {
            foreach($filesToCheck as $checkFile) {
                if(sha1_file($checkFile) == $sha1Full) {
                    unlink($filepath);
                    RawOutput::$currentErrorCode = 'client.file_exists';
                    trigger_error(t("This file was already uploaded."), E_USER_ERROR);
                    return null;
                } 
            }
        }
        
        do {
            $fileID = $sha1Short.sprintf('%02x', 
                ($numberOfFilesToCheck<0xFF) ? rand(0x00, 0xFF) : ($numberOfFilesToCheck++)
            );
        } while(File::exists($fileID, false));

        return $fileID;
    }

    protected static function checkFileArray(array $fileArray) {
        global $CONFIG;

        if(isset($fileArray['error']) && $fileArray['error'] !=  UPLOAD_ERR_OK) {
            switch($fileArray['error']) {
                case UPLOAD_ERR_INI_SIZE: 
                    RawOutput::$currentErrorCode = 'server.too_big';
                    trigger_error(t("The uploaded file exceeds the filesize limit on the server."), E_USER_ERROR);
                    break;
                case UPLOAD_ERR_PARTIAL:
                    RawOutput::$currentErrorCode = 'client.partial';
                    trigger_error(t("The uploaded file was only partially uploaded."), E_USER_ERROR);
                    break;
                case UPLOAD_ERR_NO_FILE:
                    RawOutput::$currentErrorCode = 'client.no_file';
                    trigger_error(t("No file was uploaded."), E_USER_ERROR);
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    RawOutput::$currentErrorCode = 'server.no_write';
                    trigger_error(t("No temporary directory on the server"), E_USER_ERROR);
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    RawOutput::$currentErrorCode = 'server.no_write';
                    trigger_error(t("Failed to write file to server."), E_USER_ERROR);
                    break;
                case UPLOAD_ERR_EXTENSION:
                    RawOutput::$currentErrorCode = 'server.extension';
                    trigger_error(t("File upload stopped by extension."), E_USER_ERROR);
                default:
                    RawOutput::$currentErrorCode = 'server.unkown';
                    trigger_error(t("Unkown Error during file upload."), E_USER_ERROR);
            }
        } elseif(
            !isset($fileArray['name']) || !isset($fileArray['type']) ||
            !isset($fileArray['size']) || !isset($fileArray['tmp_name']) ||
            !isset($fileArray['error']) || !is_uploaded_file($fileArray['tmp_name'])
        ) {
            RawOutput::$currentErrorCode = 'server.internal';
            trigger_error(t("Internal error: The \$_FILES array is not valid."), E_USER_ERROR);
        } elseif($fileArray['size'] > $CONFIG->Core['MaxFilesize']) {
            RawOutput::$currentErrorCode = 'client.too_big';
            trigger_error(t("The uploaded file exceeds the filesize limit."), E_USER_ERROR);
        } elseif($fileArray['size'] == 0) {
            RawOutput::$currentErrorCode = 'client.too_big';
            trigger_error(t("The uploaded file is empty."), E_USER_ERROR);
        } else {
            return true;
        }
        return false;
    }

    protected static function detectMimeType($path, $fallback = 'application/octet-stream') {
        global $CONFIG;

        $mimetype = $fallback;

        if(extension_loaded('FileInfo') && $finfo = @finfo_open(FILEINFO_MIME, $CONFIG->Core['MagicFile'])) {
            $mimetype = finfo_file($finfo, realpath($path));
            finfo_close($finfo);
        } elseif(is_callable('exec') && @exec('file -v')) {
            $mimetype = exec('file -bi '.escapeshellarg($filename));
        }

        return strtok($mimetype, ',');
    }

}

?>
