<?php


class Schracklive_SchrackCustomer_Block_Account_OrderNowPopup extends Mage_Core_Block_Template { // Mage_Checkout_Block_Onepage_Shipping {
    /*
        private $order = null;
        public function getOrder () {
            return $this->order;
        }

        public function setOrder ( $order ) {
            $this->order = $order;
        }
    */

    public function getAddressesHtmlSelect ()
    {
        $session = Mage::getSingleton('customer/session');
        if ( $session->isLoggedIn() ) {
            $customer = $session->getCustomer();
            $options = array();
            foreach ($customer->getAddresses() as $address) {
                if ( $address->getSchrackWwsAddressNumber() > 0 && $address->getSchrackWwsAddressNumber() != Schracklive_SchrackCustomer_Model_Address::NO_ADDRESS_NUMBER ) {
                    $options[] = array(
                        'value' => $address->getSchrackWwsAddressNumber(),
                        'label' => $address->format('oneline')
                    );
                }
            }

            $address = $customer->getPrimaryShippingAddress();
            if ( $address ) {
                $addressId = $address->getSchrackWwsAddressNumber();
            } else {
                $addressId = $options[0]['value'];
            }

            $select = $this->getLayout()->createBlock('core/html_select')
                ->setName('shipping_address_id')
                ->setId('shipping-address-select')
                ->setClass('address-select')
                ->setValue($addressId)
                ->setOptions($options);

            return $select->getHtml();
        }
        return '';
    }

    public function getPickupStocksHtmlSelect () {
        $session = Mage::getSingleton('customer/session');
        if ( $session->isLoggedIn() ) {
            $customer = $session->getCustomer();
            $defaultWarehouseId = Mage::helper('schrackcustomer')->getPickupWarehouseId($customer);

            $options = array();
            for ( $i = 1 ; ; ++$i ) {
                $id = Mage::getStoreConfig('carriers/schrackpickup/id' . $i);
                $name = Mage::getStoreConfig('carriers/schrackpickup/name' . $i);
                if ( ! $id ) {
                    break;
                }
                $options[] = array(
                    'value' => $id,
                    'label' => $name
                );
            }

            $select = $this->getLayout()->createBlock('core/html_select')
                ->setName('pickup_address_id')
                ->setId('pickup-address-select')
                ->setClass('address-select')
                ->setValue($defaultWarehouseId)
                ->setOptions($options);

            return $select->getHtml();
        }
        return '';
    }

}
