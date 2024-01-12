<?php

/**
 * Description of Schracklive_Datanorm_Model_Main
 *
 * @author d.laslov
 */
class Schracklive_Datanorm_Model_Main {

    const INCLUDE_PICTURE_URLS                = 'IncludePictureUrls';
    const ENCODE_UTF8                         = 'EncodeUTF8';
    const GROUP_ARTICLES_BY_SCHRACK_STRUCTURE = 'GroupArticlesBySupplierStructure';
    const USE_EDS_ARTICLE_NUMBERS             = 'UseEdsArticleID';
    const WITHOUT_LONG_TEXT                   = 'WithoutLongText';
    const WITHOUT_RESELL_PRICES               = 'WithoutResellPrices';
    const DELIMITER_4_CSV                     = 'Delimiter';

    const EXPORT_FUNCTION                     = 'exportType';
    const EXPORT_FUNCTION_DATANORM            = 'GetCatalogAsDatanormV31';
    const EXPORT_FUNCTION_CSV                 = 'GetCatalogAsCsvV32';
    const EXPORT_FUNCTION_XML                 = 'GetCatalogAsXMLV32';

    private $_argsDefaults = array(
        self::EXPORT_FUNCTION_DATANORM => array(
            self::INCLUDE_PICTURE_URLS                => false,
            self::ENCODE_UTF8                         => false,
            self::GROUP_ARTICLES_BY_SCHRACK_STRUCTURE => false,
            self::USE_EDS_ARTICLE_NUMBERS             => false,
            self::WITHOUT_LONG_TEXT                   => false,
            self::WITHOUT_RESELL_PRICES               => false,
        ),
        self::EXPORT_FUNCTION_CSV => array(
            self::DELIMITER_4_CSV                     => ',',
        ),
        self::EXPORT_FUNCTION_XML => array(
        ),
   );

   private $_client = null;

   public function mayGetCustomerPrices () {
       $res = false;
       $session = Mage::getSingleton('customer/session');
        if ( $session->isLoggedIn() ) {
            $customer = $session->getCustomer();
            $res = $customer->isAllowed('price', 'view');
        }
        return $res;
    }
    
    public function isLoggedIn () {
        $session = Mage::getSingleton('customer/session');
        return $session->isLoggedIn();
    }
    
    /**
     * @return Zend_Soap_Client
     */
   private function _getSoapClient() {
       if ( !$this->_client ) {
           $options = array(
               'schrack_system' => 'datanorm',
           );
           if ( Mage::getStoreConfig('schrackdev/datanorm/log') ) {
               $options['schrack_log_transfer'] = true;
           }
           $wsdl = Mage::getStoreConfig('schrack/datanorm/wsdl');
           $this->_client = Mage::helper('schrack/soap')->createClient($wsdl,$options);
           $this->_client->setConnectionTimeout(60);
       }
       return $this->_client;
   }

   private function _getWwsCustomerID () {
       $wwsId = null;
       if ( $this->mayGetCustomerPrices() ) {
           $session = Mage::getSingleton('customer/session');
           $customer = $session->getCustomer();
           $wwsId = $customer->getSchrackWwsCustomerId();
       }
       else {
           $wwsId = Mage::getStoreConfig('schrack/datanorm/default_customer');
       }

       $session = Mage::getSingleton('customer/session');
       // Special route for projectant:
       if ($session->isLoggedIn()) {
           $sessionCustomerId = $session->getCustomer()->getId();
           $aclRoleId = Mage::getModel('customer/customer')->load($sessionCustomerId)->getSchrackAclRoleId();
           if (Mage::helper('schrack/acl')->isProjectantRoleId($aclRoleId)) {
               $wwsId = Mage::getStoreConfig('schrack/datanorm/default_customer');
           }
       }

       return $wwsId;
   }

   public function callInitDatanorm () {
       set_time_limit(0);
       $soapClient = $this->_getSoapClient();
       $res = $soapClient->initDatanorm($this->_getWwsCustomerID());
       return $res->return->returnCode;
   }

   public function callGet ( $furtherArgs = array() ) {
       $soapClient = $this->_getSoapClient();
       $wwsCustomerId = isset($furtherArgs['wwsCustomerId']) ? $furtherArgs['wwsCustomerId'] : null;

       $session = Mage::getSingleton('customer/session');
       // Special route for projectant:
       if ($session->isLoggedIn()) {
           $sessionCustomerId = $session->getCustomer()->getId();
           $aclRoleId = Mage::getModel('customer/customer')->load($sessionCustomerId)->getSchrackAclRoleId();
           if (Mage::helper('schrack/acl')->isProjectantRoleId($aclRoleId)) {
               $wwsCustomerId = Mage::getStoreConfig('schrack/datanorm/default_customer');
           }
       }

       $shopCountry = strtoupper(Mage::getStoreConfig('general/country/default'));
       if ( $shopCountry == 'DK' ) {
           $shopCountry = 'COM';
       }

       if ( ! isset($wwsCustomerId) ) {
           $wwsCustomerId = $this->_getWwsCustomerID();
       }
       if ( ! isset($wwsCustomerId) ) {
           $wwsCustomerId = Mage::getStoreConfig('schrack/datanorm/default_customer');
       }
       $args = array(
                'Provider' => array(
                                'Code' => 'Webshop'
                           ),
                'Authentication' => array(
                                        'User' => $wwsCustomerId . '|' . $shopCountry,
                                        'Password' => 'Fg1ZTx56EC3@W1%A'
                                    ),
                'ResultType' => 'Download'
       );
       $args = array_merge($args,$furtherArgs);
       $function = $args[self::EXPORT_FUNCTION];
       unset($args[self::EXPORT_FUNCTION]);
       foreach ( $this->_argsDefaults[$function] as $key => $dfltVal ) {
           if ( ! isset($args[$key]) ) {
               $args[$key] = $dfltVal;
           }
       }
       $res = $soapClient->$function($args);
       $delaySecs = Mage::getStoreConfig('schrack/datanorm/response_delay_secs');
       if ( is_numeric($delaySecs) && $delaySecs > 0 )
           sleep($delaySecs);
       return $res->Return->DownloadURL;
   }
}

?>
