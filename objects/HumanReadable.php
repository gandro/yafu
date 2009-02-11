<?php

class HumanReadable {

    public static function getFilesize($size, $nbsp = false) {
        global $CONFIG;

        /* Note: Since php is dump and doesn't have support for large intergers,
         *       calculating any filesize larger than 2 GiB on a 32 bit machine 
         *       may does not work as expected.
         */

        if($CONFIG->Core['SiPrefixes']) {
            $unit = array('B', 'kB', 'MB', 'GB');
            $base = 1000;
        } else {
            $unit = array('B', 'KiB', 'MiB', 'GiB');
            $base = 1024;
        }

        for($i=0; $i<=count($unit); $i++) {
            $step = (int) pow($base, $i);
            if($size < $step*$base) {
                if($size%$step == 0) {
                    return $size/$step.($nbsp?'&nbsp;':' ').$unit[$i];
                } else {
                    $locale = localeconv();
                    return number_format($size/$step, 2, 
                        $locale['decimal_point'], $locale['thousands_sep']).
                        ($nbsp?'&nbsp;':' ').$unit[$i];
                }
            }
        }
    }

    public static function cutString($string, $len = 32) {
        if(strlen($string) > $len) {
            return utf8_encode(substr(utf8_decode($string), 0, $len-3).'...');
        } else {
            return $string;
        }
    }

    public static function getMimeTypeIcon($mimetype) {
        global $CONFIG;
        $mimetypeIcons = $CONFIG->Template['ImagePath'].'/mimetype/';
        list($type, $subtype) = split('/', strtok($mimetype, ';'), 2);
        switch($type) {
            case 'application':
                switch($subtype) {
                    case 'msword':
                    case 'pdf':
                    case 'postscript':
                    case 'rtf':
                        return $mimetypeIcons."document.png"; 
                        break;
                    case 'svg+xml':
                    case 'svg':
                        return $mimetypeIcons."image.png"; 
                        break;
                    case 'msexcel':
                    case 'mspowerpoint':
                        return $mimetypeIcons."spreadsheet.png"; 
                        break;
                    case 'gzip':
                    case 'x-compress':
                    case 'x-cpio':
                    case 'x-gtar':
                    case 'x-tar':
                    case 'x-rar':
                    case 'zip':
                        return $mimetypeIcons."package.png"; 
                        break;
                    case 'ogg':
                        return $mimetypeIcons."audio.png"; 
                        break;
                    case 'xhtml+xml':
                    case 'xml':
                    case 'javascript':
                    case 'x-shockwave-flash':
                        return $mimetypeIcons."web.png"; 
                        break;
                    default:
                        return $mimetypeIcons."application.png"; 
                        break;
                }
            case 'audio':
                return $mimetypeIcons."audio.png"; 
                break;
            case 'image':
                    return $mimetypeIcons."image.png"; 
                    break;
            case 'text':
                switch($subtype) {
                    case 'css':
                    case 'html':
                    case 'javascript':
                    case 'xml':
                        return $mimetypeIcons."web.png"; 
                        break;
                    case 'richtext':
                    case 'rtf':
                        return $mimetypeIcons."document.png"; 
                        break;
                    case 'comma-separated-values':
                        return $mimetypeIcons."spreadsheet.png"; 
                        break;
                    default:
                        return $mimetypeIcons."text.png"; 
                        break;
                }
                break;
            case 'video':
                return $mimetypeIcons."video.png";
                break;
        }
    }
}

?>
