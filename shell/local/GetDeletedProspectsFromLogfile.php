<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class GetDeletedProspectsFromLogfile extends Mage_Shell_Abstract {

    const DELIMITERS = ' :"';

	var $countryCode;
	var $logfiles = [];
	var $_readConnection;

    function __construct() {
        parent::__construct();
        if ( ($this->getArg('logfile')) ) {
            $this->logfiles = [ $this->getArg('logfile') ];
        }
        $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->countryCode = strtolower(Mage::getStoreConfig('schrack/general/country'));
    }

	public function run () {
        if ( count($this->logfiles) == 0 ) {
            $logDir = Mage::getBaseDir('log');
            $pattern = $logDir . DS . 'schracklive_mq_account_in.log*';
            $this->logfiles = glob($pattern);
        }
        foreach ( $this->logfiles as $logfile ) {
            $this->handleOneLogfile($logfile);
        }
	}

	private function handleOneLogfile ( $logfile ) {
        if ( strpos($logfile,'.gz') == strlen($logfile) - 3 ) {
            $fp = gzopen($logfile,"r");
        } else {
            $fp = fopen($logfile, "r");
        }
        if ( ! $fp ) {
            throw new Exception("cannot open file '$logfile'!");
        }
        $inProspect = false;
        while ( $line = fgets($fp) ) {
            $line = trim($line);
            if ( strpos($line,'prospect {') === 0 ) {
                $inProspect = true;
                $data = [];
            } else if ( strpos($line,'}') === 0 ) {
                if ( $inProspect ) {
                    $this->handleProspectData($data,$ts);
                    $inProspect = false;
                }
            } else if ( $inProspect ) {
                $k = strtok($line,self::DELIMITERS);
                $v = strtok(self::DELIMITERS);
                $data[$k] = $v;
            } else if ( strpos($line,' UTC') == 19 ) {
                $ts = substr($line,0,19);
            }
        }
        fclose($fp);
    }

    private function handleProspectData ( array $data, $timestamp ) {
        $state = $data['state'];
        if ( $state != '3' && $state != '4' ) {
            return;
        }
        $email = $data['email'];
        $dbCustomer = $this->getDbCustomer($email);
        $altDbCustomer = $this->getAltDbCustomer($email);
        echo "{$this->countryCode} $email state: $state ";
        if ( $dbCustomer ) {
            $type = $dbCustomer->getSchrackCustomerType();
            if ( $type == 'light-prospect' || $type == 'full-prospect' ) {
                if ( $state == 4  ) {
                    echo "BAD (deleted_created) ";
                } else if ( $this->sameTimestamp($timestamp,$dbCustomer->getCreatedAt()) ) {
                    echo 'OK (old inactive) ';
                } else {
                    echo "BAD (inactive_created) ";
                }
            } else {
                echo 'OK (old_contact) ';
            }
            if ( ! $dbCustomer->getConfirmation() ) {
                echo "CONFIRMED! ";
            }
        } else {
            echo 'OK (not_in_DB) ';
        }
        if ( $altDbCustomer ) {
            echo "ALT: [{$altDbCustomer->getEmail()}] [{$altDbCustomer->getSchrackWwsCustomerId()}] [{$altDbCustomer->getSchrackWwsContactNumber()}] [{$altDbCustomer->getSchrackS4yId()}]";
        }
        echo "\n";
    }

    private function sameTimestamp ( $ts1, $ts2 ) {
        return substr($ts1,0,17) == substr($ts2,0,17);
    }

    private function getDbCustomer ( $email ) {
        $sql = "SELECT entity_id FROM customer_entity WHERE email LIKE ?";
        $id = $this->_readConnection->fetchOne($sql,$email);
        return $this->getCustomerById($id);
    }

    private function getAltDbCustomer ( $email ) {
        $sql = " SELECT entity_id FROM customer_entity_text WHERE"
             . " attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'schrack_emails')"
             . " AND value LIKE '%" . $email . "%'";
        $id = $this->_readConnection->fetchOne($sql);
        return $this->getCustomerById($id);
    }

    private function getCustomerById ( $id ) {
        if ( ! $id ) {
            return null;
        }
        $customer = Mage::getModel('customer/customer')->load($id);
        if ( ! $customer->getId() ) {
            return null;
        }
        return $customer;
    }

}

$shell = new GetDeletedProspectsFromLogfile();
$shell->run();
