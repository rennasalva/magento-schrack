<?php

class Schracklive_SchrackCustomer_Model_Customer extends Mage_Customer_Model_Customer {

    const EXCEPTION_ACCOUNT_DISABLED       = 104;
    const CUSTOMER_SUDO_GROUP_ID           = 4;
	const NO_CONTACT_NUMBER                = 9999999;

    /**#@+
     * Configuration pathes for email templates and identities
     */
    const XML_PATH_REGISTER_EMAIL_TEMPLATE     = 'customer/create_account/email_template';
    const XML_PATH_REGISTER_EMAIL_IDENTITY     = 'customer/create_account/email_identity';
    //const XML_PATH_REMIND_EMAIL_TEMPLATE       = 'customer/password/remind_email_template';
    const XML_PATH_REMIND_EMAIL_TEMPLATE       = 'customer/password/forgot_email_template';
    const XML_PATH_FORGOT_EMAIL_TEMPLATE       = 'customer/password/forgot_email_template';
    const XML_PATH_RESET_EMAIL_TEMPLATE        = 'customer/password/reset_email_template';
    const XML_PATH_FORGOT_EMAIL_IDENTITY       = 'customer/password/forgot_email_identity';
    const XML_PATH_DEFAULT_EMAIL_DOMAIN        = 'customer/create_account/email_domain';
    const XML_PATH_IS_CONFIRM                  = 'customer/create_account/confirm';
    const XML_PATH_CONFIRM_EMAIL_TEMPLATE      = 'customer/create_account/email_confirmation_template';
    const XML_PATH_CONFIRMED_EMAIL_TEMPLATE    = 'customer/create_account/email_confirmed_template';
    const XML_PATH_GENERATE_HUMAN_FRIENDLY_ID  = 'customer/create_account/generate_human_friendly_id';
    /**#@-*/

	/**
	 * The customer's account
	 *
	 * @var Schracklive_Account_Model_Account
	 */
	protected $_account;

	/**
	 * The customer's additional advisors
	 *
	 * @var array
	 */
	protected $_advisors;

	/**
	 * The system contact of the customer's account
	 *
	 * @var Mage_Customer_Model_Customer
	 */
	protected $_systemContact;

	/**
	 * The customer's primary advisor
	 *
	 * @var Mage_Customer_Model_Customer
	 */
	protected $_advisor;

	private $_newAccountMailType = '';

	function _construct() {
		parent::_construct();
		$this->setSchrackAclRoleId(Mage::helper('schrack/acl')->getAnonymousRoleId());
	}

	protected function _beforeSave() {
		// If we got no ACL info at this point set to default
		if ($this->getSchrackAclRoleId() == Mage::helper('schrack/acl')->getAnonymousRoleId()) {
			$this->setSchrackAclRoleId(Mage::helper('schrack/acl')->getDefaultRoleId());
		}
		if (!$this->getSchrackSalutatory()) {
			$this->setSchrackSalutatory($this->_buildDefaultSalutatory(Mage::getStoreConfig('schrack/translation/codes')));
		}
		parent::_beforeSave();
	}

	/**
	 * @param string $locale
	 * @return string
	 */
	protected function _buildDefaultSalutatory($locale) {
		$format = array();
		$format['de_DE']['1'] = 'Sehr geehrter Herr %title% %last_name%';
		$format['de_DE']['2'] = 'Sehr geehrte Frau %title% %last_name%';
		$format['de_AT']['1'] = 'Sehr geehrter Herr %title% %last_name%';
		$format['de_AT']['2'] = 'Sehr geehrte Frau %title% %last_name%';
		$format['en_US']['1'] = 'Dear Mr. %last_name%';
		$format['en_US']['2'] = 'Dear Ms. %last_name%';
		$format['hr_HR']['1'] = 'Poštovani gospodine %title% %last_name%';
		$format['hr_HR']['2'] = 'Poštovana gospodo %title% %last_name%';
		$format['fr_FR']['1'] = 'Monsieur %last_name%';
		$format['fr_FR']['2'] = 'Madame %last_name%';
		$format['nl_NL']['1'] = 'Geachte heer %last_name%';
		$format['nl_NL']['2'] = 'Geachte mevrouw %last_name%';
		$format['ro_RO']['1'] = 'Stimate domnule %last_name%';
		$format['ro_RO']['2'] = 'Stimata doamna %last_name%';
		$format['sr_RS']['1'] = 'Poštovani gospodine %title% %last_name%';
		$format['sr_RS']['2'] = 'Poštovana gospodo %title% %last_name%';
		$format['sl_SI']['1'] = 'Spoštovani gospod %title% %last_name%';
		$format['sl_SI']['2'] = 'Spoštovana gospa %title% %last_name%';
		$format['cs_CZ']['1'] = 'Vážený Pane %title% %last_name%';
		$format['cs_CZ']['2'] = 'Milá Paní %title% %last_name%';
		$format['hu_HU']['1'] = 'Tisztelt %title% %last_name% Úr!';
		$format['hu_HU']['2'] = 'Tisztelt %title% %last_name% Asszony!';
		$format['sk_SK']['1'] = 'Vážený pán %title% %last_name%';
		$format['sk_SK']['2'] = 'Vážená pani %title% %last_name%';
		$format['bg_BG']['1'] = 'Уважаеми господин %last_name%';
		$format['bg_BG']['2'] = 'Уважаема госпожо %last_name%';
		$format['bs_BA']['1'] = 'Poštovani gospodine %title% %last_name%';
		$format['bs_BA']['2'] = 'Poštovaai gospođo %title% %last_name%';
        $format['pl_PL']['1'] = 'Szanowny Panie %title% %last_name%';
        $format['pl_PL']['2'] = 'Szanowna Pani %title% %last_name%';
        $format['ru_RU']['1'] = "Уважаеми господин %last_name%";
        $format['ru_RU']['2'] = "Уважаема госпожо %last_name%";
        $format['ar_SA']['1'] = "Dear Mr. %last_name%";
        $format['ar_SA']['2'] = "Dear Ms. %last_name%";

		if (!isset($format[$locale][$this->getGender()])) {
			return '';
		}

		$salutation = $format[$locale][$this->getGender()];

		$salutation = str_replace('%title%', $this->getPrefix(), $salutation);
		$salutation = str_replace('%first_name%', $this->getFirstname(), $salutation);
		$salutation = str_replace('%last_name%', $this->getLastname(), $salutation);
		$salutation = str_replace('  ', ' ', $salutation);

		return $salutation;
	}

	/**
	 * @param string $login
	 * @param string $password
	 * @return bool
	 * @throws Exception|Mage_Core_Exception
	 */
	public function authenticate($login, $password) {
		try {
			$this->_authenticate($login, $password);
            if ( ! $this->getIsActive() ) {
                throw Mage::exception('Mage_Core', Mage::helper('customer')->__('This account is inactive.'),
                    self::EXCEPTION_ACCOUNT_DISABLED
                );
            }
		} catch (Exception $e) {
			if (Mage::getStoreConfig('schrackdev/development/test')) {
				throw Mage::exception('Mage_Core', 'AUTHENTICATION ERROR: '.$e->getMessage(), $e->getCode(), $e);
			} else {
				throw $e;
			}
		}
		return true;
	}

	/**
	 * @param string $login
	 * @param string $password
	 * @throws Mage_Core_Exception
	 */
	protected function _authenticate($login, $password) {
		Mage::dispatchEvent('schrack_customer_customer_authenticate', array(
			'model' => $this,
			'login' => $login,
			'password' => $password,
		));

		if ($this->getIsAuthenticated()) {
			if ($this->getConfirmation() && $this->isConfirmationRequired()) {
				Mage::helper('schrack/logger')->authentication("Account '{$login}' is not confirmed.");
				throw Mage::exception('Mage_Core', Mage::helper('customer')->__('This account is not confirmed.'), self::EXCEPTION_EMAIL_NOT_CONFIRMED
				);
			}
			Mage::dispatchEvent('customer_customer_authenticated', array(
				'model' => $this,
				'password' => $password,
				'type' => 'external',
			));
		} else {
			try {
				parent::authenticate($login, $password);
			} catch (Mage_Core_Exception $e) {
				if ($e->getCode() == self::EXCEPTION_INVALID_EMAIL_OR_PASSWORD) {
					Mage::dispatchEvent('schrack_customer_customer_not_authenticated', array(
						'model' => $this,
						'login' => $login,
						'password' => $password,
					));
					if ($this->getRedirectUrl()) {
						Mage::helper('schrack/logger')->authentication("Account '{$login}' will be redirected to {$this->getRedirectUrl()}.");
						Mage::getSingleton('customer/session')->setBeforeAuthUrl($this->getRedirectUrl());
					} else {
						throw $e;
					}
				} else {
					throw $e;
				}
			}
		}
	}

	/**
	 *
	 * @param string $wwsCustomerId
	 * @param int $wwsContactNumber
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 */
	public function loadByWwsContactNumber($wwsCustomerId, $wwsContactNumber) {
		return $this->_loadByMethod('loadByWwsContactNumber', $wwsCustomerId, $wwsContactNumber);
	}
	
	/**
	 *
	 * @param string $wwsCustomerId
	 * @param string $emailAddress
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 */
	public function loadByEmailAddress($wwsCustomerId, $emailAddress) {
		return $this->_loadByMethod('loadByEmailAddress', $wwsCustomerId, $emailAddress);
	}

    /**
     * load by email address only (which we know to be unique)
     * @param $wwsCustomerId
     * @param $emailAddress
     * @return Schracklive_SchrackCustomer_Model_Customer
     */
    public function loadByEmailAddressOnly($emailAddress) {
        return $this->_loadByMethod('loadByEmailAddressOnly', $emailAddress);
    }

	/**
	 *
	 * @param int $accountId
	 * @param int $wwsContactNumber
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 */
	public function loadByAccountContactNumber($accountId, $wwsContactNumber) {
		return $this->_loadByMethod('loadByAccountContactNumber', $accountId, $wwsContactNumber);
	}

	/**
	 *
	 * @param string $userPrincipalName
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 */
	public function loadByUserPrincipalName($userPrincipalName) {
		return $this->_loadByMethod('loadByUserPrincipalName', $userPrincipalName);
	}

    /**
     * danger will robinson danger: this will return several records...
     *
     * @param $wwsCustomerId
     */
    public function loadByWwsCustomerId($wwsCustomerId) {
        $collection = $this->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('schrack_wws_customer_id', $wwsCustomerId);
        return $collection;
    }

	public function loadByS4sNickname ( $s4sNickname ) {
		return $this->_loadByMethod('loadByS4sNickname',$s4sNickname);
	}

	public function loadByS4sId ( $sisId ) {
        return $this->_loadByMethod('loadByS4sId',$sisId);
    }

	/**
	 * a copy of load()
	 *
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 */
	protected function _loadByMethod(/* methodName, arg1, ... */) {
		$params = func_get_args();
		$methodName = array_shift($params);
		array_unshift($params, $this);
		// $this->_beforeLoad($params); // not working - why?
		call_user_func_array(array($this->_getResource(), $methodName), $params);
		$this->_afterLoad();
		$this->setOrigData();
		$this->_hasDataChanges = false;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getName() {
		if ($this->isSystemContact()) {
			return $this->getAccount()->getName(true);
		}
		return parent::getName();
	}

	/**
	 * @return string
	 */
	public function getSalutation() {
		if ($this->isSystemContact()) {
			return '';
		}

		$salutation = '';
		switch ($this->getGender()) {
			case 1:
				$salutation = 'Mr.';
				break;
			case 2:
				$salutation = 'Ms.';
		}
		return Mage::helper('schrackcustomer')->__($salutation);
	}

	/**
	 * @return int
	 */
	public function getDefaultBilling() {
		if ($this->isRealContact() || $this->isProspect()) {
			return $this->getSystemContact()->getDefaultBilling();
		} else {
			return $this->getData('default_billing');
		}
	}

	/**
	 * @return int
	 */
	public function getDefaultShipping() {
		if ($this->isRealContact() || $this->isProspect()) {
			return $this->getSystemContact()->getDefaultShipping();
		} else {
			return $this->getData('default_shipping');
		}
	}

	/**
	 * Get customer default billing address
	 *
	 * @return Mage_Customer_Model_Address
	 */
	public function getPrimaryBillingAddress() {
		$primaryAddress = $this->getAddressItemById($this->getDefaultBilling());

		return $primaryAddress ? $primaryAddress : false;
	}

	/**
	 * Get default customer shipping address
	 *
	 * @return Mage_Customer_Model_Address
	 */
	public function getPrimaryShippingAddress() {
		$primaryAddress = $this->getAddressItemById($this->getDefaultShipping());

		return $primaryAddress ? $primaryAddress : false;
	}

    //
    public function getEmailAddress4wws() {
       $fake = Mage::getStoreConfig('schrackdev/development/override_email4wws');
        if ( isset($fake) && $fake > '' ) {
            return $fake;
        }
        return $this->getEmailAddress();
    }

	/**
	 * Get email address for sending an email to the customer.
	 * Will use the account's address for system contacts.
	 * Will use the additional email field for inactive contacts.
	 *
	 * @return string
	 */
	public function getEmailAddress() {
		if ($this->isSystemContact()) {
			return $this->getAccount()->getEmail();
		} elseif ($this->isInactiveContact() || $this->isDeletedContact()) {
			list($email) = explode(',', $this->getSchrackEmails());
			return $email;
        }
		return $this->getData('email');
	}

	/**
	 * @param Schracklive_Account_Model_Account $account
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 */
	public function setAccount(Schracklive_Account_Model_Account $account) {
		$this->_account = $account;
		$this->setSchrackAccountId($account->getId());
		$this->setSchrackWwsCustomerId($account->getWwsCustomerId());

		return $this;
	}

	/**
	 * @param $wwsCustomerId
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 */
	public function setAccountByWwsCustomerId($wwsCustomerId) {
		if (empty($this->_account)) {
			$this->_account = Mage::getModel('account/account');
		}
		$this->_account->loadByWwsCustomerId($wwsCustomerId);
		$this->setSchrackAccountId($this->_account->getId());
		$this->setSchrackWwsCustomerId($wwsCustomerId);

		return $this;
	}

	/**
	 * @throws Schracklive_SchrackCustomer_Exception|Mage_Core_Exception
	 * @return Schracklive_Account_Model_Account
	 */
	public function getAccount() {
	    $wwsSchrackWWSID     = 'Schrack-WWS-ID = false';
	    $wwsSchrackAccountID = 'Schrack-Account-ID = false';

		if (empty($this->_account)) {
			if ($this->getSchrackAccountId()) {
                $wwsSchrackAccountID = ' Schrack-Account-ID = ' . $this->getSchrackAccountId();
				$this->_account = Mage::getModel('account/account')->load($this->getSchrackAccountId());
			} elseif ($this->getSchrackWwsCustomerId()) {
				// this is a fallback, should not be required in production
                $wwsSchrackWWSID = ' Schrack-WWSt-ID = ' . $this->getSchrackWwsCustomerId();
				$this->_account = Mage::getModel('account/account')->loadByWwsCustomerId($this->getSchrackWwsCustomerId());
				$this->setSchrackAccountId($this->_account->getId());
			} else {
			    // Mage::log('Contact has neither account id nor WWS customer id. #013876', null, 'customer_error.log');
				// throw Mage::exception('Schracklive_SchrackCustomer', 'Contact has neither account id nor WWS customer id.');
				return null;
			}
		}
		if (!$this->_account->getId()) {
			throw Mage::exception('Schracklive_SchrackCustomer', 'Contact has no account.' . $wwsSchrackWWSID . $wwsSchrackAccountID);
		}
		return $this->_account;
	}

	/**
	 * Get the customer model of the primary advisor.
	 * For system contacts the method will return the advisor of the account.
	 *
	 * @return Schracklive_SchrackCustomer_Model_Customer	false if no principal name is set, null if the customer model couln't be loaded
	 */
	public function getAdvisor() {
    	if ( is_null($this->_advisor) ) {
            if ( $this->isSystemContact() ) {
                $principalName = $this->getAccount()->getAdvisorPrincipalName();
            } else {
                $principalName = $this->getSchrackAdvisorPrincipalName();
            }
            $this->_advisor = self::getAdvisorForPrincipalName($principalName);
        }
        return $this->_advisor;
	}

	// For re-use in account:
	public static function getAdvisorForPrincipalName ( $principalName ) {
		if ( ! $principalName ) {
			return false;
		}

		// 1. cut away bloody id from S4Y
		// a.smolka@at.schrack.lan/11f148d1-0555-7650-a26f-535e7b89e387
		if ( ($p = strpos($principalName,'/')) !== false ) {
			$principalName = substr($principalName,0,$p);
		}

        // 2. Try to get advisor over the old name:
        $advisor = Mage::getModel('customer/customer')->loadByUserPrincipalName($principalName);
        if ( $advisor->getId() ) {
            return $advisor;
        }

        // 3. Try to resolve advisor with .com if not found in customer-table with old name:
        $country = strtolower(Mage::getStoreConfig('schrack/ad/country') ? Mage::getStoreConfig('schrack/ad/country') : Mage::getStoreConfig('schrack/general/country'));
        $principalName = str_replace($country . '.schrack.lan', 'schrack.com', $principalName);
        $advisor = Mage::getModel('customer/customer')->loadByUserPrincipalName($principalName);
        if ( $advisor->getId() ) {
            return $advisor;
        }

        // 4. Try to use .<country> instead of .com:
        $principalName = str_replace('schrack.com', 'schrack.' . $country, $principalName);
        $advisor = Mage::getModel('customer/customer')->loadByUserPrincipalName($principalName);
        if ( $advisor->getId() ) {
            return $advisor;
        }

		return false;
	}

	/**
	 * Get the customer models of the advisors.
	 * For system contacts the method will return the advisors of the account.
	 *
	 * @return Schracklive_SchrackCustomer_Model_Customer[]	false if no principal name is set, null if the customer model couln't be loaded
	 */
	public function getAdditionalAdvisors() {
		$principalNames = $this->isSystemContact() ? $this->getAccount()->getAdvisorsPrincipalNames() : $this->getSchrackAdvisorsPrincipalNames();
		if (!$principalNames) {
			return false;
		}

		if (is_null($this->_advisors)) {
			$this->_advisors = array();
			foreach (explode(',', $principalNames) as $principalName) {
				$advisor = Mage::getModel('customer/customer')
						->loadByUserPrincipalName($principalName);
				if ($advisor->getId()) {
					$this->_advisors[] = $advisor;
				}
			}
		}
		return $this->_advisors;
	}

	/**
	 * @return string
	 * @todo move to a helper class, this pollutes the model
	 */
	public function getPhotoUrl($size = 'big') {
		$url = '';
		if ($size == 'big') {
			$path = Mage::getStoreConfig('schrack/shop/employee_images');
		}
		if ($size == 'medium') {
			$path = 'mab95';
		}
		if ($size == 'small') {
			$path = 'mab58';
		}
		if ($this->isEmployee()) {
			$server = Mage::getStoreConfig('schrack/general/imageserver');
			$url = $server.$path.'/'.strtolower($this->getEmail()).'.jpg';
			if (isset($_SERVER['HTTPS'])) {
				$url = str_replace('http://', 'https://', $url);
			}
		}
		return $url;
	}

	/**
	 * allow to be overwritten by test classes
	 */
	protected function _loadTypeFields() {
		$this->_getResource()->loadTypeFields($this);
	}

	/**
	 * @return bool
	 */
	public function isContact() {
		$this->_loadTypeFields();
		return ($this->getGroupId() == Mage::getStoreConfig('schrack/shop/contact_group')
				&& $this->_isHumanContact()) ? true : false;
	}

	/**
	 * @return bool
	 */
	public function isProspect() {
		$this->_loadTypeFields();
		return ($this->getGroupId() == Mage::getStoreConfig('schrack/shop/prospect_group')
				&& $this->getSchrackAccountId()
				&& !$this->getSchrackWwsContactNumber()) ? true : false;
	}

	/**
	 * @return bool
	 */
	public function isInactiveContact() {
		$this->_loadTypeFields();
		return ($this->getGroupId() == Mage::getStoreConfig('schrack/shop/inactive_contact_group')
				&& $this->_isHumanContact()) ? true : false;
	}

	/**
	 * @return bool
	 */
	public function isDeletedContact() {
		$this->_loadTypeFields();
		return ($this->getGroupId() == Mage::getStoreConfig('schrack/shop/deleted_contact_group')
				&& $this->_isHumanContact()) ? true : false;
	}

	/**
	 * @return bool
	 */
	public function _isHumanContact() {
		$contactNumberIsValid = false;
		if ($this->hasData($this->getIdFieldName()) && ($this->getSchrackWwsContactNumber() > 0)) {
			$contactNumberIsValid = true; // contacts have a positive contact number
		} elseif ($this->hasData($this->getIdFieldName()) && (!$this->getSchrackWwsContactNumber() || $this->getSchrackWwsContactNumber() == 0)) {
			$contactNumberIsValid = true; // new contacts have a contact number of 0
		}
		return ($this->getSchrackAccountId()
				&& $this->getSchrackWwsCustomerId() // DLA 2016-02-09 self registered customers do not have a wws cust id
				&& $contactNumberIsValid) ? true : false;
	}

    /**
     * @return bool
     */
    public function isHumanContactDetailledLog() {
        $contactNumberIsValid = false;

        $customerId = $this->getId();
        $idFieldName = $this->getIdFieldName();
        $hastDataIdFieldName = $this->hasData($this->getIdFieldName());
        $accountId = $this->getSchrackAccountId();
        $wwsId = $this->getSchrackWwsCustomerId();

        $infoArray = array(
            'customerId' => $customerId,
            'idFieldName' => $idFieldName,
            'hastDataIdFieldName' => $hastDataIdFieldName,
            'accountId' => $accountId,
            'wwsId' => $wwsId,
        );

        if ($this->hasData($this->getIdFieldName()) && ($this->getSchrackWwsContactNumber() > 0)) {
            $contactNumberIsValid = true; // contacts have a positive contact number
        } elseif ($this->hasData($this->getIdFieldName()) && (!$this->getSchrackWwsContactNumber() || $this->getSchrackWwsContactNumber() == 0)) {
            $contactNumberIsValid = true; // new contacts have a contact number of 0
        }

        $infoArray = array(
            'customerId' => $customerId,
            'idFieldName' => $idFieldName,
            'hastDataIdFieldName' => $hastDataIdFieldName,
            'accountId' => $accountId,
            'wwsId' => $wwsId,
            'contactNumberIsValid' => $contactNumberIsValid
        );

        return $infoArray;
    }

	/**
	 * @return bool
	 */
	// group_id = 5 (defined in DB-Table core_config_data: contact_group):
	public function isSystemContact() {
		$this->_loadTypeFields();
		return (($this->getGroupId() == Mage::getStoreConfig('schrack/shop/system_group'))
				&& $this->getSchrackAccountId()
				//&& $this->getSchrackWwsCustomerId() // Prospects have SystemContact without WWS Customer ID
				&& ($this->getSchrackWwsContactNumber() == -1)) ? true : false;
	}

	/**
	 * @return bool
	 */
	// group_id = 10 (defined in DB-Table core_config_data: prospect_group):
	public function isSystemProspect() {
		$this->_loadTypeFields();
		return (($this->getGroupId() == Mage::getStoreConfig('schrack/shop/system_group'))
				&& $this->getSchrackAccountId()
				&& ($this->getSchrackWwsContactNumber() == -1)
				&& (!$this->getSchrackWwsCustomerNumber())) ? true : false;
	}

	/**
	 * @return bool
	 */
	public function isAnyWwsContact() {
		return ($this->isRealContact() || $this->isSystemContact());
	}

	/**
	 * @return bool
	 */
	public function isRealContact() {
		return ($this->isContact() || $this->isInactiveContact() || $this->isDeletedContact());
	}

	/**
	 * @return bool
	 */
	public function isDemoUser() {
		$this->_loadTypeFields();
		//is this enough?
		return ($this->getGroupId() == Mage::getStoreConfig('schrack/shop/demo_group'));
	}

	/**
	 * @return bool
	 */
	public function isEmployee() {
		$this->_loadTypeFields();
		return ($this->getGroupId() == Mage::getStoreConfig('schrack/shop/employee_group')
				&& $this->getSchrackUserPrincipalName()) ? true : false;
	}

	/**
	 * Customer addresses collection
	 *
	 * @return Mage_Customer_Model_Entity_Address_Collection
	 * @see getSystemContact()
	 */
	public function getAddressesCollection() {
		if ($this->_addressesCollection === null) {
			$this->_addressesCollection = $this->getAddressCollection()
					->setCustomerFilter($this->getSystemContact())
					->addAttributeToSelect('*');
			foreach ($this->_addressesCollection as $address) {
				$address->setCustomer($this->getSystemContact());
			}
		}

		return $this->_addressesCollection;
	}

	/**
	 * Get address with a certain WWS number.
	 *
	 * @param int $wwsAddressNumber
	 * @return Schracklive_SchrackCustomer_Model_Entity_Address
	 */
	public function getWwsAddress($wwsAddressNumber) {
		if ($this->isAnyWwsContact()) {
			return Mage::getModel('customer/address')->loadByWwsAddressNumber($this->getSchrackWwsCustomerId(), $wwsAddressNumber);
		}
		return null;
	}

	/**
	 * Get address number of associated address.
	 *
	 * @return int
	 */
	public function getSchrackWwsAddressNumber() {
		if ($this->isAnyWwsContact()) {
			return Mage::getModel('customer/address')->load($this->getSchrackAddressId())->getSchrackWwsAddressNumber();
		}
		return false;
	}

	/**
	 * Add address to address collection
	 *
	 * @param   Mage_Customer_Model_Address $address
	 * @return  Schracklive_SchrackCustomer_Model_Customer
	 * @see getSystemContact()
	 */
	public function addAddress(Mage_Customer_Model_Address $address) {
		$address->setCustomerId($this->getSystemContact()->getId());

		return parent::addAddress($address);
	}

	/**
	 * @throws Mage_Core_Exception
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 */
	public function getSystemContact() {
		if (!$this->isRealContact() && !$this->isProspect()) {
			return $this; // non-contacts are their own system contacts
		}
		if (empty($this->_systemContact)) {
			if ($this->getAccount()->getId()) {
				$this->_systemContact = $this->getAccount()->getSystemContact();
			}
		}
		if (!$this->_systemContact || !$this->_systemContact->getId()) {
			throw Mage::exception('Mage_Core', 'Contact has no system contact.');
		}
		return $this->_systemContact;
	}

	/**
	 * @return string
	 */
	public function getSchrackPickup() {
		$pickup = $this->getSchrackPickupUnchecked();
		$stockHelper = Mage::helper('schrackcataloginventory/stock');
		$pickup = $stockHelper->ensureValidPickupStockNumber($pickup);
		return '' . $pickup;
	}
	private function getSchrackPickupUnchecked () {
		if ($this->isDemoUser()) {
			return $this->getData('schrack_pickup');
		} elseif ($this->isAnyWwsContact() || $this->isEmployee() || $this->isProspect()) {
			$pickup = $this->getData('schrack_pickup');
			if (empty($pickup) && ($this->getAccount() !== false)) {
				$pickup = Mage::helper('schrackcustomer')->getDefaultWarehouseIdForCustomer($this);
			}
			return $pickup;
		} else {
			return Mage::helper('schrackcustomer')->getDefaultWarehouseIdForCustomer($this);
		}
	}

	/**
	 * @return mixed
	 */
	public function getSchrackWarehouseId() {
		return $this->getSchrackPickup();
	}

	/**
	 * Sets the customber number.
	 *
	 * @param int $number
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 * @throws InvalidArgumentException
	 */
	public function setSchrackWwsContactNumber($number) {
		if ($this->isRealContact() && (!is_numeric($number) || !is_int(/* (numeric) */0 + $number) || ($number < 1))) {
			throw new InvalidArgumentException('WWS Contact Number must be a positive integer.');
		} elseif ($this->isSystemContact() && (!is_numeric($number) || !is_int(/* (numeric) */0 + $number) || ($number > -1))) {
			throw new InvalidArgumentException('WWS Contact Number must be -1');
		}

		$this->setData('schrack_wws_contact_number', $number);

		return $this;
	}

	/**
	 * Send email with new account specific information
	 *
	 * @param string $type
	 * @param string $backUrl
	 * @param string $storeId
     * @param string $password
	 * @return Schracklive_SchrackCustomer_Model_Customer
	 */
	public function sendNewAccountEmail($type = 'registered', $backUrl = '', $storeId = '0', $password = NULL, $xmlPathEmailTemplate = false) {
	    $this->_newAccountMailType = $type;
		if (Mage::getStoreConfig('schrackdev/customer/disableEmails')) {
			return $this;
		}

		// copied code from base class:
        $types = array(
            'registered'   => self::XML_PATH_REGISTER_EMAIL_TEMPLATE, // welcome email, when confirmation is disabled
            'confirmed'    => self::XML_PATH_CONFIRMED_EMAIL_TEMPLATE, // welcome email, when confirmation is enabled
            'confirmation' =>   $xmlPathEmailTemplate
                              ? $xmlPathEmailTemplate
                              : 'schrack/customer/create_account/email_confirmation_template_contact_from_crm', // email with confirmation link
        );
        if (!isset($types[$type])) {
            Mage::throwException(Mage::helper('customer')->__('Wrong transactional account email type'));
        }

        if (!$storeId) {
            $storeId = $this->_getWebsiteStoreId($this->getSendemailStoreId());
        }

        if (!is_null($password)) {
            $this->setPassword($password);
        }

        $this->_sendEmailTemplate($types[$type], self::XML_PATH_REGISTER_EMAIL_IDENTITY,
            array('customer' => $this, 'back_url' => $backUrl), $storeId);
        $this->cleanPasswordsValidationData();

        return $this;
		// end of copied code from base class
	}

	/**
	 * Send email with new customer password
	 *
	 * @return Mage_Customer_Model_Customer
	 */
	public function sendPasswordReminderEmail() {
		if (Mage::getStoreConfig('schrackdev/customer/disableEmails')) {
			return $this;
		}

		//return parent::sendPasswordReminderEmail();

        $storeId = $this->getStoreId();
        if (!$storeId) {
            $storeId = $this->_getWebsiteStoreId();
        }

        $this->_sendEmailTemplate(self::XML_PATH_REMIND_EMAIL_TEMPLATE, self::XML_PATH_FORGOT_EMAIL_IDENTITY,
            array('customer' => $this), $storeId);

        return $this;
	}

	public function sendResetPasswordEmail () {
		if (Mage::getStoreConfig('schrackdev/customer/disableEmails')) {
			return $this;
		}
        $storeId = $this->getStoreId();
        if (!$storeId) {
            $storeId = $this->_getWebsiteStoreId();
        }

        $this->_sendEmailTemplate(self::XML_PATH_RESET_EMAIL_TEMPLATE, self::XML_PATH_FORGOT_EMAIL_IDENTITY,
            array('customer' => $this), $storeId);

        return $this;
	}

    /**
     * Send corresponding email template
     *
     * @param string $emailTemplate configuration path of email template
     * @param string $emailSender configuration path of email identity
     * @param array $templateParams
     * @param int|null $storeId
     * @param string $customerEmail
     * @return Mage_Customer_Model_Customer
     */
    protected function _sendEmailTemplate($template, $sender, $templateParams = array(), $storeId = null, $customerEmail = NULL)
    {
        // DEVELOPER-EMAIL:
        // Replace virtual email address with developer-email (valid dev-mail -> e.g.: testuser12_at@schrack.com):
        if (preg_match('/testuser[0-9]{0,3}_.{2}@schrack.com$/', $this->getEmail())) {
            $receiverEmail = Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails');
        } else {
            $receiverEmail = $this->getEmail();
        }

        if (stristr($receiverEmail, 'ä') ||
            stristr($receiverEmail, 'ö') ||
            stristr($receiverEmail, 'ü')) {

            list($receiverPrefix, $receiverDomainName) = explode('@', $receiverEmail);

            $receiverIDNDomainName = idn_to_ascii($receiverDomainName);
            $receiverEmail = $receiverPrefix . '@' . $receiverIDNDomainName;
        }

        /** @var $singleMailApi Schracklive_SchrackSingleMail_Model_SingleMailApi */
        $singleMailApi = Mage::getModel('schracksinglemail/SingleMailApi');
        $singleMailApi->setStoreID($storeId);
        $singleMailApi->setMagentoTransactionalTemplateIDfromConfigPath($template);
        $singleMailApi->setMagentoTransactionalTemplateVariables($templateParams);
        $singleMailApi->addToEmailAddress($receiverEmail);
        if ( $this->_newAccountMailType > '' ) {
            $singleMailApi->logCustomerEmailType($this->_newAccountMailType); // just for logging
        }
        $fromEmail = Mage::getStoreConfig($sender, $storeId);
        if ( ! $fromEmail ) {
            $fromEmail = 'general';
        }
        $singleMailApi->setFromEmail($fromEmail);
        $singleMailApi->createAndSendMail();

        return $this;
    }

	/**
	 * @return array|bool
	 */
	public function validate() {
		$errors = array();
		$customerHelper = Mage::helper('customer');

		if (!Zend_Validate::is(trim($this->getLastname()), 'NotEmpty')) {
			$errors[] = $customerHelper->__('The last name cannot be empty.');
		}

		$email = $this->getEmail();
		$emailVerifyExpression = (bool) preg_match('/^[^@]+@[^@]+\.[a-zA-Z]{2,}$/', trim($email));
		//if (!Zend_Validate::is($this->getEmail(), 'EmailAddress')) { --> Zend verification is deprecated
		if (!$emailVerifyExpression) {
			$errors[] = $customerHelper->__('Invalid email address "%s".', $email);
		}

		$password = $this->getPassword();
		if (!$this->getId() && !Zend_Validate::is($password, 'NotEmpty')) {
			// TODO : Check, if we have new registration process and non-registration-user (Durchläufer).
			$errors[] = $customerHelper->__('The password cannot be empty.');
		}
		if (strlen($password) && !Zend_Validate::is($password, 'StringLength', array(6))) {
			$errors[] = $customerHelper->__('The minimum password length is %s', 6);
		}
		$confirmation = $this->getPasswordConfirmation(); //Ngarro : Added
		if ($password != $confirmation) {
			$errors[] = $customerHelper->__('Please make sure your passwords match.');
		}

		$entityType = Mage::getSingleton('eav/config')->getEntityType('customer');
		$attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'dob');
		if ($attribute->getIsRequired() && '' == trim($this->getDob())) {
			$errors[] = $customerHelper->__('The Date of Birth is required.');
		}
		$attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'taxvat');
		if ($attribute->getIsRequired() && '' == trim($this->getTaxvat())) {
			$errors[] = $customerHelper->__('The TAX/VAT number is required.');
		}
		$attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'gender');
		if ($attribute->getIsRequired() && '' == trim($this->getGender())) {
			$errors[] = $customerHelper->__('Gender is required.');
		}

		if (empty($errors)) {
			return true;
		}
		return $errors;
	}

	/**
	 * @return array|bool
	 */
	public function validateExtra() {
		$errors = array();
		$customerHelper = Mage::helper('customer');

		if (!$this->getSchrackTelephone()) {
			$errors[] = $customerHelper->__('Telephone number is required.');
		}

		if (empty($errors)) {
			return true;
		}
		return $errors;
	}

	/**
	 * @return string|null
	 */
	public function getSchrackAclRole() {
		return Mage::getSingleton('schrack/service_acl')->getRoleById($this->getData('schrack_acl_role_id'));
	}

	/**
	 * @param Zend_Acl_Resource_Interface|string $resource
	 * @param string                             $privilege
	 * @return boolean
	 */
	public function isAllowed($resource, $privilege) {
		return Mage::helper('schrackcustomer/acl')->isAllowed($resource, $privilege, $this);
	}

	public function updateWwsCustomerId() {
		$this->_getResource()->updateWwsCustomerId($this);
	}

	public function setSystemContact($systemContact) {
		$this->_systemContact = $systemContact;
	}

    public function isActAsUserPossibleDashboard ( $customer ) {
        if ( ! $customer ) {
            return false;
        }

        return Mage::helper('schrackcustomer')->mayActAsUser($customer);
    }
}
