<?php

class Schracklive_SchrackSingleMail_Model_SingleMailApi {
    const SENDING_SYSTEM                        = Schracklive_Account_Helper_Protobuf::SENDER_ID;
    const STOMP_URL_CFG_PATH                    = 'schrack/singlemail/stomp_url';
    const STOMP_OUT_QUEUE_CFG_PATH              = 'schrack/singlemail/message_queue_outbound';
    const SENDER_NAME_CFG_PATH_PATTERN          = 'trans_email/ident_%s/name';
    const SENDER_EMAIL_CFG_PATH_PATTERN         = 'trans_email/ident_%s/email';

    const DEFAULT_EMAIL_BODY_VARIABLE_NAME      = 'mailbody';
    const DEFAULT_EMAIL_SUBJECT_VARIABLE_NAME   = 'subject';

    private $_writeConnection;
    private $_readConnection;
    private $_mqHelper;
    private $_stompHelper;

    private $_magentoTransactionalTemplateID;                       // mandertory
    private $_magentoTransactionalTemplateVariables = array();

    // need either emails or Crm IDs, finally Crm IDs will be sent if available.
    private $_toEmailAddresses  = array();                          // mandertory: at least one email or crm id
    private $_toEmailCrmIDs     = array();
    private $_ccEmailAddresses  = array();
    private $_ccEmailCrmIDs     = array();
    private $_emailToNameMap    = array();                          // only 4 traditional sending

    private $_fromEmail         = 'general';
    private $_fromName;
    private $_replyToEmail;

    private $_attachementName2binaryMap = array();

    private $_emailType;
    private $_eyePinNewsletterId;
    private $_eyePinBodyVariable    = self::DEFAULT_EMAIL_BODY_VARIABLE_NAME;
    private $_eyePinSubjectVariable = self::DEFAULT_EMAIL_SUBJECT_VARIABLE_NAME;
    private $_advisorEmail;

    /** @var $_mailTemplate Schracklive_SchrackCore_Model_Email_Template */
    private $_mailTemplate;
    private $_storeId;
    private $_subjectText;
    private $_bodyText;

    private $_customerEmailType = false;                            // is just 4 logging


    function __construct() {
        self::initProtobuf();
        $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->_mqHelper = Mage::helper("schrack/mq");
        $this->_stompHelper = Mage::helper("schrack/stomp");
    }

    public static function initProtobuf () {
        Mage::helper("schrack/protobuf")->initProtobuf();
        $libDir = Mage::getBaseDir('lib');
        require_once $libDir . '/com/schrack/queue/protobuf/SendSingleMail.php';
    }


    public function setMagentoTransactionalTemplateID ( $magentoTransactionalTemplateID ) {
        $this->_magentoTransactionalTemplateID = $magentoTransactionalTemplateID;
    }

    public function setMagentoTransactionalTemplateIDfromConfigPath ( $configPath ) {
        $this->_magentoTransactionalTemplateID = Mage::getStoreConfig($configPath);
        if ( ! $this->_magentoTransactionalTemplateID ) {
            throw new Schracklive_SchrackSingleMail_Model_SingleMailApiException("No value got for config path  '$configPath'!");
        }
    }

    public function setMagentoTransactionalTemplateVariables ( array $variableKeyValueMap ) {
        $this->_magentoTransactionalTemplateVariables = $variableKeyValueMap;
    }

    public function setToEmailAddresses ( array $toEmailAddresses ) {
        $this->_toEmailAddresses = $toEmailAddresses;
    }

    public function addToEmailAddress ( $toEmailAddress, $name = null ) {
        $this->_toEmailAddresses[] = $toEmailAddress;
        if ( $name && $name > '' ) $this->_emailToNameMap[$toEmailAddress] = $name;
    }

    public function setToEmailCrmIDs ( array $toEmailCrmIDs ) {
        $this->_toEmailCrmIDs = $toEmailCrmIDs;
    }

    public function addToEmailCrmID ( $toEmailCrmID ) {
        $this->_toEmailCrmIDs[] = $toEmailCrmID;
    }

    public function setCcEmailAddresses ( array $ccEmailAddresses ) {
        $this->_ccEmailAddresses = $ccEmailAddresses;
    }

    public function addCcEmailAddress ( $ccEmailAddress, $name = null ) {
        $this->_ccEmailAddresses[] = $ccEmailAddress;
        if ( $name && $name > '' ) $this->_emailToNameMap[$ccEmailAddress] = $name;
    }

    public function setCcEmailCrmIDs ( array $ccEmailCrmIDs ) {
        $this->_ccEmailCrmIDs = $ccEmailCrmIDs;
    }

    public function addCcEmailCrmID ( array $ccEmailCrmID ) {
        $this->_ccEmailCrmIDs[] = $ccEmailCrmID;
    }

    public function getRecipientCount ( $includeCCs = false ) {
        $res = count($this->_toEmailAddresses) + count($this->_toEmailCrmIDs);
        if ( $includeCCs ) {
            $res += (count($this->_ccEmailAddresses) + count($this->_ccEmailCrmIDs));
        }
        return $res;
    }

    public function setFromEmail ( $fromEmail ) {
        $this->_fromEmail = $fromEmail;
    }

    public function setFromName ( $fromName ) {
        $this->_fromName = $fromName;
    }

    public function setReplyToEmail ( $replyToEmail ) {
        $this->_replyToEmail = $replyToEmail;
    }

    public function addAttachement ( $name, $binaryData ) {
        $this->_attachementName2binaryMap[$name] = $binaryData;
    }

    public function setEmailType ( $emailType ) {
        $this->_emailType = $emailType;
    }

    public function setEyePinNewsletterId ( $eyePinNewsletterId ) {
        $this->_eyePinNewsletterId = $eyePinNewsletterId;
    }

    public function setEyePinBodyVariable ( $eyePinBodyVariable ) {
        $this->_eyePinBodyVariable = $eyePinBodyVariable;
    }

    public function setEyePinSubjectVariable ( $eyePinSubjectVariable ) {
        $this->_eyePinSubjectVariable = $eyePinSubjectVariable;
    }

    public function setAdvisorEmail ( $advisorEmail ) {
        $this->_advisorEmail = $advisorEmail;
    }

    public function setStoreID ( $storeID ) {
        $this->_storeId = $storeID;
    }

    public function logCustomerEmailType ( $type ) {
        $this->_customerEmailType = $type;
    }

    public function hasEyePinNewsletteId () {
        $this->_checkMemberData(true);
        if ( $this->_eyePinNewsletterId ) {
            return true;
        }
        $this->_loadMailTemplate();
        if ( $this->_mailTemplate->getSchrackEyepinNewsletterId() ) {
            return true;
        }
        return false;
    }

    public function createAndSendMail () {
        self::log("START createAndSendMail()");
        if ( $this->_customerEmailType ) {
            $this->logMemberVar("_customerEmailType");
        }
        $this->logMemberVar("_magentoTransactionalTemplateID");
        $this->logMemberVar("_magentoTransactionalTemplateVariables");
        $this->logMemberVar("_toEmailAddresses");
        $this->logMemberVar("_toEmailCrmIDs");
        $this->logMemberVar("_ccEmailAddresses");
        $this->logMemberVar("_ccEmailCrmIDs");
        $this->logMemberVar("_emailToNameMap");
        $this->logMemberVar("_fromEmail");
        $this->logMemberVar("_fromName");
        $this->logMemberVar("_replyToEmail");
        $this->logMemberVar("_advisorEmail");
        try {
            $this->_checkMemberData();
            $this->_prepareSender();

            if ( $this->hasEyePinNewsletteId() ) {
                $this->_sendViaMqToEyepin();
            } else {
                $this->_sendTraditionally();
            }
        } catch ( Exception $ex ) {
            self::logException($ex);
            self::log("re-throwing now");
            throw $ex;
        }
        self::log("END createAndSendMail()\n");
    }

    private function _sendViaMqToEyepin () {
        self::log("    START _sendViaMqToEyepin()");

        if (count($this->_toEmailAddresses) > 0 && !stristr($this->_toEmailAddresses[0], 'nagarro_test_')) {
            $this->_ensureCrmIDs();
        }
        $this->_setDefaults();

        $this->_renderEmail();

        $message = $this->_createProtobufMessage();

        $headers = [
            'country_eyepin' => strtolower(Mage::getStoreConfig('schrack/general/country')),
            'protobuf_class' => 'com.schrack.queue.protobuf.SendSingleMail.Message',
            'sender_id' => self::SENDING_SYSTEM,
            'receipt' => time()
        ];

        $stomp = $this->_stompHelper->createStompClientFromConfigPath(self::STOMP_URL_CFG_PATH);
        $queue = $this->_stompHelper->getQueuePath(self::STOMP_OUT_QUEUE_CFG_PATH);

        $res = $stomp->send($queue, $message, $headers);
        if ( !$res ) {
            $error = $stomp->error();
            $error = $error ? $error : '(unknown error)';
            throw new Schracklive_SchrackSingleMail_Model_SingleMailApiException('MQ message sending failed: ' . $error);
        }
        Schracklive_Account_Helper_Protobuf::logMessage($message, $headers,
            Schracklive_Account_Helper_Protobuf::QUEUE_OUT, 'singlemailAPI');
        // $res2 = $stomp->send('/queue/dl_test_out', $message, $headers); // TODO: remove me later on

        unset($stomp);
        self::log("    END _sendViaMqToEyepin()");
    }

    private function _sendTraditionally () {
        self::log("    START _sendTraditionally()");
        $this->_setDefaults();
        $this->_fetchCrmIdEmails();
        $this->_fetchNames();

        $this->_mailTemplate->addBcc($this->_ccEmailAddresses);

        foreach ( $this->_toEmailAddresses as $toEmailAddr ) {
            self::log("preparing for recipient $toEmailAddr");
            foreach ( $this->_attachementName2binaryMap as $name => $binary ) {
                self::log("adding attachment $name");
                $this->_mailTemplate->getMail()->createAttachment(
                    $binary,
                    Zend_Mime::TYPE_OCTETSTREAM,
                    Zend_Mime::DISPOSITION_ATTACHMENT,
                    Zend_Mime::ENCODING_BASE64,
                    $name
                );
            }
            self::log("calling now sendTransactional()");
            $this->_mailTemplate->sendTransactional(
                $this->_magentoTransactionalTemplateID,
                array('name' => $this->_fromName, 'email' => $this->_fromEmail),
                $toEmailAddr,
                $this->_emailToNameMap[$toEmailAddr],
                $this->_magentoTransactionalTemplateVariables
            );
            self::log("call of sendTransactional() done");
        }
        self::log("    END _sendTraditionally()");
    }

    private function _fetchCrmIdEmails () {
        $crmIDs = $this->_toEmailCrmIDs + $this->_ccEmailCrmIDs;
        if ( count($crmIDs) == 0 ) {
            return;
        }
        $sql = "SELECT email, schrack_s4y_id FROM customer_entity WHERE schrack_s4y_id IN ('" . implode("','",$crmIDs) . "');";
        $dbRes = $this->_readConnection->fetchAll($sql);
        $id2mailMap = array();
        foreach ( $dbRes as $row ) {
            $id2mailMap[$row['schrack_s4y_id']] = $row['email'];
        }
        foreach ( $this->_toEmailCrmIDs as $crmID ) {
            $this->_toEmailAddresses[] = $id2mailMap[$crmID];
        }
        foreach ( $this->_ccEmailCrmIDs as $crmID ) {
            $this->_ccEmailAddresses[] = $id2mailMap[$crmID];
        }
        $this->_toEmailAddresses = array_unique($this->_toEmailAddresses);
        $this->_ccEmailAddresses = array_unique($this->_ccEmailAddresses);
    }

    private function _fetchNames () {
        $emailAddrs = $this->_toEmailAddresses + $this->_ccEmailAddresses;
        $emailAddrsToSearch = array();
        foreach ( $emailAddrs as $emailAddr ) {
            if ( ! isset($this->_emailToNameMap[$emailAddr]) ) {
                $emailAddrsToSearch[] = $emailAddr;
            }
        }
        $sql = " SELECT cust.email, fn.value AS firstname, ln.value AS lastname FROM customer_entity cust"
             . " JOIN customer_entity_varchar AS fn ON fn.entity_id = cust.entity_id AND fn.attribute_id = "
             . "    (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'firstname' AND entity_type_id = 1)"
             . " JOIN customer_entity_varchar AS ln ON ln.entity_id = cust.entity_id AND ln.attribute_id = "
             . "    (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'lastname' AND entity_type_id = 1)"
             . " WHERE email IN ('" . implode("','",$emailAddrsToSearch) . "');";
        $dbRes = $this->_readConnection->fetchAll($sql);
        foreach ( $dbRes as $row ) {
            $this->_emailToNameMap[$row['email']] = $row['firstname'] . ' ' . $row['lastname'];
        }
    }

    private function _prepareSender () {
        if ( strpos($this->_fromEmail,'@') === false ) {
            $path = sprintf(self::SENDER_NAME_CFG_PATH_PATTERN,$this->_fromEmail);
            $this->_fromName = Mage::getStoreConfig($path);
            $path = sprintf(self::SENDER_EMAIL_CFG_PATH_PATTERN,$this->_fromEmail);
            $tmp = Mage::getStoreConfig($path);
            if ( ! $tmp ) {
                throw new Schracklive_SchrackSingleMail_Model_SingleMailApiException("No value got for config path '$path'");
            }
            $this->_fromEmail = $tmp;
            self::log("changed members after _prepareSender():");
            $this->logMemberVar("_fromName");
            $this->logMemberVar("_fromEmail");
        }
    }

    private function _setDefaults () {
        if (    (! isset($this->_replyToEmail) || ! $this->_replyToEmail)
             && isset($this->_fromEmail) && $this->_fromEmail               ) {
            $this->_replyToEmail = $this->_fromEmail;
        }
        if ( ! isset($this->_advisorEmail) || ! $this->_advisorEmail ) {
            if ( count($this->_toEmailCrmIDs) > 0 ) {
                // get advisor for first 'to' that must have one:
                $firstCrmID = reset($this->_toEmailCrmIDs);
                $sql = " SELECT a.value FROM customer_entity c"
                     . " JOIN customer_entity_varchar a ON c.entity_id = a.entity_id"
                     . "        AND a.attribute_id = (SELECT attribute_id FROM eav_attribute "
                     . "                              WHERE attribute_code = 'schrack_advisor_principal_name')"
                     . " WHERE c.schrack_s4y_id = ?";
                $advisorPrincipalName = $this->_readConnection->fetchOne($sql, $firstCrmID);
            } else {
                // else use shop default (= not logged in) advisor
                $advisorPrincipalName = Mage::getStoreConfig('schrack/shop/default_advisor');
            }
            if ( $advisorPrincipalName ) {
                $advisor = Schracklive_SchrackCustomer_Model_Customer::getAdvisorForPrincipalName($advisorPrincipalName);
                if ( $advisor && $advisor->getId() ) {
                    $this->_advisorEmail = $advisor->getEmail();
                }
            }
        }
        self::log("possible changed members after _setDefaults():");
        $this->logMemberVar("_replyToEmail");
        $this->logMemberVar("_advisorEmail");
    }

    private function _createProtobufMessage () {
        $msg = new com\schrack\queue\protobuf\SendSingleMail\Message();
        foreach ( $this->_toEmailCrmIDs as $crmID ) {
            $msg->addTo($this->_createProtobufEmailAddress($crmID));
        }
        foreach ( $this->_ccEmailCrmIDs as $crmID ) {
            $msg->addCc($this->_createProtobufEmailAddress($crmID));
        }
        foreach ( $this->_toEmailAddresses as $email ) {
            $msg->addTo($this->_createProtobufEmailAddress($email,false));
        }
        foreach ( $this->_ccEmailAddresses as $email ) {
            $msg->addCc($this->_createProtobufEmailAddress($email,false));
        }
        $msg->setNewsletterId($this->_eyePinNewsletterId);
        if ( isset($this->_fromEmail) && $this->_fromEmail )        $msg->setFromEmail($this->_fromEmail);
        if ( isset($this->_fromName) && $this->_fromName )          $msg->setFromName($this->_fromName);
        if ( isset($this->_replyToEmail) && $this->_replyToEmail )  $msg->setReplyTo($this->_replyToEmail);
        if ( isset($this->_advisorEmail) && $this->_advisorEmail )  $msg->setAccountManager($this->_advisorEmail);
        $msg->setSendingSystem(self::SENDING_SYSTEM);
        $msg->setType( isset($this->_emailType) && $this->_emailType ? $this->_emailType : 'common');
        $msg->setContext("Webshop");
        $msg->setHandleAccountManager(false); // suppress setting advisor in MQ/EyepinConnector
        $content = new com\schrack\queue\protobuf\SendSingleMail\Message\Content();
        $content->setKey($this->_eyePinSubjectVariable);
        $content->setValue($this->_subjectText);
        $msg->addContents($content);
        $content = new com\schrack\queue\protobuf\SendSingleMail\Message\Content();
        $content->setKey($this->_eyePinBodyVariable);
        $content->setValue($this->_bodyText);
        $msg->addContents($content);
        foreach ( $this->_attachementName2binaryMap as $name => $binary ) {
            $base64 = base64_encode($binary);
            $attachement = new com\schrack\queue\protobuf\SendSingleMail\Message\Attachment();
            $attachement->setType(com\schrack\queue\protobuf\SendSingleMail\Message\AttachmentType::Base64);
            $attachement->setName($name);
            $attachement->setValue($base64);
            $msg->addAttachments($attachement);
            unset($base64);
        }
        unset($this->_attachementName2binaryMap);
        $codec = new \DrSlump\Protobuf\Codec\Binary();
        $data = $msg->serialize($codec);
        return $data;
    }

    private function _createProtobufEmailAddress ( $eMailOrCrmID, $isCrmId = true ) {
        $res = new com\schrack\queue\protobuf\SendSingleMail\Message\EmailAddress();
        if ( $isCrmId ) {
            $res->setCrmId($eMailOrCrmID);
        } else {

            // Special Case: Nagarro Testing (S4S) -> all registrations which are using a pattern, redirect to a central address:
            //-------------------------------------------------------------------
            if (stristr($eMailOrCrmID, 'nagarro_test_')) {
                $eMailOrCrmID = 'schrack.support@nagarro.com';
            }
            //-------------------------------------------------------------------
            self::log('Email :' . $eMailOrCrmID);
            $res->setAddress($eMailOrCrmID);
            // Name cannot have '@' inside string, because this will be recognised as spam
            if (stristr($eMailOrCrmID, '@')) {
                list($name, $postfix) = explode('@', $eMailOrCrmID);
                // Assign prefix as name to variable in case of variable consists of email address:
                $eMailOrCrmID = $name;
            }
            self::log('Name :' . $eMailOrCrmID);
            $res->setName($eMailOrCrmID);
        }
        return $res;
    }

    private function _ensureCrmIDs () {
        $mailAddrs = array_merge($this->_toEmailAddresses,$this->_ccEmailAddresses);
        if ( count($mailAddrs)< 1 ) {
            return;
        }
        $sql = "SELECT email, schrack_s4y_id FROM customer_entity WHERE email IN ('" . implode("','",$mailAddrs) . "')";
        $dbRes = $this->_readConnection->fetchAll($sql);
        $dbResMap = array();
        foreach ( $dbRes as $row ) {
            $dbResMap[$row['email']] = $row['schrack_s4y_id'];
        }

        $mapped = $this->_getMappedCrmIDs($this->_toEmailAddresses,$dbResMap);
        $this->_toEmailCrmIDs = array_merge($this->_toEmailCrmIDs,$mapped);
        $this->_toEmailAddresses = $this->_removeMappedEmailAddresses($this->_toEmailAddresses,$dbResMap);

        $mapped = $this->_getMappedCrmIDs($this->_ccEmailAddresses,$dbResMap);
        $this->_ccEmailCrmIDs = array_merge($this->_ccEmailCrmIDs,$mapped);
        $this->_ccEmailAddresses = $this->_removeMappedEmailAddresses($this->_ccEmailAddresses,$dbResMap);
    }

    private function _getMappedCrmIDs ( $emailArray, $email2crmIdMap ) {
        $res = array();
        foreach ( $emailArray as $email ) {
            if ( isset($email2crmIdMap[$email]) ) {
                $res[] = $email2crmIdMap[$email];
            }
        }
        return $res;
    }

    private function _removeMappedEmailAddresses ( $emailArray, $email2crmIdMap ) {
        $res = array();
        foreach ( $emailArray as $email ) {
            if ( ! isset($email2crmIdMap[$email]) ) {
                $res[] = $email;
            }
        }
        return $res;
    }

    private function _loadMailTemplate () {
        if ( $this->_mailTemplate ) {
            return;
        }
        if ( ! $this->_storeId ) {
            $sessionLoggedInCustomer = Mage::getSingleton('customer/session')->getCustomer();
            if ( $sessionLoggedInCustomer && $sessionLoggedInCustomer->getId() ) {
                $this->_storeId = $sessionLoggedInCustomer->getStoreId();
            } else {
                $this->_storeId = Mage::app()->getStore('default')->getStoreId();
            }
        }
        $this->_mailTemplate = Mage::getModel('core/email_template');
        $this->_mailTemplate->setDesignConfig(array('area' => 'frontend', 'store' => $this->_storeId));
        $this->_mailTemplate->load($this->_magentoTransactionalTemplateID);

        if ( ! $this->_mailTemplate->getId() ) {
            throw new Schracklive_SchrackSingleMail_Model_SingleMailApiException(
                "Invalid transactional email code: {$this->_magentoTransactionalTemplateID}"
            );
        }
    }

    private function _renderEmail () {
        $translate = Mage::getSingleton('core/translate');
        $translate->setTranslateInline(false);
        $this->_loadMailTemplate();
        $this->_bodyText = $this->_mailTemplate->getProcessedTemplate($this->_magentoTransactionalTemplateVariables, true);
        $this->_subjectText = $this->_mailTemplate->getProcessedTemplateSubject($this->_magentoTransactionalTemplateVariables,
            true);
        if ( !isset($this->_eyePinNewsletterId) ) {
            $this->_eyePinNewsletterId = $this->_mailTemplate->getSchrackEyepinNewsletterId();
            if ( !$this->_eyePinNewsletterId ) {
                throw new Schracklive_SchrackSingleMail_Model_SingleMailApiException(
                    "Missing data for EyePin newsletter ID. Please ensure it in Magento backend transactional email "
                    . " detail screen or call setEyePinTemplateId() before!"
                );
            }
        }
    }

    private function _checkMemberData ( $onlyTemplateID = false ) {
        if ( ! $this->_checkField('_magentoTransactionalTemplateID'.false) ) {
            throw new Schracklive_SchrackSingleMail_Model_SingleMailApiException(
                "Missing data for Magento template ID. Please call setMagentoTransactionalTemplateID()"
                . " or setMagentoTransactionalTemplateIDfromConfigPath() before!"
            );
        }

        if ( ! $onlyTemplateID && count($this->_toEmailAddresses) == 0 && count($this->_toEmailCrmIDs) == 0 ) {
            throw new Schracklive_SchrackSingleMail_Model_SingleMailApiException(
                "Missing data: Need at leas one email recipient!"
            );
        }
    }

    private function _checkField ( $name, $throwException = true ) {
        if ( ! isset($this->$name) || ! $this->$name ) {
            if ( $throwException ) {
                $setterName = 'set' . ucfirst(substr($name, 1));
                throw new Schracklive_SchrackSingleMail_Model_SingleMailApiException(
                    "Missing data $name. Please call $setterName before!"
                );
            }
            return false;
        }
        return true;
    }

    private static function log ( $msg, $level = null ) {
        Mage::log($msg,$level,'single_mail_api.log');
    }

    private static function logVar ( $name, $val ) {
        $printVal = self::getVarPrintVal($val);
        self::log("VAR $name : $printVal");
    }

    private static function getVarPrintVal ( $val, $deep = 0 ) {
        if ( ! isset($val) ) {
            return '(undefined)';
        }
        if ( is_string($val) ) {
            return "'$val'";
        }
        if ( is_object($val) ) {
            return "{object}";
        }
        if ( is_array($val) ) {
            if ( $deep == 0 ) {
                $printVal = '[';
                foreach ( $val as $ndx => $ndxVal ) {
                    if ( $printVal > '[' ) {
                        $printVal .= ', ';
                    }
                    $printVal .= "$ndx = ";
                    $printVal .= self::getVarPrintVal($ndxVal,$deep + 1);
                }
                $printVal .=  ']';
                return $printVal;
            } else {
                return '[array]';
            }
        }
        return $val;
    }

    private function logMemberVar ( $name ) {
        self::logVar("this->$name",$this->$name);
    }

    private static function logException ( $e ) {
        self::log("\n" . $e->__toString(), Zend_Log::ERR);
    }
}
