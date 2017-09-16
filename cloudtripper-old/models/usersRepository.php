<?php
require_once dirname(dirname(__FILE__)).'/utilities/ApiException.php';
require_once 'helpers/logger.php';
require_once 'interfaces/iRepository.php';
require_once 'Repository.php';

class UsersRepository extends Repository
{
    // Datos de la tabla "usuario"
    const TABLE_NAME = "Users";
    const ID = "id";
    const FACEBOOK_ID = "facebookid";
    const USERNAME = "username";
    const PASSWORD = "password";
    const NAME = "name";
    const LASTUPDATE = "lastupdate";
    const AVATAR = "avatar";

    const QUERY_SELECT = "SELECT * FROM " . self::TABLE_NAME;

    public function get($request){
        if(array_key_exists('id', $request) || array_key_exists('facebookid', $request)){
            return self::getUser($request);
        }
        else{
            return self::getUsers($request);
        }
    }

    public function put($request){
        $id = $request['id'];
        $text = $request['text'];

        try {
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "UPDATE " . self::TABLE_NAME . " SET " .
                self::TEXT . "=:text WHERE " . self::ID . "=:id";

            $sentence = $pdo->prepare($command);

            $sentence->bindParam(':text', $text, PDO::PARAM_STR);
            $sentence->bindParam(':id', $id, PDO::PARAM_INT);
            
            $result = $sentence->execute();
            
            if(!empty($userData["image"])){
                self::addUserAvatar($userData["image"], $id);
            }

            http_response_code(200);

            return $result;
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }
    }

    public function post($request)
    {
        if($request['action'] == "register"){
            return self::register($request);
        } else {
            try{
                $hasUsername = array_key_exists('username', $request) && $request['username'] != "";
                $hasPassword = array_key_exists('password', $request) && $request['password'] != "";
                $hasFacebookid = array_key_exists('facebookid', $request) && $request['facebookid'] != "";
                if(!($hasUsername && $hasPassword) && !$hasFacebookid){
                    throw new Exception('No password or username.');
                }
                $user = self::login($request);
                // $user = self::login($request);
                if($user != null)
                {
                    return [
                        "user" => $user,
                        "success" => true
                    ];
                }
                else {
                    return [
                        "success" => false
                    ];
                }
                
            } catch(Exception $e){
                header("HTTP/1.1 401 Unauthorized", true, 401);
                throw $e;
            }
        }
    }

    private function register($request)
    {
        $username = $request["username"];
        $password = $request["password"];
        $facebookid = $request["facebookid"];
        $avatar = $request["avatar"];
        $name = $request["name"];
        
        try {
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "INSERT INTO " . self::TABLE_NAME . " ( " .
                self::USERNAME . "," .
                self::PASSWORD . "," .
                self::FACEBOOK_ID . "," .
                self::NAME . "," .
                self::AVATAR . ")" .
                " VALUES(:username,:password,:facebookid,:name,:avatar)";

            $sentence = $pdo->prepare($command);

            $sentence->bindParam(':username', $username, PDO::PARAM_STR);
            $sentence->bindParam(':password', $password, PDO::PARAM_STR);
            $sentence->bindParam(':facebookid', $facebookid, PDO::PARAM_STR);
            $sentence->bindParam(':avatar', $avatar, PDO::PARAM_STR);
            $sentence->bindParam(':name', $name, PDO::PARAM_STR);
            
            $result = $sentence->execute();
            
            http_response_code(200);
            $id = $pdo->lastInsertId();
            return [
                "id"=>$id
            ];
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }
    }

    private function login($request)
    {
        $sql = self::QUERY_SELECT;
        $filter = "";
        if(array_key_exists('username', $request) && array_key_exists('password', $request)){
            $username = $request["username"];
            $password = $request["password"];
            $filter = " WHERE " . self::USERNAME . "='" . $username .  "' AND " . self::PASSWORD . "='" . $password . "' LIMIT 1";
        }else if(array_key_exists('facebookid', $request)){
            $facebookid = $request["facebookid"];
            $filter = " WHERE " . self::FACEBOOK_ID . "='" . $facebookid . "' LIMIT 1";
        }
        
        $sql = $sql . $filter;

        try {
            $pdo = DbConnection::getInstance()->getDb();
            $sentence = $pdo->query($sql);
            $result = $sentence->fetch();
            http_response_code(200);
            return $result;
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
    private function getUser($request)
    {
        $command = "SELECT * FROM " . self::TABLE_NAME . " ORDER BY " . self::USERNAME . " WHERE :filter";
        $filter = "";
        if(array_key_exists('id', $request)){
            $filter = self::ID . "=" . $request['id'];
        } else if(array_key_exists('facebookid', $request)) {
            $filter = self::FACEBOOKID . "=" . $request['facebookid'];
        }

        $sentence = DbConnection::getInstance()->getDb()->prepare($command);
        $sentence->bindParam(":filter", $filter, PDO::PARAM_STR);

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

    private function executeQuery($data, $message){
        return
        [
            "error" => false,
            "data" => $data,
            "state" => "SUCCESS",
            "message" => $message
        ];
    }
}