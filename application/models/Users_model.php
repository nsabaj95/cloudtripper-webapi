<?php
class Users_model extends CI_Model {
    public static $table = "Users";

    public function __construct()
    {
        $this->load->database();
    }
    
    public function get_users()
    {
        $query = $this->db->get('Users');
        return $query->result();
    }
    public function get_userById($id)
    {
        $query = $this->db->get_where('Users',  array('id' => $id));
        return $query->row();
    }
    public function get_login($username, $password)
    {
        $query = $this->db->get_where('Users',  array('username' => $username, 'password' => $password));
        return $query->row();
    }
    public function get_userByFacebookId($facebookid)
    {
        $query = $this->db->get_where('Users',  array('facebookid' => $facebookid));
        return $query->row();
    }
    public function post_user($username, $password, $facebookid, $avatar, $name){
        $data = array(
        'username' => $username,
        'password' => $password,
        'facebookid' => $facebookid,
        'avatar' => $avatar,
        'name' => $name,
        );
        
        $this->db->insert('Users', $data);
        return $this->db->insert_id();
    }
    public function put_user($id, $data){
        // $data = array(
        // 'lastupdate' => $lastupdate,
        // );

        $this->db->where('id', $id);
        $this->db->update('Users', $data);
    }
}