<?php
require_once 'application/libraries/REST_Controller.php';
require_once 'application/helpers/JsonResponse.php';
require_once 'application/helpers/HttpHelper.php';
// require_once 'application/models/Users_model.php';

class Logs extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('logs_model');
        $this->load->helper(array('form', 'url'));
        // $this->load->helper('url_helper');
    }
    public function index_get()
    {
        $skip = $this->get('skip');
        $take = $this->get('take');
        $subscriptor_id = $this->get('subscriptor_id');
        $updateDate = $this->get('updateDate');
        $trip_id = $this->get('trip_id');
        $id = $this->get('id');

        $data;
        $success = true;
        $message = "";

        if($subscriptor_id != null){
            $data = $this->logs_model->get_news($subscriptor_id, $skip, $take, $updateDate);
        }
        else if($trip_id != null){
            $data = $this->logs_model->get_logsByTripId($trip_id, $skip, $take);
        }
        else if($id != null){
            $data = $this->logs_model->get_logById($id);
        }
        else {
            $data = $this->logs_model->get_logs($skip, $take);
        }

        $this->response(JsonResponse::getResponse($data, $success, $message));
    }
    
    public function index_post()
    {
        $p = HttpHelper::getParametersArray(true);
        $image = array_key_exists('image', $p) && $p['image'] != null ? addslashes($p['image']) : "";
        $data = $this->logs_model->post_log($p['title'], $p['message'], $p['positionEnabled'], $p['latitude'], $p['longitude'], $p['date'], $image, $p['trip_id'], $p['address']);
        $this->response(JsonResponse::getResponse($data, true, "New log added"));
    }
    // public function index_put()
    // {
    //     $lastupdate = $this->put('lastupdate');
    //     $id = $this->put('id');

    //     $this->response($this->users_model->put_user($id, $lastupdate));
    //     // Create a new book
    // }
}