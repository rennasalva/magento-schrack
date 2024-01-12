<?php

class Schracklive_SchrackCustomer_Model_Loginswitch extends Mage_Core_Model_Abstract {
    private $_read;
    private $_write;
    private $_tokenIntervalSeconds = 60;

    private $_wsdl;
    private $_redirectUrl;
    private $_sessionId;
    private $_countryId;

    public function __construct() {
        parent::__construct();
        $this->_read = Mage::getSingleton('core/resource')->getConnection('commondb_read');
        $this->_write = Mage::getSingleton('core/resource')->getConnection('commondb_write');
    }

    public function findCountryByEmail($email) {
        $row = false;
        $query = "SELECT c.id AS country_id, c.wsdl, c.redirect_url, c.session_id FROM login_token t, country c WHERE t.email = ? AND t.country_id = c.id ORDER BY country_id ASC";
        if ( ($p = strpos($email,'@schrack.')) !== false ) {
            $emailCountry = substr($email,$p + 9);
            if ( $emailCountry == 'com' ) {
                $emailCountry = 'at';
            }
            $firstRow = false;
            $rows = $this->_read->fetchAll($query, array($email));
            foreach ( $rows as $r ) {
                if ( ! $firstRow ) {
                    $firstRow = $r;
                }
                if ( $r['country_id'] == $emailCountry ) {
                    $row = $r;
                    break;
                }
            }
            if ( ! $row ) {
                $row = $firstRow;
            }
        } else {
            $row = $this->_read->fetchRow($query, array($email));
        }
        if ( $row ) {
            $this->_countryId = $row['country_id'];
            $this->_wsdl = $row['wsdl'];
            $this->_redirectUrl = $row['redirect_url'];
            $this->_sessionId = $row['session_id'];
            return true;
        }
        return false;
    }

    public function getCountryId() {
        return $this->_countryId;
    }

    public function getWsdl() {
        return $this->_wsdl;
    }

    public function getRedirectUrl() {
        return $this->_redirectUrl;
    }

    public function getSessionId() {
        return $this->_sessionId;
    }

    public function createToken($email) {
        $countryId = substr(Mage::getStoreConfig('schrack/general/country'),0,2);
        $query = "UPDATE login_token SET token=?, token_created=NOW() WHERE email = ? AND country_id LIKE ?";
        $token = $this->_createToken($email);
        $this->_write->query($query, array($token, $email, $countryId));
        return $token;
    }

    public function validateToken ( $token, $validSeconds = 0 ) {
        list ($email, $dummy) = explode(':', base64_decode($token));
        $query = 'SELECT COUNT(*) FROM login_token WHERE email=? AND token=? AND token_created >= DATE_SUB(NOW(), '
               . ' INTERVAL ' . ($validSeconds > 0 ? $validSeconds : $this->_tokenIntervalSeconds) . ' SECOND)';
        $count = $this->_read->fetchOne($query, array($email, $token));
        if ((int)$count === 1) {
            $this->_removeToken($email);
            return $email;
        } else {
            return false;
        }
    }

    private function _createToken($email) {
        return Mage::helper('schrack/tokens')->createTokenString($email);
    }

    private function _removeToken($email) {
        $query = "UPDATE login_token SET token=NULL, token_created=NULL WHERE email = ?";
        $this->_write->query($query, array($email));
    }

    public function authenticate($eMail, $passWord) {
        $customer = Mage::getModel('customer/customer');
        $customer->loadByEmail($eMail);
        try {
            $ok = $customer->authenticate($eMail, $passWord);
        } catch (Exception $e) {
            Mage::logException($e);
            $ok = false;
        }
        $wwsId           = $ok ? $customer->getSchrackWwsCustomerId() : "";
        $acl             = $ok ? $wwsId."/*" : "";
        $pickupStockNo   = $ok ? $customer->getSchrackPickup() : 0;
        $deliveryStockNo = $ok ? Mage::helper('schrackcataloginventory/stock')->getLocalDeliveryStock()->getStockNumber() : 0;

        $res = array(
            'ok'              => $ok,
            'acl'             => $acl,
            'wwsId'           => $wwsId,
            'pickupStockNo'   => $pickupStockNo,
            'deliveryStockNo' => $deliveryStockNo
        );

        if (isset($res['pickupStockNo'])) unset($res['pickupStockNo']);
        if (isset($res['deliveryStockNo'])) unset($res['deliveryStockNo']);

        return ($res && $res['ok']);
    }
}

?>
