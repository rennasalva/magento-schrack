<?php
/**
 *
 * @package	Orcamultimedia_Ids
 *
 **/

class Orcamultimedia_Ids_BaseController extends Mage_Core_Controller_Front_Action
{

    public function preDispatch()
    {
        parent::preDispatch();
        return $this;
    }


    public function validateAction()
    {
        $session = $this->_getSession();
        if (!$session->isLoggedIn()
            || $session['ids']['FUNCTION'] != 'VALIDATE'
            || !isset($session['ids']['PRODUCTID'])
            || !isset($session['ids']['QUANTITY'])) {

            $this->_redirect('*/*/');
            return;
        }

        $this->loadLayout();
        $this->renderLayout();
    }


    public function searchAction()
    {
        $session = $this->_getSession();
        if (!$session->isLoggedIn()
            || $session['ids']['FUNCTION'] != 'BACKGROUND_SEARCH'
            || !isset($session['ids']['SEARCHSTRING'])) {

            $this->_redirect('*/*/');
            return;
        }

        $queryText = Mage::helper('core/string')->cleanString(trim($session['ids']['SEARCHSTRING']));

        $query = Mage::getSingleton('catalogsearch/query')->loadByQuery($queryText);
        if (!$query->getId())
            $query->setQueryText($queryText);

        $query->setStoreId(Mage::app()->getStore()->getId());

        $this->loadLayout();
        $this->renderLayout();
    }


    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }




}
