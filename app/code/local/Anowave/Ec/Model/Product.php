<?php
/**
 * Anowave Google Tag Manager Enhanced Ecommerce (UA) Tracking
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
 * @package 	Anowave_Ec
 * @copyright 	Copyright (c) 2016 Anowave (http://www.anowave.com/)
 * @license  	http://www.anowave.com/license-agreement/
 */

class Anowave_Ec_Model_Product extends Schracklive_SchrackCatalog_Model_Product
{
	/**
	 * Experiment 
	 * 
	 * @var array
	 */
	private $experiment = array();
	

	/**
	 * Get short description
	 */
	public function getShortDescription()	
	{
		if (property_exists($this->getExperimentData(), 'short_description') && $this->trace())
		{
			return $this->createAttributeExperimentBlock()->setData(array
			(
				'content'	 => $this->getData('description'),
				'experiment' => $this->getExperimentData()->short_description
			))->toHtml();
		}
		
		return $this->getData('short_description');
	}
	
	/**
     * Get full description
	 */
	public function getDescription()
	{
		if (property_exists($this->getExperimentData(), 'description') && $this->trace())
		{
			return $this->createAttributeExperimentBlock()->setData(array
			(
				'content'	 => $this->getData('description'),
				'experiment' => $this->getExperimentData()->description
			))->toHtml();
		}
		
		return $this->getData('description');
	}
	
	/**
	 * Get experiment data
	 */
	public function getExperimentData()
	{
		if (!$this->experiment)
		{
			$experiment = Mage::helper('ec/experiments')->getCurrentExperiment();
			
			if ($experiment)
			{	
				/**
				 * Load products in this experiment
				 */
				$collection = Mage::getModel('ec/data')->getCollection()->addFieldToFilter('data_product_id', $this->getId())->addFieldToFilter('ab_store_id', Mage::helper('ec/experiments')->getCurrentStoreScope());
					
				$collection->addFieldToFilter('data_ab_id', array
				(
					'in' => array($experiment['data']['ab_id'])
				));
					
				/**
				 * Join stores
				*/
				$collection->getSelect()->join
				(
					array('ab_store' => Mage::getSingleton('core/resource')->getTableName('anowave_ab_store')), 'ab_store.ab_id = main_table.data_ab_id'
				);
					
				foreach ($collection as $entity)
				{
					if ($entity->getDataAttributeContent())
					{
						$this->experiment[$entity->getDataAttributeCode()] = $entity->getDataAttributeContent();
					}
				}
			}
			
			$this->experiment = (object) $this->experiment;
		}
		
		return $this->experiment;
	}
	
	public function createAttributeExperimentBlock()
	{
		return Mage::app()->getLayout()->createBlock('ec/experiment_attribute')->setTemplate('ec/attribute.phtml');
	}
	
	private function trace()
	{
		if (Anowave_Ec_Model_System_Config_Behaviour::CONTENT_REPLACE === Mage::helper('ec/experiments')->getBehaviour())
		{
			return false;
		}
	
		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $trace)
		{
			if ($trace['object'] instanceof  Mage_Catalog_Block_Product_View)
			{
				return true;
			}
		}
	
		return false;
	}
}