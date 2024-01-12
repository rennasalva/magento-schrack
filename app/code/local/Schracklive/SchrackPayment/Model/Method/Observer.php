<?php

class Schracklive_SchrackPayment_Model_Method_Observer
{

    // event: "payment_method_is_active"
    public function isActive($observer)
    {
        $result = $observer->getResult();
        $paymentMethod = $observer->getMethodInstance();
        /* @var $paymentMethod Mage_Payment_Model_Method_Abstract */
        $quote = $observer->getQuote();
        /* @var $quote Mage_Sales_Model_Quote */
        $shippingRate = $quote->getShippingAddress()->getShippingRateByCode($quote->getShippingAddress()->getShippingMethod());
        /* @var $shippingRate Mage_Shipping_Model_Rate_Abstract */

        // business rules: therefor not is isAvaiable() of methods
        if (is_object($paymentMethod) && is_object($shippingRate)) {
            $isPickupShipping = false;

            // This is the first time, when registry-key 'pickup-location' is used, and it will be deleted after usinng second time in:
            // ---> app/design/frontend/schrack/schrackresponsive/template/checkout/onepage/payment/methods.phtml
            $pickupLocation = Mage::registry('pickup_location');
            if ($pickupLocation) {
                $isPickupShipping = true;
            }

            // cash payment ("Barzahlung") only available for pickup shipping
            if ($this->_isCashPayment($paymentMethod) && !$isPickupShipping) {
                $result->isAvailable = false;
            }
            // cash/collect on delivery ("Nachnahme") not available for pickup shipping
            elseif ($this->_isCollectOnDeliveryPayment($paymentMethod) && $isPickupShipping) {
                $result->isAvailable = false;
            }
        }
    }

    protected function _isCashPayment(Mage_Payment_Model_Method_Abstract $paymentMethod)
    {
        return ($paymentMethod->getCode() == 'schrackcash');
    }

    protected function _isCollectOnDeliveryPayment(Mage_Payment_Model_Method_Abstract $paymentMethod)
    {
        return ($paymentMethod->getCode() == 'schrackcod');
    }
}
