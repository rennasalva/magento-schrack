<?php

/**
 * Magento-internal replacement for the app-specific switch.php functionality
 * 
 */
class Schracklive_SchrackCustomer_Helper_Loginswitch extends Mage_Customer_Helper_Data {
    public function getRedirectDataByLoginData($email, $password) {
        $model = Mage::getModel('schrackcustomer/loginswitch');
        $found = $model->findCountryByEmail($email);
        if ($found) {
            $ok = $model->authenticate($email, $password);
            if ($ok) {                
                $redirectUrl = $model->getRedirectUrl();
                $token = $model->createToken($email);
                return array($redirectUrl, $token);
            }
        }
        return array(false, false);
    }    
    
    public function loginByToken($token) {
        $loginSwitch = Mage::getModel('schrackcustomer/loginswitch');
        $email = $loginSwitch->validateToken($token);
        if ($email !== false) {
            $customer = Mage::getModel('schrackcustomer/customer')
                    ->loadByEmail($email);
            $session = Mage::getSingleton('customer/session')->setCustomer($customer)->setCustomerAsLoggedIn($customer);
            return true;
        } else
            return false;
    }
    
    public function createToken($email) {
        $model = Mage::getModel('schrackcustomer/loginswitch');
        $found = $model->findCountryByEmail($email);
        if ($found) {
            return $model->createToken($email);
        } else {
            return null;
        }
    }

    public function validateToken ( $token, $validSeconds = 0 ) {
        $loginSwitch = Mage::getModel('schrackcustomer/loginswitch');
        return $loginSwitch->validateToken($token,$validSeconds);
    }
}

?>
