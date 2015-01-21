<?php
/**
 * Created by PhpStorm.
 * User: remimichel
 * Date: 21/01/15
 * Time: 22:14
 */

namespace AppBundle\Services;
use \GuzzleHttp\Stream\Stream;


class BodyReader {

    public function extractDataFromStream($stream, $start, $end){
        $found = false;
        $after = false;
        $finalContent = "";
        while(!$stream->eof() && !$after){
            $content = $stream->read(200);
            if(preg_match($start, $content) || $found){
                $finalContent .= $content;
                $found = true;
            }
            if(preg_match($end, $content)){
                $finalContent .= $content;
                $after = true;
            }
        }
        return $finalContent;
    }

} 