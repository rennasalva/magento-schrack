<?php

class Schracklive_SchrackCatalogInventory_Block_Adminhtml_Stock_Edit_Form extends Mage_Adminhtml_Block_Widget_Form {

    const DATE_TIME_FORMAT = 'yyyy-MM-dd HH:mm:ss';

    protected function _prepareForm() {
        if (Mage::getSingleton('adminhtml/session')->getExampleData()) {
            $data = Mage::getSingleton('adminhtml/session')->getExamplelData();
            Mage::getSingleton('adminhtml/session')->getExampleData(null);
        } elseif (Mage::registry('stock_data')) {
            $data = Mage::registry('stock_data')->getData();
        } else {
            $data = array();
        } 
        $form = new Varien_Data_Form(array(
            'id' => 'edit_form', 
            'action' => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))), 
            'method' => 'post', 
            'enctype' => 'multipart/form-data',));
        $form->setUseContainer(true);
        $this->setForm($form);
        $fieldset = $form->addFieldset('stock_form', array('legend' => Mage::helper('schrackcataloginventory')->__('Stock Information')));
        
        $fieldset->addField('stock_number', 'text', array(
            'label'    => Mage::helper('schrackcataloginventory')->__('Number'), 
            'class'    => 'validate-digits',
            'required' => true,
            'name'     => 'stock_number',));

        $fieldset->addField('stock_location', 'text', array(
            'label'    => Mage::helper('schrackcataloginventory')->__('Vendor'),
            'required' => false,
            'name'     => 'stock_location',));

        $fieldset->addField('stock_name', 'text', array(
            'label'    => Mage::helper('schrackcataloginventory')->__('Stock Name'),
            'class'    => 'required-entry', 
            'required' => true, 
            'name'     => 'stock_name',));

        $fieldset->addField('is_pickup', 'checkbox', array(
            'label'   => Mage::helper('schrackcataloginventory')->__('Is Pickup'), 
            'onclick' => 'this.value = this.checked ? 1 : 0;', 
            'name'    => 'is_pickup',))->setIsChecked(!empty($data['is_pickup']));
        
        $fieldset->addField('is_delivery', 'checkbox', array(
            'label'   => Mage::helper('schrackcataloginventory')->__('Is Delivery'), 
            'onclick' => 'this.value = this.checked ? 1 : 0;', 
            'name'    => 'is_delivery',))->setIsChecked(!empty($data['is_delivery']));
        
        $fieldset->addField('delivery_hours', 'text', array(
            'label'    => Mage::helper('schrackcataloginventory')->__('Delivery Hours'), 
            'class'    => 'validate-digits',
            'required' => true, 
            'name'     => 'delivery_hours',));

        $dateFormatIso = self::DATE_TIME_FORMAT; // Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $fieldset->addField('locked_until', 'date', array(
            'label'        => Mage::helper('schrackcataloginventory')->__('Locked Until'),
            'class'        => 'validate-date',
            'required'     => false,
            'name'         => 'locked_until',
            'input_format' => $dateFormatIso,
            'format'       => $dateFormatIso,
            'time'         => true,
            'image'     =>    $this->getSkinUrl('images/grid-cal.gif')
        ));

        $form->setValues($data);
        return parent::_prepareForm();
    }

}

