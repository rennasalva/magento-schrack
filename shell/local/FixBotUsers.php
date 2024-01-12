<?php

require_once('shell.php');

class Schracklive_Shell_FixBotUsers extends Schracklive_Shell {

    public function run() {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');

        $spamDate = '2017-08-18';

        $massQuery = "SELECT schrack_account_id, email FROM customer_entity WHERE schrack_customer_type LIKE 'light-prospect' AND schrack_wws_contact_number = 0 AND DATE(created_at) > '" . $spamDate . "' ORDER BY schrack_account_id";

        $recordsets = $readConnection->fetchAll($massQuery);

        $fileCustomers = "/var/log/schracklive/cz/magento/deleted_customers_sql.log";
        $fileAccounts = "/var/log/schracklive/cz/magento/deleted_accounts_sql.log";

        foreach ($recordsets as $recordset) {
            $lastName = '';
            $existingContact = Mage::getModel('customer/customer');
            $existingContact->setWebsiteId(Mage::app()->getWebsite()->getId());
            $existingContact->loadByEmail($recordset['email']);

            $lastName = $existingContact->getLastname();
            if (stristr($lastName, 'www.robot') || stristr($lastName, 'www.rabot')) {
                //Mage::log($recordset['email'], null, 'deleted_accounts.log');
                $deleteQueryCustomer = "DELETE FROM customer_entity WHERE schrack_account_id = " . $recordset['schrack_account_id'] . ';';
                $deleteQueryAccount  = "DELETE FROM account WHERE account_id = " . $recordset['schrack_account_id'] . ';';
                file_put_contents($fileCustomers, $deleteQueryCustomer, FILE_APPEND | LOCK_EX);
                file_put_contents($fileAccounts, $deleteQueryAccount, FILE_APPEND | LOCK_EX);
            }
        }
    }
}

set_time_limit(0);
$shell = new Schracklive_Shell_FixBotUsers();
$shell->run();
die('Script successfully finished');