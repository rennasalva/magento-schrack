<?php
/**
 * Anowave Package
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Anowave license that is
 * available through the world-wide-web at this URL:
 * http://www.anowave.com/license-agreement/
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category 	Anowave
 * @package 	Anowave_Package
 * @copyright 	Copyright (c) 2016 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */
abstract class Anowave_Package_Model_Setup
{
	/**
	 * @var (object) Core Setup
	 */
	protected $core = null;
	
	/**
	 * @var (object) Catalog Setup
	 */
	protected $catalog_setup = null;
	
	/**
	 * @var (object) Catalog Resource Setup
	 */
	protected $catalog_resource_setup = null;
	
	/**
	 * @var (object) Entity Setup
	 */
	protected $entity_setup = null;
	
	/**
	 * Installer
	 */
	protected $installer = null;
	
	/**
	 * Get Core Setup
	 * @return (object)
	 */
	protected function getCoreSetup()
	{
		if (!$this->core)
		{
			$this->core = new Mage_Eav_Model_Entity_Setup('core_setup');
		}
	
		return $this->core;
	}
	
	/**
	 * Get Catalog Setup 
	 * @return (object)
	 */
	protected function getCatalogSetup()
	{
		if (!$this->catalog_setup)
		{
			$this->catalog_setup = new Mage_Eav_Model_Entity_Setup('catalog_setup');
		}
		return $this->catalog_setup;
	}
	
	public function getCatalogResourceSetup()
	{
		if (!$this->catalog_resource_setup)
		{
			$this->catalog_resource_setup = new Mage_Catalog_Model_Resource_Setup('catalog_setup');
		}
		return $this->catalog_resource_setup;
	}
	
	/**
	 * Get Entity Setup
	 * @return (object)
	 */
	protected function getEntitySetup()
	{
		if (!$this->entity_setup)
		{
			$this->entity_setup = Mage::getModel('customer/entity_setup', 'core_setup');
		}
		return $this->entity_setup;
	}
	
	/**
     * Define abstract functions
	 */
	abstract function setup(Mage_Core_Model_Resource_Setup $installer);
}