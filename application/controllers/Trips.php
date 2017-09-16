<?php
require_once 'application/libraries/REST_Controller.php';
require_once 'application/helpers/JsonResponse.php';
require_once 'application/helpers/HttpHelper.php';
// require_once 'application/models/Users_model.php';

class Trips extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('trips_model');
        // $this->load->helper('url_helper');
    }
    public function index_get()
    {
        $skip = $this->get('skip');
        $take = $this->get('take');
        $subscriptor_id = $this->get('subscriptor_id');
        $user_id = $this->get('user_id');
        $id = $this->get('id');
        
        $data;
        $success = true;
        $message = "";
        
        if($subscriptor_id != null){
            $data = $this->trips_model->get_tripsBySubscriptorId($subscriptor_id, $skip, $take);
        }
        else if($user_id != null){
            $data = $this->trips_model->get_tripsByUserId($user_id, $skip, $take);
        }
        else if($id != null){
            $data = $this->trips_model->get_tripById($id);
        }
        else {
            $data = $this->trips_model->get_trips($skip, $take);
        }
        
        $this->response(JsonResponse::getResponse($data, $success, $message));
    }
    
    public function index_post()
    {
        $p = HttpHelper::getParametersArray(true);
        $image = array_key_exists('image', $p) && $p['image'] != null ? addslashes($p['image']) : "";
        $data = $this->trips_model->post_trip($p['origin'], $p['destination'], $p['startDate'], $p['endDate'], $p['user_id'], $p['lastUpdate'], $image);
        $this->response(JsonResponse::getResponse($data, true, "New trip added"));
    }
    // public function index_put()
    // {
    //     $lastupdate = $this->put('lastupdate');
    //     $id = $this->put('id');
    
    //     $this->response($this->users_model->put_user($id, $lastupdate));
    //     // Create a new book
    // }
}