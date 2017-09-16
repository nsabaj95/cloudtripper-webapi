<?php
class Logger{
    public static function log($data){
        $message = print_r($data, true);
        file_put_contents(dirname(dirname(__FILE__))."/uploads/files.txt", $message, FILE_APPEND);
    }
}