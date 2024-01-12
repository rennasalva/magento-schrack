<?php

class Schracklive_S4s_InvalidRequestDataException extends Schracklive_S4s_Exception {

    public function __construct () {
        parent::__construct("Invalid request data!",-101,null);
    }
}