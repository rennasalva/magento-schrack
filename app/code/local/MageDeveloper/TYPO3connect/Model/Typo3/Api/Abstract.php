<?php
/**
 * MageDeveloper TYPO3connect Module
 * ---------------------------------
 *
 * @category    Mage
 * @package    MageDeveloper_TYPO3connect
 * @copyright   Magento Developers / magedeveloper.de <kontakt@magedeveloper.de>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class MageDeveloper_TYPO3connect_Model_Typo3_Api_Abstract extends Varien_Object
{
	/**
	 * Url
	 * @var string
	 */
	protected $url;
	
	/**
	 * Type Num
	 * @var string
	 */
	protected $type_num;
	
	/**
	 * Additional Params
	 * @var array
	 */
	protected $additional_params = array();
	
	/**
	 * XML Contents
	 * @var Varien_Simplexml_Config
	 */
	protected $xml;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * setTypeNum
	 * Set the TYPO3 typenum for getting
	 * xml data
	 * 
	 * @param string $typeNum TypeNum
	 * @return self
	 */
	public function setTypeNum($typeNum) 
	{
		$this->type_num = $typeNum;
		$this->additional_params['type'] = $typeNum;
		return $this;
	}

	/**
	 * call
	 * Request to xml and store information
	 */
	public function call()
	{
		if ($url = $this->getUrl()) {
			
			try {
				$contents = file_get_contents( $this->getUrl() );
				$this->xml = new Varien_Simplexml_Config($contents);
			} catch (Exception $e) {
				throw $e;
			}
			
		} else {
			Mage::throwException(Mage::helper('typo3connect')->__('Please set up an TYPO3 Base Url.'));
		}	
	}

	/**
	 * getUrl
	 * Gets the url for the request
	 * 
	 * @param 
	 */
	public function getUrl()
	{
		$parameter = '';
		foreach ($this->additional_params as $_param=>$_value) {
			$parameter .= '&'.$_param.'='.$_value;
		}

		return $this->getData('url') . $parameter;	
	}
	
	/**
	 * getXml
	 * Gets the Varien SimpleXML Config instance
	 * 
	 * @return Varien_Simplexml_Config
	 */
	public function getXml()
	{
		return $this->xml;
	}
	
	/**
	 * getArray
	 * Gets the whole xml data as array
	 */
	public function getArray()
	{
		
		
	}
	
	
	
}
