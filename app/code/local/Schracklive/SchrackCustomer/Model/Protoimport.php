<?php

use DrSlump\Protobuf\Message;
use com\schrack\queue\protobuf\ContactTransfer\ContactMessage;

class Schracklive_SchrackCustomer_Model_Protoimport extends Schracklive_Account_Model_Protoimport_Base {
    const EXTRANET_STATUS_INVITED           = "1";
    const EXTRANET_STATUS_ACTIVE            = "2";
    const EXTRANET_STATUS_INACTIVE          = "3";
    const EXTRANET_STATUS_DELETED_IN_SHOP   = "4";

    static $allStates = array(  self::EXTRANET_STATUS_INVITED, self::EXTRANET_STATUS_ACTIVE,
                                self::EXTRANET_STATUS_INACTIVE, self::EXTRANET_STATUS_DELETED_IN_SHOP);

    static $nameMap = array(
        'wws_custumer_id'          => 'schrack_wws_customer_id',
        'contact_id'               => 'schrack_wws_contact_number',
        'main'                     => 'schrack_main_contact',
        'salutatory'               => 'schrack_salutatory',
        'gender'                   => 'gender',
        'first_name'               => 'firstname',
        'last_name'                => 'lastname',
        'phone1'                   => 'schrack_telephone',
        'mobile'                   => 'schrack_mobile_phone',
        'fax'                      => 'schrack_fax',
        'email'                    => 'email',
        'department'               => 'schrack_department',
        'prefix'                   => 'prefix',
        'crm_role_id'              => 'schrack_crm_role_id',
        'address_id'               => 'schrack_address_id',
        'advisor_principal_name'   => 'schrack_advisor_principal_name',
        'advisors_principal_names' => 'schrack_advisors_principal_names',
        'newsletter'               => 'schrack_newsletter',
        'comments'                 => 'schrack_comments',
        'interests'                => 'schrack_interests',
        'schrack_emails'           => 'schrack_emails',
        'acl_role'                 => 'schrack_acl_role_id',
        's4y_id'                   => 'schrack_s4y_id',
        's4s_nickname'             => 'schrack_s4s_nickname',
        's4s_school'               => 'schrack_s4s_school',
        's4s_id'                   => 'schrack_s4s_id'
/*
		optional int32  xrow       = 1;
		optional bool   deleted    = 2;
		optional string country    = 3;
		optional string salutation = 8;
		optional string name       = 11;
		optional string phone2     = 16;
		optional string user_id    = 21;
		optional bool   shop_admin = 23;
 */
    );
    static $nameOnly4checkMap = array(
        'state'                    => 'confirmation',
        'webshop'                  => 'schrack_active'
    );
    var $allCheckNameMap = null;

    function __construct() {
        parent::__construct();
        $this->allCheckNameMap = array_merge(self::$nameMap,self::$nameOnly4checkMap);
    }

    protected function getMappingNamesProtobufToMagento () {
        return self::$nameMap;
    }

    protected function getMessageKeyFromProtobuf ( Message $message ) {
        $contact = $message->getContact();
        $wwsCustomerId = $contact->getWwsCustumerId();
        $wwsContactNumber = $contact->getContactId();
        return $this->getMessageKey($wwsCustomerId,$wwsContactNumber);
    }

    private function getMessageKey ( $wwsCustomerId, $wwsContactNumber ) {
        return sprintf("customer-%s-%d",$wwsCustomerId,$wwsContactNumber);
    }

    protected function getType () {
        return Schracklive_Account_Helper_Protobuf::TYPE_CONTACT;
    }

    protected function checkInstance ( Message $message ) {
        return $message instanceof ContactMessage;
    }

    protected function insertOrUpdateOrDeleteImpl ( Message $message ) {
        /** @var com\schrack\queue\protobuf\ContactTransfer\ContactMessage\Contact $protoContact */
        $protoContact = $message->getContact();
        $wwsCustomerId = $protoContact->getWwsCustumerId();
        $wwsContactNumber = $protoContact->getContactId();
        if ( $protoContact->getDeleted() ) {
            Mage::helper('schrackcustomer/api')->deleteContact($wwsCustomerId,$wwsContactNumber);
        } else {
            $tmpMagentoContact = Mage::getModel('customer/customer');
            $this->mapProtobufToMagento($protoContact, $tmpMagentoContact);
            $array = $tmpMagentoContact->getData();
            $state = $protoContact->getState();
            if ( ! $state || ! in_array($state,self::$allStates) ) {
                throw new Mage_Api_Exception('illegal_state', $state ? "$state" : '(no value given)');
            }
            if ( $state == self::EXTRANET_STATUS_DELETED_IN_SHOP ) {
                self::log("Contact recieved with state $state - ignoring.");
                return null;
            }
            if ( ( $protoContact->getEmail() == null || strlen($protoContact->getEmail()) < 1 ) && $state != self::EXTRANET_STATUS_INACTIVE ) {
                throw new Mage_Api_Exception('data_invalid',"Customer email is required for state $state.");
            }
            if ( strpos($protoContact->getEmail(),'@schrack.com') !== false ) {
                self::log("Contact recieved with Schrack email address {$protoContact->getEmail()} - ignoring.");
                return null;
            }
            $array['schrack_active']                =    $state == self::EXTRANET_STATUS_INACTIVE
                                                      || $state == self::EXTRANET_STATUS_DELETED_IN_SHOP ? false : true;
            $array['schrack_confirmed']             = $state == self::EXTRANET_STATUS_INVITED  ? false : true;
            $array['schrack_mailinglist_types_csv'] = implode(",",$protoContact->getMailingListTypesList());

            // we do not take empty/unset values for Schrack 4 Students
            if ( (isset($array['schrack_s4s_nickname']) && $array['schrack_s4s_nickname'] === null || $array['schrack_s4s_nickname'] <= ' ') )
                unset($array['schrack_s4s_nickname']);
            if ( (isset($array['schrack_s4s_school']) && $array['schrack_s4s_school'] === null || $array['schrack_s4s_school'] <= ' ') )
                unset($array['schrack_s4s_school']);
            if ( (isset($array['schrack_s4s_id']) && $array['schrack_s4s_id'] === null || $array['schrack_s4s_id'] <= ' ') )
                unset($array['schrack_s4s_id']);

            return Mage::helper('schrackcustomer/api')->replaceContact($wwsCustomerId, $wwsContactNumber, $array);
        }
    }

    public function createSystemContactProtobufMessage ( Schracklive_SchrackCustomer_Model_Customer $customer, $delete = false, $force = false ) {
        $msgKey = $this->getMessageKey($customer->getSchrackWwsCustomerId(),$customer->getSchrackWwsContactNumber());
        if ( self::isInInsertUpdate($msgKey) ) {
            return false;
        }
        if ( ! $delete && ! Mage::helper('schrackcore/model')->isModified($customer,$this->allCheckNameMap) && ! $force ) {
            return false;
        }
        $protoContact = new ContactMessage\Contact();

        $this->mapMagentoToProtobuf($customer,$protoContact);
        if ( $protoContact->getContactId() == Schracklive_SchrackCustomer_Model_Customer::NO_CONTACT_NUMBER ) {
            $protoContact->clearContactId();
        }
        $protoContact->setCountry(strtoupper(Mage::getStoreConfig('schrack/general/country')));
        $protoContact->setShopAdmin(Mage::helper('schrack/acl')->isAdminRoleId($customer->getSchrackAclRoleId()) ? 1 : 0);
        $email = $customer->getEmail();
        if ( $customer->isInactiveContact() || $customer->isDeletedContact() ) {
            $vals = $customer->getSchrackEmails();
            if ( isset($vals) ) {
                $valAr = explode(',', $vals);
                if ( isset($valAr) && count($valAr) > 0 ) {
                    $email = $valAr[0];
                }
            }
            $protoContact->setState(self::EXTRANET_STATUS_INACTIVE);
        } else if ( $customer->getConfirmation() > "" ) {
            $protoContact->setState(self::EXTRANET_STATUS_INVITED);
        } else {
            $protoContact->setState(self::EXTRANET_STATUS_ACTIVE);
        }
        if ( $email == null || strlen($email) < 1 ) {
            throw new Exception('Wrong email in protobuf msg (oroginal magento contact has: ' . $customer->getEmail() . ')');
        }
        $protoContact->setEmail($email);
        $protoContact->setDeleted($delete);

        $sessionCustomerS4YId  = Mage::getSingleton('customer/session')->getCustomer()->getSchrackS4yId();
        $protoContact->setModifiedBy($sessionCustomerS4YId);

        $strAction = 'none';

        if ($delete) {
            if ($customer->getSchrackS4yId() == $sessionCustomerS4YId) {
                $strAction = 'admin deleted himself';
            } else {
                $strAction = 'admin deleted contact';
            }
        } else {
            $strModificationAction = Mage::getSingleton('core/session')->getUserModificationAction();

            if ($strModificationAction) {
                if ($strModificationAction == 'contact deactivated') {
                    if ($customer->getSchrackS4yId() == $sessionCustomerS4YId) {
                        $strAction = 'admin deactivated himself';
                    } else {
                        $strAction = 'admin deactivated contact';
                    }
                }
                if ($strModificationAction == 'contact activated') {
                    if ($customer->getSchrackS4yId() == $sessionCustomerS4YId) {
                        $strAction = 'admin activated himself';
                    } else {
                        $strAction = 'admin activated contact';
                    }
                }
                if ($strModificationAction == 'contact changed') {
                    if ($customer->getSchrackS4yId() == $sessionCustomerS4YId) {
                        $strAction = 'admin changed himself';
                    } else {
                        $strAction = 'admin changed contact';
                    }
                }
                if ($strModificationAction == 'contact created') {
                    $strAction = 'admin created contact';
                }
                if ($strModificationAction == 'contact changed himself') {
                    $strAction = 'contact changed himself';
                }
            }
        }
        $protoContact->setModificationAction($strAction);
        Mage::getSingleton('core/session')->setUserModificationAction('');

        $msg = new ContactMessage();
        $msg->setContact($protoContact);

        $codec = new \DrSlump\Protobuf\Codec\Binary();
        $data = $msg->serialize($codec);
        return $data;
    }

}
