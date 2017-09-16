<?php

abstract class ApiView{
    
    // Código de error
    public $state;
    public $error;

    public abstract function print($body);
}

?>