<?php

class Schracklive_SchrackAdminhtml_Model_Config extends Mage_Adminhtml_Model_Config {

	/**
	 * @param string $sectionCode
	 * @param string $websiteCode
	 * @param string $storeCode
	 * @return Varien_Simplexml_Element
	 */
	public function getSection($sectionCode=null, $websiteCode=null, $storeCode=null) {
		if ($sectionCode) {
			// sh@plan2.net/mk@plan2.net: Added code for carriers (warehouse pickup)
			$this->processSectionGroups($sectionCode, $this->getSections()->$sectionCode);
			return $this->getSections()->$sectionCode;
		} elseif ($websiteCode) {
			return $this->getSections()->$websiteCode;
		} elseif ($storeCode) {
			return $this->getSections()->$storeCode;
		}
	}

	protected function processSectionGroups($sectionCode, $section) {
		foreach ($section->groups->children() as $groupCode => $group) {
			$this->processGroupFields($sectionCode, $groupCode, $group);
		}
	}

	protected function processGroupFields($sectionCode, $groupCode, $group) {
		if (!$group->block_templates) {
			return;
		}
		foreach ($group->fields->children() as $fieldCode => $field) {
			$templateCode = $field->block_template;
			$repetitions = Mage::getStoreConfig($sectionCode.'/'.$groupCode.'/'.$fieldCode);
			if ($templateCode && ($repetitions > 0) && $group->block_templates->$templateCode) {
				$this->addTemplateRepeatedlyToFields($group->block_templates->$templateCode, $repetitions, $group->fields, $field->sort_order + 1);
			}
		}
	}

	protected function addTemplateRepeatedlyToFields($template, $repetions, $fields, $sortOrder) {
		for ($i = 1; $i <= $repetions; $i++) {
			foreach ($template->children() as $fieldCode => $field) {
				$newFieldCode = $fieldCode.$i;
				$newField = $fields->addChild($newFieldCode);
				$this->copyNodeAttributesAndChildrenToNode($field, $newField);
				$newField->sort_order = $sortOrder;
				$sortOrder++;
			}
		}
	}

	protected function copyNodeAttributesAndChildrenToNode($sourceNode, $targetNode) {
		$this->copyNodeAttributesToNode($sourceNode, $targetNode);
		foreach ($sourceNode->children() as $childNode) {
			$newNode = $targetNode->addChild($childNode->getName(), (string)$childNode);
			$this->copyNodeAttributesAndChildrenToNode($childNode, $newNode);
		}
	}

	protected function copyNodeAttributesToNode($sourceNode, $targetNode) {
		foreach ($sourceNode->attributes() as $name => $value) {
			$targetNode->addAttribute($name, $value);
		}
	}

}
