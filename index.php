<?php
require_once("init.php");

switch(strtolower($Command)) {
    case 'f': /* get file */
        $fileRequest = new FileRequest($Parameter);
        $fileRequest->sendHTTPHeaders();
        $fileRequest->sendData();
        break;
    case 'a': /* action */
        switch($Parameter) {
            case 'upload':
                $mainTemplate->Content = new Template("Upload.html");
                $source = isset($_GET['s']) ? $_GET['s'] : 'file'; /* source */
                switch($source) {
                    case 'file':
                    default:
                        $Template = 'uploadFile';
                        break;
                    case 'text':
                        $Template = 'uploadText';
                        break;
                    case 'link':
                        $Template = 'uploadLink';
                        break;
                }
                $mainTemplate->Content->Source = new Template($Template.".html");
                $mainTemplate->Content->Source->MaxFilesize = 
                    HumanReadable::getFilesize($CONFIG->Core['MaxFilesize'], true);
                break;
            case 'search':
                $mainTemplate->Content = new Template("Search.html");
                $mainTemplate->Content->EntryCounter = 0;
                $mainTemplate->Content->Query = $query = isset($_GET['q']) ? str_html($_GET['q']) : '';
                $mainTemplate->Content->SortBy = $sortby = isset($_GET['s']) ? str_html($_GET['s']) : '';

                $mainTemplate->Content->FileList = new FileList();

                if(!empty($query)) {
                    $mainTemplate->Content->FileList->searchFor($query);
                }

                if($mainTemplate->Content->FileList->count() == 0) {
                    trigger_error(t("No files found!"), E_USER_NOTICE);
                    break;
                }

                if(!empty($sortby)) {
                    $mainTemplate->Content->FileList->sortBy($sortby);
                }



                break;
            default:
            case '404':
                header("HTTP/1.1 404 Not Found");
                trigger_error(t("Requested file not found!"), E_USER_ERROR);
        }
        $mainTemplate->display();
        break;
    case 'u': /* upload data */

        $uploadedFile = null;
        switch($Parameter) {
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
            trigger_error(t("There was an error uploading your file!"), E_USER_ERROR);
        }

        $mainTemplate->Content = new Template("FileInfo.html");
        $mainTemplate->Content->downloadLink = 
            $uploadedFile->getDownloadLink();

        $mainTemplate->Content->Filename = 
            str_html(HumanReadable::cutString($uploadedFile->Filename, 42));
        $mainTemplate->Content->Filesize =
            HumanReadable::getFilesize($uploadedFile->Size);
        $mainTemplate->Content->Mimetype = 
            $uploadedFile->Mimetype;
        $mainTemplate->Content->MimetypeIcon = 
            HumanReadable::getMimeTypeIcon($uploadedFile->Mimetype);

        $mainTemplate->display();
        break;
    default:
        header("HTTP/1.1 404 Not Found");
        trigger_error(t("Requested file not found!"), E_USER_ERROR);
}

@removeLeftOverFiles();
?>
