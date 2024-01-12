<?php
require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_AddressTool extends Mage_Shell_Abstract {

    private $systemContacts;
    private $customerNo;
    private $fix = false;
    private $restoreDefaultShipping = false;
    private $saveDefaultShipping = false;
    private $readConnection, $writeConnection;
    private $maxAccounts = 999999;
    private $dryRun = false;

    function __construct () {
        parent::__construct();
        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        if ( $this->getArg('maxAccounts') ) {
            $this->maxAccounts = $this->getArg('maxAccounts');
        }
        if ( $this->getArg('customerNo') ) {
            $this->customerNo = $this->getArg('customerNo');
        }
        if ($this->getArg('dry_fix')) {
            $this->dryRun = true;
            $this->fix = true;
        } else if ($this->getArg('fix')) {
            $this->fix = true;
        }
        if ($this->getArg('restore_default_shipping')) {
            $this->restoreDefaultShipping = array();
            $fp = fopen($this->getDefaultBillingFile(),"rt");
            while ( $line = fgetcsv($fp) ) {
                $this->restoreDefaultShipping[$line[0]] = $line[1];
            }
            fclose($fp);
        } else if ($this->getArg('save_default_shipping')) {
            $this->saveDefaultShipping = fopen($this->getDefaultBillingFile(),"wt");
        }
    }

    public function run () {
        $this->getSystemContacts();
        $cnt = $good = $bad = 0;
        foreach ( $this->systemContacts as $systemContact ) {
            if ( $this->handleSystemContact($systemContact) ) {
                ++$good;
            } else {
                ++$bad;
            }
            if ( ++$cnt >= $this->maxAccounts ) {
                break;
            }
        }
        if ( $this->saveDefaultShipping ) {
            fclose($this->saveDefaultShipping);
        }
        echo "$good accounts seems to be ok, $bad are/was not." . PHP_EOL;
        echo 'done.' . PHP_EOL;
    }

    private function getDefaultBillingFile () {
        $magentoCountryCode = strtoupper(Mage::getStoreConfig('general/country/default'));
        return "/tmp/default_billing_$magentoCountryCode.csv";
    }

    private function getSystemContacts () {
        $this->systemContacts = Mage::getModel('customer/customer')
        ->getCollection()
        ->addAttributeToSelect('*')
        ->addFieldToFilter('group_id',Mage::getStoreConfig('schrack/shop/system_group'));
        if ( $this->customerNo ) {
            $this->systemContacts->addFieldToFilter('schrack_wws_customer_id',$this->customerNo);
        }
        if ( $this->maxAccounts < 999999 ) {
            $this->systemContacts->getSelect()->limit($this->maxAccounts);
        }
    }

    private function handleSystemContact ( Schracklive_SchrackCustomer_Model_Customer $systemContact ) {
        $ok = true;
        $touchAccount = false;
        $accountID = $systemContact->getSchrackAccountId();
        $customerID = $systemContact->getSchrackWwsCustomerId();
        $cntActiveUsers = $this->countActiveUsers($accountID);
        if ( $cntActiveUsers < 1 ) {
            return $ok;
        }
        if ( $this->fix || $this->restoreDefaultShipping ) {
            $this->writeConnection->beginTransaction();
        }
        try {
            $addresses = $systemContact->getAddresses();

            if ( $this->restoreDefaultShipping ) {
                $addrId = $this->restoreDefaultShipping[$systemContact->getId()];
                if ( $addrId != null && $addrId != $systemContact->getDefaultShipping() ) {
                    $systemContact->setDefaultShipping($addrId);
                    $touchAccount = true;
                }
            }

            $cnt = count($addresses);
            if ( $cnt < 2 ) {
                echo "$customerID: too less addresses ($cnt)." . PHP_EOL;
            }

            // we do not delete duplets yet because they can be still referenced in WWS for example
            // $ok &= $this->handleDuplets($customerID,$addresses,$systemContact->getDefaultShipping(),$touchAccount);

            $wwsId2addrMap = array();
            $entityId2addrMap = array();
            $defaultShipping = -1;
            $defaultBilling = -1;
            foreach ( $addresses as $addr ) {
                $entityId2addrMap[$addr->getId()]= $addr;
                $wwsID = intval($addr->getSchrackWwsAddressNumber());
                if ( $addr->getId() == $systemContact->getDefaultBilling() ) {
                    $defaultBilling = $wwsID;
                } else if ( $addr->getId() == $systemContact->getDefaultShipping() ) {
                    $defaultShipping = $wwsID;
                }
                if ( !isset($wwsID) ) {
                    echo $customerID . ': address with entity_id ' . $addr->getId() . ' has no WWS ID.' . PHP_EOL;
                    // TODO: fix  --- really?
                    $ok = false;
                    continue;
                }
                if ( !isset($wwsId2addrMap[$wwsID]) ) {
                    $wwsId2addrMap[$wwsID] = array();
                }
                $wwsId2addrMap[$wwsID][] = $addr;
            }
            $wwsId2addrMap = ksort($wwsId2addrMap);

            if ( !isset($wwsId2addrMap[0]) ) {
                echo "$customerID: no address with ID 0 found." . PHP_EOL;
                // TODO: fix -- but how???
                $ok = false;
            }

            foreach ( $wwsId2addrMap as $wwsID => $addrsPerWwsID ) {
                $cnt = count($addrsPerWwsID);
                if ( $cnt > 1 ) {
                    echo "$customerID: $cnt addresses with the same address ID $wwsID." . PHP_EOL;
                    if ( $this->fix ) {
                        $first = true;
                        $keepThat = null;
                        foreach ( $addrsPerWwsID as $addr ) {
                            if ( $addr->getUpddatedBy() == Schracklive_Schrack_Helper_Stomp::MQ_IMPORT_MARKER ) {
                                $keepThat = $addr;
                                break;
                            }
                        }
                        foreach ( $addrsPerWwsID as $addr ) {
                            if (    ($keepThat != null && $addr->getId() == $keepThat->getId())
                                 || ($keepThat == null && $first)                               ) {
                                $first = false;
                            } else {
                                $addr->setSchrackWwsAddressNumber(Schracklive_SchrackCustomer_Model_Address::NO_ADDRESS_NUMBER);
                                $addr->save();
                            }
                        }
                        $touchAccount = true;
                    }
                    $ok = false;
                }
            }

            if ( $defaultBilling == -1 ) {
                echo "$customerID: default billing address with entity_id " . $systemContact->getDefaultBilling() . " does not exist. ";
                if ( $this->fix && $cnt > 0 ) {
                    $touchAccount = $this->assignDefaultBilling($systemContact,$wwsId2addrMap[0]);
                }
                echo  PHP_EOL;
                $ok = false;
            } else if ( !isset($defaultBilling) ) {
                echo "$customerID: default billing address with entity_id " . $systemContact->getDefaultBilling() . " has no address ID.";
                if ( $this->fix && $cnt > 0 ) {
                    $touchAccount = $this->assignDefaultBilling($systemContact,$wwsId2addrMap[0]);
                    if ( ! $touchAccount ) {
                        $addr = $entityId2addrMap[$systemContact->getDefaultBilling()];
                        $addr->setSchrackWwsAddressNumber(0);
                        $addr->save();
                        $touchAccount = true;
                    }
                }
                echo  PHP_EOL;
                $ok = false;
            } else if ( $defaultBilling != 0 ) {
                echo "$customerID: default billing address with entity_id " . $systemContact->getDefaultBilling() . " is not ID 0";
                if ( $this->fix && $cnt > 0 ) {
                    $touchAccount = $this->assignDefaultBilling($systemContact,$wwsId2addrMap[0]);
                }
                echo  PHP_EOL;
                $ok = false;
            }

            if ( $defaultShipping == -1 ) {
                echo "$customerID: default shipping address with entity_id " . $systemContact->getDefaultShipping() . " does not exist." . PHP_EOL;
                if ( $this->fix && $cnt > 0 ) {
                    $v = $addresses[0];
                    if ( $cnt > 1 ) {
                        foreach ( $wwsId2addrMap as $k => $varr ) {
                            if ( $k > 0 ) {
                                $v = $varr[0];
                                break;
                            }
                        }
                    }
                    $systemContact->setDefaultShipping($v);
                    $touchAccount = true;
                }
                $ok = false;
            } else if ( !isset($defaultShipping) ) {
                echo "$customerID: default shipping address with entity_id " . $systemContact->getDefaultShipping() . " has no address ID." . PHP_EOL;
                if ( $this->fix && $cnt > 0 ) {
                    $addr = $entityId2addrMap[$systemContact->getDefaultShipping()];
                    $addr->setSchrackWwsAddressNumber(Schracklive_SchrackCustomer_Model_Address::NO_ADDRESS_NUMBER);
                    $addr->save();
                    $touchAccount = true;
                }
                $ok = false;
            }
            if ( $this->saveDefaultShipping ) {
                fputcsv($this->saveDefaultShipping,array($systemContact->getId(),$systemContact->getDefaultShipping()));
            }

            if ( $touchAccount ) {
                $account = $systemContact->getAccount();
                $account->setCrmStatus(Schracklive_Crm_Helper_Data::STATUS_ACCOUNT_PENDING);
                $systemContact->save();
                $account->save();
            }
        } catch ( Exception $ex ) {
            if ( $this->fix || $this->restoreDefaultShipping ) {
                $this->writeConnection->roolback();
            }
            throw $ex;
        }
        if ( $this->fix || $this->restoreDefaultShipping ) {
            if ( $touchAccount && ! $this->dryRun ) {
                $this->writeConnection->commit();
            } else {
                $this->writeConnection->rollback();
            }
        }

        return $ok;
    }

    private function handleDuplets ( $customerID, &$addresses, $defaultShipping, &$touchAccount ) {
        $indexedAddresses = array_values($addresses);
        $ok = true;
        $len = count($indexedAddresses);
        $toDelete = array();
        for ( $i = 0; $i < $len; $i++ ) {
            $addr1 = $indexedAddresses[$i];
            $wwsID1 = $this->getSaveWwsId($addr1);
            if ( intval($wwsID1) == 0 ) {
                continue;
            }
            $data1 = $this->getAddrData2compare($addr1);
            for ( $j = $i + 1; $j < $len; $j++ ) {
                $addr2 = $indexedAddresses[$j];
                if ( $addr1->getId() == $addr2->getId() ) {
                    continue;
                }
                $wwsID2 = $this->getSaveWwsId($addr2);
                if ( intval($wwsID2) == 0 ) {
                    continue;
                }
                $data2 = $this->getAddrData2compare($addr1);
                $diff = array_diff_assoc($data1,$data2);
                if ( count($diff) == 0 ) {
                    echo "$customerID: 2 addresses seems to be equal: $wwsID1 and $wwsID2" . PHP_EOL;
                    $ok = false;
                    $toDelete[] = ( $addr2->getId() == $defaultShipping ) ? $addr1 : addr2;
                }
            }
        }
        if ( $this->fix && count($toDelete) > 0 ) {
            foreach ( $toDelete as $deleteAddress ) {
                $wwsID = $this->getSaveWwsId($deleteAddress);
                echo "$customerID: deleting now address $wwsID" . PHP_EOL;
                $this->delete($deleteAddress,$addresses);
                $touchAccount = true;
            }
        }

        return $ok;
    }

    private function delete ( $deleteAddress, &$addresses ) {
        $k = $v = null;
        foreach ( $addresses as $k => $v ) {
            if ( $deleteAddress->getId() == $v->getId() ) {
                break;
            }
        }
        if ( $k ) {
            unset($addresses[$k]);
            $deleteAddress->delete();
        }
    }

    private function getSaveWwsId ( $addr ) {
        $id = $addr->getSchrackWwsAddressNumber();
        if ( $id = null ) {
            return '(null)';
        }
        return $id;
    }

    private function getAddrData2compare ( $addr ) {
        $data = $addr->getData();
        unset($data['entity_id']);
        unset($data['created_at']);
        unset($data['updated_at']);
        unset($data['created_by']);
        unset($data['updated_by']);
        unset($data['schrack_wws_address_number']);
        return $data;
    }

    private function assignDefaultBilling ( $systemContact, $AddrWithId0 ) {
        if ( $AddrWithId0 != null && $AddrWithId0->getId() != null ) {
            $systemContact->setDefaultBilling($AddrWithId0->getId());
            return true;
        }
        return false;
    }

    private function countActiveUsers ( $accountID ) {
        $activGroupID = Mage::getStoreConfig('schrack/shop/contact_group');
        $sql = "SELECT count(*) FROM customer_entity WHERE group_id = $activGroupID AND schrack_account_id = '$accountID';";
        try {
            $res = $this->readConnection->fetchOne($sql);
        } catch ( Exception $ex ) {
            echo PHP_EOL . $sql . PHP_EOL;
            throw $ex;
        }
        return $res;
    }

    public function usageHelp () {
        return <<<USAGE

Usage: php AddressTool.php [--maxAccounts <count>] [--customerNo <wws customer number>] [--fix|--dry_fix] [--save_default_shipping] [--restore_default_shipping]


USAGE;
    }
}

(new Schracklive_Shell_AddressTool())->run();
