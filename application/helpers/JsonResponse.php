<?php


/**
 * Clase para imprimir en la salida respuestas con formato JSON
 */
class JsonResponse
{
    /**
     * Imprime el cuerpo de la respuesta y setea el código de respuesta
     * @param mixed $cuerpo de la respuesta a enviar
     */
    public static function getResponse($data, $success, $message)
    {
        // header('Content-Type: application/json; charset=utf8');
        $body = 
        [
            "success" => $success,
            "data" => $data,
            "message" => $message,
        ];
        return $body;
    }
}