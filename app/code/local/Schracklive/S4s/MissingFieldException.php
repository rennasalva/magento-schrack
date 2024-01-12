<?php

class Schracklive_S4s_MissingFieldException extends Schracklive_S4s_Exception {

    public function __construct ( $fieldName ) {
        parent::__construct("Missing mandatory field '$fieldName' in request!",-102,null);
    }
}