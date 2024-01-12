<?php

class Schracklive_SchrackCatalogInventory_Adminhtml_StockController extends Mage_Adminhtml_Controller_Action {

    public function indexAction() {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function newAction() {
        $this->_forward('edit');
    }

    public function editAction() { 
        $id = $this->getRequest()->getParam('id', null);
        $model = Mage::getModel('cataloginventory/stock');
        if ( $id ) {
            $model->load((int)$id);
            if ( $model->getId() ) {
                $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
                if ( $data ) {
                    $model->setData($data)->setId($id);
                }
            } else {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('schrackcataloginventory')->__('Stock does not exist'));
                $this->_redirect('*/*/');
            }
        } 
        Mage::register('stock_data', $model);
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->renderLayout();
    }    
 
    public function saveAction() { 
        $data = $this->getRequest()->getPost();
        if ( $data ) {
            $model = Mage::getModel('cataloginventory/stock');
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
            }

            $deliveryHours = $data['delivery_hours'];
            $errMsg = null;
            $errVal = null;
            if ( isset($deliveryHours) && ! $this->_isDeliveryHoursValid($deliveryHours) ) {
                $errMsg = 'Invalid Delivery Hours'; $errVal = $deliveryHours;
            }
            if ( isset($errMsg) ) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('schrackcataloginventory')->__($errMsg) . ' ' . $errVal);
                $this->_redirect('*/*/edit', array('id' => $model->getId()));
                return;
            }
            
            $model->setData($data);
            $model->setIsDelivery(!empty($data['is_delivery']));
            $model->setIsPickup(!empty($data['is_pickup']));
            Mage::getSingleton('adminhtml/session')->setFormData($data);
            try {
                if ($id) {
                    $model->setId($id);
                }
                $model->save();
                if (!$model->getId()) {
                    Mage::throwException(Mage::helper('schrackcataloginventory')->__('Error saving stock'));
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('schrackcataloginventory')->__('Stock was successfully saved.'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                // The following line decides if it is a "save" or "save and continue"                 
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                } else {
                    $this->_redirect('*/*/');
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                if ($model && $model->getId()) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                } else {
                    $this->_redirect('*/*/');
                }
            }
            return;
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('schrackcataloginventory')->__('No data found to save'));
        $this->_redirect('*/*/');
    }        
    
    public function deleteAction() {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = Mage::getModel('cataloginventory/stock');
                $model->setId($id);
                $model->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('schrackcataloginventory')->__('The stock has been deleted.'));
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        } Mage::getSingleton('adminhtml/session')->addError(Mage::helper('schrackcataloginventory')->__('Unable to find the stock to delete.'));
        $this->_redirect('*/*/');
    }

    private function _isStockNumberValid ( $stockNumber, $model, $id ) {
        if ( ! is_numeric($stockNumber) )
            return false;
        if ( $stockNumber < 1 )
            return false;
        return true;
    }

    
    private function _isDeliveryHoursValid ( $deliveryHours ) {
        if ( ! is_numeric($deliveryHours) )
            return false;
        if ( $deliveryHours < 0 )
            return false;
        return true;
    }

    // This is a necessary ACL-adaption for the security update SUPEE-6285:
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/stocks');
    }
}

?>
