<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('account')} ADD `delivery_block` tinyint(4) NOT NULL default '0';
ALTER TABLE {$this->getTable('account')} ADD `email` varchar(255) NOT NULL default '';
ALTER TABLE {$this->getTable('account')} ADD `homepage` varchar(255) NOT NULL default '';

    ");

$installer->endSetup();

// create Magento customer addresses for the system contacts of the accounts from the obsolete account addresses
$account = Mage::getModel('account/account');
/* @var $account Schracklive_Account_Model_Account */
$address = Mage::getModel('customer/address');
/* @var $address Schracklive_SchrackCustomer_Model_Address */
$connection = $installer->getConnection();
/* @var $connection Varien_Db_Adapter_Pdo_Mysql */
$selectAccounts = $connection->select()
                ->from('account')
                ->where("wws_customer_id!=''");
foreach ($connection->fetchAll($selectAccounts) as $row) {
    $account->setData($row);
    $customer = $account->getSystemContact();
    /* @var $customer Schracklive_SchrackCustomer_Model_Customer */
    if ($customer->getPrimaryBillingAddress()) {
        continue;
    }
    $address->setData(array()); // clear attributes
    if ($customer->getId()) {
        $address->setCustomerId($customer->getId());
        $address->setSchrackWwsAddressNumber(0);
        $address->setSchrackType(1);
        $address->setName1($row['name1']);
        $address->setName2($row['name2']);
        $address->setName3($row['name3']);
        $address->setStreet($row['street']);
        $address->setCity($row['city']);
        $address->setPostcode($row['postcode']);
        $address->setCountryId($row['country_id']);
        $address->save();

        $customer->setDefaultBilling($address->getId());
        $customer->getResource()->saveAttribute($customer, 'default_billing');

        // get ids of other contacts and set new address as default billing address
        // note: might have been done with a collection - how?
        $resource = $customer->getResource();
        $selectContacts = $connection->select()
                        ->from(array('e' => $resource->getEntityTable()))
                        ->join(array('a1' => $resource->getAttribute('schrack_wws_customer_id')->getBackendTable()),
                                'e.' . $resource->getEntityIdField() . '=a1.entity_id AND a1.attribute_id=' . $resource->getAttribute('schrack_wws_customer_id')->getId(),
                                array())    // no columns from the atribute value table
                        ->join(array('a2' => $resource->getAttribute('schrack_wws_contact_number')->getBackendTable()),
                                'e.' . $resource->getEntityIdField() . '=a2.entity_id AND a2.attribute_id=' . $resource->getAttribute('schrack_wws_contact_number')->getId(),
                                array())    // no columns from the atribute value table
                        ->where('a1.value=? AND a2.value!=-1', $account->getWwsCustomerId());
        foreach ($connection->fetchAll($selectContacts) as $contactRow) {
            $customer->setData($contactRow);
            $customer->setDefaultBilling($address->getId());
            $customer->getResource()->saveAttribute($customer, 'default_billing');
        }
    }
}
