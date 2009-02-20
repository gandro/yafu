<?php
require_once("init.php");

do {

    switch($Command) {
        ////////////////////////////////////////////////////////////////////////
        case 'f': /* get file */
        ////////////////////////////////////////////////////////////////////////

            if(File::exists($Parameter)) {
                $fileDownload = new Download($Parameter);
                $fileDownload->sendHTTPHeaders();
                $fileDownload->sendData();
            } else {
                /* throw HTTP 404 */
                $Command = 'a'; $Parameter = '404';
                continue(2);
            }
            break;

        ////////////////////////////////////////////////////////////////////////
        case 'a': /* action */
        ////////////////////////////////////////////////////////////////////////

            switch($Parameter) {
                ////////////////////////////////////////////////////////////////

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

                ////////////////////////////////////////////////////////////////

                case 'search':
                    /* just an alias */
                    $Command = 'q'; $Parameter = null;
                    continue(3);

                ////////////////////////////////////////////////////////////////

                case 'about':
                    $mainTemplate->Content = new Template("About.html");
                    break;

                ////////////////////////////////////////////////////////////////

                case 'sitemap':
                    $mainTemplate->Content = new Template("Sitemap.html");
                    break;

                ////////////////////////////////////////////////////////////////

                default:

                    /* TODO: Plugin hook */

                ////////////////////////////////////////////////////////////////

                case '404':
                    header("HTTP/1.1 404 Not Found");
                    trigger_error(t("Requested file not found!"), E_USER_ERROR);

                ////////////////////////////////////////////////////////////////
            }

            $mainTemplate->display();
            break;

        ////////////////////////////////////////////////////////////////////////
        case 'u': /* upload data */
        ////////////////////////////////////////////////////////////////////////

            $uploadedFile = null;
            switch($Parameter) {
                ////////////////////////////////////////////////////////////////

                case 'file':
                    if(!$CONFIG->Core['AllowFileUpload']) {
                        trigger_error(t("Upload from this source is disabled!"), E_USER_ERROR);
                        break 2;
                    } elseif(isset($_FILES['upload'])) {
                        $uploadedFile = Upload::uploadFromFile($_FILES['upload']);
                    }
                    break;

                ////////////////////////////////////////////////////////////////

                case 'text':
                    if(!$CONFIG->Core['AllowTextUpload']) {
                        trigger_error(t("Upload from this source is disabled!"), E_USER_ERROR);
                        break 2;
                    } elseif(isset($_POST['text'])) {
                        $uploadedFile = Upload::uploadFromText(
                            $_POST['text'],
                            isset($_POST['name']) ? $_POST['name'] : null,
                            isset($_POST['type']) ? $_POST['type'] : null,
                            isset($_POST['raw'])
                        );
                    }
                    break;

                ////////////////////////////////////////////////////////////////

                case 'link':
                    if(!$CONFIG->Core['AllowLinkUpload']) {
                        trigger_error(t("Upload from this source is disabled!"), E_USER_ERROR);
                        break 2;
                    } elseif(isset($_POST['link'])) {
                        $uploadedFile = Upload::uploadFromLink($_POST['link']);
                    }
                    break;

                ////////////////////////////////////////////////////////////////
            }

            if(is_null($uploadedFile)) {
                trigger_error(t("There was an error uploading your file!"), E_USER_ERROR);
            } else {
                /* lets call main switch-case routine again */
                $Command = 'i'; $Parameter = $uploadedFile->FileID;
                continue(2);
            }
            break;

        ////////////////////////////////////////////////////////////////////////
        case 'q': /* search */
        ////////////////////////////////////////////////////////////////////////

            $query = isset($Parameter) ? $Parameter : '';
            $sortby = isset($_GET['s']) ? $_GET['s'] : '';

            $mainTemplate->Content = new Template("Search.html");
            $mainTemplate->Content->EntryCounter = 0;
            $mainTemplate->Content->totalFilesize = 0;
            $mainTemplate->Content->HTML_Query = str_html($query);
            $mainTemplate->Content->URL_Query = urlencode($query);
            $mainTemplate->Content->SortBy = str_html($sortby);

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

            $mainTemplate->display();
            break;

        ////////////////////////////////////////////////////////////////////////
        case 'i': /* info */
        ////////////////////////////////////////////////////////////////////////

            if(File::exists($Parameter)) {
                $File = isset($uploadedFile) ? $uploadedFile : new File($Parameter);
            } else {
                /* throw HTTP 404 */
                $Command = 'a'; $Parameter = '404';
                continue(2);
            }

            $mainTemplate->Content = new Template("FileInfo.html");

            $mainTemplate->Content->downloadLink = $File->getDownloadLink();
            $mainTemplate->Content->FullFilename = str_html($File->Filename);
            $mainTemplate->Content->Filename = str_html(HumanReadable::cutString($File->Filename, 42));
            $mainTemplate->Content->Filesize = HumanReadable::getFilesize($File->Size);
            $mainTemplate->Content->Mimetype = $File->Mimetype;
            $mainTemplate->Content->MimetypeIcon = HumanReadable::getMimeTypeIcon($File->Mimetype);
            $mainTemplate->Content->isImage = (strtok($File->Mimetype, '/') == 'image');

            $mainTemplate->display();
            break;

        ////////////////////////////////////////////////////////////////////////
        default: /* wtf? */
        ////////////////////////////////////////////////////////////////////////

            if(Plugin::triggerHook("unkownCommand", array(), '||')) {
               break;
            }
            /* throw HTTP 404 */
            $Command = 'a'; $Parameter = '404';
            continue(2);
    }

    break;

} while(true);
?>
