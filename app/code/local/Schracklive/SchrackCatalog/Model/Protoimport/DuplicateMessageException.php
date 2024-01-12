<?php

class Schracklive_SchrackCatalog_Model_Protoimport_DuplicateMessageException extends Exception {
    public function __construct ( $message = "", $code = 0, $additionalFields = array(), Throwable $previous = null ) {
        parent::__construct($message, $code, $previous);
    }
}
