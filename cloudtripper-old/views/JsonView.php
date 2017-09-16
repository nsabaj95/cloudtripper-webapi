<?php

require_once "ApiView.php";

/**
 * Clase para imprimir en la salida respuestas con formato JSON
 */
class JsonView extends ApiView
{
    public function __construct($error = false, $state = 200)
    {
        $this->state = $state;
        $this->error = $error;
    }

    /**
     * Imprime el cuerpo de la respuesta y setea el código de respuesta
     * @param mixed $cuerpo de la respuesta a enviar
     */
    public function print($data)
    {
        if ($this->state) {
            http_response_code($this->state);
        }
        header('Content-Type: application/json; charset=utf8');
        $body = 
        [
            "error" => $this->error,
            "data" => $data,
            "state" => "SUCCESS",
            "message" => "",
        ];
        echo json_encode($body, JSON_PRETTY_PRINT);
        exit;
    }
}