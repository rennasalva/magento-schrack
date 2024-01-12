<?php

class Schracklive_SchrackAdminhtml_Adminhtml_ToolsController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function cacheflushAction()
    {
        $redirectUrl = $this->getUrl('adminhtml/system_config/edit/section/schrack');
        /** @var Finpin_Sezame_Model_Admin $model */
        $model = Mage::getModel('schrackadminhtml/admin');
        $model->flushCache();
        $this->_redirectUrl($redirectUrl);
    }

}