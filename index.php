<?php
require_once("init.php");

$doMainLoop = true;
while($doMainLoop) {
    $doMainLoop = false;

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
                            $Disabled = $CONFIG->Core['AllowFileUpload']?'':'disabled="disabled"';
                            break;
                        case 'text':
                            $Template = 'uploadText';
                            $Disabled = $CONFIG->Core['AllowTextUpload']?'':'disabled="disabled"';;
                            break;
                        case 'link':
                            $Template = 'uploadLink';
                            $Disabled = $CONFIG->Core['AllowLinkUpload']?'':'disabled="disabled"';;
                            break;
                    }
                    $mainTemplate->Content->Source = new Template($Template.".html");
                    $mainTemplate->Content->Source->Disabled = $Disabled;
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
                case 'info':
                    if(isset($_GET['i']) && File::exists($_GET['i'])) {
                        $File = new File($_GET['i']);
                    } elseif(isset($_GET['u']) && isset($uploadedFile)) {
                        $File = $uploadedFile;
                    }

                    if(!isset($File)) {
                        /* throw HTTP 404 */
                        $Command = 'a'; $Parameter = '404';
                        $doMainLoop = true;
                        break 2;
                    }
                    
                    $mainTemplate->Content = new Template("FileInfo.html");

                    $mainTemplate->Content->downloadLink = $File->getDownloadLink();
                    $mainTemplate->Content->Filename = str_html(HumanReadable::cutString($File->Filename, 42));
                    $mainTemplate->Content->Filesize = HumanReadable::getFilesize($File->Size);
                    $mainTemplate->Content->Mimetype = $File->Mimetype;
                    $mainTemplate->Content->MimetypeIcon = HumanReadable::getMimeTypeIcon($File->Mimetype);
                    $mainTemplate->Content->isImage = (strtok($File->Mimetype, '/') == 'image');
                    break;
                case 'sitemap':
                    $mainTemplate->Content = new Template("Sitemap.html");
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
                    if(!$CONFIG->Core['AllowFileUpload']) {
                        trigger_error(t("Upload from this source is disabled!"), E_USER_ERROR);
                        break 2;
                    } elseif(isset($_FILES['upload'])) {
                        $uploadedFile = Upload::uploadFromFile($_FILES['upload']);
                    }
                    break;
                case 'text':
                    if(!$CONFIG->Core['AllowTextUpload']) {
                        trigger_error(t("Upload from this source is disabled!"), E_USER_ERROR);
                        break 2;
                    } elseif(isset($_POST['text'])) {
                        $uploadedFile = Upload::uploadFromText($_POST['text']);
                    }
                case 'link':
                    if(!$CONFIG->Core['AllowLinkUpload']) {
                        trigger_error(t("Upload from this source is disabled!"), E_USER_ERROR);
                        break 2;
                    } elseif(isset($_POST['link'])) {
                        $uploadedFile = Upload::uploadFromLink($_POST['link']);
                    }
                    break;
            }

            if(is_null($uploadedFile)) {
                trigger_error(t("There was an error uploading your file!"), E_USER_ERROR);
            } else {
                /* lets call main switch-case routine again */
                $Command = 'a'; $Parameter = 'info';
                $doMainLoop = true;
            }

            break;
        default:
            /* throw HTTP 404 */
            $Command = 'a'; $Parameter = '404';
            $doMainLoop = true;
    }
}

@removeLeftOverFiles();
?>
