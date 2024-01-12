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
 
class Anowave_Ec_Block_Ab_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	function __construct()
    {
        parent::__construct();
        
        $this->setId('abGrid');
        $this->setDefaultSort('ab_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare grid collection object
     *
     * @return Anowave_Ab_Block_Ab_Grid
     */
    protected function _prepareCollection()
    {
    	$collection = Mage::getModel('ec/ab')->getResourceCollection();
    	
    	$collection->getSelect()->joinLeft
    	(
    		array('store' => Mage::getConfig()->getTablePrefix() . 'anowave_ab_store'), 'main_table.ab_id = store.ab_id', array()
    	);
    	

    	$collection->getSelect()->columns('GROUP_CONCAT(store.ab_store_id) AS store_id');
    	$collection->getSelect()->group('main_table.ab_id');
    	
        $this->setCollection($collection);
 
        parent::_prepareCollection();
        
        /**
         * Update store value(s)
         */
	    foreach ($collection as $view) 
	    {
	        if ( $view->getStoreId() && $view->getStoreId() != 0 ) 
	        {
	            $view->setStoreId
	            (
	            	explode(chr(44),$view->getStoreId())
	            );
	        } 
	        else 
	        {
	            $view->setStoreId
	            (
	            	array('0')
	            );
	        }
	    }
    }
    
	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('ab_id');
		$this->getMassactionBlock()->setFormFieldName('ab_id');
		$this->getMassactionBlock()->addItem('delete', array
		(
			'label'		=> Mage::helper('ec')->__('Delete'),
			'url'  		=> $this->getUrl('*/*/deleteAll', array('' => '')),
			'confirm' 	=> Mage::helper('ec')->__('Are you sure?')
		));
		
		return $this;
	}

    protected function _prepareColumns()
    {
    	parent::_prepareColumns();
    	
        $this->addColumn('ab_id', array
        (
            'header'    => Mage::helper('ec')->__('Id'),
            'align'     => 'right',
            'width'     => '50px',
            'index'     => 'ab_id',
            'type'      => 'number',
        ));
        
      	$this->addColumn('ab_experiment', array
        (
            'header'    => Mage::helper('ec')->__('Experiment'),
            'align'     => 'left',
            'index'     => 'ab_experiment'
        ));

        $this->addColumn('store_id', array
        (
         	'header'        			=> Mage::helper('ec')->__('Store View'),
         	'index'         			=> 'store_id',
         	'type'          			=> 'store',
         	'store_all'     			=> true,
         	'store_view'    			=> true,
         	'sortable'      			=> true,
         	'filter_condition_callback' => array($this,'_filterStoreCondition')
         ));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    protected function _filterStoreCondition($collection, $column)
    {
    	if (!$value = $column->getFilter()->getValue())
    	{
    		return true;
    	}
    	else 
    	{
    		$collection->addStoreFilter($value);
    	}
    }
}