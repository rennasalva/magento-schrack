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

class Anowave_Ec_Helper_Experiments extends Anowave_Ec_Helper_Data
{
	/**
	 * Experiments pool
	 * 
	 * @var array
	 */
	private $experiments = array();
	
	/**
	 * Experiment behaviour 
	 * 
	 * @var int
	 */
	private $behaviour = null;
	
	/**
	 * Check if experiments are enabled
	 */
	public function isEnabled() 
	{
		
		return (bool) (int) Mage::getStoreConfig('ec/experiments/enable');
	}
	
	/**
	 * Array of attributes to experiment with.
	 */
	public function getExperimentAttributes()
	{
		$label = function($attribute_code, $experiment = array())
		{
			$front_label = Mage::getModel('eav/config')->getAttribute('catalog_product', $attribute_code)->getFrontendLabel();
			
			return "$front_label ({$experiment['name']})";
		};
		
		return array
		(
			'name' => array
			(
				'field' => array
				(
					'type' => 'text',
					'args' => function($experiment_id, $experiment, $value) use ($label)
					{
						return array
						(
							'label'  	=> $label('name', $experiment),
							'name'   	=> "experiment[$experiment_id][name]",
							'value'		=> $value
						);
					}
				)
			),
			'description' => array
			(
				'field' => array
				(
					'type' => 'textarea',
					'args' => function($experiment_id, $experiment, $value) use ($label)
					{
						return array
						(
							'label'  	=> $label('description', $experiment),
							'name'   	=> "experiment[$experiment_id][description]",
							'config'  	=> Mage::getSingleton('cms/wysiwyg_config')->getConfig(),
							'wysiwyg'	=> true,
							'value'		=> $value
						);
					}
				)
			), 
			'short_description' => array
			(
				'field' => array
				(
					'type' => 'textarea',
					'args' => function($experiment_id, $experiment, $value) use ($label)
					{
						return array
						(
							'label'		=> $label('short_description', $experiment),
							'name'		=> "experiment[$experiment_id][short_description]",
							'value'		=> $value
						);
					}
				)	
			)
		);
	}
	
	/**
	 * Get available experiments
	 */
	public function getExperiments()
	{
		if (!$this->isEnabled())
		{
			return array();
		}
			
		if (!$this->experiments)
		{
			/**
			 * Experiment callback renderer
			 *
			 * @var Closure
			 */
			$experiment = function($data)
			{
				@$dom = new DOMDocument('1.0', 'utf-8');
					
				/**
				 * Load HTML
				*/
				@$dom->loadHTML
				(
					Mage::app()->getLayout()->createBlock('ec/experiment')->setTemplate('ec/experiment.phtml')->setData($data)->toHtml()
				);
			
				$closure = $dom->getElementById('closure');
					
				if ($closure)
				{
					return $closure->textContent;
				}
				else
				{
					return 'function(){ return false }';
				}
			};
			
			$collection = Mage::getModel('ec/ab')->getCollection()->setOrder('ab_experiment','ASC');
			
			/**
			 * Join store table
			 */
			$collection->getSelect()->join
			(
				array('ab_store' => Mage::getSingleton('core/resource')->getTableName('anowave_ab_store')), 'ab_store.ab_id = main_table.ab_id'
			);
			
			$collection->getSelect()->columns('GROUP_CONCAT(DISTINCT ab_store.ab_store_id) AS stores');
			$collection->getSelect()->group('main_table.ab_id');
			
			/**
			 * Filter collection by store
			 */
			
			$collection->addFieldToFilter('ab_store_id', $this->getCurrentStoreScope());
			
			
			foreach ($collection as $entity)
			{
				$this->experiments[(int) $entity->getAbId()] = array
				(
					'name' => $entity->getAbExperiment(),
					'data' => $entity->getData(),
					'di'   => function() use ($experiment, $entity)
					{
						return $experiment(array
						(
							'experiment_name' => $entity->getAbExperiment()
						));
					},
					'triggered' => function()
					{
						return 0;
					}
				);
			}
		}
		
		return $this->experiments;
	}
	
	/**
	 * Get current experiment
	 */
	public function getCurrentExperiment()
	{
		$cookie = Mage::getModel('core/cookie')->get('experiment');
		
		if ($cookie)
		{
			$experiments = $this->getExperiments();
	
			if (isset($experiments[$cookie]))
			{
				return $experiments[$cookie];
			}
		}
		
		return null;
	}
	
	/**
	 * Get current backend store
	 */
	public function getCurrentStoreScope()
	{
		$store_id = Mage::app()->getStore()->getId();
		
		if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore()))
		{
			$store_id = Mage::getModel('core/store')->load($code)->getId();
		}
		elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite()))
		{
			$store_id = Mage::app()->getWebsite(Mage::getModel('core/website')->load($code)->getId())->getDefaultStore()->getId();
		}
		else if (Mage::app()->getRequest()->getParam('store'))
		{
			$store_id = (int) Mage::app()->getRequest()->getParam('store');
		}

		return $store_id;
	}
	

	public function getBehaviour()
	{
		if (!$this->behaviour)
		{
			$this->behaviour = (int) Mage::getStoreConfig('ec/experiments/behaviour');
		}
	
		return $this->behaviour;
	}
}