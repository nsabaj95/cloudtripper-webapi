<?php
require_once 'application/libraries/REST_Controller.php';
require_once 'application/helpers/JsonResponse.php';
require_once 'application/helpers/HttpHelper.php';

class Subscriptions extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('subscriptions_model');
        // $this->load->helper('url_helper');
    }
    public function index_get()
    {
        $subscriptor_id = $this->get('subscriptor_id');
        $fromDate = $this->get('fromDate');
        
        $success = true;
        $data;

        $data = $this->subscriptions_model->get_numberOfNews($subscriptor_id, $fromDate);
        
        $message = "Se han encontrado " . $data . " novedades";

        $this->response(JsonResponse::getResponse($data, $success, $message));
    }
    
    public function index_post()
    {
        $p = HttpHelper::getParametersArray(true);
        $trip_id = $p['trip_id'];
        $user_id = $p['user_id'];
        
        $data = $this->subscriptions_model->post_subscription($trip_id, $user_id);
        $this->response(JsonResponse::getResponse($data, true, "New subscription added"));
    }

    public function index_delete()
    {
        $p = HttpHelper::getParametersArray(true);
        $trip_id = $p['trip_id'];
        $user_id = $p['user_id'];
        
        $this->subscriptions_model->delete_subscription($trip_id, $user_id);
        $this->response(JsonResponse::getResponse(null, true, "New subscription added"));
    }
}

// class News extends CI_Controller {

//         public function __construct()
//         {
//                 parent::__construct();
//                 $this->load->model('news_model');
//                 $this->load->helper('url_helper');
//         }

//         public function index()
//         {
//             $data['news'] = $this->news_model->get_news();
//             $data['title'] = 'News archive';

//             $this->load->view('templates/header', $data);
//             $this->load->view('news/index', $data);
//             $this->load->view('templates/footer');
//         }

//         public function view($slug = NULL)
//         {
//                 $data['news_item'] = $this->news_model->get_news($slug);
//         }
// }