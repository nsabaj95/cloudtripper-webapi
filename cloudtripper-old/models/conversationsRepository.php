<?php
require_once dirname(dirname(__FILE__)).'/utilities/ApiException.php';
require_once 'helpers/logger.php';
require_once 'interfaces/iRepository.php';

class usersRepository 
{
    // Datos de la tabla "usuario"
    const TABLE_NAME = "Conversations";
    const ID = "Id";
    const RECEIVER_ID = "Receiver_Id";
    const TRANSMITTER_ID = "Transmitter_Id";
    
    public function get($request){
        if(array_key_exists('id', $request)){
            return self::getConversation($request);
        }else{
            return self::getConversations($request);
        }
    }

    public function post($request)
    {
        try{
            $receiver_id = $request['receiver_id'];
            $transmitter_id = $request['transmitter_id'];
            $message = $request['message'];
            
            try {
                $pdo = DbConnection::getInstance()->getDb();
                $sql = "SELECT * FROM " . self::TABLE_NAME . " INNER JOIN Messages ON Conversations.Id = Messages.Conversation_Id 
                WHERE UserName='" . $username .  "' AND Password='" . $password . "' LIMIT 1";
                $sentence = $pdo->query($sql);
                http_response_code(200);
                return $sentence->fetch();
            } catch (Exception $e) {
                print($e);
                throw new ApiException(self::PDO_ERROR, $e->getMessage());
            }

            if($user != null){
                return
                [
                    "error" => false,
                    "user" => $user,
                    "state" => "SUCCESS",
                    "message" => utf8_encode("Authentication success.")
                ];
            } else {
                return
                [
                    "error" => true,
                    "state" => "FAIL",
                    "message" => utf8_encode("Username or password are incorrect.")
                ];
            }
            
        } catch(Exception $e){
            header("HTTP/1.1 401 Unauthorized", true, 401);
            return 
            [
                "error" => true,
                "state" => "Authentication failed",
                "message" => utf8_encode($e)
            ];
        }
    }
    
    private function register($userData)
    {
        $username = $userData["username"];
        $password = $userData["password"];
        $text = $userData["text"];
        
        try {
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "INSERT INTO " . self::TABLE_NAME . " ( " .
                self::USERNAME . "," .
                self::PASSWORD . "," .
                self::TEXT . ")" .
                " VALUES(:username,:password, :text)";

            $sentence = $pdo->prepare($command);

            $sentence->bindParam(':username', $username, PDO::PARAM_STR);
            $sentence->bindParam(':password', $password, PDO::PARAM_STR);
            $sentence->bindParam(':text', $text, PDO::PARAM_STR);
            
            $result = $sentence->execute();
            
            if(!empty($userData["avatar"])){
                $id = $pdo->lastInsertId();
                self::addUserAvatar($userData["avatar"], $id);
            }

            http_response_code(200);

            return $result;
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }
    }

    private function addUserAvatar($avatar, $user_id){        
        $image = addslashes($avatar); //SQL Injection defence!

        try {
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "UPDATE " . self::TABLE_NAME . " SET " .
                "Avatar=" . "'{$avatar}', HasAvatar=1 WHERE Id=" . "$user_id";

            $sentence = $pdo->prepare($command);
            $result = $sentence->execute();
            
            http_response_code(200);

            return $result;
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }

    }

    private function login($userData)
    {
        $username = $userData["username"];
        $password = $userData["password"];
        
        try {
            if($username != "" && $password != ""){
                $pdo = DbConnection::getInstance()->getDb();
                $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE UserName='" . $username .  "' AND Password='" . $password . "' LIMIT 1";
                $sentence = $pdo->query($sql);
                return $sentence->fetch();
            }
            http_response_code(200);
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }

    }

    private function getUsers($parameters)
    {
        if(array_key_exists('skip', $parameters) && array_key_exists('take', $parameters)){
            $skip = (int)$parameters['skip'];
            $take = (int)$parameters['take'];
            $command = "SELECT * FROM " . self::TABLE_NAME . " ORDER BY UserName desc LIMIT :skip , :take ";

            // Preparar sentencia
            $sentence = DbConnection::getInstance()->getDb()->prepare($command);
            $sentence->bindParam(":skip", $skip, PDO::PARAM_INT);
            $sentence->bindParam(":take", $take, PDO::PARAM_INT);
        } else {
            $command = "SELECT * FROM " . self::TABLE_NAME . " ORDER BY UserName";

            // Preparar sentencia
            $sentence = DbConnection::getInstance()->getDb()->prepare($command);
        }

        try{
            $result = $sentence->execute();
        }catch(Exception $e){
            var_dump($e);
        }

        if ($result) {
            http_response_code(200);        
            return
                [
                    "state" => "SUCCESS",
                    "data" => $sentence->fetchAll(PDO::FETCH_ASSOC)
                ];
        } else
            throw new ApiException("Error", "Se ha producido un error");
    }

    public function updateLastUpdate($user_id, $value){
        $pdo = DbConnection::getInstance()->getDb();
        $sql = "UPDATE Users SET LastUpdate='" . $value . "' WHERE Id=" . $user_id;
        // var_dump($sql);
        $sentence = $pdo->query($sql);        
    }
    private function getUser($id)
    {
        $command = "SELECT Title, Message, LocationEnabled, Latitude, Longitude, Date, HasImage, Image FROM " . self::TABLE_NAME . " WHERE Id=:id ";

        // Preparar sentencia
        $sentence = DbConnection::getInstance()->getDb()->prepare($command);
        $sentence->bindParam(":id", $id, PDO::PARAM_INT);

        // $sentence->bindColumn(1, $title);
        // $sentence->bindColumn(2, $message);
        // $sentence->bindColumn(3, $locationEnabled);
        // $sentence->bindColumn(4, $latitude);
        // $sentence->bindColumn(5, $longitude);
        // $sentence->bindColumn(6, $date);
        // $sentence->bindColumn(7, $hasImage);
        // $sentence->bindColumn(8, $image);
        
        $executionResult = $sentence->execute();
        
        // $fetchResult = $sentence->fetch(PDO::FETCH_BOUND);

        // $result['Title'] = $title;
        // $result['Message'] = $message;
        // $result['LocationEnabled'] = $locationEnabled;
        // $result['Latitude'] = $latitude;
        // $result['Longitude'] = $longitude;
        // $result['Date'] = $date;
        // $result['HasImage'] = $hasImage;
        // $result['Image'] = $image;

        // $sentence->fetchAll(PDO::FETCH_ASSOC);
        // var_dump($sentence->fetchAll(PDO::FETCH_ASSOC));
        if ($executionResult) {
            http_response_code(200);        
            return
                [
                    "state" => "SUCCESS",
                    "data" => $sentence->fetchAll(PDO::FETCH_ASSOC)
                ];
        } else{

            throw new ApiException("Error", "Se ha producido un error");
        }
    }
}