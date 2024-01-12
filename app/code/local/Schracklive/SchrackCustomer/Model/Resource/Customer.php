<?php

/**
 * Customer entity resource model
 *
 * @category    Mage
 * @package    Mage_Customer
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Schracklive_SchrackCustomer_Model_Resource_Customer extends Mage_Customer_Model_Resource_Customer
{
    /**
     * Resource initialization
     */
    public function __construct()
    {
        $this->setType('customer');
        $this->setConnection('customer_read', 'customer_write');
    }

    /**
     * Retrieve customer entity default attributes
     *
     * @return array
     */
    protected function _getDefaultAttributes()
    {
        return array(
            'entity_type_id',
            'attribute_set_id',
            'created_at',
            'updated_at',
            'increment_id',
            'store_id',
            'website_id'
        );
    }

    /**
     * Check customer scope, email and confirmation key before saving
     *
     * @param Mage_Customer_Model_Customer $customer
     * @throws Mage_Customer_Exception
     * @return Mage_Customer_Model_Resource_Customer
     */
    protected function _beforeSave(Varien_Object $customer)
    {
        // Do some checks before execute Default Magento Checks:
        if (!$customer->getEmail()) {
            $data = openssl_random_pseudo_bytes(16);
            assert(strlen($data) == 16);

            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

            $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

            Mage::log($uuid . ' - Saved Customer Object as follows:', null, 'customer_email_is_required.log');
            Mage::log($customer, null, 'customer_email_is_required.log');
            throw Mage::exception('Mage_Customer', Mage::helper('customer')->__('Customer email is required #1 - Log :' . $uuid));
        } else{
            // Used for concatenated Log-String:
            $givenEmail = $customer->getEmail();
        }

        $adapter = $this->_getWriteAdapter();
        $bind    = array('email' => $customer->getEmail());

        $select = $adapter->select()
                          ->from($this->getEntityTable(), array($this->getEntityIdField()))
                          ->where('email = :email');
        if ($customer->getSharingConfig()->isWebsiteScope()) {
            $bind['website_id'] = (int)$customer->getWebsiteId();
            $select->where('website_id = :website_id');
        }

        if ($customer->getId()) {
            // Case #1: Customer Object should be updated:
            $bind['entity_id'] = (int)$customer->getId();
            $select->where('entity_id != :entity_id');
            // Used for concatenated Log-String:
            $givenEntityID = $customer->getId() . ' (Should result in an UPDATE Statement : updates existing recordset in customer_entity)';
        } else {
            // Case #2: New Customer Object should be inserted
            // Used for concatenated Log-String:
            $givenEntityID = 'NONE (Should result in an INSERT Statement : new recordset into customer_entity)';
        }

        $fetchedEntityID = $adapter->fetchOne($select, $bind);

        if ($fetchedEntityID) {
            $rebuildCustomer = Mage::getModel('customer/customer');
            $rebuildCustomer->setWebsiteId(Mage::app()->getWebsite()->getId());
            $rebuildCustomer->loadByEmail($customer->getEmail());
            // Slit cases:
            if ($rebuildCustomer->getSchrackCustomerType() == 'light-prospect' || $rebuildCustomer->getSchrackCustomerType() == 'full-prospect') {
                Mage::log('======================================== This log-entry is from custom code module (case#1) ===================================================================', null, 'debug_already_existing_customer.log');
                Mage::log($customer, null, 'debug_already_existing_customer.log');
                Mage::log('Account-ID: ' . $rebuildCustomer->getSchrackAccountId() . ' ' . $givenEmail . ' ==> Fetched Entity ID -> '  . $fetchedEntityID . ' Given Entity ID -> ' . $givenEntityID, null, 'debug_already_existing_customer.log');
                Mage::log('======================================================================================================================================================' . "\n" . "\n", null, 'debug_already_existing_customer.log');
            } else {
                Mage::log('======================================== This log-entry is from custom code module (default case) ===================================================================', null, 'debug_already_existing_customer.log');
                Mage::log($customer, null, 'debug_already_existing_customer.log');
                Mage::log($givenEmail . ' ==> Fetched Entity ID -> '  . $fetchedEntityID . ' Given Entity ID -> ' . $givenEntityID, null, 'debug_already_existing_customer.log');
                Mage::log('======================================================================================================================================================' . "\n" . "\n", null, 'debug_already_existing_customer.log');
            }

        }

        // Real Check Routines with possible Exception, if errors occurred:
        parent::_beforeSave($customer);

        if ($fetchedEntityID) {
            if ($rebuildCustomer->getSchrackCustomerType() == 'light-prospect' || $rebuildCustomer->getSchrackCustomerType() == 'full-prospect') {
                throw Mage::exception(
                    'Mage_Customer', Mage::helper('customer')->__('This customer email already exists (custom code module) (case#1: shop-customer is ' . $rebuildCustomer->getSchrackCustomerType() . ') (Schrack-Account-ID: ' . 'Account-ID: ' . $rebuildCustomer->getSchrackAccountId() . ')'),
                    Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS
                );
            } else {
                throw Mage::exception(
                    'Mage_Customer', Mage::helper('customer')->__('This customer email already exists (custom code module) (default case)'),
                    Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS
                );
            }
        }

        // set confirmation key logic
        if ($customer->getForceConfirmed()) {
            $customer->setConfirmation(null);
        } elseif (!$customer->getId() && $customer->isConfirmationRequired()) {
            $customer->setConfirmation($customer->getRandomConfirmationKey());
        }
        // remove customer confirmation key from database, if empty
        if (!$customer->getConfirmation()) {
            $customer->setConfirmation(null);
        }

        return $this;
    }

    /**
     * Save customer addresses and set default addresses in attributes backend
     *
     * @param Varien_Object $customer
     * @return Mage_Eav_Model_Entity_Abstract
     */
    protected function _afterSave(Varien_Object $customer)
    {
        $this->_saveAddresses($customer);
        return parent::_afterSave($customer);
    }

    /**
     * Save/delete customer address
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return Mage_Customer_Model_Resource_Customer
     */
    protected function _saveAddresses(Mage_Customer_Model_Customer $customer)
    {
        $defaultBillingId   = $customer->getData('default_billing');
        $defaultShippingId  = $customer->getData('default_shipping');
        foreach ($customer->getAddresses() as $address) {
            if ($address->getData('_deleted')) {
                if ($address->getId() == $defaultBillingId) {
                    $customer->setData('default_billing', null);
                }
                if ($address->getId() == $defaultShippingId) {
                    $customer->setData('default_shipping', null);
                }
                $address->delete();
            } else {
                $address->setParentId($customer->getId())
                    ->setStoreId($customer->getStoreId())
                    ->setIsCustomerSaveTransaction(true)
                    ->save();
                if (($address->getIsPrimaryBilling() || $address->getIsDefaultBilling())
                    && $address->getId() != $defaultBillingId
                ) {
                    $customer->setData('default_billing', $address->getId());
                }
                if (($address->getIsPrimaryShipping() || $address->getIsDefaultShipping())
                    && $address->getId() != $defaultShippingId
                ) {
                    $customer->setData('default_shipping', $address->getId());
                }
            }
        }
        if ($customer->dataHasChangedFor('default_billing')) {
            $this->saveAttribute($customer, 'default_billing');
        }
        if ($customer->dataHasChangedFor('default_shipping')) {
            $this->saveAttribute($customer, 'default_shipping');
        }

        return $this;
    }

    /**
     * Retrieve select object for loading base entity row
     *
     * @param Varien_Object $object
     * @param mixed $rowId
     * @return Varien_Db_Select
     */
    protected function _getLoadRowSelect($object, $rowId)
    {
        $select = parent::_getLoadRowSelect($object, $rowId);
        if ($object->getWebsiteId() && $object->getSharingConfig()->isWebsiteScope()) {
            $select->where('website_id =?', (int)$object->getWebsiteId());
        }

        return $select;
    }

    /**
     * Load customer by email
     *
     * @throws Mage_Core_Exception
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param string $email
     * @param bool $testOnly
     * @return Mage_Customer_Model_Resource_Customer
     */
    public function loadByEmail(Mage_Customer_Model_Customer $customer, $email, $testOnly = false)
    {
        $adapter = $this->_getReadAdapter();
        $bind    = array('customer_email' => $email);
        $select  = $adapter->select()
            ->from($this->getEntityTable(), array($this->getEntityIdField()))
            ->where('email = :customer_email');

        if ($customer->getSharingConfig()->isWebsiteScope()) {
            if (!$customer->hasData('website_id')) {
                Mage::throwException(
                    Mage::helper('customer')->__('Customer website ID must be specified when using the website scope')
                );
            }
            $bind['website_id'] = (int)$customer->getWebsiteId();
            $select->where('website_id = :website_id');
        }

        $customerId = $adapter->fetchOne($select, $bind);
        if ($customerId) {
            $this->load($customer, $customerId);
        } else {
            $customer->setData(array());
        }

        return $this;
    }

    /**
     * Change customer password
     *
     * @param Mage_Customer_Model_Customer $customer
     * @param string $newPassword
     * @return Mage_Customer_Model_Resource_Customer
     */
    public function changePassword(Mage_Customer_Model_Customer $customer, $newPassword)
    {
        $customer->setPassword($newPassword);
        $this->saveAttribute($customer, 'password_hash');
        return $this;
    }

    /**
     * Check whether there are email duplicates of customers in global scope
     *
     * @return bool
     */
    public function findEmailDuplicates()
    {
        $adapter = $this->_getReadAdapter();
        $select  = $adapter->select()
            ->from($this->getTable('customer/entity'), array('email', 'cnt' => 'COUNT(*)'))
            ->group('email')
            ->order('cnt DESC')
            ->limit(1);
        $lookup = $adapter->fetchRow($select);
        if (empty($lookup)) {
            return false;
        }
        return $lookup['cnt'] > 1;
    }

    /**
     * Check customer by id
     *
     * @param int $customerId
     * @return bool
     */
    public function checkCustomerId($customerId)
    {
        $adapter = $this->_getReadAdapter();
        $bind    = array('entity_id' => (int)$customerId);
        $select  = $adapter->select()
            ->from($this->getTable('customer/entity'), 'entity_id')
            ->where('entity_id = :entity_id')
            ->limit(1);

        $result = $adapter->fetchOne($select, $bind);
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * Get customer website id
     *
     * @param int $customerId
     * @return int
     */
    public function getWebsiteId($customerId)
    {
        $adapter = $this->_getReadAdapter();
        $bind    = array('entity_id' => (int)$customerId);
        $select  = $adapter->select()
            ->from($this->getTable('customer/entity'), 'website_id')
            ->where('entity_id = :entity_id');

        return $adapter->fetchOne($select, $bind);
    }

    /**
     * Custom setter of increment ID if its needed
     *
     * @param Varien_Object $object
     * @return Mage_Customer_Model_Resource_Customer
     */
    public function setNewIncrementId(Varien_Object $object)
    {
        if (Mage::getStoreConfig(Mage_Customer_Model_Customer::XML_PATH_GENERATE_HUMAN_FRIENDLY_ID)) {
            parent::setNewIncrementId($object);
        }
        return $this;
    }

    /**
     * Change reset password link token
     *
     * Stores new reset password link token and its creation time
     *
     * @param Mage_Customer_Model_Customer $newResetPasswordLinkToken
     * @param string $newResetPasswordLinkToken
     * @return Mage_Customer_Model_Resource_Customer
     */
    public function changeResetPasswordLinkToken(Mage_Customer_Model_Customer $customer, $newResetPasswordLinkToken) {
        if (is_string($newResetPasswordLinkToken) && !empty($newResetPasswordLinkToken)) {
            $customer->setRpToken($newResetPasswordLinkToken);
            $currentDate = Varien_Date::now();
            $customer->setRpTokenCreatedAt($currentDate);
            $this->saveAttribute($customer, 'rp_token');
            $this->saveAttribute($customer, 'rp_token_created_at');
        }
        return $this;
    }
}
