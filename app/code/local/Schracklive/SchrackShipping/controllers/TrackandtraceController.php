<?php

class Schracklive_SchrackShipping_TrackandtraceController extends Mage_Core_Controller_Front_Action {
    private $_statusMap;
    private $_detailedStatusMap;
    
    public function _construct() {
        parent::_construct();
    
        $helper = Mage::helper('schrackshipping/trackandtrace');
        
        $this->_statusMap = $helper->getStatusMap();
        $this->_detailedStatusMap = $helper->getDetailedStatusMap();
    }    
    
    public function indexAction() {
        $helper = Mage::helper('schrackshipping/trackandtrace');
        $this->loadLayout();

        try {
            $shouldHaveResults = false;
            if ($this->getRequest()->isGet() && $this->getRequest()->getParam('colloNumbers')) {
                $cnParam = $this->getRequest()->getParam('colloNumbers');
                if (is_array($cnParam)) {
                    $colloNumbersArray = $this->getRequest()->getParam('colloNumbers');
                } else {
                    $colloNumbersArray = explode(',', $cnParam);
                }
                $this->checkColloNumbersArray($colloNumbersArray);
                $colloNumbersString = implode("\n", $colloNumbersArray);

                $this->getLayout()->getBlock('shipping.trackandtrace')->assign('colloNumbers', $colloNumbersString);
                $this->getLayout()->getBlock('shipping.trackandtrace')->assign('colloNumbersArray', $colloNumbersArray);
                $results = $helper->fetchResultsForColloNumbers($colloNumbersArray);
                $results = $this->_handleResults($results);
                $shouldHaveResults = true;
            } elseif ($this->getRequest()->isPost() && $this->getRequest()->getParam('search')) {
                $colloNumbersString = $this->getRequest()->getPost('colloNumbers');
                $this->getLayout()->getBlock('shipping.trackandtrace')->assign('colloNumbers', $colloNumbersString);
                if (isset($colloNumbersString)) {
                    $colloNumbersArray = $this->_extractColloNumbersFromPostParam($colloNumbersString);
                    $this->checkColloNumbersArray($colloNumbersArray);
                    $this->getLayout()->getBlock('shipping.trackandtrace')->assign('colloNumbersArray', $colloNumbersArray);
                    $results = $helper->fetchResultsForColloNumbers($colloNumbersArray);
                    $results = $this->_handleResults($results);
                    $shouldHaveResults = true;
                }
            } else {
                $shouldHaveResults = false;
                $this->getLayout()->getBlock('shipping.trackandtrace')->assign('badResults', false);
                $results = null;
                $colloNumbersArray = array();
                $this->getLayout()->getBlock('shipping.trackandtrace')->assign('colloNumbers', null);
            }

            $foundColloOnce = null;
            $helper->reorgResult($results,$colloNumbersArray,$foundColloOnce);

            if ($shouldHaveResults && !$foundColloOnce) {
                // Removed temporary, because of hotfix:
                //$this->getLayout()->getBlock('shipping.trackandtrace')->assign('badResults', 1003);
            }

            $this->getLayout()->getBlock('shipping.trackandtrace')->assign('results', $results);
        } catch (Exception $e) {
            $this->getLayout()->getBlock('shipping.trackandtrace')->assign('badResults', 1002);
            $this->getLayout()->getBlock('shipping.trackandtrace')->assign('results', array());
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
        $this->renderLayout();
    }


    private function _handleResults($results) {
        if ($results !== null) {
            if (isset($results->shipmentList) && is_object($results->shipmentList) && isset($results->shipmentList->Shipment)) {
                $this->getLayout()->getBlock('shipping.trackandtrace')->assign('badResults', false);
            } else {
                $results = null;
                $this->getLayout()->getBlock('shipping.trackandtrace')->assign('badResults', 1001);
            }
        }
        return $results;
    }
    
    /**
     * extracts the collo numbers array from the post param (textarea) with one number per line
     * 
     * @param string $colloNumbers
     * @return array
     */
    private function _extractColloNumbersFromPostParam($colloNumbers) {
        $colloNumbers = preg_split('/\\r?\\n/', $colloNumbers);
        if (!is_array($colloNumbers))
            return null;
        return $colloNumbers;
    }

    private function checkColloNumbersArray(&$array) {
        if ( count($array) > 50 ) {
            throw new Exception('maximum number of collos exceeded');
        }
    }
}
?>