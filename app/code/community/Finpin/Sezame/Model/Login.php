<?php

class Finpin_Sezame_Model_Login extends Mage_Core_Model_Observer
{
    public function preDispatchLogin($observer)
    {
        Mage::getSingleton('core/session')->setSezameUsed(false);
    }

    /**
     * this method is invked after a login, send fraud alert if enabled and if sezame was not used
     *
     * @param $observer
     */
    public function customerLogin($observer)
    {
        /** @var Finpin_Sezame_Model_Auth $model */
        $model = Mage::getModel('sezame/auth');

        $sezameEnabled = $model->getConfigParam('settings/enabled');
        if (!$sezameEnabled) {
            return;
        }
        if(Mage::getSingleton('customer/session')->isLoggedIn())
        {
            if (Mage::getSingleton('core/session')->getSezameUsed())
                return;

            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            $customerData = Mage::getModel('customer/customer')->load($customer->getId());

            /** @var Finpin_Sezame_Model_Auth $model */
            $model = Mage::getModel('sezame/auth');

            if ($model->getConfigParam('settings/fraud'))
            {
                /** @var Finpin_Sezame_Model_Link $linkModel */
                $linkModel = Mage::getModel('sezame/link');
                if ($linkModel->status($customerData->getEmail()))
                    $model->fraud($customerData->getEmail())->send();
            }
        }
    }
}