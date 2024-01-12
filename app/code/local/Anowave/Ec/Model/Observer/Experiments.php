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
class Anowave_Ec_Model_Observer_Experiments extends Anowave_Ec_Model_Observer
{
	/**
	 * Prepare product form in backend 
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function prepare(Varien_Event_Observer $observer)
	{
		$form = $observer->getForm();
		
		if (Mage::registry('product'))
		{
			$experiment_data = Mage::registry('product')->getData('experiment');
		}
		else 
		{
			$experiment_data = array();
		}
		
		/**
		 * Get experiment attributes 
		 * 
		 * @var array
		 */
		$experiment_attributes = Mage::helper('ec/experiments')->getExperimentAttributes();
		
		foreach ($experiment_attributes as $attribute => $options)
		{
			if ($observer->getForm()->getElement($attribute))
			{
				$fieldset = null;
					
				$entity_attribute = $observer->getForm()->getElement($attribute)->getEntityAttribute();
					
				foreach($form->getElements() as $element)
				{
					if ($element instanceof Varien_Data_Form_Element_Fieldset)
					{
						$fieldset = $element;
					}
				}
				
				if ($fieldset)
				{
					$queue = array();
					
					$queue[] = $attribute;
					
					foreach ($this->getExperiments() as $experiment_id => $experiment)
					{
						/**
						 * Read value
						 *
						 * @var string
						 */
						$value = isset($experiment_data[$experiment_id][$attribute]) ? $experiment_data[$experiment_id][$attribute] : '';
							
						/**
						 * Get element position
						 */
						$after = end($queue);
							
						/**
						 * Generate element id
						 *
						 * @var string
						*/
						$element = "experiment_{$experiment_id}_{$attribute}";
							
						/**
						 * Add field
						 */
	
						$field = $fieldset->addField($element, $options['field']['type'], call_user_func_array($options['field']['args'], array($experiment_id, $experiment, $value)), $after);
							
						/**
						 * Mimic entity attribute
						*/
						$field->setEntityAttribute($entity_attribute);
							
						/**
						 * Push new element to queue
						 *
						 * @var string
						*/
						$queue[] = $element;
					}
				}
			}
		}
	}
	
	/**
	 * Get experiments
	 */
	private function getExperiments()
	{
		return Mage::helper('ec/experiments')->getExperiments();
	}
	
	/**
	 * Save product experiments 
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function save(Varien_Event_Observer $observer)
	{
		$data = (array) @$_POST['product']['experiment'];
		
		if (isset($_POST['product']['experiment']))
		{
			try 
			{
				/**
				 * Delete previous experiment data
				 */
				$collection = Mage::getModel('ec/data')->getCollection()->addFieldToFilter('data_product_id', $observer->getProduct()->getId())->addFieldToFilter('ab_store_id', Mage::helper('ec/experiments')->getCurrentStoreScope());
				
				$collection->addFieldToFilter('data_ab_id', array
				(
					'in' => array_keys($data)
				));
				
				/**
				 * Join stores
				 */
				$collection->getSelect()->join
				(
					array('ab_store' => Mage::getSingleton('core/resource')->getTableName('anowave_ab_store')), 'ab_store.ab_id = main_table.data_ab_id'
				);
					
				$collection->getSelect()->group('main_table.data_id');	
				
				foreach ($collection as $entity)
				{
					$entity->delete();
				}
				
				foreach ($this->getExperiments() as $experiment_id => $experiment)
				{
					foreach ($_POST['product']['experiment'][$experiment_id] as $attribute => $content)
					{
						$model = Mage::getModel('ec/data');
							
						$model->setDataAbId($experiment_id);
						$model->setDataProductId($observer->getProduct()->getId());
						$model->setDataAttributeCode($attribute);
						$model->setDataAttributeContent($content);

						$model->save();
					}
				}
				
			}
			catch (\Exception $e)
			{
				return $e->getMessage();
			}
		}
		
		return true;
	}
	
	/**
	 * Load experiment data 
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function load(Varien_Event_Observer $observer)
	{
		$collection = Mage::getModel('ec/data')->getCollection()->addFieldToFilter('data_product_id', $observer->getProduct()->getId());
		
		$experiments = array();
		

		foreach($collection as $entity)
		{
			$experiments[$entity->getDataAbId()][$entity->getDataAttributeCode()] = $entity->getDataAttributeContent();
		}
		
		$observer->getProduct()->setData('experiment', $experiments);
		
		return true;
	}
	
	/**
	 * Load front product experiment attributes 
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function front(Varien_Event_Observer $observer)
	{
		$experiment = Mage::helper('ec/experiments')->getCurrentExperiment();
		
		if ($experiment)
		{
			try 
			{
				$collection = Mage::getModel('ec/data')->getCollection()->addFieldToFilter('data_product_id', $observer->getProduct()->getId())->addFieldToFilter('ab_store_id', Mage::helper('ec/experiments')->getCurrentStoreScope());
				
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
					/**
					 * Change content only if experiment has content
					 */
					if ($entity->getDataAttributeContent())
					{
						$this->experiment($observer->getProduct(), $entity->getDataAttributeCode(), $entity->getDataAttributeContent());
					}
				}
			}
			catch (\Exception $e)
			{
				return $e->getMessage();
			}
		}
	}
	
	public function frontCollection(Varien_Event_Observer $observer)
	{
		$experiment = Mage::helper('ec/experiments')->getCurrentExperiment();
		
		if ($experiment)
		{
			$ids = array();
			
			foreach ($observer->getCollection() as $entity)
			{
				$ids[] = (int) $entity->getId();
			}
			
			/**
			 * Load products in this experiment
			 */
			$collection = Mage::getModel('ec/data')->getCollection()->addFieldToFilter('data_product_id', array
			(
				'in' => $ids
			))->addFieldToFilter('ab_store_id', Mage::helper('ec/experiments')->getCurrentStoreScope());
			
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
				foreach ($observer->getCollection() as $product) 
				{
					if ($product->getId() == $entity->getDataProductId())
					{
						/**
						 * Change content only if experiment has content
						 */
						if ($entity->getDataAttributeContent())
						{
							$this->experiment($product, $entity->getDataAttributeCode(), $entity->getDataAttributeContent());
						}
					}
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Change layout theme
	 */
	public function changeLayout(Varien_Event_Observer $observer)
	{
		if ($experiment = Mage::helper('ec/experiments')->getCurrentExperiment())
		{
			if ($experiment['data']['ab_experiment_theme'])
			{
				list($package, $theme) = explode(chr(47),$experiment['data']['ab_experiment_theme']);
				
				Mage::getDesign()->setArea('frontend')->setPackageName($package)->setTheme($theme);
			}
		}
		
		return true;
	}
	
	/**
	 * Layout prepare adjustments 
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function prepareLayout(Varien_Event_Observer $observer)
	{
		if ($observer->getBlock() instanceof Mage_Catalog_Block_Product_View)
		{
			$product = $observer->getBlock()->getProduct();
			
			if ($description = $product->getOriginalDescription())
			{
				$description = strip_tags(html_entity_decode($description));
				
				strip_tags(Mage::app()->getLayout()->getBlock('head')->setDescription($description));
			}
		}
	}
	
	/**
	 * Conduct experiment 
	 * 
	 * @param Varien_Object $object
	 * @param string $attribute
	 * @param mixed $value
	 */
	private function experiment(Varien_Object $object, $attribute, $value)
	{
		if ($value)
		{
			if (Anowave_Ec_Model_System_Config_Behaviour::CONTENT_REPLACE === Mage::helper('ec/experiments')->getBehaviour())
			{
				$object->setData($attribute, $value);
			}
			else 
			{
				$object->setData("original_$attribute", $object->getData($attribute));
			}
		}
		
		return true;
	}
}