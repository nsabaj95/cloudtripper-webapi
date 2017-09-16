<?php

require_once 'helpers/logger.php';

class HttpHelper{
    
    public static function getResource(){
        $resource = null;
        if (isset($_GET['PATH_INFO'])){
            $request = explode('/', $_GET['PATH_INFO']);
            $resource = array_shift($request);
        }
        return $resource;
    }
    public static function getMethod(){
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        return $method;
    }
    public static function getParametersArrayFromURL(){
        $query = [];
        $parts = parse_url("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
        if(array_key_exists('query', $parts)){
            parse_str($parts['query'], $query);
        } 
        return $query;
    }
    public static function getParametersArrayFromJson(){
        $input = json_decode(file_get_contents('php://input'), true);
        return $input;
    }
    public static function getParametersArrayFromHttpPost(){
        return $_POST;
    }
    public static function getParametersArray($getUploadedFile){
        $result;
        switch (self::getMethod()){
            case 'get':
                $result = self::getParametersArrayFromURL();
                break;
            case 'delete':
                $result = self::getParametersArrayFromURL();
                break;
            case 'post':
                $result = self::getParametersArrayFromJson();
                if(empty($result)){
                    $result = self::getParametersArrayFromHttpPost();                    
                }
                if($getUploadedFile){
                    $file = self::getUploadedFile("name", "uploads");
                    // $result["image"] = $file;
                    $result["image"] = $file;
                }
                break;
            case 'put':
                $result = self::getParametersArrayFromJson();
                if(empty($result)){
                    $result = self::getParametersArrayFromHttpPost();                    
                }
                if($getUploadedFile){
                    $file = self::getUploadedFile("name", "uploads");
                    // $result["image"] = $file;
                    $result["image"] = $file;
                }
                break;
        }
        return $result;
    }
    public static function getUploadedFile($fileName, $targetDirectory){
        $file = null;
        $base64 = null;
        if(!empty($_FILES["file"][$fileName])){
            $target_file = $targetDirectory . basename($_FILES["file"][$fileName]);
            
            $uploadOk = 1;
            $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
            $origin = $_FILES["file"]["tmp_name"];
            $destination = "uploads/" . $_FILES["file"]["name"];
            move_uploaded_file($origin, $destination);
            
            if (file_exists($target_file)) {
                echo "Sorry, file already exists.";
                $uploadOk = 0;
            }

            $file = file_get_contents($destination);
            $base64 = 'data:image/jpeg;base64,' . base64_encode($file);
        }
        return $base64;
    }
}