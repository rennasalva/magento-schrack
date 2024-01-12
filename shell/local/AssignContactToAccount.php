<?php

require_once 'shell.php';

class Schracklive_Shell_AssignContactToAccount extends Schracklive_Shell {
    private $email;
    private $wwsId;
    private $contactNo = 1;
    private $customer;
    private $account;
    private $roleId = false;

	public function usageHelp() {
		return <<<USAGE

Usage:  php -f AssignContactToAccount.php --email <contact email> --customerid <wws customer id> [--contactno <contact number>] [--groupid <group id>]


USAGE;
	}

	public function run () {
        $this->email = $this->getArg('email');
        $this->wwsId = $this->getArg('customerid');
        if ( ! $this->email ||! $this->wwsId ) {
            die($this->usageHelp());
        }
        if ( $this->getArg('contactno') ) {
            $this->contactNo = $this->getArg('contactno');
        }
        // groupid
        if ( $this->getArg('groupid') ) {
            $this->roleId = $this->getArg('groupid');
        } else {
            $this->roleId = Mage::getStoreConfig('schrack/shop/contact_group');
        }
        $this->loadCustomer();
        $this->loadAccount();

        if ( $this->customer->getAccount() && $this->account->getId() == $this->customer->getAccount()->getId() ) {
            die("ERROR: customer " . $this->email . " is already on account " . $this->wwsId . PHP_EOL);
        }
        foreach ( $this->account->getAllContacts() as $otherContact ) {
            if ( intval($otherContact->getSchrackWwsContactNumber()) === intval($this->contactNo) ) {
                if ( $otherContact->getGroupId() == $this->roleId ) {
                    die("ERROR: contact number " . $this->contactNo . " already in use for contact " . $otherContact->getEmail() . PHP_EOL);
                } else {
                    echo "INFO: deleting now contact " . $otherContact->getEmail() . " with same contact no" . PHP_EOL;
                    $otherContact->delete();
                    break;
                }
            }
        }
        $oldAccount = $this->customer->getAccount();
        $this->customer->getSchrackWwsContactNumber($this->contactNo);
        $this->customer->setSchrackWwsCustomerId($this->account->getWwsCustomerId());
        $this->customer->setGroupId($this->roleId);
        $this->customer->setAccount($this->account);
        $this->customer->save();
        if ( $oldAccount ) {
            $oldAccount = $oldAccount->loadByWwsCustomerId($oldAccount->getWwsCustomerId());
            foreach ( $this->account->getAllContacts() as $otherContact ) {
                if ( intval($otherContact->getSchrackWwsContactNumber()) !== -1 && $otherContact->getId() != $this->customer->getId() ) {
                    die("INFO: account " . $oldAccount->getWwsCustomerId() . " has other, maybe inactive contacts and will not be deleted" . PHP_EOL);
                }
            }
            $oldAccount->delete();
        }
        echo "done." . PHP_EOL;
    }

    private function loadCustomer () {
        $this->customer = Mage::getModel('customer/customer');
        $this->customer->loadByEmail($this->email);
        if ( $this->customer->getId() ) {
            echo "loaded customerID " . $this->customer->getId() . PHP_EOL;
        } else {
            die("ERROR: customer email " . $this->email . " not found!" . PHP_EOL);
        }
    }

    private function loadAccount () {
        $this->account = Mage::getModel('account/account');
        $this->account->loadByWwsCustomerId($this->wwsId);
        if ( $this->account->getId() ) {
            echo "loaded accountID " . $this->account->getId() . PHP_EOL;
        } else {
            die("ERROR: account with wws id " . $this->wwsId . " not found!" . PHP_EOL);
        }
    }
}


(new Schracklive_Shell_AssignContactToAccount())->run();
