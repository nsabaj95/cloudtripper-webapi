<?php
require_once dirname(dirname(__FILE__)).'/utilities/ApiException.php';
require_once 'helpers/logger.php';
require_once 'interfaces/iRepository.php';

class subscriptionsRepository implements iRepository 
{
    // Datos de la tabla "usuario"
    const TABLE_NAME = "SubscriptorsByTrip";
    const TRIP_ID = "Trip_Id";
    const USER_ID = "User_Id";

    public function get($request){
        if(array_key_exists('subscriptor_id', $request) && array_key_exists('fromDate', $request))
            return self::getNumberOfNews($request);
        else if(array_key_exists('trip_id', $request))
            return self::getSubscriptions($request);
        
    }

    public function getNumberOfNews($request)
    {
        $subscriptor_id = $request["subscriptor_id"];
        $fromDate = $request["fromDate"];
        // $updateDate = $request["lastUpdate"];
        // var_dump($fromDate);
        try {
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "SELECT COUNT(*) FROM Logs WHERE Date > :fromDate AND Trip_Id IN (SELECT Trip_Id FROM SubscriptorsByTrip WHERE User_Id = :subscriptor_id)";
            // var_dump($command);

            $sentence = $pdo->prepare($command);

            $sentence->bindParam(':fromDate', $fromDate, PDO::PARAM_STR);
            $sentence->bindParam(':subscriptor_id', $subscriptor_id, PDO::PARAM_INT);
            
            // $sentence = $pdo->query($command);
            $sentence->execute();
            // var_dump($sentence);
            $numberOfNews = $sentence->fetchColumn();

            http_response_code(200);
            return
                [
                    "numberOfNews" => (int)$numberOfNews
                ];
        } catch (Exception $e) {
            print($e);
            throw new ApiException("SubscriptionsDeleteError", $e->getMessage());
        }
    }
    public function delete($request)
    {
        $subscriptor_id = $request["user_id"];
        $trip_id = $request["trip_id"];
        
        try {
            // var_dump(self::COLUMNS);
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "DELETE FROM " . self::TABLE_NAME . " WHERE " .
                self::USER_ID . "=:user_id AND " . self::TRIP_ID . "=:trip_id";

            $sentence = $pdo->prepare($command);

            $sentence->bindParam(':user_id', $subscriptor_id, PDO::PARAM_INT);
            $sentence->bindParam(':trip_id', $trip_id, PDO::PARAM_INT);
            
            $result = $sentence->execute();

            $tripsRepository = new tripsRepository();
            $tripsRepository->updateNumberOfSubscriptions(-1, $trip_id);

            http_response_code(200);

            return $result;
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }
    }
    private function getTrips($parameters)
    {
        $selectQuery = "SELECT * FROM " . self::TABLE_NAME;
        $bySubscriptor = !empty($logData["subscriptor_id"]);
        var_dump($bySubscriptor);
        $byUser = !empty($logData["user_id"]);
        
        if(array_key_exists('skip', $parameters) && array_key_exists('take', $parameters)){
            $skip = (int)$parameters['skip'];
            $take = (int)$parameters['take'];
            $user_Id = 0;
            
            if($byUser)
                $user_Id = (int)$parameters['user_id'];
            
            $command = $selectQuery;

            if($byUser){
                $command = $command . " WHERE " . self::USER_ID . "=" . $user_Id;
            } else if ($bySubscriptor){
                $command = $command . " WHERE Id IN (SELECT Trip_Id FROM SubscriptorsByTrip WHERE User_Id = " . $logData["subscriptor_id"];
            }

            $command = $command . " ORDER BY StartDate desc LIMIT :skip , :take";

            // Preparar sentencia
            $sentence = DbConnection::getInstance()->getDb()->prepare($command);
            $sentence->bindParam(":skip", $skip, PDO::PARAM_INT);
            $sentence->bindParam(":take", $take, PDO::PARAM_INT);
        } else {
            $command = $selectQuery;

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

    public function post($request)
    {
        try{
            
            $id = self::create($request);
            if($id != null){
                return
                [
                    "id" => $id,
                ];
            } else {
                throw new ApiException("error", $e->getMessage());
            }
            
        } catch(Exception $e){
            return 
            [
                "error" => true,
                "state" => "error",
                "message" => utf8_encode($e)
            ];
        }
    }
    private function create($logData)
    {
        $trip_id = $logData["trip_id"];
        $user_id = $logData["user_id"];
        
        try {
            $pdo = DbConnection::getInstance()->getDb();

            // Sentencia INSERT
            $command = "INSERT INTO " . self::TABLE_NAME . " ( " .
                self::TRIP_ID . "," .
                self::USER_ID . ")" .
                " VALUES(:trip_id,:user_id)";

            $sentence = $pdo->prepare($command);

            $sentence->bindParam(':trip_id', $trip_id, PDO::PARAM_STR);
            $sentence->bindParam(':user_id', $user_id, PDO::PARAM_STR);
            
            $result = $sentence->execute();
            
            $tripsRepository = new tripsRepository();
            $tripsRepository->updateNumberOfSubscriptions(1, $trip_id);

            http_response_code(200);

            return $result;
        } catch (Exception $e) {
            print($e);
            throw new ApiException(self::PDO_ERROR, $e->getMessage());
        }
    }
    
    private function getSubscriptions($parameters)
    {
        if(array_key_exists('skip', $parameters) && array_key_exists('take', $parameters)){
            $skip = (int)$parameters['skip'];
            $take = (int)$parameters['take'];
            $command = "SELECT  FROM Users u INNER JOIN SubscriptorsByTrip sbt ON u.Id = sbt.User_Id 
            WHERE Trip_Id = " . $parameters["trip_id"];

            // Preparar sentencia
            $sentence = DbConnection::getInstance()->getDb()->prepare($command);
            $sentence->bindParam(":skip", $skip, PDO::PARAM_INT);
            $sentence->bindParam(":take", $take, PDO::PARAM_INT);
        } else {
            $command = "SELECT * FROM Users u INNER JOIN SubscriptorsByTrip sbt ON u.Id = sbt.User_Id 
            WHERE Trip_Id = " . $parameters["trip_id"];

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
        } else{
            // throw new ApiException("Error", "Se ha producido un error");
            return
                [
                    "state" => "ERROR",
                    "data" => null
                ];
        }
    }
}