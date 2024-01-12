<?php

class Schracklive_S4s_UserNotConnectedException extends Schracklive_S4s_Exception {

    private $customer = null;
    private $connectionToken = null;

    public function __construct ( $connectionToken, $customer ) {
        parent::__construct("Email already registered but not connected to S4S - please use updateuserprofile request!",115,array('connectionToken' => $connectionToken));
        $this->customer = $customer;
        $this->connectionToken = $connectionToken;
    }

    public function getCustomer () {
        return $this->customer;
    }

    public function getConnectionToken () {
        return $this->connectionToken;
    }
}