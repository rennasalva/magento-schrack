<?php

class Schracklive_SchrackShipping_Model_Carrier_Schrackinpost extends Mage_Shipping_Model_Carrier_Abstract implements Mage_Shipping_Model_Carrier_Interface {

    protected $_code = 'schrackinpost';

    /**
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {

        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = Mage::getModel('shipping/rate_result');
        $method = Mage::getModel('shipping/rate_result_method');

        $method->setCarrier($this->_code);
        $method->setCarrierCode($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('warehouse' . $this->getConfigData('id'));
        $method->setMethodTitle($this->getConfigData('name'));

        $method->setPrice(0);
        $method->setCost(0);

        $result->append($method);

        return $result;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */

    public function getAllowedMethods() {
        $methods = array(
            'warehouse' . $this->getConfigData('id') => $this->getConfigData('name'),
        );
        return $methods;
    }
}