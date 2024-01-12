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

        if (!$this->getArg('max_messages')) {
            die($this->usageHelp());
        }

        $number = $this->getArg('max_messages');
        if ($number && intval($number)) {
            $this->_maxMessageCount = intval($number);
        }

        $type = $this->getArg('type');
        if ($type) {
            $this->_type = $type;
        }

        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
    }


    public function run() {
        $msg = null;
        $country = strtolower(Mage::getStoreConfig('schrack/ad/country') ? Mage::getStoreConfig('schrack/ad/country') : Mage::getStoreConfig('schrack/general/country'));
        $logFile = 's4s_' . $country . '.csv';
        if (!file_exists($logFile)) {
            file_put_contents($logFile, '');
            chmod($logFile, 0750);
            chown($logFile, 'nginx');
            chgrp($logFile, 'develop');
        }

        $query  = "SELECT * FROM customer_entity WHERE schrack_s4s_id IS NOT NULL";
        $query .= " AND (schrack_wws_contact_number > -1 OR schrack_wws_contact_number IS NULL)";

        $queryResult = $this->_readConnection->query($query);

        if ($queryResult->rowCount() > 0) {
            $count = 0;
            foreach ($queryResult as $recordset) {
                if ($this->_maxMessageCount > $count) {
                    if($recordset['schrack_wws_customer_id'] && $this->_type == 'contacts') {
                        // Generate ContactMessages:
                        $customerId = $recordset['entity_id'];
                        $customer = Mage::getModel('schrackcustomer/customer')->load($customerId);

                        try {
                            if ($customer && $customer->getEmail()) {
                                $wwsContactNumber = Mage::getSingleton('crm/connector')->putCustomer($customer, false, true);

                                if ($wwsContactNumber) {
                                    Mage::log("Customer successfully send to DYNOS : " . $customer->getEmail() . " -> " . $recordset['schrack_wws_customer_id'], null, "s4s_message.log");
                                } else {
                                    Mage::log("ERR: Message could not be send to DYNOS : " . $customer->getEmail() . " -> " . $recordset['schrack_wws_customer_id'], null, "s4s_message.err.log");
                                }
                            }
                        } catch (Exception $e) {
                            Mage::log("ERR: Message could not be send to DYNOS : " . $recordset['schrack_wws_customer_id'], null, "s4s_message.err.log");
                            Mage::logException($e);
                        }
                    } else {
                        if ($this->_type == 'prospects') {
                            $email  = $recordset['email'];
                            $s4s_id = $recordset['schrack_s4s_id'];
                            file_put_contents($logFile, '"' . $email . '";"' . $s4s_id . '"' . "\n", FILE_APPEND | LOCK_EX);
                        }
                    }
                    $count++;
                }
            }
        }
    }


    public function usageHelp() {
    // --type         : 'prospects' || 'contacts'
    // --max_messages : some integer value more than zero
        return <<<USAGE

Usage:  php -f syncToDynos.php [--max_messages <number>] [--type <string>]



USAGE;
    }

}

$shell = new Schracklive_Shell_syncToDynos();
$shell->run();
