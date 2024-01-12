<?php

// dead code?
class Schracklive_SchrackCatalog_Model_Import_Category extends Schracklive_SchrackCatalog_Model_Import_Entity {

	protected $entityCode = 'catalog_category';

	public function createAttribute($name, $label, $type='text') {
		$attribute = $this->getAttribute($name, TRUE);
		if ($attribute != false) {
			$data = array();
			$data['attribute_code'] = $name;
			$data['frontend_input'] = $type;
			$data['frontend_label'] = $label;
			$attribute->addData($data);
			$attribute->save();
			return $attribute; //->getAttributeId();
		} else {
			$model = clone $this->attributeModel;
			$data = array();
			$data['attribute_code'] = $name;
			$data['frontend_input'] = $type;
			$data['frontend_label'] = $label;
			$data['is_global'] = "1";
			$data['is_required'] = "0";
			$data['is_searchable'] = "0";
			$data['is_comparable'] = "0";
			$data['is_visible_in_advanced_search'] = "0";
			$data['is_filterable'] = "0";
			$data['is_html_allowed_on_front'] = "1";
			$data['is_visible_on_front'] = "1";
			$data['is_unique'] = "0";
			$data['backend_type'] = $model->getBackendTypeByInput($data['frontend_input']);
			$model->addData($data);
			$model->setEntityTypeId($this->entityType);
			$model->setIsUserDefined(1);
			$model->save();
			return $model;
		}
	}

}