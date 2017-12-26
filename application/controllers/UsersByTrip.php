<?php
require_once 'application/libraries/REST_Controller.php';
require_once 'application/helpers/JsonResponse.php';
require_once 'application/helpers/HttpHelper.php';
// require_once 'application/models/Users_model.php';

class UsersByTrip extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('trips_model');
        // $this->load->helper('url_helper');
    }
    public function index_get()
    {
        $trip_id = $this->get('trip_id');
        
        $data;
        $success = true;
        $message = "";
        
        $data = $this->trips_model->get_usersByTrip($trip_id);
        
        $this->response(JsonResponse::getResponse($data, $success, $message));
    }
    
    public function index_post()
    {
        $p = HttpHelper::getParametersArray(false);
        $data = $this->trips_model->post_userByTrip($p['user_id'], $p['trip_id']);
        $this->response(JsonResponse::getResponse($data, true, "New user added"));
    }
    // public function index_put()
    // {
    //     $lastupdate = $this->put('lastupdate');
    //     $id = $this->put('id');
    
    //     $this->response($this->users_model->put_user($id, $lastupdate));
    //     // Create a new book
    // }
}