<?php

class Schracklive_S4s_Exception extends Exception {

    private $additionalFields = array();

    public function __construct ( $message = "", $code = 0, $additionalFields = array(), Throwable $previous = null ) {
        parent::__construct($message,$code,$previous);
        if ( is_array($additionalFields) ) {
            $this->additionalFields = $additionalFields;
        }
    }

    public function getAdditionalFields () {
        return $this->additionalFields;
    }
}