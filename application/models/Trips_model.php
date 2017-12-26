<?php
require_once 'Users_model.php';

class Trips_model extends CI_Model {
    
    public static $table = "Trips";
    
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }
    
    private function getQuery($skip, $take){
        $this->db->select('Trips.*,
        Users.username,
        Users.name');
        $this->db->join(Users_model::$table, self::$table. '.user_id = ' . Users_model::$table . '.id');
        
        if($skip != null && $take != null){
            $this->db->order_by('startDate', 'DESC');
            $query = $this->db->get(self::$table, $take, $skip);
        }else{
            $query = $this->db->get(self::$table);
        }
        return $query;
    }
    
    public function get_trips($skip, $take)
    {
        $query = $this->getQuery($skip, $take);
        return $query->result();
    }
    public function get_tripById($id)
    {
        $query = $this->db->get_where(self::$table, array('id' => $id));
        return $query->row();
    }
    public function get_tripsBySubscriptorId($subscriptor_id, $skip, $take)
    {
        $where = "Trips.id IN (SELECT s.Trip_Id FROM SubscriptorsByTrip s WHERE s.User_Id = " . $subscriptor_id . ")";
        $this->db->where($where);
        $query = $this->getQuery($skip, $take);
        return $query->result();
    }
    public function get_tripsByUserId($user_id, $skip, $take)
    {
        $where = "Trips.user_id = " . $user_id . " || Trips.id IN (SELECT u.trip_id FROM UsersByTrip u WHERE u.user_id = " . $user_id . ")";
        $this->db->where($where);
        $query = $this->getQuery($skip, $take);
        return $query->result();
    }
    public function post_trip($origin, $destination, $startDate, $endDate, $user_id, $lastUpdate, $image){
        $data = array(
        'origin' => $origin,
        'destination' => $destination,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'user_id' => $user_id,
        'lastUpdate' => $lastUpdate,
        'image' => $image
        );
        
        $this->db->insert(self::$table, $data);
        return $this->db->insert_id();
    }
    public function post_userByTrip($user_id, $trip_id){
        $data = array(
        'user_id' => $user_id,
        'trip_id' => $trip_id,
        );
        
        $this->db->insert('UsersByTrip', $data);
        return $this->db->insert_id();
    }
    public function get_usersByTrip($trip_id){
        
        $this->db->select('Users.username, Users.id, Users.avatar, Users.name');
        $this->db->join('UsersByTrip', 'Users.id = UsersByTrip.user_id AND UsersByTrip.trip_id=' . $trip_id);
        $query = $this->db->get('Users');
        return $query->result();
    }
    public function delete_trip($id){
        $this->db->delete(self::$table, array('id' => $id));
    }
    public function put_trip($id, $data){
        // $data = array(
        // 'lastupdate' => $lastupdate,
        // );
        
        $this->db->where('id', $id);
        $this->db->update(self::$table, $data);
    }
}