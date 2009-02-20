<?php

class Download {
    protected $requestedFile;

    public function Download($fileID) {
        if(!File::exists($fileID)) {
            header("HTTP/1.1 404 Not Found");
            trigger_error(t("Requested file not found!"), E_USER_ERROR);
        }

        $this->requestedFile = new File($fileID);
    }

    public function sendHTTPHeaders() {
        global $CONFIG;

        if($_SERVER['HTTP_USER_AGENT'] == 'Yet Another File Upload 2 on '.$_SERVER['SERVER_NAME']) {
            header('X-Wormhole: Alert', true);
            return false;
        }

        header('Accept-Ranges: bytes', true);
        header('Content-Transfer-Encoding: binary', true);
        header("Last-Modified: ".gmdate("D, d M Y H:i:s", filemtime($this->requestedFile->getDataPath()))." GMT", true); 
        header("Expires: ".gmdate("D, d M Y H:i:s", 0x7FFFFFFF)." GMT", true); /* omg, yafu2 is not year 2038 compliant */

        if($_SERVER['SCRIPT_NAME'] == $_SERVER['PHP_SELF']) {

            /* Note: Well, the people who wrote RFC2183 (Content-Disposition) and/or 
             *       RFC2045 (MIME) are mad and insane, since the encoding for the
             *       filename parameter has to be US-ASCII.
             *       Even worse, every browser parses non-US-ASCII characters 
             *       differently. 
             *
             *          See: http://greenbytes.de/tech/tc2231/
             */

            $encodedFilename = ascii_encode($this->requestedFile->Filename);

            header('Content-Disposition: attachment; filename="'.$encodedFilename.'"', true);
        }

        if(is_array($fileRange = $this->getFileRange())) {
            header("HTTP/1.1 206 Partial Content", true);
            header('Content-Length: '.$fileRange['size'], true);
            header("Content-Range: bytes ".$fileRange['start']."-".$fileRange['end']."/".$this->requestedFile->Size, true);
        } else {
            header('Content-Length: '.$this->requestedFile->Size, true);
        }
        header('Content-Type: '.$this->requestedFile->Mimetype, true);
    }

    public function sendData() {
        $fileHandler = fopen($this->requestedFile->getDataPath(), 'rb');
        if(is_array($fileRange = $this->getFileRange())) {
            fseek($fileHandler, $fileRange['start']);
            echo(fread($fileHandler, $fileRange['end']+1 - $fileRange['start']));
        } else {
            fpassthru($fileHandler);
        }
        fclose($fileHandler);
    }

    protected function getFileRange() {
        if(!isset($_SERVER['HTTP_RANGE'])) {
            return false;
        }

        list($unit, $range) = explode('=', $_SERVER['HTTP_RANGE']);
        $startRange = strtok($range, '-/');
        if(empty($startRange)) {
            header("HTTP/1.1 416 Requested Range not satisfiable");
            trigger_error(t("Requested Range not satisfiable!"), E_USER_ERROR);
        }
        if(!ctype_digit($endRange = strtok('-/'))) {
            $endRange = $this->requestedFile->Size - 1;
        }
        return array(
            'start' => $startRange,
            'end' => $endRange,
            'size' => ($endRange+1 - $startRange)
        );

    }

}
?>
