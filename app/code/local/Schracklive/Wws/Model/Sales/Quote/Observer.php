<?php

class Schracklive_Wws_Model_Sales_Quote_Observer
{

    // event: "sales_quote_collect_totals_before"
    public function beforeCollectTotals($observer)
    {
        // the shipping memo will be sent after we have finalized the order in the WWS
        $quote = $observer->getQuote();
        /* @var $quote Mage_Sales_Model_Quote */
        $hasSchrackWwsShipMemo = $quote->hasSchrackWwsShipMemo();
        $schrackWwsOrderNumber = $quote->getSchrackWwsOrderNumber();
        if ( $hasSchrackWwsShipMemo && $schrackWwsOrderNumber ) {
            $message = 'Recollecting totals after quote has been finalized in the WWS - resetting cart.';
            Mage::helper('schrack/logger')->error($message);

            try {
                $payment_check = $quote->getPayment();
                $payment_method = $payment_check->getMethod();

                if(! Mage::helper('schrackpayment/method')->isExternalMethod($payment_method) ) {
                    // Mage::log('Schracklive_Wws_Model_Sales_Quote_Observer beforeCollectTotals: will set quote inactive', null, 'pupay.log'); PayUnitiy remove action
                    // deactivate current quote
                    $quote->setIsActive(false);
                    $quote->save();
                }
            } catch (Exception $e) {
                // deactivate current quote
                // Mage::log('Schracklive_Wws_Model_Sales_Quote_Observer beforeCollectTotals - Exception: will set quote inactive - ' . $e->getMessage(), null, 'pupay.log'); PayUnitiy remove action
                Mage::logException($e);
                $quote->setIsActive(false);
                $quote->save();                
            }

            // detach quote from session
            Mage::getSingleton('checkout/session')->clear();

            if (Mage::getStoreConfig('schrackdev/development/test')) {
                throw Mage::exception('Schracklive_Wws', $message);
            }
        }
    }

}
