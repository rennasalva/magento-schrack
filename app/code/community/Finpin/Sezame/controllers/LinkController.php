<?php

class Finpin_Sezame_LinkController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();
        $action = $this->getRequest()->getActionName();

        $loginUrl = Mage::helper('customer')->getLoginUrl();

        if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }

    public function indexAction ()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('sezame.link.index');
        $this->renderLayout();
    }

    public function linkAction ()
    {
        /** @var Mage_Customer_Helper_Data $helper */
        $helper = Mage::helper('customer');
        /** @var Mage_Customer_Model_Customer */
        $customer = $helper->getCustomer();

        /** @var Finpin_Sezame_Model_Link $model */
        $model = Mage::getModel('sezame/link');

        $qrcode = null;
        $message = null;
        $username = $customer->getEmail();
        try {
            $linked = $model->status($username);
            if ($linked)
                $message = 'User is already linked.';
            else
                $qrcode = $model->qrCode($username);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $this->loadLayout();
        /** @var Finpin_Sezame_Block_Link $block */
        $block = $this->getLayout()->getBlock('sezame.link');
        $block->setQrCode($qrcode);
        $block->setMessage($message);
        $this->renderLayout();
    }
}