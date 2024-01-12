<?php

use DrSlump\Protobuf\Message;
use com\schrack\queue\protobuf\MailingListTypeTransfer\MailingListTypeMessage;

class Schracklive_SchrackCustomer_Model_Mailinglisttype_Protoimport extends Schracklive_Account_Model_Protoimport_Base {

    static $nameMap = array(
        'code' => 'code',
        'name' => 'name',
        'unsubscribeable' => 'unsubscribeable',
        'price_critical' => 'price_critical'
    );


    protected function insertOrUpdateOrDeleteImpl ( Message $message ) {
        $protoMailingListType = $message->getMailingListType();
        $code = $protoMailingListType->getCode();
        $mageEntity = Mage::getModel('schrackcustomer/mailinglisttype')->loadByCode($code);
        if ( $protoMailingListType->getDeleted() ) {
            if ( $mageEntity->getId() ) {
                $mageEntity->delete();
            } else {
                return false;
            }
        } else {
            $this->mapProtobufToMagento($protoMailingListType,$mageEntity);
            $mageEntity->save();
        }
        return true;
    }

    protected function getMessageKeyFromProtobuf ( Message $message ) {
        return $message->getMailingListType()->getCode();
    }

    protected function getType () {
        return Schracklive_Account_Helper_Protobuf::TYPE_ML_TYPE;
    }

    protected function checkInstance ( Message $message ) {
        return $message instanceof MailingListTypeMessage;
    }

    protected function getMappingNamesProtobufToMagento () {
        return self::$nameMap;
    }
}
