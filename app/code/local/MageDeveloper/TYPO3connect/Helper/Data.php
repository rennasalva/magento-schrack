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

class MageDeveloper_TYPO3connect_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * Import Tree Root Name
	 * @var string
	 */
	const TREE_ROOT_NAME = 'TYPO3';
	
	/**
	 * getTypo3UrlByUid
	 * Gets the TYPO3 Url by a given uid
	 * 
	 * @param int $uid UID of the page
	 * @param array $params Parameters
	 * @return string
	 */
	public function getTypo3UrlByUid($uid, $params = array())
	{
		$url = $this->getTypo3BaseUrl()."/?id=".$uid;
		
		$parameter = "";
		
		if (!empty($params)) 
		{
		
			foreach ($params as $_param=>$_value) 
			{
				$parameter .= '&'.$_param.'='.$_value;
			}
		}

		$url = $url.$parameter;
		return $url;
	}
	
	
	/**
	 * getRootName
	 * Gets the root name of the 
	 * TYPO3 Pages Tree
	 * 
	 * @return string
	 */
	public function getRootName()
	{
		return self::TREE_ROOT_NAME;
	}

	/**
	 * getTypo3BaseUrl
	 * Gets the configuration of the
	 * typo3 base url
	 * 
	 * @return string
	 */
	public function getTypo3BaseUrl()
	{
		$url = Mage::getStoreConfig(MageDeveloper_TYPO3connect_Helper_Config::XML_PATH_TYPO3_BASE_URL, Mage::app()->getStore());
		$url = trim($url,'/');
		return $url;
	}
	
	/**
	 * getSharedKey
	 * Gets the shared key for
	 * encryption/decryption
	 * 
	 * @return string
	 */
	public function getSharedKey()
	{
		return Mage::getStoreConfig(MageDeveloper_TYPO3connect_Helper_Config::XML_PATH_SHARED_KEY, Mage::app()->getStore());
	}
	
	/**
	 * canShowLoginTemplates
	 * Gets configuration and returns if
	 * a login template can be shown or not
	 * 
	 * @return bool
	 */
	public function canShowLoginTemplate()
	{
		$setting = Mage::getStoreConfig(MageDeveloper_TYPO3connect_Helper_Config::XML_PATH_SHOW_LOGIN_TEMPLATE, Mage::app()->getStore());
		
		if ($setting == true) {
			return true;
		}
		return false;
	}
	
	/**
	 * getPagesListUrl
	 * Get the url where the xml_adapter
	 * xml sitemap is located
	 * 
	 * @return string
	 */
	public function getPagesListUrl()
	{
		return Mage::getStoreConfig(MageDeveloper_TYPO3connect_Helper_Config::XML_PATH_ADAPTER_PAGES_LIST_URL, Mage::app()->getStore());
	}
	
	/**
	 * getTypeNum
	 * Gets the type num where the xml_adapter
	 * puts out its xml page information
	 * Default is 777
	 * 
	 * @return string
	 */
	public function getTypeNum()
	{
		return Mage::getStoreConfig(MageDeveloper_TYPO3connect_Helper_Config::XML_PATH_ADAPTER_TYPE_NUM, Mage::app()->getStore());
	}
	

	
	
	/**
	 * secGP
	 * Safes a string and returns the
	 * safer version
	 * 
	 * @param string $string
	 * @return string
	 */
	public function secGP($var)
	{
		if(empty($var)) return;

		if (isset($var)) {
			if (is_array($var)) {
				$this->secGPArray($var); 
			} else {
				$var = stripslashes($var); 
			}
		}
		return $var;
	}	
	
	/**
	 * secGPArray
	 * Safes an array and returns the
	 * safer version
	 * 
	 * @param array $arr
	 * @return array
	 */
	private function secGPArray($arr) 
	{
		if (is_array($arr)) {
			
			reset($arr);
			
			while(list($Akey,$AVal)=each($arr)) {
				if (is_array($AVal)) {
					$this->secGPArray($arr[$Akey]);
				} else {
					$arr[$Akey] = stripslashes($AVal);
				}
			}
			reset($arr);
		}		
	}
	
	/**
	 * createCodeFromValue
	 * Creates unified code from a given value
	 * 
	 * @param string $value Value to generate code of
	 * @return string
	 */
	public function createCodeFromValue($value)
	{ 
		$value = strtolower($value);
		$value = str_replace(" ","_", $value);
		$removable_values = array(
											";" 	=> 	"_", 
											":" 	=> 	"_",
											"/" 	=> 	"_",
											"."		=>	"_",
											"ö" 	=> 	"oe",
											"ä" 	=> 	"ae",
											"ü" 	=> 	"ue",
											"," 	=> 	"_",
											"__" 	=> 	"_",
											"___" 	=> 	"_",
		);
				
		$value = preg_replace('/[^a-zA-Z0-9_]/u', '_', $value);
		$value = strtr($value, $removable_values); 
		
		return $value;	
	}	
	
}
