<?php
ini_set('max_execution_time', '0');
set_time_limit(0);

require_once('shell.php');

class Schracklive_Shell_syncToDynos extends Schracklive_Shell {

    private $_readConnection;
    private $_maxMessageCount;
    private $_type;


    public function __construct () {
        parent::__construct();

        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
    }


    public function run() {
        $msg = null;
        $country = strtolower(Mage::getStoreConfig('schrack/ad/country') ? Mage::getStoreConfig('schrack/ad/country') : Mage::getStoreConfig('schrack/general/country'));
        $dataFile = '/tmp/all_shop_users_' . $country . '.csv';
        if (!file_exists($dataFile)) {
            file_put_contents($dataFile, '');
            chmod($dataFile, 0750);
            chown($dataFile, 'nginx');
            chgrp($dataFile, 'develop');
        }

        $query  = "SELECT entity_id FROM customer_entity WHERE schrack_wws_contact_number != -1";

        $queryResult = $this->_readConnection->query($query);

        if ($queryResult->rowCount() > 0) {

            $dataStructureHeader  = '"s4y_id";';
            $dataStructureHeader .= '"email";';
            $dataStructureHeader .= '"s4s_id";';
            $dataStructureHeader .= '"s4s_nickname";';
            $dataStructureHeader .= '"s4s_school";';
            $dataStructureHeader .= '"customer_type";';
            $dataStructureHeader .= '"state";' . "\n";

            file_put_contents($dataFile, $dataStructureHeader, FILE_APPEND | LOCK_EX);

            foreach ($queryResult as $recordset) {
                $customerId = $recordset['entity_id'];
                $customer   = Mage::getModel('schrackcustomer/customer')->load($customerId);

                // Customer Data:
                $s4y_id       = $customer->getSchrackS4yId();
                $email        = $customer->getEmail();
                $s4s_id       = $customer->getSchrackS4sId();
                $s4s_nickname = $customer->getSchrackS4sNickname();
                $s4s_school   = $customer->getSchrackS4sSchool();

                if ( $customer->getSchrackWwsContactNumber() == 0 ) {
                    $customerType = 'Prospect';
                } else {
                    $customerType = 'Contact';
                }

                if (stristr($email, 'inactive')) {
                    $email = $customer->getSchrackEmails();
                    $state = "inaktiv";
                } else {
                    $state = "aktiv";
                }

                $dataStructure  = '"' . $s4y_id . '";';
                $dataStructure .= '"' . $email . '";';
                $dataStructure .= '"' . $s4s_id . '";';
                $dataStructure .= '"' . $s4s_nickname . '";';
                $dataStructure .= '"' . $s4s_school . '";';
                $dataStructure .= '"' . $customerType . '";';
                $dataStructure .= '"' . $state . '";' . "\n";

                file_put_contents($dataFile, $dataStructure, FILE_APPEND | LOCK_EX);
            }
        }
    }

}

$shell = new Schracklive_Shell_syncToDynos();
$shell->run();
