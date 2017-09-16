<?php
require_once dirname(dirname(__FILE__)).'/utilities/ApiException.php';
require_once 'helpers/logger.php';

class tripsRepository
{
    // Datos de la tabla "usuario"
    const entityName = "trip";
    const TABLE_NAME = "Trips";
    const ID = "Id";
    const ORIGIN = "Origin";
    const DESTINATION = "Destination";
    const STARTDATE = "StartDate";
    const ENDDATE = "EndDate";
    const USER_ID = "User_Id";
    const ACTIVE = "Active";
    const NUMBEROFLOGS = "NumberOfLogs";
    const LASTUPDATE = "LastUpdate";
    const IMAGE = "Image";
    const COLUMNS = self::ORIGIN . "," . self::DESTINATION . "," . self::STARTDATE . "," . self::ENDDATE . "," . self::USER_ID . "," . self::NUMBEROFLOGS . "," . self::LASTUPDATE;

    public function get($request){
        if(array_key_exists('id', $request)){
            return self::getTrip($request['id']);
        }else{
            return self::getTrips($request);
        }
    }

    public function post($request)
    {
        //Obtener body de la petición
        // $body = file_get_contents('php://input');
        // print(' > Body decoded');
        try{
            $result = self::create($request);
            http_response_code(200);
        } catch(Exception $e){
            return 
            [
                "state" => "FAIL",
                "message" => utf8_encode($e)
            ];
        }
    }
    public function addTripImage($image, $trip_id){        
        $image = addslashes($image); //SQL Injection defence!

        try {
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "UPDATE " . self::TABLE_NAME . " SET " .
                "Image=" . "'{$image}', HasImage=1 WHERE Id=" . "$trip_id";

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
        $origin = $logData["origin"];
        $destination = $logData["destination"];
        $startDate = $logData["startDate"];
        $endDate = $logData["endDate"];
        $user_Id = $logData["user_id"];
        $lastUpdate = $logData["lastUpdate"];
        $image = $logData["image"];

        try {
            // var_dump(self::COLUMNS);


            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "INSERT INTO " . self::TABLE_NAME . " ( " .
                self::COLUMNS . ")" .
                " VALUES(:origin,:destination,:startDate,:endDate,:user_Id,0,:lastUpdate)";


            $sentence = $pdo->prepare($command);

            $sentence->bindParam(':origin', $origin, PDO::PARAM_STR);
            $sentence->bindParam(':destination', $destination, PDO::PARAM_STR);
            $sentence->bindParam(':startDate', $startDate, PDO::PARAM_STR);
            $sentence->bindParam(':endDate', $endDate, PDO::PARAM_STR);
            $sentence->bindParam(':user_Id',$user_Id, PDO::PARAM_INT);
            $sentence->bindParam(':lastUpdate',$user_Id, PDO::PARAM_STR);
            
            $result = $sentence->execute();

            if(!empty($logData["image"])){
                $id = $pdo->lastInsertId();
                self::addTripImage($image, $id);
            }

            http_response_code(200);

            return $result;
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }
    }
    public function delete($logData)
    {
        $id = $logData["id"];
        
        try {
            // var_dump(self::COLUMNS);
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "DELETE FROM " . self::TABLE_NAME . " WHERE " .
                self::ID . "=:id";

            $sentence = $pdo->prepare($command);

            $sentence->bindParam(':id', $id, PDO::PARAM_INT);
            
            $result = $sentence->execute();

            http_response_code(200);

            return $result;
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }
    }
    public function updateLogDynamicData($lastUpdate, $trip_id){
        $pdo = DbConnection::getInstance()->getDb();
        $sql = "SELECT NumberOfLogs FROM Trips WHERE Id=" . $trip_id . " LIMIT 1";
        $sentence = $pdo->query($sql);
        $numberOfLogs = $sentence->fetchColumn() + 1;
        
        $updateSql = "UPDATE Trips SET NumberOfLogs=" . $numberOfLogs . ", LastUpdate='" . $lastUpdate ."' WHERE Id=" . $trip_id;
        $updateSentence = $pdo->query($updateSql);
    }
    public function updateNumberOfLogs($value, $trip_id){
        $pdo = DbConnection::getInstance()->getDb();
        $sql = "SELECT NumberOfLogs FROM Trips WHERE Id=" . $trip_id . " LIMIT 1";
        $sentence = $pdo->query($sql);
        $numberOfLogs = $sentence->fetchColumn() + $value;
        
        $updateSql = "UPDATE Trips SET NumberOfLogs=" . $numberOfLogs . " WHERE Id=" . $trip_id;
        $updateSentence = $pdo->query($updateSql);
    }

    public function updateNumberOfSubscriptions($value, $trip_id){
        $pdo = DbConnection::getInstance()->getDb();
        $sql = "SELECT NumberOfSubscriptions FROM Trips WHERE Id=" . $trip_id . " LIMIT 1";
        $sentence = $pdo->query($sql);
        $numberOfSubscriptions = $sentence->fetchColumn() + $value;
        
        $updateSql = "UPDATE Trips SET NumberOfSubscriptions=" . $numberOfSubscriptions . " WHERE Id=" . $trip_id;
        $updateSentence = $pdo->query($updateSql);
    }
    private function getTrips($parameters)
    {
        $selectQuery = "SELECT Trips.*, Users.UserName FROM " . self::TABLE_NAME . " INNER JOIN Users ON Trips.User_Id = Users.Id ";
        $bySubscriptor = !empty($parameters["subscriptor_id"]);
        $byUser = !empty($parameters["user_id"]);
        $sentence;
        $result;
        $command = $selectQuery;

        if($byUser){
            $command = $command . " WHERE " . self::USER_ID . "=" . (int)$parameters['user_id'];
        } else if ($bySubscriptor){
            $command = $command . " WHERE Trips.Id IN (SELECT s.Trip_Id FROM SubscriptorsByTrip s WHERE s.User_Id = " . $parameters["subscriptor_id"] . ")";
        }
        
        $command = $command . " ORDER BY StartDate desc";
        
        if(array_key_exists('skip', $parameters) && array_key_exists('take', $parameters)){
            $command = $command . " LIMIT :skip , :take";
            $skip = (int)$parameters['skip'];
            $take = (int)$parameters['take'];
            $sentence = DbConnection::getInstance()->getDb()->prepare($command);
            $sentence->bindParam(":skip", $skip, PDO::PARAM_INT);
            $sentence->bindParam(":take", $take, PDO::PARAM_INT);
        } else {
            $sentence = DbConnection::getInstance()->getDb()->prepare($command);
        }


        try{
            $result = $sentence->execute();
        }catch(Exception $e){
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
    private function getTrip($id)
    {
        $command = "SELECT * FROM " . self::TABLE_NAME . " WHERE Id=:id ";

        // Preparar sentencia
        $sentence = DbConnection::getInstance()->getDb()->prepare($command);
        $sentence->bindParam(":id", $id, PDO::PARAM_INT);
        
        $executionResult = $sentence->execute();
        $sentence->fetchAll(PDO::FETCH_ASSOC);
        // var_dump($sentence->fetchAll(PDO::FETCH_ASSOC));
        if ($executionResult) {
            http_response_code(200);        
            return $sentence->fetchAll(PDO::FETCH_ASSOC);
        } else{

            throw new ApiException("Error", "Se ha producido un error");
        }
    }
}