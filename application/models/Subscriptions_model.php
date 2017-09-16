<?php
require_once 'Logs_model.php';
require_once 'Trips_model.php';

class Subscriptions_model extends CI_Model {
    public static $table = "SubscriptorsByTrip";

    public function __construct()
    {
        $this->load->database();
    }
    
    public function get_numberOfNews($subscriptor_id, $fromDate)
    {
        $this->db->where('date >', $fromDate);
        $this->db->where('trip_id IN (SELECT trip_id FROM SubscriptorsByTrip WHERE user_id = ' . $subscriptor_id . ')');
        $this->db->from(Logs_model::$table);
        return $this->db->count_all_results();
    }
    public function post_subscription($trip_id, $user_id){
        $data = array(
        'trip_id' => $trip_id,
        'user_id' => $user_id
        );
        
        $this->db->insert(self::$table, $data);

        $trips_model = new Trips_model();
        $trip = $trips_model->get_tripById($trip_id);
        $trip->numberOfSubscriptions = $trip->numberOfSubscriptions + 1;
        $trips_model->put_trip($trip_id, $trip);

        return $this->db->insert_id();
    }
    public function delete_subscription($trip_id, $user_id){
        $data = array(
        'trip_id' => $trip_id,
        'user_id' => $user_id
        );
     
        $this->db->where($data);
        $this->db->delete(self::$table);

        $trips_model = new Trips_model();
        $trip = $trips_model->get_tripById($trip_id);
        $trip->numberOfSubscriptions = $trip->numberOfSubscriptions - 1;
        $trips_model->put_trip($trip_id, $trip);
    }
}