<?php
require_once 'application/libraries/REST_Controller.php';
require_once 'application/helpers/JsonResponse.php';
require_once 'application/helpers/HttpHelper.php';

class ExifUtility extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('logs_model');
        $this->load->helper('url_helper');
    }
    // public function index_get()
    // {
    //     $facebookid = $this->get('facebookid');
    //     $id = $this->get('id');
    //     $username = $this->get('username');
    //     $password = $this->get('password');
        
    //     $success = true;
    //     $data;
    //     $message = "";

    //     if($facebookid != null){
    //         $data = $this->users_model->get_userByFacebookId($facebookid);    
    //         if($data == null){
    //             $success = false;
    //             $message = "Datos de usuario incorrectos";
    //         }
    //     }
    //     else if($id != null){
    //         $data = $this->users_model->get_userById($facebookid);    
    //     }
    //     else if($username != null && $password != null){
    //         $data = $this->users_model->get_login($username, $password);    
    //         if($data == null){
    //             $success = false;
    //             $message = "Datos de usuario incorrectos";
    //         }
    //     }
    //     else{
    //         $data = $this->users_model->get_users();
    //     }

    //     $this->response(JsonResponse::getResponse($data, $success, $message));
    // }
    
    public function index_post()
    {
        $data = $this->logs_model->get_logById(23);
        $image = base64_decode($data->image);
        // $image = str_replace("data:image/jpg;base64,", "", $data->image);
        $size = getimagesizefromstring($image);
        // $image = imagecreatefromstring(base64_decode($image));
        var_dump($size);

        // $exif = @exif_read_data ( $image, 'EXIF' , true, false);
        $this->response(JsonResponse::getResponse($size, true, "Exif data loaded"));
    }
    // public function index_put()
    // {
    //     $lastupdate = $this->put('lastupdate');
    //     $id = $this->put('id');

    //     $this->response($this->users_model->put_user($id, $lastupdate));
    //     // Create a new book
    // }
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