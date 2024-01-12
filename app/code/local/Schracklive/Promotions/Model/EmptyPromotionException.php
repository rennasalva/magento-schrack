<?php

class Schracklive_Promotions_Model_EmptyPromotionException extends Exception {
    public function __construct ( $message = "", $code = 0, $additionalFields = array(), Throwable $previous = null ) {
        parent::__construct($message, $code, $previous);
    }
}

