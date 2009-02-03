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
            trigger_error(t("Failed to write file to server."), E_USER_ERROR);
            return null;
        }

        return $uploadedFile;
    }

    public static function uploadFromText($uploadedText) {
        global $CONFIG;

        $uploadedText = str_eval($uploadedText);

        $tmpFile = createTempFile();
        file_put_contents($tmpFile, $uploadedText);
        unset($uploadedText);

        $fileID = self::calculateFileID($tmpFile);
        $filename = t("Textsnippet.txt");
        $size = filesize($tmpFile);
        $mimetype = self::detectMimeType($tmpFile, "text/plain; charset=utf-8");

        if($size > $CONFIG->Core['MaxFilesize']) {
            trigger_error(t("The uploaded file exceeds the filesize limit."), E_USER_ERROR);
        } elseif($size == 0) {
            trigger_error(t("The uploaded file is empty."), E_USER_ERROR);
        }

        $uploadedFile = File::create($fileID, $filename, $size, $mimetype);

        if(!($uploadedFile->save() && rename($tmpFile, $uploadedFile->getDataPath()))) {
            trigger_error(t("Failed to write file to server."), E_USER_ERROR);
            return null;
        }

        return $uploadedFile;
    }

    public static function uploadFromLink($uploadedLink) {
        global $CONFIG;

        $uploadedLink = str_eval($uploadedLink);

        $protocol = parse_url($uploadedLink, PHP_URL_SCHEME);

        if(!in_array($protocol, stream_get_wrappers())) {
            trigger_error(t("The scheme \"%s\" is not supported!", $protocol), E_USER_ERROR);
            return null;
        }

        switch($protocol) {
            case 'http':
            case 'https':

                /* nasty, but documented hack */
                $uploadedLink =  strtr($uploadedLink, "\n\r\t", "   ");
                $uri = parse_url($uploadedLink);
                ini_set('user_agent',
                    'Yet Another File Upload 2 on '.$_SERVER['SERVER_NAME']."\r\n".
                    "Referer: ".$uri['scheme'].'://'.$uri['host'].(isset($uri['port'])?':'.$uri['port']:'').'/'
                );
                unset($uri);

                $urlHandler = @fopen($uploadedLink, 'r');
                if(!$urlHandler) {
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
                                trigger_error(t("The uploaded file exceeds the filesize limit."), E_USER_ERROR);
                                return null;
                            }
                            break;
                        case 'content-disposition':
                            parse_str($parameter, $parameterArray);
                            $filename = $parameterArray['filename'];
                            unset($parameterArray);
                            break;
                        case 'x-wormhole':
                            trigger_error(t("Wormhole Alert! Don't be silly."), E_USER_ERROR);
                            return null;
                            break;
                    }
                }
                break;
            case 'ftp':
            case 'ftps':
                if(!is_file($uploadedLink)) {
                    trigger_error(t("\"%s\" is not a file.", $uploadedLink), E_USER_ERROR);
                    return null;
                } elseif(filesize($uploadedLink) > $CONFIG->Core['MaxFilesize']) {
                    trigger_error(t("The uploaded file exceeds the filesize limit."), E_USER_ERROR);
                    return null;
                }

                $urlHandler = @fopen($uploadedLink, 'r');
                if(!$urlHandler) {
                    trigger_error(t("Failed to open url \"%s\": %s", 
                        $uploadedLink, ErrorHandler::getLastError()), E_USER_ERROR);
                    return null;
                }
                break;
            case 'data':
                $urlHandler = @fopen($uploadedLink, 'r');
                if(!$urlHandler) {
                    trigger_error(t("Failed to open url \"%s\": %s", 
                        $uploadedLink, ErrorHandler::getLastError()), E_USER_ERROR);
                    return null;
                }
                $metaData = stream_get_meta_data($urlHandler);
                $filename = t("Datasnippet");
                $mimetype = $metaData['mediatype'];
                break;
            case 'file':
                    trigger_error(t("Select \"hard disk\" as source to upload files from your computer."), E_USER_ERROR);
                    return null;
            case '':
            case false:
                    trigger_error(t("The string \"%s\" is not a valid url.", $uploadedLink), E_USER_ERROR);
                    return null;
            default:
                    trigger_error(t("The scheme \"%s\" is not supported!", $protocol), E_USER_ERROR);
                    return null;
        }


        $tmpFile = createTempFile();

        if(!($tmpHandler = fopen($tmpFile, 'w'))) {
            trigger_error(t("Failed to write file to server."), E_USER_ERROR);
            return null;
        }

        if(!stream_copy_to_stream($urlHandler, $tmpHandler)) {
            trigger_error(t("Failed to download form url \"%s\".", $uploadedLink), E_USER_ERROR);
            return null;
        }

        fclose($urlHandler);
        fclose($tmpHandler);

        $fileID = self::calculateFileID($tmpFile);
        $filename = isset($filename) ? str_eval($filename) : basename(parse_url($uploadedLink, PHP_URL_PATH));
        $size = filesize($tmpFile);
        $mimetype = self::detectMimeType($tmpFile, isset($mimetype) ? $mimetype : 'application/octet-stream');

        if($size > $CONFIG->Core['MaxFilesize']) {
            trigger_error(t("The uploaded file exceeds the filesize limit."), E_USER_ERROR);
        } elseif($size == 0) {
            trigger_error(t("The uploaded file is empty."), E_USER_ERROR);
        }

        $uploadedFile = File::create($fileID, $filename, $size, $mimetype);

        if(!($uploadedFile->save() && rename($tmpFile, $uploadedFile->getDataPath()))) {
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
                    trigger_error(t("The uploaded file exceeds the filesize limit on the server."), E_USER_ERROR);
                    break;
                case UPLOAD_ERR_PARTIAL:
                    trigger_error(t("The uploaded file was only partially uploaded."), E_USER_ERROR);
                    break;
                case UPLOAD_ERR_NO_FILE:
                    trigger_error(t("No file was uploaded."), E_USER_ERROR);
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    trigger_error(t("No temporary directory on the server"), E_USER_ERROR);
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    trigger_error(t("Failed to write file to server."), E_USER_ERROR);
                    break;
                case UPLOAD_ERR_EXTENSION:
                    trigger_error(t("File upload stopped by extension."), E_USER_ERROR);
                default:
                    trigger_error(t("Unkown Error during file upload."), E_USER_ERROR);
            }
        } elseif(
            !isset($fileArray['name']) || !isset($fileArray['type']) ||
            !isset($fileArray['size']) || !isset($fileArray['tmp_name']) ||
            !isset($fileArray['error']) || !is_uploaded_file($fileArray['tmp_name'])
        ) {
            trigger_error(t("Internal error: The \$_FILES array is not valid."), E_USER_ERROR);
        } elseif($fileArray['size'] > $CONFIG->Core['MaxFilesize']) {
            trigger_error(t("The uploaded file exceeds the filesize limit."), E_USER_ERROR);
        } elseif($fileArray['size'] == 0) {
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
