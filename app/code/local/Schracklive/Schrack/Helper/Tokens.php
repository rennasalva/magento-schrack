<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Tokens
 *
 * @author d.laslov
 */
class Schracklive_Schrack_Helper_Tokens extends Mage_Core_Helper_Abstract {

    private $_read;
    private $_write;
    
    public function __construct() {
        $this->_read = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->_write = Mage::getSingleton('core/resource')->getConnection('core_write');
    }

    public function createTokenString ( $prefix = null ) {
        if ( ! $prefix ) {
            $prefix = "sl"; // just some random string, really
        }
        return base64_encode($prefix . ':' . sha1(uniqid(mt_rand(), true) ));
    }
    
    public function createToken ( $customer, $prefix = null ) {
        $query = "INSERT INTO tokens SET token=?, created_at=NOW(), user_id=?";
        $token = $this->createTokenString($prefix);
        $this->_write->query($query, array($token, $customer->getId()));
        return $token;
    }
    
    public function checkTokenAndReturnUserId ( $token, $maxAgeSeconds = 60 ) {
        $query = 'SELECT user_id FROM tokens WHERE token=? AND created_at >= DATE_SUB(NOW(), INTERVAL ' . $maxAgeSeconds . ' SECOND)';
        $userId = $this->_read->fetchOne($query, array($token));
        return $userId;
    }
    
    public function checkToken ( $token, $maxAgeSeconds = 60 ) {
        $userId = $this->checkTokenAndReturnUserId($token,$maxAgeSeconds);
        return $userId !== null;
    }
}

?>
