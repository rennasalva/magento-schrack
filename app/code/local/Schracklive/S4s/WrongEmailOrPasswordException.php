<?php

class Schracklive_S4s_WrongEmailOrPasswordException extends Schracklive_S4s_Exception {

    public function __construct () {
        parent::__construct("Wrong email or password!",-111,null);
    }
}