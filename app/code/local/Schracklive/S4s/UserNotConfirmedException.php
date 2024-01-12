<?php

class Schracklive_S4s_UserNotConfirmedException extends Schracklive_S4s_Exception {

    public function __construct () {
        parent::__construct("Email address is not confirmed!",-112,null);
    }
}