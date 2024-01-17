<?php

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Fpccrawler
 */
class Amasty_Fpccrawler_Adminhtml_Amfpccrawler_AjaxController extends Mage_Adminhtml_Controller_Action
{

    public function generateAction()
    {
        try {
            $helper = Mage::helper('amfpccrawler');
            $helper->generateQueue();
            $msg = $helper->__('Queue was generated.');
            Mage::getSingleton('adminhtml/session')->addSuccess($msg);
        } catch (Exception $e){
            $helper->logDebugMessage('queue_generate', $e->getMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('adminhtml/system_config/edit/section/amfpccrawler');

        return true;
    }

    public function processAction()
    {
        try {
            $helper = Mage::helper('amfpccrawler');
            $helper->processQueue();
            $msg = $helper->__('Queue was processed.');
            Mage::getSingleton('adminhtml/session')->addSuccess($msg);
        } catch (Exception $e){
            $helper->logDebugMessage('queue_process', $e->getMessage());
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }

        $this->_redirect('adminhtml/system_config/edit/section/amfpccrawler');

        return true;
    }

    protected function _isAllowed()
    {
        return true;
    }

}
