<?php
class Anowave_Ec_Block_Ab_Edit_Tabs_General extends Mage_Adminhtml_Block_Widget_Form 
{
	/**
     * Prepare form before rendering HTML
     *
     * @return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $form->setUseContainer(false);
        $this->setForm($form);
        
        $fieldset = $form->addFieldset('ab_form', array
        (
            'legend'	=> Mage::helper('ec')->__('Experiment'),
            'class'		=> 'fieldset-wide'
        ));
        
        $fieldset->addField('store_id', 'multiselect', array
        (
        	'name' 		=> 'stores[]',
        	'label' 	=> Mage::helper('ec')->__('Store View'),
        	'title' 	=> Mage::helper('ec')->__('Store View'),
        	'required' 	=> true,
        	'values' 	=> Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true)
        ));
        

        $fieldset->addField('ab_experiment', 'text', array
        (
            'name'      			=> 'ab_experiment',
            'after_element_html'	=> '<p class="note">(up to 255 chars.)</p>',
            'label'     			=> Mage::helper('ec')->__('Experiment'),
            'title'     			=> Mage::helper('ec')->__('Experiment'),
            'required'  			=> true
        ));
        
        $fieldset->addField('ab_experiment_theme', 'select', array
        (
        	'name'      			=> 'ab_experiment_theme',
        	'after_element_html'	=> '<p class="note">Theme change may affect Google rankings. It is recommended to experiment with identical themes which differ in terms of CSS/JS</p>',
        	'label'     			=> Mage::helper('ec')->__('Experiment theme'),
        	'title'     			=> Mage::helper('ec')->__('Experiment theme'),
        	'values'				=> Mage::getSingleton('core/design_source_design')->getAllOptions(),
        	'required'  			=> false
        ));

        if (Mage::registry('ab')) 
        {
            $form->setValues
            (
            	Mage::registry('ab')->getData()
            );
        }

        return parent::_prepareForm();
    }
}