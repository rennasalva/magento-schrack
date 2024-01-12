<?php

use DrSlump\Protobuf\Message;
use com\schrack\queue\protobuf\AddressTransfer\AddressMessage;

class Schracklive_SchrackCustomer_Model_Address_Protoimport extends Schracklive_Account_Model_Protoimport_Base {

    static $nameMap = array(
        'address_id' => 'schrack_wws_address_number',
        'type'       => 'schrack_type',
        'name1'      => 'lastname',
        'name2'      => 'middlename',
        'name3'      => 'firstname',
        'street'     => 'street',
        'city'       => 'city',
        'zip'        => 'postcode',
        'country'    => 'country_id',
        'phone1'     => 'telephone',
        'phone2'     => 'schrack_additional_phone',
        'fax'        => 'fax',
        'comment'    => 'schrack_comments'
        /*
                optional bool deleted = 2;
                required string wws_custumer_id = 4;
                optional string user_id = 18;
                optional bool default_billing = 19;
                optional bool default_shipping = 20;
        */
    );

    protected function getMappingNamesProtobufToMagento () {
        return self::$nameMap;
    }

    protected function getType () {
        new AddressMessage();
        return Schracklive_Account_Helper_Protobuf::TYPE_ACCOUNT;
    }

    protected function checkInstance ( Message $message ) {
        return $message instanceof AddressMessage;
    }

    protected function getMessageKeyFromProtobuf ( Message $message ) {
        $address = $message->getAddress();
        $wwsCustomerId = $address->getWwsCustumerId();
        $wwsAddressNumber = $address->getAddressId();
        return $this->getMessageKey($wwsCustomerId,$wwsAddressNumber);
    }

    private function getMessageKey ( $wwsCustomerId, $wwsAddressNumber ) {
        return sprintf("address-%s-%d",$wwsCustomerId,$wwsAddressNumber);
    }

    protected function insertOrUpdateOrDeleteImpl ( Message $message ) {
        $protoAddress = $message->getAddress();
        $wwsCustomerId = $protoAddress->getWwsCustumerId();
        $wwsAddressNumber = $protoAddress->getAddressId();

        if ( $protoAddress->getDeleted() ) {
            $deleteEntireCustomer = $protoAddress->getDeleteEntireCustomer();
            return Mage::helper('schrackcustomer/address_api')->deleteLocation($wwsCustomerId,$wwsAddressNumber,$deleteEntireCustomer);
        } else {
            $tmpMagentoAddress = Mage::getModel('customer/address');
            $this->mapProtobufToMagento($protoAddress, $tmpMagentoAddress);
            $array = $tmpMagentoAddress->getData();
            $array['is_default_billing']  = $protoAddress->getDefaultBilling();
            $array['is_default_shipping'] = $protoAddress->getDefaultShipping();
            return Mage::helper('schrackcustomer/address_api')->replaceLocation($wwsCustomerId,$wwsAddressNumber,$array);
        }
    }

    public function createAddressProtobufMessage ( Schracklive_SchrackCustomer_Model_Customer $customer,Schracklive_SchrackCustomer_Model_Address $address, $delete = false ) {
        if ( $address->getSchrackWwsAddressNumber() == null ) {
            return false;
            // throw new Exception("Cannot send address message without address number!");
        }
        if ( $address->getSchrackWwsAddressNumber() && $address->getSchrackWwsAddressNumber() == 0 ) {
            return false; // Never send address ID 0 to S4Y!
        }
        $msgKey = $this->getMessageKey($customer->getSchrackWwsCustomerId(),$address->getSchrackWwsAddressNumber());
        if ( self::isInInsertUpdate($msgKey) ) {
            return false;
        }
        if ( ! $delete  && ! Mage::helper('schrackcore/model')->isModified($address,self::$nameMap) ) {
            return false;
        }

        $protoAddress = new AddressMessage\Address();

        $this->mapMagentoToProtobuf($address,$protoAddress);
        if ( $protoAddress->getAddressId() == Schracklive_SchrackCustomer_Model_Address::NO_ADDRESS_NUMBER ) {
            if (Mage::registry('schrack_address_type_from_checkout')) {
                $address->setSchrackType(Mage::registry('schrack_address_type_from_checkout'));
            }
            if (Mage::registry('schrack_address_phone_from_checkout')) {
                $addressPhone = Mage::registry('schrack_address_phone_from_checkout');
                $protoAddress->setPhone1($addressPhone);
            }
            $protoAddress->clearAddressId();
        }
        $protoAddress->setWwsCustumerId($customer->getSchrackWwsCustomerId());
        $val = $address->getIsDefaultShipping();
        if ( isset($val) ) {
            $protoAddress->setDefaultShipping($val ? true : false);
        } else {
            $protoAddress->setDefaultShipping(($address->getId() == $customer->getSystemContact()->getDefaultShipping()));
        }
        $protoAddress->setType($address->getSchrackType());
        $protoAddress->setDeleted($delete);

        $msg = new AddressMessage();
        $msg->setAddress($protoAddress);

        $codec = new DrSlump\Protobuf\Codec\Binary();
        $data = $msg->serialize($codec);
        return $data;
    }

} 