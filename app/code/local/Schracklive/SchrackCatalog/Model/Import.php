<?php

class Schracklive_SchrackCatalog_Model_Import {

	const STANDARD_ATTRIBUTESET_NAME = 'Schrack';

	var $magento;
	var $categoryModel;
	var $attributeGroups = array();
	var $catPaths = array();
	var $existingProducts = array();
	var $activeProducts = array();
	var $existingCategories = array();
	var $savedCategories = array();
	var $attributeCodes = array();
	var $attributeSets = array();
	var $facetOptionsCache = array();
	var $importOptions = array();

	/* importOptions-structure
	 * Array
	  (
	  [UseHash] => 0
	  [SetHash] => 1
	  [Formatted] => 1
	  [BaseData] => 1
	  [Properties] => 1
	  [Attributes] => 1
	  [References] => 1
	  [Urls] => 1
	  )
	 */

	private function init() {
		$this->magento = Mage::getModel('schrackcatalog/import_product');
		$this->categoryModel = Mage::getModel('catalog/category')->addData(array(
			'custom_design' => '',
			'custom_use_parent_settings' => 0,
			'custom_apply_to_products' => 0,
			'custom_layout_update' => '',
			'custom_design_from' => '',
			'custom_design_to' => '',
				));
	}

	private function saveFacetsArray(&$attributes) {
		$setId = $this->reliableGetStandardAttributeSetId();
		if (!isset($this->attributeGroups[$setId])) {
			$groups = Mage::getModel('eav/entity_attribute_group')->getResourceCollection()->setAttributeSetFilter($setId)->load()->toArray();
			$this->attributeGroups[$setId] = $groups['items'][0]["attribute_group_id"];
		}
		$index = 0;
		foreach ($attributes as $attribute) {
            echo '###' . $attribute['name'] . '###' . PHP_EOL;
            if ( $attribute['name'] === 'Messgerät' ) {
                echo '';
            }
            /*
			$attribute['name'] = strtolower(str_replace(' ', '_', $attribute['name']));
			$attribute['name'] = strtolower(str_replace('/', '_', $attribute['name']));
             */
            $attribute['name'] = $this->name2code($attribute['name']);
			$attribute['code'] = "schrack_facet_".$attribute['name']."_".strtolower($attribute['select']);
			if (isset($attribute['options'])) {
				if (isset($attribute['label']) && strlen($attribute['label']) > 0) {
                    try {
                        $attr = $this->magento->createAttribute($attribute['code'], $attribute['label'], $this->attributeGroups[$setId], $setId, 'multiselect', $index, $attribute['options']);
                    }
                    catch ( Mage_Core_Exception  $ex ) {
                        echo "create of attribute '".$attribute['code'].','.$attribute['label']."' failed.".PHP_EOL;
                        throw $ex;
                    }
					// TODO: Remove workaround, why does Magento not return the same objects after save as for load?
					$attr = $this->magento->getAttribute($attribute['code']);
					$_source = $attr->getSource();
					if ($_source instanceof Mage_Eav_Model_Entity_Attribute_Source_Interface) {
						$_options = $_source->getAllOptions(true, true);
						if (count($_options) > 0) {
							$this->facetOptionsCache[$attribute['code']] = $_options;
						}
					}
				}
			} else {
				if (isset($attribute['label'])) {
					$attr = $this->magento->createAttribute($attribute['code'], $attribute['label'], $this->attributeGroups[$setId], $setId, 'text', $index);
				}
			}
			$index++;
			$this->attributeCodes[$attribute['name']] = $attribute['code'];
		}
	}

	private function saveAttributesArray(&$attributes, $prefix = 'schrack_') {
		$setId = $this->reliableGetStandardAttributeSetId();
		if (!isset($this->attributeGroups[$setId])) {
			$groups = Mage::getModel('eav/entity_attribute_group')->getResourceCollection()->setAttributeSetFilter($setId)->load()->toArray();
			$this->attributeGroups[$setId] = $groups['items'][0]["attribute_group_id"];
		}
		$index = 0;
		foreach ($attributes as $attribute) {
            /*
			$attribute['name'] = strtolower(str_replace(' ', '_', $attribute['name']));
			$attribute['name'] = strtolower(str_replace('/', '_', $attribute['name']));
             */
            $attribute['name'] = $this->name2code($attribute['name']);
			if (isset($attribute['label'])) {
				$attr = $this->magento->createAttribute($prefix.$attribute['name'], $attribute['label'], $this->attributeGroups[$setId], $setId, 'text', $index);
			}
			$index++;
		}
	}

	private function reliableGetStandardAttributeSetId() {
		$setId = 0;
		if (isset($this->attributeSets[self::STANDARD_ATTRIBUTESET_NAME]) && isset($this->attributeSets[self::STANDARD_ATTRIBUTESET_NAME]['id'])) {
			$setId = $this->attributeSets[self::STANDARD_ATTRIBUTESET_NAME]['id'];
		}
		if (!$setId) {
			$setId = $this->magento->getAttributeSetId(self::STANDARD_ATTRIBUTESET_NAME);
		}
		if (!$setId) {
			$setId = $this->magento->createAttributeSet(self::STANDARD_ATTRIBUTESET_NAME);
			$this->attributeSets[self::STANDARD_ATTRIBUTESET_NAME] = array(
				'id' => $setId,
				'attributes' => array(),
			);
		}
		return $setId;
	}

	private function saveCategoryArray($parentId, $data, &$position) {
		$update = false;
		$category = null;
		if (isset($this->existingCategories[$data['id']])) {
			$category = $this->existingCategories[$data['id']];
			unset($this->existingCategories[$data['id']]);
			$update = true;
		} else {
			$category = clone $this->categoryModel;
		}
		$category->disableCache();
		$apiData = array();
		$apiData['general']['name'] = $data['title'];
		// map foreign characters to ascii equivalent
		$asciiUrlString = Mage::helper('schrackcore/string')->utf8ToAscii($data['title']);
		// lowercase and all remaining (and not URL compatible) characters replaced with -
		$asciiUrlString = strtolower(preg_replace("/\s+/", '-', preg_replace("/[^A-Za-z0-9]/", ' ', $asciiUrlString)));
		$apiData['general']['url_key'] = $asciiUrlString;
		if (isset($data['desc'])) $apiData['general']['description'] = $data['desc'];
		if (isset($data['keywords'])) $apiData['general']['meta_keywords'] = $data['keywords'];
		$apiData['general']['is_active'] = "1";
		$apiData['category']['parent'] = $parentId;
		// 2 = first level (see calls for saveCategoryArray below)
		if ($parentId == 2) {
			$apiData['general']['display_mode'] = 'PAGE';
			$apiData['general']['landing_page'] = '';
			$apiData['general']['page_layout'] = 'three_columns';
		}
		$category->addData($apiData['general']);
		$category->setStatus(1);
		$category->setStoreId = 1; //storeId

		if (!array_key_exists($parentId, $this->catPaths)) {

			$parentCategory = Mage::getModel('catalog/category')->load($parentId);
			$this->catPaths[$parentId] = $parentCategory->getPath();
		}

		if ($update) {
			$category->setPath($this->catPaths[$parentId]."/".$category->getId());
		} else {
			$category->setPath($this->catPaths[$parentId]);
			// TODO: Find cause that makes this workaround needed (mixup with Attachments)
			$category->save();
			$category->load($category->getId());
		}
		if (isset($data['id'])) {
			$category->setSchrackGroupId($data['id']);
			unset($data['id']);
		}
		if (isset($data['attrrefs'])) {
			$schrackFacetArray = array();
			foreach ($data['attrrefs'] as $attrrefName => $attrref) {
                /*
				$attrref['name'] = strtolower(str_replace(' ', '_', $attrref['name']));
    			$attrref['name'] = strtolower(str_replace('/', '_', $attrref['name']));
                 */
                $attrref['name'] = $this->name2code($attrref['name']);

				$attrref['code'] = "schrack_facet_".$attrref['name']."_".strtolower($attrref['select']);
				$schrackFacetArray[] = $attrref['code'];
			}
			unset($attrrefName);
			unset($attrref);
			$category->setSchrackFacetList(implode(',', $schrackFacetArray));
			unset($data['attrrefs']);
			unset($schrackFacetArray);
		}
		if (isset($data['urls'])) {

			if (isset($data['urls']['foto'])) {
				$category->setSchrackImageUrl($data['urls']['foto'][0]['file']);
				unset($data['urls']['foto']);
			}
			if (isset($data['urls']['thumbnails'])) {
				$category->setSchrackThumbnailUrl($data['urls']['thumbnails'][0]['file']);
				unset($data['urls']['thumbnails']);
			}
			//handle the rest
			foreach ($data['urls'] as $urlgroup) {
				foreach ($urlgroup as $url) {
					$attachment = Mage::getModel('schrackcatalog/attachment');
					$attachment->setFiletype($url['typ']);
					$attachment->setUrl($url['file']);
					$attachment->setLabel($url['title']);
					$category->addAttachment($attachment);
				}
			}
			$category->cleanAttachments();
		}
		$category->setPosition($position);
		$position++;
		$res = $category->save();

		$this->savedCategories[$res['entity_id']] = $res;
		return $res['entity_id'];
	}

	private function saveArticlesArray(&$articles, &$categories, &$attributes, $importIds=array(), $importStart = 0, $limit = 1000) {
		echo "limit ".$limit."\n";
		$hasMoreArticles = true;
		$im_start = time();
		$im_count = 0;
		$art_count = 0;
		$partial = false;

		if (count($importIds) > 0) $partial = true;

		foreach ($articles as $article) {
			$art_count++;

			$update = false;
			$new = true;
			$data = array();
			$product = Mage::getModel('catalog/product');
			if (is_array($article['id'])) {
				$article['id'] = $article['id']['id'];
			}
			if (isset($this->activeProducts[$article['id']])) {
				unset($this->activeProducts[$article['id']]);
			}
			if ($partial && !in_array($article['id'], $importIds)) {
				continue;
			}
			if ($art_count < $importStart) {
				continue;
			}
			if (isset($article['basedata']) || isset($article['properties']) || isset($article['attributes']) || isset($article['urls']) || isset($article['references'])) {
				$update = true;
			}
			if (!$update) {
				echo ".";
				continue;
			}
			if (isset($this->existingProducts[$article['id']])) {
				$product->load($this->existingProducts[$article['id']]);
				$data = $product->getData();
				$new = false;
			}

			$data['sku'] = $article['id'];
			$data['websites'] = array(1);
			$data['type'] = 'simple';
			$data['categories'] = $categories[$article['id']];

			if (isset($article['basedata'])) {
				if ($product->getId()) {
					//clean old properties
					foreach ($data as $key => $value) {
						if (stripos($key, 'schrack_') === 0) {
							if ((stripos($key, 'schrack_spec_') === FALSE) && (stripos($key, 'schrack_facet_') === FALSE) && (stripos($key, 'schrack_url_') === FALSE))
									$data[$key] = '';
						}
					}
				}
				$update = true;
				if (isset($article['basedata']['ean'])) $data['schrack_ean'] = $article['basedata']['ean'];
				if (isset($article['basedata']['description'])) {
					$data['name'] = $article['basedata']['description'];
					$data['description'] = $article['basedata']['description'];
					$data['short_description'] = $article['basedata']['description'];
				}
				if (isset($article['basedata']['keywords'])) {
					$data['meta_keyword'] = $article['basedata']['keywords'];
				}
				if (isset($article['basedata']['catalognr'])) $data['schrack_catalognr'] = $article['basedata']['catalognr'];
				if (isset($article['basedata']['productgroup']))
						$data['schrack_productgroup'] = $article['basedata']['productgroup'];
				if (isset($article['basedata']['sortiment'])) $data['schrack_sortiment'] = $article['basedata']['sortiment'];
				if (isset($data['name'])) {
					$asciiUrlString = Mage::helper('schrackcore/string')->utf8ToAscii($data['name']);
					$asciiUrlString = strtolower(preg_replace("/\s+/", '-', preg_replace("/[^A-Za-z0-9]/", ' ', $asciiUrlString)));
					$data['url_key'] = $asciiUrlString.'-'.$data['sku'];
					$data['schrack_url_key_without_sku'] = $asciiUrlString;
				}
			}

			if (isset($article['properties'])) {
				$update = true;
				if ($product->getId()) {
					//clean old
					foreach ($data as $key => $value) {
						if (stripos($key, 'schrack_spec_') === 0) {
							$data[$key] = '';
						}
					}
					$update = true;
				}
				foreach ($article['properties'] as $key => $value) {
					switch (strtolower($key)) {
						case 'vklw':
							$data['price'] = $value;
							break;
					/*	case 'ntogewicht':
							$data['weight'] = $value;
							break;*/
						default:
							$data['schrack_spec_'.strtolower(str_replace('/', '_',str_replace(' ', '_', $key)))] = $value;
							break;
					}
				}
			}
			$data['schrack_position'] = $article['position'];


			if (isset($article['attributes'])) {
				$update = true;
				if ($product->getId()) {
					//clean old
					foreach ($data as $key => $value) {
						if (stripos($key, 'schrack_facet_') === 0) {
							$data[$key] = '';
						}
					}
				}
				foreach ($article['attributes'] as $key => $value) {
                    // $dlKey = str_replace('/', '_',strtolower(str_replace(' ', '_', $key)));
                    $dlKey = $this->name2code($key);
					$code = $this->attributeCodes[$dlKey];
					if (isset($this->facetOptionsCache[$code]) && is_array($this->facetOptionsCache[$code])) {
						$_options = $this->facetOptionsCache[$code];
						$_values = explode('|', $value);
						$_selected = array();
						foreach ($_values as $_value) {
							foreach ($_options as $_option) {
								if ($_value === $_option['label']) {
									$_selected[] = $_option['value'];
									break;
								}
							}
						}
						$value = implode(',', $_selected);
					}
					$data[$code] = $value;
				}
			}



			if (isset($article['urls'])) {
				$data['attachments'] = array();
				$update = true;
				if ($product->getId()) {
					//clean old
					foreach ($data as $key => $value) {
						if (stripos($key, 'schrack_url_') === 0 && $key != 'schrack_url_key_without_sku') {
							$data[$key] = '';
						}
					}
					$update = true;
				}

				foreach ($article['urls'] as $key => $value) {
                    foreach ( $value['values'] as $val ) {
                        $urlLabel = $value['title'];
                        $attachment = Mage::getModel('schrackcatalog/attachment');
                        $attachment->setFiletype($value['typ']);
                        $attachment->setUrl($val);
                        $attachment->setLabel(trim($urlLabel));
                        $data['attachments'][] = $attachment;
                    }
				}
			}
			if (isset($article['references'])) {
				$update = true;
				foreach ($article['references'] as $key => $value) {
					if (isset($data['schrack_references'])) {
						$data['schrack_references'].=$value.';';
					} else {
						$data['schrack_references'] = $value.';';
					}
				}
			}
			if ($update || $new) {
				$setId = $this->reliableGetStandardAttributeSetId();
				if ($product->getId()) {
					$this->magento->addProduct($data, 1, $setId, $product);
					echo "u";
				} else {
					$this->magento->addProduct($data, 1, $setId);
					echo "+";
				}
			} else {
				echo ".";
			}
			$im_count++;
			if ($im_count == $limit) {
				$duration = time() - $im_start;
				echo "\n".$limit." products processed in ".($duration / $limit).".s\n";
				$statusFile = Mage::getConfig()->getOptions()->getTmpDir().DS.'importStatus';
				file_put_contents($statusFile, serialize(array('count' => count($articles), 'start' => ($art_count + 1))));
				if (count($articles) > $art_count) {
					return true;
				} else {
					return false;
				}
			}
		}
		return false;
	}

	public function getAllProducts($storeId=1) {
		$collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('schrack_lastmodified')->addAttributeToSelect('status')->load();
		foreach ($collection as $_prod) {
			$this->existingProducts[$_prod->getSku()] = $_prod->getId();
			if ($_prod->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
				$this->activeProducts[$_prod->getSku()] = $_prod->getId();
			}
		}
		$collection = null;
	}

	protected function processArticle($input, &$properties, &$attributes, &$urls, &$position, $output = null ) {
        if ( ! isset($output) ) {
            $output = array();
        }
        if ( ! isset($output['id']) ) $output['id'] = $input['attr']['id'];
		if ( ! isset($output['position']) ) {
            $output['position'] = $position;
            $position++;
        }

		if (isset($input['basedata']) && ($this->importOptions['BaseData'] == 1)) {
            if ( ! isset($output['basedata']) ) {
                $output['basedata'] = array();
            }
            if ( !isset($output['basedata']['ean']) ) {
                if ( isset($input['basedata']['ean']['value']) ) {
                    $output['basedata']['ean'] = $input['basedata']['ean']['value'];
                } else  {
                    $output['basedata']['ean'] = '';
                    foreach ($input['basedata']['ean'] as $ean) {
                        $output['basedata']['ean'].=$ean['value'].',';
                    }
                }
            }
			if ( isset($input['basedata']['description']['value']) && ! isset($output['basedata']['description']) ) {
				$output['basedata']['description'] = $input['basedata']['description']['value'];
			}
			if (isset($input['basedata']['keywords']['value']) && ! isset($output['basedata']['keywords']) ) {
				$output['basedata']['keywords'] = $input['basedata']['keywords']['value'];
			}
			if ( isset($input['basedata']['productgroup']['value']) && ! isset($output['basedata']['productgroup']) ) {
				$output['basedata']['productgroup'] = $input['basedata']['productgroup']['value'];
			}
			if ( isset($input['basedata']['catalognr']['value']) && ! isset($output['basedata']['catalognr']) ) {
				$output['basedata']['catalognr'] = $input['basedata']['catalognr']['value'];
			}
			if ( isset($input['basedata']['sortiment']['value']) && ! isset($output['basedata']['sortiment']) ) {
				$output['basedata']['sortiment'] = $input['basedata']['sortiment']['value'];
			}
		}
		//if (isset($input['properties']['property']) && ($this->importOptions['Properties'] == 1)) {
		if (isset($input['properties']) && ($this->importOptions['Properties'] == 1)) {
            if ( ! isset($output['properties']) ) {
                $output['properties'] = array();
            }
			if (isset($input['properties']['property'])) {
				if (isset($input['properties']['property']['attr'])) {
					$value = $input['properties']['property'];
					$properties[$value['attr']['name']]['label'] = $value['attr']['title'];
					if (isset($value['value']['value'])) {
						$output['properties'][$value['attr']['name']] = $value['value']['value'];
						if (isset($value['attr']['unit'])) {
							$output['properties'][$value['attr']['name']] = $output['properties'][$value['attr']['name']].' '.$value['attr']['unit'];
						}
					}
				} else {
					foreach ($input['properties']['property'] as $value) {
						$properties[$value['attr']['name']]['label'] = $value['attr']['title'];
						if (isset($value['value']['value'])) {
							$output['properties'][$value['attr']['name']] = $value['value']['value'];
							if (isset($value['attr']['unit'])) {
								$output['properties'][$value['attr']['name']] = $output['properties'][$value['attr']['name']].' '.$value['attr']['unit'];
							}
						}
					}
				}
			}
		}
		//if (isset($input['attributes']['attribute']) && ($this->importOptions['Attributes'] == 1)) {
		if (isset($input['attributes']) && ($this->importOptions['Attributes'] == 1)) {
            if ( ! isset($output['attributes']) ) {
    			$output['attributes'] = array();
            }
			if (isset($input['attributes']['attribute'])) {
				if (isset($input['attributes']['attribute']['attr'])) {
					$value = $input['attributes']['attribute'];
					$attributes[$value['attr']['name']]['label'] = $value['attr']['title'];
					if (isset($value['value']['value'])) {
						//single value
						$output['attributes'][$value['attr']['name']] = $value['value']['value'];
					} else {
						if (is_array($value['value'])) {
							$tmp_array = array();
							foreach ($value['value'] as $multivalue) {
								$tmp_array[] = $multivalue['value'];
							}
							$output['attributes'][$value['attr']['name']] = implode('|', $tmp_array);
						}
					}
				} else {
					foreach ($input['attributes']['attribute'] as $value) {
						$attributes[$value['attr']['name']]['label'] = $value['attr']['title'];
						$output['attributes'][$value['attr']['name']] = '';
						if (isset($value['value']['value'])) {
							//single_value
							$output['attributes'][$value['attr']['name']] = $value['value']['value'];
						} else {
							if (is_array($value['value'])) {
								$tmp_array = array();
								foreach ($value['value'] as $multivalue) {
									$tmp_array[] = $multivalue['value'];
								}
								$output['attributes'][$value['attr']['name']] = implode('|', $tmp_array);
							}
						}
					}
				}
			}
		}
		if (isset($input['urls']) && ($this->importOptions['Urls'] == 1)) {
            if ( ! isset($output['urls']) ) {
                $output['urls'] = array();
            }
			if (isset($input['urls']['url'])) {
				if (isset($input['urls']['url']['attr'])) {
					$input['urls']['url'] = array($input['urls']['url']);
				}
				foreach ($input['urls']['url'] as $value) {
					if (!isset($urls[$value['attr']['typ']])) {
						$urls[$value['attr']['typ']]['typ'] = $value['attr']['typ'];
						$urls[$value['attr']['typ']]['name'] = $value['attr']['typ'];
						$urls[$value['attr']['typ']]['label'] = ucfirst($value['attr']['typ']);
					}
					$output['urls'][$value['attr']['typ']]['typ'] = $value['attr']['typ'];
					$output['urls'][$value['attr']['typ']]['title'] = $value['attr']['title'];
					$output['urls'][$value['attr']['typ']]['values'][] = $value['file']['value'];
				}
			}
		}
		if (isset($input['references']['reference']) && ($this->importOptions['References'] == 1)) {
			if (isset($input['references']['reference'])) {
				$output['references'][$input['references']['reference']['attr']['typ']] = $input['references']['reference']['articleid']['value'];
			}
		}
		return $output;
	}

	public function run($importFile, $idFile, $productStatus, $importStart = 0, $importLimit = 1000, $task = Schracklive_Shell_ProductImport::TASK_IMPORT_PRODUCTS) {
		$this->init();
		$importIds = array();
		if ($idFile) {
			echo "reading import ids\n";
			$importIdData = explode("\n", file_get_contents($idFile));
			foreach ($importIdData as $importId) {
				$importId = trim($importId);
				if (!empty($importId)) {
					$importIds[] = $importId;
				}
			}
			echo count($importIds)." products to import\n";
		}

		switch ($productStatus) {
			case Schracklive_Shell_ProductImport::PRODUCT_ACTIVE:
				break;
			case Schracklive_Shell_ProductImport::PRODUCT_INACTIVE:
				$this->magento->setProductStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
				break;
		}

		echo "preloading catalog\n";
		$this->getAllProducts();
		echo "loaded ".count($this->existingProducts)." products with ".count($this->activeProducts)." active \n";

		$filename = Mage::getConfig()->getOptions()->getTmpDir().DS.'importData';
		if ($task != Schracklive_Shell_ProductImport::TASK_IMPORT && file_exists($filename) && is_readable($filename)) {
			echo "loading prepared data\n";
			$data = unserialize(file_get_contents($filename));
			$attributes = $data['attributes'];
			$articles = $data['articles'];
			$articlesCategories = $data['articlesCategories'];
			$this->attributeGroups = $data['attributeGroups'];
			$this->catPaths = $data['catPaths'];
			$this->existingCategories = $data['existingCategories'];
			$this->savedCategories = $data['savedCategories'];
			$this->attributeCodes = $data['attributeCodes'];
			$this->attributeSets = $data['attributeSets'];
			$this->facetOptionsCache = $data['facetOptionsCache'];
			$this->importOptions = $data['importOptions'];
			unset($data);
			unset($filename);
		} elseif (is_writable(Mage::getConfig()->getOptions()->getTmpDir())) {
			$category = Mage::getModel('catalog/category');
			$tree = $category->getTreeModel();
			$tree->load();

			$ids = $tree->getCollection()->getAllIds();
			$arr = array();

			if ($ids) {
				foreach ($ids as $id) {
					$cat = Mage::getModel('catalog/category');
					$cat->load($id);
					$groupId = $cat->getSchrackGroupId();
					if (($id <= 2) || (empty($groupId))) {
						echo "setting group-id for category(".$cat->getId()."):".$cat->getName()."\n";
						$cat->setData('schrack_group_id', 'magento_group_'.$id);
						$cat->save();
					}
					if (!isset($this->existingCategories[$cat->getSchrackGroupId()])) {
						echo "found category with schrack_group_id:".$cat->getSchrackGroupId()."\n";
						$this->existingCategories[$cat->getSchrackGroupId()] = $cat;
					} else {
						echo "deactivate duplicate category(".$cat->getId()."):".$cat->getName()."\n";
						//$cat->delete();
						if (strpos($cat->getName(), '[INACTIVE]') !== 0) {
							$cat->setName('[INACTIVE]'.$cat->getName());
						}
						$cat->setIsActive(0);
						$cat->save();
					}
				}
			}
			$ids = null;
			echo "convert importdata\n";
			$importArray = Mage::getModel('schrackcatalog/import_parser')->xml2array(file_get_contents($importFile), TRUE);
			$catalog = $importArray['catalog'];
			$_importOptions = explode(',', $catalog['attr']['exportoptions']);
			foreach ($_importOptions as $value) {
				$_option = explode('=', $value);
				$this->importOptions[$_option[0]] = $_option[1];
			}
			echo "parsing importdata\n";
			$basedata = array();
			$basedata['ean']['name'] = 'ean';
			$basedata['ean']['label'] = 'EAN-Code';
			$basedata['position']['name'] = 'position';
			$basedata['position']['label'] = 'Katalogposition';
			$basedata['productgroup']['name'] = 'productgroup';
			$basedata['productgroup']['label'] = 'Produktgruppe';
			$basedata['catalognr']['name'] = 'catalognr';
			$basedata['catalognr']['label'] = 'Katalognummer';
			$basedata['sortiment']['name'] = 'sortiment';
			$basedata['sortiment']['label'] = 'Sortiment';

			$properties = array();


			if (isset($catalog['definitions']['propdefs']['propdef'])) {
				foreach ($catalog['definitions']['propdefs']['propdef'] as $propdef) {
					$properties[$propdef['attr']['name']]['name'] = $propdef['attr']['name'];
				}
			}
			$attributes = array();
			if (isset($catalog['definitions']['attrdefs']['attrdef'])) {
				foreach ($catalog['definitions']['attrdefs']['attrdef'] as $attrdef) {
					$attributes[$attrdef['attr']['name']]['name'] = $attrdef['attr']['name'];
					if (isset($attrdef['attr']['select'])) {
						$attributes[$attrdef['attr']['name']]['select'] = $attrdef['attr']['select'];
					}
					if (isset($attrdef['attr']['values'])) {
						$attributes[$attrdef['attr']['name']]['values'] = $attrdef['attr']['values'];
					}
					if (isset($attrdef['attr']['search'])) {
						$attributes[$attrdef['attr']['name']]['search'] = $attrdef['attr']['search'];
					}
					if (isset($attrdef['values']) && isset($attrdef['values']['value']) && is_array($attrdef['values']['value'])) {
						$attributes[$attrdef['attr']['name']]['options'] = array();
						foreach ($attrdef['values']['value'] as $valuedef) {
							$attributes[$attrdef['attr']['name']]['options'][] = $valuedef['value'];
						}
					}
				}
			}
			$groups = array();
			$articles = array();
			$articlesCategories = array();
			$urls = array();
			//only single group
			if (isset($catalog['group']['attr'])) {
				$catalog['group'] = array($catalog['group']);
			}
			$position = 0;
			$groupPosition = 0;
			foreach ($catalog['group'] as $group) {

				if (!isset($group['attr'])) $group['attr'] = $group;


				$groups[$group['attr']['title']]['title'] = $group['attr']['title'];
				$groups[$group['attr']['title']]['id'] = $group['attr']['id'];

				if (isset($group['attr']['desc'])) $groups[$group['attr']['title']]['desc'] = $group['attr']['desc'];
				if (isset($group['attr']['keywords'])) $groups[$group['attr']['title']]['keywords'] = $group['attr']['keywords'];
				if (isset($group['urls']['url'])) {
					if (isset($group['urls']['url']['attr'])) {
						$group['urls']['url'] = array($group['urls']['url']);
					}
					foreach ($group['urls']['url'] as $url) {
						$_url = array();
						$_url['typ'] = $url['attr']['typ'];
						$_url['title'] = $url['attr']['title'];
						$_url['file'] = $url['file']['value'];
						$groups[$group['attr']['title']]['urls'][$url['attr']['typ']][] = $_url;
					}
				}

				if (isset($group['attrrefs']['attrref'])) {
					if (isset($group['attrrefs']['attrref']['attr'])) {
						$group['attrrefs']['attrref'] = array($group['attrrefs']['attrref']);
					}
					foreach ($group['attrrefs']['attrref'] as $attrref) {
						if (isset($attributes[$attrref['attr']['name']])) {
							$groups[$group['attr']['title']]['attrrefs'][$attrref['attr']['name']] = $attributes[$attrref['attr']['name']];
						}
					}
				}

				$parentId = $this->saveCategoryArray(2, $groups[$group['attr']['title']], $groupPosition);
				if (isset($group['article'])) {
					if (isset($group['article']['attr'])) {
						$group['article'] = array($group['article']);
					}
					foreach ($group['article'] as $article) {
						if (!isset($articles[$article['attr']['id']])) {
							$articles[$article['attr']['id']] = $this->processArticle($article, $properties, $attributes, $urls, $position);
						}
                        else {
                           $articles[$article['attr']['id']] = $this->processArticle($article, $properties, $attributes, $urls, $position, $articles[$article['attr']['id']]);
                        }
						$articlesCategories[$article['attr']['id']][] = $parentId;
					}
				}
				if (isset($group['group'])) {
					// If only 1 group/group => array
					if (isset($group['group']['attr'])) {
						$group['group'] = array($group['group']);
					}
					foreach ($group['group']as $subgroup) {
						$groups[$subgroup['attr']['title']]['title'] = $subgroup['attr']['title'];
						$groups[$subgroup['attr']['title']]['id'] = $subgroup['attr']['id'];
						$groups[$subgroup['attr']['title']]['parent'] = $group['attr']['title'];
						if (isset($subgroup['attr']['desc'])) $groups[$subgroup['attr']['title']]['desc'] = $subgroup['attr']['desc'];
						if (isset($subgroup['attr']['keywords']))
								$groups[$subgroup['attr']['title']]['keywords'] = $subgroup['attr']['keywords'];
						if (isset($subgroup['urls']['url'])) {
							foreach ($subgroup['urls']['url'] as $url) {
								$_url = array();
								$_url['typ'] = $url['attr']['typ'];
								$_url['title'] = $url['attr']['title'];
								$_url['file'] = $url['file']['value'];
								$groups[$subgroup['attr']['title']]['urls'][$url['attr']['typ']][] = $_url;
							}
						}
						if (isset($subgroup['attrrefs']['attrref'])) {
							
							if (isset($subgroup['attrrefs']['attrref']['attr'])) {
								$subgroup['attrrefs']['attrref'] = array($subgroup['attrrefs']['attrref']);
							}
							
							foreach ($subgroup['attrrefs']['attrref'] as $attrref) {
								if (isset($attributes[$attrref['attr']['name']])) {
									$groups[$subgroup['attr']['title']]['attrrefs'][$attrref['attr']['name']] = $attributes[$attrref['attr']['name']];
								}
							}
							
						}
						$parentIdSub = $this->saveCategoryArray($parentId, $groups[$subgroup['attr']['title']], $groupPosition);
						if (isset($subgroup['article'])) {
							if (isset($subgroup['article']['attr']) && isset($subgroup['article']['attr']['id'])) {
								$subgroup['article'] = array($subgroup['article']);
							}
							foreach ($subgroup['article'] as $article) {
								if (!isset($articles[$article['attr']['id']])) {
									$articles[$article['attr']['id']] = $this->processArticle($article, $properties, $attributes, $urls, $position, $articles[$article['attr']['id']]);
								}
                                else {
                                   $articles[$article['attr']['id']] = $this->processArticle($article, $properties, $attributes, $urls, $position, $articles[$article['attr']['id']]);
                                }
								$articlesCategories[$article['attr']['id']][] = $parentIdSub;
							}
						}
					}
				}
			}
			//check for remaining categories in $this->existingCategories;
			foreach ($this->existingCategories as $cname => $_category) {
				if ($_category->getId() > 2) {//skip system-kategories (root,default)
					echo "deactivating category(".$_category->getId()."):".$_category->getName()."\n";
					if (strpos($_category->getName(), '[INACTIVE]') !== 0) {
						$_category->setName('[INACTIVE]'.$_category->getName());
					}
					$_category->setIsActive(0);
					$_category->save();
				}
			}

			Mage::getResourceSingleton('catalog/category_flat')->rebuild();

			$catalog = null;
			$importarray = null;
			//print_r($attributes);
			//print_r($properties);
			$groups = null;
			echo "saving ".count($basedata)." basedata \n";
			$this->saveAttributesArray($basedata, 'schrack_');

			echo "saving ".count($attributes)." facets \n";
			$this->saveFacetsArray($attributes);

			echo "saving ".count($properties)." properties \n";
			$this->saveAttributesArray($properties, 'schrack_spec_');

			//echo "saving ".count($urls)." url definitions \n";
			//$this->saveAttributesArray($urls, 'schrack_url_');
			// Save data for article import runs
			$data = array();
			$data['attributes'] = $attributes;
			$data['articles'] = $articles;
			$data['articlesCategories'] = $articlesCategories;
			$data['attributeGroups'] = $this->attributeGroups;
			$data['catPaths'] = $this->catPaths;
			$data['existingCategories'] = $this->existingCategories;
			$data['savedCategories'] = $this->savedCategories;
			$data['attributeCodes'] = $this->attributeCodes;
			$data['attributeSets'] = $this->attributeSets;
			$data['facetOptionsCache'] = $this->facetOptionsCache;
			$data['importOptions'] = $this->importOptions;
			file_put_contents($filename, serialize($data));
			$statusFile = Mage::getConfig()->getOptions()->getTmpDir().DS.'importStatus';
			if (count($importIds) > 0) {
				$articleCount = count($importIds);
			} else {
				$articleCount = count($articles);
			}
			file_put_contents($statusFile, serialize(array('count' => $articleCount, 'start' => 0)));
			unset($data);
			unset($filename);
			return;
		} else {
			echo $filename." is not writeable, can't continue.\n";
			die();
		}


		echo "saving ".count($articles)." articles \n";
		$hasMoreArticles = $this->saveArticlesArray($articles, $articlesCategories, $attributes, $importIds, $importStart, $importLimit);
		if (!$hasMoreArticles) {
			switch ($productStatus) {
				case Schracklive_Shell_ProductImport::PRODUCT_ACTIVE:
					echo "cleaning up products\n";
					echo "found ".count($this->activeProducts)." products to deactivate\n";
					print_r($this->activeProducts);
					echo "\n";
					foreach ($this->activeProducts as $_prodId) {
						$product = Mage::getModel('catalog/product')->load($_prodId);
						echo "deactivating ".$product->getName()."[".$product->getSku()."]\n";
						$product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
						$product->save();
					}
					break;
				case Schracklive_Shell_ProductImport::PRODUCT_INACTIVE:
					break;
			}

			echo "finished import\n";
			echo "truncating url-index\n";

			$conn = Mage::getSingleton('core/resource')->getConnection('core_write');
			$conn->query("TRUNCATE TABLE core_url_rewrite");

			echo "now you MUST reindex magento using ./reindex.sh\n";
			echo "and update solr using php exportProducts.php all\n";

			Mage::getModel('catalog/product')->cleanCache(true);
		}
	}

    private function name2code ( $name ) {
        $res = '';
        $pointer = 0;
        while ( ($c = $this->nextchar($name,$pointer)) ) {
            switch ( $c ) {
                case 'Ä' : 
                case 'ä' :
                    $c = 'ae';
                    break;
                case 'Ö' : 
                case 'ö' :
                    $c = 'oe';
                    break;
                case 'Ü' : 
                case 'ü' :
                    $c = 'ue';
                    break;
                case 'ß' :
                    $c = 'ss';
                    break;
                default :
                    if ( $c >= 'A' && $c <= 'Z' ) {
                        $c = chr(ord($c) + 32);
                    }
                    else if (    $c < '0' 
                              || ($c > '9' && $c < 'a')
                              || $c > 'z'               ) {
                       $c = '_';
                    }
            }
            $res .= $c;
        }
        return $res;
    }

    private function nextchar ( $string, &$pointer ){
        if(!isset($string[$pointer])) return false;
        $char = ord($string[$pointer]);
        if($char < 128){
            return $string[$pointer++];
        }else{
            if($char < 224){
                $bytes = 2;
            }elseif($char < 240){
                $bytes = 3;
            }elseif($char < 248){
                $bytes = 4;
            }elseif($char = 252){
                $bytes = 5;
            }else{
                $bytes = 6;
            }
            $str =  substr($string, $pointer, $bytes);
            $pointer += $bytes;
            return $str;
        }
    }
}

?>
