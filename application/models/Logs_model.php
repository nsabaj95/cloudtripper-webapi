<?php
require_once 'Trips_model.php';
require_once 'Users_model.php';
require_once 'Trips_model.php';

class Logs_model extends CI_Model {
    
    public static $table = "Logs";

    public function __construct()
    {
        $this->load->database();
    }

    private function getQuery($skip, $take){
        $this->db->select('Logs.id, 
            Logs.title, 
            Logs.message, 
            Logs.locationEnabled, 
            Logs.latitude, 
            Logs.longitude, 
            Logs.date, 
            Logs.image, 
            Logs.trip_Id, 
            Trips.destination, 
            Users.username,
            Users.name');
        // $this->db->from(self::$table);
        $this->db->order_by("Logs.date", "desc");
        $this->db->join(Trips_model::$table, self::$table . '.trip_id = ' . Trips_model::$table . '.id');
        $this->db->join(Users_model::$table,  Trips_model::$table. '.user_id = ' . Users_model::$table . '.id');

        if($skip != null && $take != null){
            $query = $this->db->get(self::$table, $take, $skip);
        }else{
            $query = $this->db->get(self::$table);
        }
        return $query;
    }
    
    public function get_logs($skip, $take)
    {
        $query = $this->getQuery($skip, $take);
        return $query->result();
    }
    public function get_logById($id)
    {
        $query = $this->db->get_where(self::$table, array('Logs.id' => $id));
        return $query->row();
    }
    public function get_news($subscriptor_id, $skip, $take, $lastupdate)
    {
        $where = "Logs.trip_id IN (SELECT s.Trip_Id FROM SubscriptorsByTrip s WHERE s.User_Id = " . $subscriptor_id . ")";
        $this->db->where($where);
        $query = $this->getQuery($skip, $take);

        $usersModel = new Users_model();
        $subscriptor = $usersModel->get_userById($subscriptor_id);
        $subscriptor->lastupdate = $lastupdate;
        $usersModel->put_user($subscriptor_id, $subscriptor);

        return $query->result();
    }
    public function get_logsByTripId($trip_id, $skip, $take)
    {
        $this->db->where('trip_id', $trip_id);
        $query = $this->getQuery($skip, $take);
        $result = $query->result();
        // print_r($this->db->last_query());
        return $result;
    }
    public function post_log($title, $message, $locationEnabled, $latitude, $longitude, $date, $image, $trip_id){
        $data = array(
        'title' => $title,
        'message' => $message,
        'locationEnabled' => $locationEnabled,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'date' => $date,
        'image' => $image,
        'trip_id' => $trip_id
        );

        $this->db->insert(self::$table, $data);

        $trips_model = new Trips_model();
        $trip = $trips_model->get_tripById($trip_id);
        $trip->numberOfLogs = $trip->numberOfLogs + 1;
        $trips_model->put_trip($trip_id, $trip);

        return $this->db->insert_id();
    }

    public function delete_log($id){
        $this->db->delete(self::$table, array('id' => $id));
        
        $trips_model = new Trips_model();
        $trip = $trips_model->get_tripById($trip_id);
        $trip->numberOfLogs = $trip->numberOfLogs + 1;
        $trips_model->put_trip($trip_id, $trip);
    }
    
}