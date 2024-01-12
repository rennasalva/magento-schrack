<?php

require_once 'shell.php';

class Schracklive_Shell_InviteUser extends Schracklive_Shell {

	var $_readConnection = null;
	var $_writeConnection = null;
	var $_commonDbReadConnection = null;
	var $_commonDbWriteConnection = null;

    var $_storeId = null;
    var $_countryCode = null;

    var $_customerID = null;
    var $_contactNO = null;
    var $_emailAddress = null;
    var $_templateID = null;
    var $_csvFile = null;

    var $_batchDelaySeconds = 0;

    function __construct() {
        parent::__construct();
	    $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_commonDbReadConnection = Mage::getSingleton('core/resource')->getConnection('commondb_read');
        $this->_commonDbWriteConnection = Mage::getSingleton('core/resource')->getConnection('commondb_write');
        $this->_storeId = Mage::app()->getStore('default')->getStoreId();
        $this->_countryCode = strtolower(Mage::getStoreConfig('schrack/general/country'));

        if ( $v = $this->getArg('customerId') ) {
            $this->_customerID = $v;
        }
        if ( $v = $this->getArg('contactNo') ) {
            $this->_contactNO = $v;
        }
        if ( $v = $this->getArg('email') ) {
            $this->_emailAddress = $v;
        }
        if ( $v = $this->getArg('mailTemplateId') ) {
            $this->_templateID = $v;
        }
        if ( $v = $this->getArg('csvFile') ) {
            $this->_csvFile = $v;
        }
        if ( $v = $this->getArg('delaySeconds') ) {
            $this->_batchDelaySeconds = $v;
        }
        if ( $this->getArg('help') || (! $this->_csvFile && (! $this->_customerID || ! $this->_contactNO) ) || ! $this->_templateID ) {
            die($this->usageHelp());
        }
    }

	public function run() {
        if ( $this->_csvFile ) {
            $this->doCsv();
        } else {
            $this->doCustomer($this->_customerID, $this->_contactNO, $this->_emailAddress, $this->_templateID);
        }
		echo 'done.' . PHP_EOL;
	}

	private function doCsv () {
        $fp = false;
        try {
            $fp = fopen($this->_csvFile, "rt");
            if ( !$fp ) {
                throw new Exception("Could not open file '{$this->_csvFile}'!");
            }
            $delimiter = $ndxEmail = $ndxContactNO = $ndxCustomerID = false;
            while ( $line = fgets($fp) ) {
                $line = str_replace(array("\r\n", "\n"),"", $line);
                if ( !$delimiter ) {
                    if ( strrpos($line, ';') !== false ) {
                        $delimiter = ';';
                    } else if ( strrpos($line, ',') !== false ) {
                        $delimiter = ',';
                    } else {
                        throw new Exception("Not a CSV file!");
                    }

                    $fields = explode($delimiter, $line);
                    foreach ($fields as $index => $value) {
                        if ($value == 's4y_wws_cust_id') {
                            $ndxCustomerID = $index;
                        }
                        if ($value == 's4y_wws_contact_num') {
                            $ndxContactNO = $index;
                        }
                        if ($value == 'email_address') {
                            $ndxEmail = $index;
                        }
                    }
                    if ( $ndxCustomerID === false || $ndxContactNO === false || $ndxEmail === false ) {
                        throw new Exception("Need a header line with colum titles 's4y_wws_cust_id', 's4y_wws_contact_num' and 'email_address'!");
                    }
                } else {
                    $fields = explode($delimiter,$line);
                    $customerIdValue = $fields[$ndxCustomerID];
                    $contactNumberValue = $fields[$ndxContactNO];
                    $emailValue = str_replace(' ', '', $fields[$ndxEmail]);
                    $this->doCustomer($customerIdValue, $contactNumberValue, $emailValue, $this->_templateID);
                    if ( $this->_batchDelaySeconds ) {
                        sleep($this->_batchDelaySeconds);
                    }
                }
            }
            fclose($fp);
            $fp = false;
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            echo "ERROR: " . $ex->getMessage() . PHP_EOL;
            if ( $fp ) {
                fclose($fp);
            }
        }
    }

    private function doCustomer ( $customerID, $contactNO, $email, $templateID ) {
        try {
            $customer = Mage::getModel('customer/customer')->loadByWwsContactNumber($customerID, $contactNO);
            if ( ! $customer->getId() ) {
                if ( ! $email )  $email = '(unknown email address)';
                throw new Exception("User $customerID/$contactNO - $email - not found!");
            }
            $kind = "inactive";
            if ( $customer->isInactiveContact() ) {
                $this->handleInactive($customerID, $contactNO, $customer, $email, $templateID);
            } else if ( $customer->isContact() && $customer->getConfirmation() && $customer->getConfirmation() > '' ) {
                $this->handleUnconfirmed($customerID, $contactNO, $customer, $templateID);
                $kind = "unconfirmed";
            } else {
                if ( ! $email )  $email = $customer->getEmail();
                throw new Exception("Customer $customerID/$contactNO - $email - is neither inactive nor unconfirmed contact!");
            }
            echo "SUCCESS ($kind): $customerID/$contactNO - " . $customer->getEmail() . PHP_EOL;
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            echo "ERROR: " . $ex->getMessage() . PHP_EOL;
        }
    }

    private function handleUnconfirmed ( $customerID, $contactNO, $customer, $templateID ) {
        $customer->changePassword($customer->generatePassword());
        $this->sendConfirmationEmail($customerID,$contactNO,$customer,$templateID);
    }

    private function handleInactive ( $customerID, $contactNO, $customer, $email, $templateID ) {
        if ( ! $email || $email <= '' ) {
            throw new Exception("Customer $customerID/$contactNO: email necessary for inactive user!");
        }
        $sql = "SELECT country_id FROM login_token WHERE email = ?;";
        $countryId = $this->_commonDbReadConnection->fetchOne($sql,$email);
        if ( $countryId ) {
            throw new Exception("Customer $customerID/$contactNO: email $email already exists for country '$countryId' !");
        }
        $customer->setUpdatedBy('InviteUser cli');
        $customer->setGroupId(Mage::getStoreConfig('schrack/shop/contact_group'));
        $customer->setEmail($email);
        $customer->setPassword($customer->generatePassword());
        $customer->setConfirmation($customer->getRandomConfirmationKey());
        $customer->setSchrackAclRoleId(3); // reallly? Always admin?
        $customer->save();

        $sql = "REPLACE INTO login_token (email, country_id, entity_id) VALUES(?,?,?);";
        $this->_commonDbWriteConnection->query($sql,array($email,strtolower($this->_countryCode),$customer->getId()));
        
        $this->sendConfirmationEmail($customerID,$contactNO,$customer,$templateID);
    }

    private function sendConfirmationEmail ( $customerID, $contactNO, $customer, $templateID ) {
        $this->_storeId = $customer->getSendemailStoreId();
        $vars = array(
            'customer'          => $customer,
            'confirmation_url'  => $this->_getFrontendUrl('customer/account/confirmAndSetPassword/',array('id' => $customer->getId(), 'key' => $customer->getConfirmation()))
        ); //for replacing the variables in email with data
        $email = $customer->getEmail();
        $name = $customer->getName();
        try {
            /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
            $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
            $singleMailApi->setStoreID($this->_storeId);
            $singleMailApi->setMagentoTransactionalTemplateID($templateID);
            $singleMailApi->setMagentoTransactionalTemplateVariables($vars);
            $singleMailApi->addToEmailAddress($email, $name);
            $singleMailApi->setFromEmail('general');
            $singleMailApi->createAndSendMail();
        } catch ( Exception $ex ) {
            echo $ex->__toString() . PHP_EOL;
            throw new Exception("Customer $customerID/$contactNO: Sending mail to $email failed!",0,$ex);
        }
    }

    public function usageHelp() {
        return <<<USAGE

Usage:  

    php -f InviteUser.php --customerId <customer id> --contactNo <contact number> [--email <email address>] --mailTemplateId <transactional email template id>
     
or

    php -f InviteUser.php --csvFile <csv file> --mailTemplateId <transactional email template id> [--delaySeconds <seconds>]



USAGE;
    }
}

$shell = new Schracklive_Shell_InviteUser();
$shell->run();
