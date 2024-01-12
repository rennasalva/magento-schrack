<?php

class Finpin_Sezame_Adminhtml_SezameController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Index action
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function registerAction()
    {
        $redirectUrl = $this->getUrl('adminhtml/system_config/edit/section/sezame');
        /** @var Finpin_Sezame_Model_Admin $model */
        $model = Mage::getModel('sezame/admin');
        $model->register();
        $this->_redirectUrl($redirectUrl);
    }

    public function signAction()
    {
        $redirectUrl = $this->getUrl('adminhtml/system_config/edit/section/sezame');
        /** @var Finpin_Sezame_Model_Admin $model */
        $model = Mage::getModel('sezame/admin');
        $model->sign();
        $this->_redirectUrl($redirectUrl);
    }

    public function cancelAction()
    {
        $redirectUrl = $this->getUrl('adminhtml/system_config/edit/section/sezame');
        /** @var Finpin_Sezame_Model_Admin $model */
        $model = Mage::getModel('sezame/admin');
        $model->cancel();
        $this->_redirectUrl($redirectUrl);
    }

    public function makecsrAction()
    {
        $redirectUrl = $this->getUrl('adminhtml/system_config/edit/section/sezame');
        /** @var Finpin_Sezame_Model_Admin $model */
        $model = Mage::getModel('sezame/admin');
        $model->makeCsr();
        $this->_redirectUrl($redirectUrl);
    }

    protected function _isAllowed()
    {
        // XXX ToDo
        return true;
    }
}
