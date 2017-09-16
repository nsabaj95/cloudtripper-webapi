<?php
require 'data/DbConnection.php';
require 'views/JsonView.php';
require 'interfaces/iRepository.php';
require 'models/logsRepository.php';
require 'models/usersRepository.php';
require 'models/tripsRepository.php';
require 'models/subscriptionsRepository.php';
require 'helpers/httpHelper.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PUT");

$view = new JsonView(false);
$existing_resources = array('logs', 'users', 'trips', 'subscriptions');

//Logs, etc.
$resource = HttpHelper::getResource();

//GET, POST, PUT, DELETE, etc.
$method = HttpHelper::getMethod();
//Comprobar si el recurso está disponible
if (!in_array($resource, $existing_resources)) {
    // Respuesta error
    http_response_code(400);
}

$parameters = HttpHelper::getParametersArray(true); 
$repository = getRepository($resource);

switch ($method) {
    case 'get':
        // Procesar método get
        $result = $repository::get($parameters);
        break;
    case 'post':
        // Procesar método post
        $result = $repository::post($parameters);
        break;
    case 'put':
        $result = $repository::put($parameters);
        break;
    case 'delete':
        $result = $repository::delete($parameters);
        // Procesar método delete
        break;
    default:
        // Método no aceptado
}

$view->print($result);

set_exception_handler(function ($exception) use ($view) {
    $body = array(
        "error" => true,
        "state" => $exception->state,
        "message" => $exception->getMessage()
    );
    if ($exception->getCode()) {
        $view->state = $exception->getCode();
    } else {
        $view->state = 500;
    }

    $view->print($body);
});

function getRepository($resource){
    $repo;
    switch($resource){
        case "trips":
            $repo=new tripsRepository();
            break; 
        case "logs":
            $repo=new logsRepository();
            break;
        case "users":
            $repo=new UsersRepository();
            break;
        case "subscriptions":
            $repo=new subscriptionsRepository();
            break;
    }
    return $repo;
}
?>