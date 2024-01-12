<?php
use \DrSlump\Protobuf\Message;
use \com\schrack\queue\protobuf\AccountTransfer\AccountMessage;

class Schracklive_Account_Model_Protoimport extends Schracklive_Account_Model_Protoimport_Base {

    static $nameMap = array(
        'wws_custumer_id'            => 'wws_customer_id',
        's4y_id'                     => 'schrack_s4y_id',
        'wws_branch_id'              => 'wws_branch_id',
        'match'                      => 'match_code',
        'salutation'                 => 'prefix',
        'name1'                      => 'name1',
        'name2'                      => 'name2',
        'name3'                      => 'name3',
        'currency_code'              => 'currency_code',
        'vat_id'                     => 'vat_identification_number',
        'com_reg_num'                => 'company_registration_number',
        'street'                     => 'street',
        'city'                       => 'city',
        'zip'                        => 'postcode',
        'country'                    => 'country_id',
        'description'                => 'description',
        'sales_area'                 => 'sales_area',
        'phone'                      => 'telephone',
        'fax'                        => 'fax',
        'advisor_principal_name'     => 'advisor_principal_name',
        'information'                => 'information',
        'advisors_principal_names'   => 'advisors_principal_names',
        'gtc_accepted'               => 'gtc_accepted',
        'email'                      => 'email',
        'homepage'                   => 'homepage',
        'delivery_block'             => 'delivery_block',
        'rating'                     => 'rating',
        'enterprise_size'            => 'enterprise_size',
        'account_type'               => 'account_type',
        'limit_web'                  => 'limit_web',
        'oldaccount_id'              => 'wws_customerid_history',
        'oldaccount_wwsnumber'       => 'schrack_s4y_id_history'

/*
        optional int32 xrow = 1;                         -
        optional bool deleted = 3;                       -
        optional string customer_group = 11;             -
        optional string language_code = 12;              -
        optional int32 sales_rep = 20;                   -
        optional string salutatory = 24;                 -
        optional string shop = 27;                       -
        optional float limit = 36;                       -
        optional string limit_text = 37;                 -
        optional string dept_insurenum = 38;             -
        optional float dept_insuresum = 39;              -
        optional float dept_retention = 40;              -
        optional string exp_date = 41;                   -
        optional string payment_terms = 42;              -
        optional string inco_terms = 43;                 -
        optional string permission_level = 44;           -
        optional bool bonus_customer = 45;               -
        optional bool sare_customer = 46;                -
        optional bool bank_collect = 47;                 -
        optional string oldaccount_id => 61;             -
        optional string oldaccount_wwsnumber => 62;      -
*/
    );

    protected function getMappingNamesProtobufToMagento () {
        return self::$nameMap;
    }

    protected function getType () {
        return Schracklive_Account_Helper_Protobuf::TYPE_ACCOUNT;
    }

    protected function checkInstance ( Message $message ) {
        return $message instanceof AccountMessage;
    }

    protected function getMessageKeyFromProtobuf ( Message $message ) {
        $contact = $message->getAccount();
        $wwsCustomerId = $contact->getWwsCustumerId();
        return $this->getMessageKey($wwsCustomerId);
    }

    private function getMessageKey ( $wwsCustomerId ) {
        return sprintf("account-%s",$wwsCustomerId);
    }

    protected function insertOrUpdateOrDeleteImpl ( Message $accountMessage ) {
        $protoAccount = $accountMessage->getAccount();
        $wwsCustomerId = $protoAccount->getWwsCustumerId();
        $accountHelper = Mage::helper('account');

        if ( $protoAccount->getDeleted() ) {
            $accountHelper->deleteAccount($wwsCustomerId);
        } else {
            $tmpMagentoAccount = Mage::getModel('account/account');
            $this->mapProtobufToMagento($protoAccount, $tmpMagentoAccount);
            $array = $tmpMagentoAccount->getData();
            $limitvalue =  $protoAccount->getLimit();


            if ($limitvalue == 0) {
                $array['limit_web'] = 0;
            } elseif ($limitvalue == 1) {
                $array['limit_web'] = 1;
            } elseif ($limitvalue > 1) {
                $array['limit_web'] = 2;
            } else {
                $array['limit_web'] = 0;
                Mage::log("UNKNOWN LIMIT - Customer Id: ". $wwsCustomerId. " limitvalue: ". $limitvalue,null,'proto_import_account.log');
            }
            $protoAccount = $accountHelper->updateOrCreateAccount($wwsCustomerId, $array);
            $systemContact = $accountHelper->updateOrCreateSystemContact($protoAccount, $array);
        }
    }

    public function createProtobufMessage ( Schracklive_Account_Model_Account $account, $isNew = false ) {
        $msgKey = $this->getMessageKey($account->getWwsCustomerId());
        if ( self::isInInsertUpdate($msgKey) ) {
            return false;
        }
        $protoAccount = new AccountMessage\Account();
        $this->mapMagentoToProtobuf($account,$protoAccount);
        if ( $protoAccount->getSalesArea() == 0 ) {
            $protoAccount->clearSalesArea();
        }
        $msg = new AccountMessage();
        $msg->setAccount($protoAccount);
        $codec = new \DrSlump\Protobuf\Codec\Binary();
        $data = $msg->serialize($codec);
        return $data;
    }

    public function readFileToMessage($fileName) {
        $data = @file_get_contents($fileName);
        $l = strlen($data);
        if ( $l < 1 ) {
            return null;
        }
        echo "object with size {$l} got. ========================================== <br>".PHP_EOL;
        $msg = new AccountMessage($data);
        return $msg;
    }
}