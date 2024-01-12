<?php

require_once('shell.php');

/**
 * AD User Sync Shell Script
 *
 * @author      Martin Kutschker <mk@plan2.net>
 */
class Schracklive_Shell_syncAD extends Schracklive_Shell {

	protected $_verbose = false;
	private $ldap;

    protected function connectNew() {
        $options = Mage::getSingleton('ad/connector')->getLdapOptions('config2');
        $ldap = new Zend_Ldap($options['server1']); // There is also a fallback server configurable (= server2)!!
        $ldap->bind(Mage::getStoreConfig('schrack/ad/username2'), Mage::getStoreConfig('schrack/ad/password2'));
        $this->ldap = $ldap;
        if ($this->getArg('verbose')) echo 'LDAP connection new domain successful' . "\n";
        return true;
    }

    protected function syncFENew() {
        if (!$this->ldap) {
            throw new Exception('No connection to the AD.');
        }

        $country = strtoupper(Mage::getStoreConfig('schrack/ad/country') ? Mage::getStoreConfig('schrack/ad/country') : Mage::getStoreConfig('schrack/general/country'));

        // SPECIAL CASES:
        // The old logic of RS = CS in old domain is fixed in new domain! So, the AD-Country must be fixed here also for new domain:
        if ($country == 'CS') $country = 'RS';
        if ($country == 'RU') $country = 'AT';
        if ($country == 'DE') $country = 'AT';

        // Normal Case (including som special cases: CS, RU, DE):
        $abstractCountry = $country;

        // Very special Case:
        if ($country == 'SA') {
            $country = 'AT';
            $abstractCountry = 'SA';
        }

        $result = array();
        $log_string = date('Y-m-d H:i:s') .
                      '--> search in LDAP: ' . Mage::getSingleton('ad/connector')->getAccountFilter() .
                      ', ' . 'OU=Schrack_' . $country . ',' . Mage::getSingleton('ad/connector')->getBaseDn('config2');
        Mage::log($log_string, null, '/syncAD.log');
        try {
            $result = $this->ldap->search(Mage::getSingleton('ad/connector')->getAccountFilter(), 'OU=Schrack_' . $country . ',' . Mage::getSingleton('ad/connector')->getBaseDn('config2'));
        } catch (Zend_Ldap_Exception $e) {
            if ($e->getCode() == Zend_Ldap_Exception::LDAP_NO_SUCH_OBJECT) {
                if ($this->getArg('verbose')) echo "WARNING: no FE users found in " . $country . "\n";
                Mage::log(date('Y-m-d H:i:s') . '--> WARNING: no FE users found in ' . $country . ' (NEW Domain: schrack.com)', null, '/syncAD.log');
            }
        }
        $processedIds = array();

        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');

        foreach ($result as $data) {
            if ($this->getArg('verbose')): print_r($data, true); endif;

            // Multiple Advisor Feature:
            // Check, if active:
            if (intval(Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature')) > 0) {
                // Check advisor 1:
                if (Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_one_email')) {
                    if (stristr($data['mail'][0], Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_one_email'))) {
                        $query = 'UPDATE core_config_data SET value = "' . $data['givenname'][0] . ' ' . $data['sn'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_one_name"';
                        $writeConnection->query($query);
                        $query = 'UPDATE core_config_data SET value = "' . $data['telephonenumber'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_one_phone"';
                        $writeConnection->query($query);
                        $query = 'UPDATE core_config_data SET value = "' . $data['facsimiletelephonenumber'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_one_fax"';
                        $writeConnection->query($query);
                        $query = 'UPDATE core_config_data SET value = "' . $data['mobile'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_one_mobile"';
                        $writeConnection->query($query);
                    }
                }
                // Check advisor 2:
                if (Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_two_email')) {
                    if (stristr($data['mail'][0], Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_two_email'))) {
                        $query = 'UPDATE core_config_data SET value = "' . $data['givenname'][0] . ' ' . $data['sn'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_two_name"';
                        $writeConnection->query($query);
                        $query = 'UPDATE core_config_data SET value = "' . $data['telephonenumber'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_two_phone"';
                        $writeConnection->query($query);
                        $query = 'UPDATE core_config_data SET value = "' . $data['facsimiletelephonenumber'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_two_fax"';
                        $writeConnection->query($query);
                        $query = 'UPDATE core_config_data SET value = "' . $data['mobile'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_two_mobile"';
                        $writeConnection->query($query);
                    }
                }
                // Check advisor 3:
                if (Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_three_email')) {
                    if (stristr($data['mail'][0], Mage::getStoreConfig('schrack/toolsma/multiple_advisor_feature_advisor_three_email'))) {
                        $query = 'UPDATE core_config_data SET value = "' . $data['givenname'][0] . ' ' . $data['sn'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_three_name"';
                        $writeConnection->query($query);
                        $query = 'UPDATE core_config_data SET value = "' . $data['telephonenumber'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_three_phone"';
                        $writeConnection->query($query);
                        $query = 'UPDATE core_config_data SET value = "' . $data['facsimiletelephonenumber'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_three_fax"';
                        $writeConnection->query($query);
                        $query = 'UPDATE core_config_data SET value = "' . $data['mobile'][0] . '" WHERE path LIKE "schrack/toolsma/multiple_advisor_feature_advisor_three_mobile"';
                        $writeConnection->query($query);
                    }
                }
            }

            $processUser = true;

            // Override importing new domain in case of default advisor in some countries, because they have their own choice
            // Normally shop-user.<COUNTRY>@schrack.com is the default Advisor userPrincipal, but not in all countries:
            if (!in_array( strtolower($abstractCountry), array('at', 'co', 'com', 'de', 'ro', 'sa', 'sk'))) {
                if (stristr($data['userprincipalname'][0], 'shop-user.' . strtolower($country) . '@schrack.com')) {
                    if ($this->getArg('verbose')) echo '***************************************** ' . $data['userprincipalname'][0];
                    continue;
                }
            }

            if(stristr($data['userprincipalname'][0], 'shop-user.' )) {
                if ($this->getArg('verbose')) echo '***************************************** ' . $data['userprincipalname'][0];
            }

            // Override all users, who are not completely migrated to new domain:
            if (is_array($data['distinguishedname']) && !empty($data['distinguishedname'])) {
                foreach ($data['distinguishedname'] as $index => $value) {
                    if (stristr($value, 'OU=Migration')) {
                        // User is existent in new domain, but not moigrated completely:
                        if ($this->getArg('verbose')) echo 'Found incomplete migrated user: ' . $data['mail'][0] . "\n";
                        $processUser = false;
                    }
                }
            }

            if ($processUser == false) continue;

            /** @var Schracklive_SchrackCustomer_Model_Customer $customer */
            // $customer = Mage::getModel('customer/customer')->loadByUserPrincipalName($data['userprincipalname'][0]);
            $customer = Mage::getModel('customer/customer')->loadByEmail($data['mail'][0]);

            if ($this->getArg('verbose')) echo $data['userprincipalname'][0].' <'.$data['mail'][0].'> #'.$customer->getId()."\n";

            if ($customer->getEmail() != $data['mail'][0]) {
                $customer->setEmail($data['mail'][0]);
                $customer->setForceConfirmed(true); // never confirm employees
            }
            $customer->setGroupId(Mage::getStoreConfig('schrack/shop/employee_group'));
            $customer->setSchrackAclRoleId(Mage::helper('schrack/acl')->getEmployeeRoleId());
            $customer->setPrefix('');
            $customer->setFirstname($data['givenname'][0]);
            $customer->setLastname($data['sn'][0]);
            $customer->setSchrackUserPrincipalName($data['userprincipalname'][0]);
            $customer->setSchrackSalesmanId($data['extensionattribute2'][0]);
            $customer->setSchrackBranchId((int)$data['extensionattribute3'][0] % 10);
            if ($data['telephonenumber'][0]) {
                $customer->setSchrackTelephone($data['telephonenumber'][0]);
            } else {
                $customer->setSchrackTelephone($data['mobile'][0]);
            }
            $customer->setSchrackFax($data['facsimiletelephonenumber'][0]);
            $customer->setSchrackMobilePhone($data['mobile'][0]);
            $customer->setSchrackTitle($data['title'][0]);
            $customer->setSchrackDepartment($data['department'][0]);
            $customer->setSchrackComments($data['description'][0]);
            // $data['initials'][0]
            // $data['l'][0]	// City

            try {
                $customer->save();
                $processedIds[] = $customer->getId();

                // TODO: Insert new employee (email / country / entity-id) into magento_common:
                $email            = $data['mail'][0];
                $countryId        = strtolower(Mage::getStoreConfig('schrack/general/country'));
                $customerEntityId = $customer->getId();

                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $query = "SELECT * FROM magento_common.login_token 
                            WHERE email = '${email}'
                                AND country_id = '{$countryId}'";
                $queryResult = $readConnection->query($query);

                if ($queryResult->rowCount() > 0) {
                    // Do something meanful, because entry already exists!
                } else {
                    $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                    $query  = "INSERT INTO magento_common.login_token (email, country_id, entity_id) 
                                VALUES ('${email}', '${countryId}', ${customerEntityId})";
                    $result = $readConnection->query($query);
                }
            } catch (Exception $e) {
                if ($this->getArg('verbose')) echo 'ERROR: Cannot save customer "'.$data['userprincipalname'][0].'" - ', $e->getMessage(), "\n";
                if ($this->_verbose) {
                    fwrite(STDERR, $customer->toXML());
                }

                if ($data['mail'][0] && $data['userprincipalname'][0]) {
                    // UPDATE customer
                    $query = 'UPDATE customer_entity SET schrack_user_principal_name = "' . $data['userprincipalname'][0] . '" WHERE email LIKE "' . $data['mail'][0] . '"' ;
                    $writeConnection->query($query);

                    if ($this->getArg('verbose')) echo 'UPDATED FE user with eMail -> ' . $data['mail'][0] . ' SET schrack_user_principal_name TO -> ' . $data['userprincipalname'][0] . "\n";
                }

                continue;
            }
        }

        // Delete all other employees
        // Fetch all employees which are not in Active Directory:
        $missingEmployees = mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('email')
            ->addAttributeToFilter('group_id', Mage::getStoreConfig('schrack/shop/employee_group'))
            ->addAttributeToFilter('schrack_acl_role_id', Mage::helper('schrack/acl')->getEmployeeRoleId())
            ->addAttributeToFilter('schrack_user_principal_name', array('notnull' => ''))
            ->addAttributeToFilter('schrack_user_principal_name', array('neq' => ''))
            ->addAttributeToFilter('entity_id', array('nin' => $processedIds))
            ->load();

        foreach ($missingEmployees as $missingEmployee) {
            // @var Schracklive_SchrackCustomer_Model_Customer $missingEmployee
            if ($missingEmployee->isEmployee()) {
                // Everybody, who is not in the Active Directory Response, should be deleted:
                $deleteUserMailAddress = $missingEmployee->getEmail();
                // Mage::log($missingEmployee->getEmail(), null, 'syncAD_missing_employees.log');

                // Employee should also be deleted from User Table (Frontend):
                $deleteUserSql = "DELETE FROM customer_entity WHERE email LIKE '" . $deleteUserMailAddress . "'";
                Mage::log($deleteUserMailAddress, null, 'syncAD_deleted_employees_admin_user.log');
                $writeConnection->query($deleteUserSql);
            }
        }
    }

    protected function syncBENew() {
        if (!$this->ldap) {
            throw new RuntimeException('No connection to the AD.');
        }
        $adminRole = Mage::getStoreConfig('schrack/ad/admin_role');
        $country = strtoupper(Mage::getStoreConfig('schrack/ad/country') ? Mage::getStoreConfig('schrack/ad/country') : Mage::getStoreConfig('schrack/general/country'));

        $filter = Mage::getSingleton('ad/connector')->getAccountFilter();
        $baseDN = Mage::getSingleton('ad/connector')->getBaseDn('config2');
        if ($this->getArg('verbose')) echo $baseDN . "\n";
        $result = $this->ldap->search($filter, $baseDN);

        foreach ($result as $data) {
            $createRole = false;
            $processUser = false;

            if (array_key_exists('memberof', $data) && is_array($data['memberof']) && !empty($data['memberof'])) {
                foreach ($data['memberof'] as $index => $value) {
                    if (stristr($value, Mage::getStoreConfig('schrack/ad/admin_group2'))) {
                        // User is in correct admin-usergroup (process next step)
                        if ($this->getArg('verbose')) echo 'Found admin user (' . Mage::getStoreConfig('schrack/ad/admin_group2') . ') on ' . Mage::getStoreConfig('schrack/ad/host2') . ' --> ' . $data['mail'][0] . "\n";
                        $processUser = true;
                    }
                }
            } else {
                continue;
            }

            if ($processUser == false) continue;

            $user = Mage::getModel('admin/user')->load($data['mail'][0], 'email');

            $user->setUsername($data['userprincipalname'][0]);

            if (!$user->getId()) {
                $user->setPassword('');
                $user->setIsActive(1);
                $createRole = true;
            }

            if ($this->getArg('verbose')) echo $data['userprincipalname'][0].' <'.$data['mail'][0].'> #'.$user->getId()."\n";

            $user->setFirstname($data['givenname'][0]);
            $user->setLastname($data['sn'][0]);
            $user->setEmail($data['mail'][0]);
            $role = Mage::getModel('admin/role');
            try {
                $totalMailstring = explode('@', $data['mail'][0]);
                $username = $totalMailstring[0];
                // Workaroung for Active Directory:
                // Magento DB-Table -> admin_user:
                // "email" ==> 'windows-username@schrack.com' & "username" ==> 'windows-username@xx.schrack.lan'
                // "xx" can be any country pl, at, cz.....
                if (!in_array($username, array('d.coldea', 'd.coppens', 'd.andrzejewski', 'j.mlacka', 'd.yordanova','p.lager'))) {
                    $user->save();

                    if ($createRole) {
                        $role->setUserId($user->getId());
                        $role->setParentId($adminRole);
                        $role->setTreeLevel(2);
                        $role->setRoleType('U');
                        $role->setRoleName($data['userprincipalname'][0]);
                        try {
                            $role->save();
                        } catch (Exception $e) {
                            if ($this->getArg('verbose')) echo 'ERROR: Cannot save role for "'.$data['userprincipalname'][0].'" - ', $e->getMessage(), "\n";
                        }
                    }
                }
            } catch (Exception $e) {
                if ($this->getArg('verbose')) echo 'ERROR: Cannot save user "'.$data['userprincipalname'][0].'" - ', $e->getMessage(), "\n";
            }
            unset($user);
        }
    }

	public function usageHelp() {
		return <<<USAGE
Usage:  php -f syncAD.php [options] [-verbose]

  FE                          Synchronize shop customers with AD (Frontend-Login)
  BE                          Synchronize shop admins with AD (Backend-Login)
  all                         Synchronize shop with AD (Frontend + Backend-Login)
  -verbose                    Optional output
  help                        This help

USAGE;
	}

	public function run() {
        Mage::log(date('Y-m-d H:i:s') . '*** Starting syncAD.php ***', null, '/syncAD.log');
        if ($this->getArg('verbose')) echo "\n" . "\n" . '*** Starting syncAD.php ***'. "\n" . "\n";
		if ($this->getArg('FE')) {
            if ($this->connectNew()) {
                if ($this->getArg('verbose')) echo '<<<<<<<<<<<<<<<<<<<<<<<<  Starting sync FRONTEND-New Domain >>>>>>>>>>>>>>>>>>>>>>>>' . "\n";
                $this->syncFENew();
                if ($this->getArg('verbose')) echo "\n" . '>>>>>>>>>>>>>>>>>>>>>>>>  Finished sync FRONTEND-New Domain <<<<<<<<<<<<<<<<<<<<<<<<' . "\n";
            }
            Mage::log(date('Y-m-d H:i:s') . '*** Finished syncAD.php (FE) ***', null, '/syncAD.log');
		} elseif ($this->getArg('BE')) {
            if ($this->connectNew()) {
                if ($this->getArg('verbose')) echo '<<<<<<<<<<<<<<<<<<<<<<<<  Starting sync BACKEND-New Domain >>>>>>>>>>>>>>>>>>>>>>>>' . "\n";
                $this->syncBENew();
                if ($this->getArg('verbose')) echo "\n" . '>>>>>>>>>>>>>>>>>>>>>>>>  Finished sync BACKEND-New Domain <<<<<<<<<<<<<<<<<<<<<<<<' . "\n";
            }
            Mage::log(date('Y-m-d H:i:s') . '*** Finished syncAD.php (BE) ***', null, '/syncAD.log');
		} elseif ($this->getArg('all')) {
            if ($this->connectNew()) {
                if ($this->getArg('verbose')) echo '<<<<<<<<<<<<<<<<<<<<<<<<  Starting sync FRONTEND-New Domain >>>>>>>>>>>>>>>>>>>>>>>>' . "\n";
                $this->syncFENew();
                if ($this->getArg('verbose')) echo "\n" . '>>>>>>>>>>>>>>>>>>>>>>>>  Finished sync FRONTEND-New Domain <<<<<<<<<<<<<<<<<<<<<<<<' . "\n" . "\n";
                if ($this->getArg('verbose')) echo "\n" . '>>>>>>>>>>>>>>>>>>>>>>>>  Starting sync BACKEND-New Domain <<<<<<<<<<<<<<<<<<<<<<<<' . "\n";
                $this->syncBENew();
                if ($this->getArg('verbose')) echo "\n" . '>>>>>>>>>>>>>>>>>>>>>>>>  Finished sync BACKEND-New Domain <<<<<<<<<<<<<<<<<<<<<<<<' . "\n";
                Mage::log(date('Y-m-d H:i:s') . '*** Finished syncAD.php (--all) ***', null, '/syncAD.log');
            }
		} else {
			echo $this->usageHelp();
		}

        if ($this->getArg('verbose')) echo "\n" . '*** Finished syncAD.php ***'. "\n" . "\n";
        die();
	}

}

$shell = new Schracklive_Shell_syncAD();
$shell->run();
