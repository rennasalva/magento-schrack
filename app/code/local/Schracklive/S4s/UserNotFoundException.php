<?php

class Schracklive_S4s_UserNotFoundException extends Schracklive_S4s_Exception {

    public function __construct ( $eMailOrNickname ) {
        parent::__construct("Email address or nickname '$eMailOrNickname' not found!",-117,null);
    }
}