<?php
require_once("global.php");
//header("Content-Type: text/plain");
//print_r($_GET); exit();
foreach($_GET as $cmd => $parameter) {
    switch(strtolower($cmd)) {
        case 'f': /* get file */
            $fileRequest = new FileRequest($fileID);
            $fileRequest->sendHTTPHeaders();
            $fileRequest->sendData();
            break;
        case 'a': /* action */
            switch($parameter) {
                case 'uploadFile':
                case 'uploadText':
                case 'uploadLink':
                    $mainTemplate->Content = new Template("Upload.html");
                    $mainTemplate->Content->Source = new Template($parameter.".html");
                    break;
                case 'search':
                    $mainTemplate->Content = new Template("Search.html");
                    break;
                default:
                case '404':
                    header("HTTP/1.1 404 Not Found");
                    trigger_error("Requested file not found!", E_USER_ERROR);
            }
            $mainTemplate->display();
            break;
        case 'u': /* upload data */

            $uploadedFile = null;
            switch($parameter) {
                case 'file':
                    if(isset($_FILES['upload'])) {
                        $uploadedFile = Upload::uploadFromFile($_FILES['upload']);
                    }
                    break;
                case 'text':
                    if(isset($_POST['text'])) {
                        $uploadedFile = Upload::uploadFromText($_POST['text']);
                    }
                case 'link':
                    if(isset($_POST['link'])) {
                        $uploadedFile = Upload::uploadFromLink($_POST['link']);
                    }
                    break;
            }

            if(is_null($uploadedFile)) {
                trigger_error("There was an error uploading your file!", E_USER_ERROR);
            }

            $mainTemplate->Content = new Template("FileInfo.html");
            $mainTemplate->Content->httpRoot = getHttpRoot();

            $mainTemplate->Content->FileID = $uploadedFile->FileID;

            $mainTemplate->Content->Filename = str_html(HumanReadable::cutString($uploadedFile->Filename, 42));
            $mainTemplate->Content->Filesize = HumanReadable::getFilesize($uploadedFile->Size);

            $mainTemplate->Content->Mimetype = $uploadedFile->Mimetype;
            $mainTemplate->Content->MimetypeIcon = HumanReadable::getMimeTypeIcon($uploadedFile->Mimetype);

            $mainTemplate->display();
            break;
        default:
            header("HTTP/1.1 404 Not Found");
            trigger_error("Requested file not found!", E_USER_ERROR);
    }
}

@removeLeftOverFiles();
?>
