<?php
class Anowave_Ec_Block_System_Google_Auth extends Mage_Adminhtml_Block_System_Config_Form_Field
{
	/**
	 * Use API 
	 * 
	 * @var boolean
	 */
	private $use = true;
	
	/**
	 * Google Tag Manager API
	 * 
	 * @var Anowave_Ec_Model_Api
	 */
	private $api = null;

    protected function _construct()
    {
    	if ($this->use)
    	{
    		set_time_limit(360);
    		
    		set_include_path(get_include_path() . PATH_SEPARATOR . '/lib/Google');
    		
    		/**
    		 * Set custom template
    		 */
    		$this->setTemplate('ec/system/google/auth.phtml');
    	}
    }
    
    public function getApi()
    {
    	if (!$this->api)
    	{
    		$this->api = Mage::getModel('ec/api');
    	}
    	
    	return $this->api;
    }

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setNamePrefix($element->getName())->setHtmlId($element->getHtmlId());
        
        return $this->_toHtml();
    }
}