<?php

class Schracklive_SchrackCatalog_Model_Resource_Eav_Mysql4_Product extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product {

	/**
	 * Default product attributes
	 *
	 * @return array
	 */
	protected function _getDefaultAttributes() {
		return array('entity_id', 'entity_type_id', 'attribute_set_id', 'type_id', 'created_at', 'updated_at');
	}

	/**
	 * Get product identifier by reference_id
	 *
	 * @param   string $reference_id
	 * @return  array
	 */
	public function getIdsByReference($reference_id) {
		//die ("rewrite rulez ".$reference_id);
		//$refs=
		return $this->_read->fetchAll("select entity_id from ".$this->getEntityTable()." where schrack_substitute like '%".$reference_id."%'");
	}

	public function getIdByEan($ean) {
		return $this->_read->fetchOne("select entity_id from ".$this->getEntityTable()." where LOWER(schrack_ean) LIKE '%".strtolower($ean)."%'");
	}

}
