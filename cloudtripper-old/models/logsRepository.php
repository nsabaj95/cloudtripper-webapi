<?php
require_once dirname(dirname(__FILE__)).'/utilities/ApiException.php';
require_once 'helpers/logger.php';
require_once 'interfaces/iRepository.php';

class logsRepository implements iRepository
{
    // Datos de la tabla "usuario"
    const TABLE_NAME = "Logs";
    const IMAGES_TABLE_NAME = "ImagesPerLog";
    const ID = "Id";
    const TITLE = "Title";
    const MESSAGE = "Message";
    const LOCATION_ENABLED = "LocationEnabled";
    const LATITUDE = "Latitude";
    const LONGITUDE = "Longitude";
    const DATE = "Date";
    const IMAGE = "Image";
    const TRIP_ID = "Trip_Id";

    public function get($request){
        if(array_key_exists('id', $request)){
            return self::getLog($request['id']);
        }else{
            return self::getLogs($request);
        }
    }

    public function post($request)
    {
        //Obtener body de la petición
        // $body = file_get_contents('php://input');
        // print(' > Body decoded');
        $result = self::create($request);
        http_response_code(200);
    }
    // public function getImage($id){
    //     $command2 = "SELECT Image FROM " . self::TABLE_NAME . " WHERE ID = 124";
    //     $sentence2 = DbConnection::getInstance()->getDb()->prepare($command2);
    //     $sentence2->bindColumn(1, $Image);
    //     $sentence2->execute();
    //     $result2 = $sentence2->fetch(PDO::FETCH_BOUND);
    // }
    public function delete($request)
    {
        $id = $request["id"];
        $trip_id = $request["trip_id"];
        
        try {
            // var_dump(self::COLUMNS);
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "DELETE FROM " . self::TABLE_NAME . " WHERE " .
                self::ID . "=:id";

            $sentence = $pdo->prepare($command);

            $sentence->bindParam(':id', $id, PDO::PARAM_INT);
            
            $result = $sentence->execute();

            $tripsRepository = new tripsRepository();
            $tripsRepository->updateNumberOfLogs(-1, $trip_id);

            http_response_code(200);
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }
    }
    public function addLogImage($image, $log_id){        
        $image = addslashes($image); //SQL Injection defence!

        try {
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "UPDATE " . self::TABLE_NAME . " SET " .
                "Image=" . "'{$image}', HasImage=1 WHERE Id=" . "$log_id";

            $sentence = $pdo->prepare($command);
            $result = $sentence->execute();
            
            http_response_code(200);

            return $result;
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }

    }
    private function create($logData)
    {
        $title = $logData["title"];
        $message = $logData["message"];
        $positionEnabled = $logData["positionEnabled"] == true ? 1 : 0;
        $latitude = $logData["latitude"];
        $longitude = $logData["longitude"];
        $date = str_replace("/", "-", $logData["date"]);        
        $image = $logData["image"];
        $trip_id = $logData["trip_id"];

        try {
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "INSERT INTO " . self::TABLE_NAME . " ( " .
                self::TITLE . "," .
                self::MESSAGE . "," .
                self::LOCATION_ENABLED . "," .
                self::DATE . "," .
                self::LONGITUDE . "," .
                self::LATITUDE . "," .
                self::TRIP_ID . ")" .
                " VALUES(:title,:message,:positionEnabled,:date, :longitude,:latitude,:trip_id)";

            $sentence = $pdo->prepare($command);

            $sentence->bindParam(':title', $title, PDO::PARAM_STR);
            $sentence->bindParam(':message', $message, PDO::PARAM_STR);
            $sentence->bindParam(':positionEnabled', $positionEnabled, PDO::PARAM_BOOL);
            $sentence->bindParam(':date', $date, PDO::PARAM_STR);
            $sentence->bindParam(':longitude',$longitude, PDO::PARAM_STR);
            $sentence->bindParam(':latitude', $latitude, PDO::PARAM_STR);
            $sentence->bindParam(':trip_id', $trip_id, PDO::PARAM_INT);
            
            $result = $sentence->execute();
            
            if(!empty($logData["image"])){
                $id = $pdo->lastInsertId();
                self::addLogImage($image, $id);
            }

            $tripsRepository = new tripsRepository();
            $tripsRepository->updateLogDynamicData($date, $trip_id);

            http_response_code(200);

            return $result;
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }
    }

    private function getLogs($parameters)
    {
        $byTrip = !empty($parameters["trip_id"]);
        $bySubscriptor = !empty($parameters["subscriptor_id"]);
        if(array_key_exists('skip', $parameters) && array_key_exists('take', $parameters)){
            $skip = (int)$parameters['skip'];
            $take = (int)$parameters['take'];
            
// const IMAGES_TABLE_NAME = "ImagesPerLog";
//     const TITLE = "Title";
//     const MESSAGE = "Message";
//     const LOCATION_ENABLED = "LocationEnabled";
//     const LATITUDE = "Latitude";
//     const LONGITUDE = "Longitude";
//     const DATE = "Date";
//     const IMAGE = "Image";
//     const TRIP_ID = "Trip_Id";

            $command = "SELECT l.Id, l.Title, l.Message, l.LocationEnabled, l.Latitude, l.Longitude, Date, l.Image, l.Trip_Id, t.Destination, u.UserName FROM "
             . self::TABLE_NAME . " l 
              INNER JOIN Trips t ON t.Id = l.Trip_Id 
              INNER JOIN Users u ON u.Id = t.User_Id ";
            //   INNER JOIN Users u ON l.User_Id = u.Id";

            if($byTrip)
                $command = $command . " WHERE l.Trip_Id=" . (int)$parameters['trip_id'];
            if($bySubscriptor){
                $subscriptor_id = (int)$parameters['subscriptor_id'];
                $usersRepository = new usersRepository();
                $usersRepository->updateLastUpdate($subscriptor_id, $parameters['updateDate']);
                $command = $command . " INNER JOIN SubscriptorsByTrip s ON l.Trip_Id = s.Trip_Id INNER JOIN Users us ON us.Id = t.User_Id WHERE s.User_Id=" . $subscriptor_id;
            }

            $command = $command . " ORDER BY date desc LIMIT :skip , :take ";
// var_dump($command);
            // Preparar sentencia
            $sentence = DbConnection::getInstance()->getDb()->prepare($command);
            $sentence->bindParam(":skip", $skip, PDO::PARAM_INT);
            $sentence->bindParam(":take", $take, PDO::PARAM_INT);
        } else {
            
            $command = "SELECT * FROM " . self::TABLE_NAME;

            if($byTrip)
                $command = $command . " WHERE Trip_Id=" . (int)$parameters['trip_id'];

            $command = $command . " ORDER BY date";
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
            return $sentence->fetchAll(PDO::FETCH_ASSOC);
        } else
            throw new ApiException("Error", "Se ha producido un error");
    }
    private function getLog($id)
    {
        $command = "SELECT Title, Message, LocationEnabled, Latitude, Longitude, Date, HasImage, Image, Trip_Id FROM " . self::TABLE_NAME . " WHERE Id=:id ";

        // Preparar sentencia
        $sentence = DbConnection::getInstance()->getDb()->prepare($command);
        $sentence->bindParam(":id", $id, PDO::PARAM_INT);
        $executionResult = $sentence->execute();
        
        // var_dump($sentence->fetchAll(PDO::FETCH_ASSOC));
        if ($executionResult) {
            http_response_code(200);        
            return $sentence->fetchAll(PDO::FETCH_ASSOC);
        } else{

            throw new ApiException("Error", "Se ha producido un error");
        }
    }
}