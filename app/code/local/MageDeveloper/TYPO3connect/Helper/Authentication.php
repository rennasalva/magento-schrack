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

// Standard decryption key
define("STANDARD_KEY", "m((=k.42/jnK)?##21.,--HH");

class MageDeveloper_TYPO3connect_Helper_Authentication extends Mage_Core_Helper_Abstract
{
	/**
	 * Array for user credentials
	 * @var array
	 */
	private $_userCredentials = array();
	
	/**
	 * __construct
	 * Constructor
	 */
	public function __construct()
	{
		
	}
	
	/**
	 * _getDecryptionKey
	 * Gets the decryption key from store config
	 * 
	 * @return string
	 */
	protected function _getDecryptionKey()
	{
		$decryptionKey = Mage::helper('typo3connect')->getSharedKey();
		
		if (!$decryptionKey || $decryptionKey == NULL || empty($decryptionKey)) {
			return STANDARD_KEY;
		}
		return $decryptionKey;
	}
	
	/**
	 * init
	 * Initialize Values
	 * 
	 * @param string $username
	 * @param string $password
	 */
	public function init($username, $password)
	{
		// Check user data		
		if ($this->_checkLogin($username, $password))
		{
			return $this->_userCredentials;
		}
		return false;
	}
	
	/**
	 * _checkLogin
	 * Checks if the login credentials are
	 * matching needed layout
	 * 
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	protected function _checkLogin($username, $password)
	{
		$pattern = '/^(("[\w-\s]+")|([\w-]+(?:\.[\w-]+)*)|("[\w-\s]+")([\w-]+(?:\.[\w-]+)*))(@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i';

		if( preg_match($pattern, $username) ) {
			
			$this->_userCredentials['username'] = $username;
			$this->_userCredentials['password'] = $password;
			return true;
		}
		return false;
	}
	
	/**
	 * getEncrypted
	 * Returns an encrypted string
	 * 
	 * @param string $string
	 * @return string
	 */
	public function getEncrypted($string)
	{
		return $this->_encrypt($string);
	}

	/**
	 * _encrypt
	 * Decrypts a string
	 * 
	 * @param string $string
	 * @return string
	 */
	protected function _encrypt($string)
	{
		$string = trim($string);
		$key = $this->_getDecryptionKey();
		
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
		$passcrypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_ECB, $iv);
		$encode = base64_encode($passcrypt);
		return $encode;
	}	

	
	/**
	 * _decrypt
	 * Decrypts a string
	 * 
	 * @param string $encString
	 * @return string
	 */
	protected function _decrypt($encString)
	{
		$key = $this->_getDecryptionKey();
		
		$decoded = base64_decode($encString);
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
		$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_ECB, $iv);
		return $decrypted;		
	}
	
	
	
	
}