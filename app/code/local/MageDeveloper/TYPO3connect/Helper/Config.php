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

class MageDeveloper_TYPO3connect_Helper_Config extends Mage_Core_Helper_Abstract
{
	/**
	 * XML Configuration Paths
	 * @var string
	 */
	const XML_PATH_SHARED_KEY					= 'typo3connect/t3general/decryption_key';
	const XML_PATH_SHOW_LOGIN_TEMPLATE			= 'typo3connect/t3general/show_login';
	
	const XML_PATH_TYPO3_BASE_URL				= 'typo3connect/xml_adapter/typo3_baseurl';
	const XML_PATH_ADAPTER_PAGES_LIST_URL		= 'typo3connect/xml_adapter/pages_list_url';
	const XML_PATH_ADAPTER_TYPE_NUM				= 'typo3connect/xml_adapter/typenum';
}
