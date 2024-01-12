<?php

class Schracklive_Mobile_Model_Handler extends Schracklive_Mobile_Model_Handler_Abstract {

	protected $searchableCustomerAttributes = array('prefix', 'firstname', 'middlename', 'lastname', 'schrack_wws_customer_id', 'schrack_advisor_principal_name');
	protected $solrFieldConfigFile = "SolrSearchFields.php";
	protected $solrSearchFields = array(
		'entity_id',
		'name',
		'status',
		'description',
		'schrack_ean',
		'sku',
		'category',
	);

	public function __construct() {
		Zend_Mail::setDefaultTransport(new Zend_Mail_Transport_Smtp('localhost'));
	}

	/*
	 * Public API
	 *
	 */

	/**
	 * Init function for iPhone application.
	 *
	 * Es werden zwei verschiedene Versionsnummern zurückgeliefert: die aktuelle und die zumindest notwendige (seit der letzten Schnittstellenänderung, o.ä.) Version. Daran kann die iPhone-Software entscheiden, ob ein Update notwendig oder optional ist.
	 */
	public function init() {
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');

		$version = $document->createElement('version');
		$version->appendChild($document->createElement('current', Mage::getStoreConfig('schrack/mobile/current')));
		$version->appendChild($document->createElement('mandatory', Mage::getStoreConfig('schrack/mobile/mandatory')));
		$document->appendChild($version);

		return $document;
	}

	/**
	 * @param      $needle
	 * @param null $customer_id
	 * @param int  $article_group_id
	 * @param int  $offset
	 * @param null $sort
	 * @return \Mage_Core_Model_Abstract|mixed|null <type>
	 */
	public function search($needle = null, $customer_id = NULL, $article_group_id = 0, $perPage = 25, $page = 1, $sort = NULL) {
		$offset = $perPage * ($page - 1);
        $customer = $this->_getCustomer($customer_id);

		$products = array();
		$product_counter = 0;
        $availableCategories = array();
        $filterCategory = null;

        if ( $needle == null || strlen($needle) == 0 ) {
            if ( ! $article_group_id ) {
                $collection = $this->_getTopLevelCategories();
                $res = $this->_categories2Xml($collection);
                return $res;
            }
            else {
                $collection = $this->_searchChildCategories($article_group_id);
                if ( $collection && $collection->count() > 0 ) {
                    $res = $this->_categories2Xml($collection);                
                    return $res;
                }
                else {
                    $category = Mage::getModel('catalog/category');
                    $category->load($article_group_id);
                    $products = $category->getProductCollectionWithoutSolr();
                    $product_counter = $products->count();
                    $products = $category->getProductCollectionWithoutSolr();
                    $products->addAttributeToSelect('*');
                    $products->setPage($page, $perPage);
                    $products->count();
                }
            }
        }
        else {
            //search products
            /* @var $query Mage_CatalogSearch_Model_Query */
            $query = Mage::helper('catalogSearch')->getQuery();
            $query->setStoreId(Mage::app()->getStore()->getId());
            if ( $needle ) {
                $query->setQueryText($needle);
            }
            $collection = $query->getResultCollection();
            $collection->addAttributeToSelect('*');

            if (0 != $article_group_id) {
                $filterCategory = Mage::getModel('catalog/category')->load($article_group_id);
                $collection->addCategoryFilter($filterCategory);

                $products = $collection->getAllIds();
                $product_counter = count($products);

                
            } else {
                $products = $collection->load();
                $product_counter = count($products);
            }

            //search old articles via sku
            if (count($products) == 0) {
                $id = Mage::getModel('catalog/product')->getIdBySku(strtoupper($needle));
                if ($id) {
                    $products = array();
                    $products[] = Mage::getModel('catalog/product')->load($id);
                    $product_counter = count($products);
                }
            }
            if (count($products) == 0) {
                $id = Mage::getModel('catalog/product')->getIdByEan($needle);
                if ($id) {
                    $products = array();
                    $products[] = Mage::getModel('catalog/product')->load($id);
                    $product_counter = count($products);
                }
            } else {
                $offset = $perPage * ($page - 1);
                $product_counter = count($products);
                $products = array_slice($products, $offset, $perPage);
            }
        }        
        
		if (count($products) == 0) {
			$document = Mage::getModel('mobile/document', '1.0', 'utf-8');
			$document->appendChild($document->createElement('articles'));

			return $document;
		}

		foreach ($products as $product) {
			if (!is_object($product)) {
				$product = Mage::getModel('catalog/product')->load($product);
			}
			if (($product->getStatus() == 1) || ($product->getSku() == strtoupper($needle)) || (strpos($product->getSchrackEan(), $needle) !== FALSE)) {

				$category = $filterCategory;
				$categoryIds = $product->getCategoryIds();
				foreach ($categoryIds as $categoryId) {
					if ($category == null) {
						if (!array_key_exists($categoryId, $availableCategories)) {
							$availableCategories[$categoryId] = array();
							$availableCategories[$categoryId][] = $product;
						} else {
							$availableCategories[$categoryId][] = $product;
						}
					} else {
						if ($category->getId() == $categoryId) {
							if (!array_key_exists($categoryId, $availableCategories)) {
								$availableCategories[$categoryId] = array();
								$availableCategories[$categoryId][] = $product;
							} else {
								$availableCategories[$categoryId][] = $product;
							}
						}
					}
				}
			} else {
				$product_counter--;
			}
		}

		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');
		if ((count($availableCategories) != 1) && (0 == $article_group_id) && ($product_counter > 1)) {

			if (count($availableCategories) > 1) {
				//Produktgruppen

				$articleGroups = $document->createElement('articleGroups');
				$count_all = 0;
				$all_present = 0;
				foreach ($availableCategories as $key => $value) {

					if ($count_all < $offset) {
						$count_all++;
						continue;
					}
					$category = Mage::getModel('catalog/category');
					$category->load($key);
					if ($category->getName() == 'Alle Artikel') { // special category in AT (currentyl disabled, 18.11.2001)
						$all_present++;
						continue;
					}
					if ( ! $this->_showCategory($category) ) {
						continue;
					}

					$articleGroup = $this->_createCategoryNode($document, $category, 'articleGroup');

					$numberOfProducts = $document->createElement('numberOfProducts', count($value));
					$articleGroup->appendChild($numberOfProducts);

					$articleGroups->appendChild($articleGroup);
					$count_all++;
					if (($count_all - $offset) >= 25) {
						break;
					}
				}
				$articleGroups->setAttribute('totalNumberOfObjects', count($availableCategories) - $all_present);
				$document->appendChild($articleGroups);

				return $document;
			}
		} else {
			//Artikelliste
			$articles = $document->createElement('articles');
			$count = 0;
			$xmlProducts = array();
			foreach ($availableCategories as $products) {
				foreach ($products as $product) {
					$product = Mage::getModel('catalog/product')->load($product->getId()); //load product to have FULL product
					if (isset($xmlProducts[$product->getSKU()])) {
						continue;
					}
					if ($count == 25) {
						break;
					}
					$xmlProducts[$product->getSKU()] = $product;

					$articles->appendChild($this->_createArticleNode($document, $product, $customer, 1, false));
					$count++;
				}
			}
			$articles->setAttribute('totalNumberOfObjects', $product_counter);
			$document->appendChild($articles);

			return $document;
		}
	}

	/**
	 * @param string $needle
	 * @param null|int $customer_id
	 * @param int $article_group_id
	 * @param int $page
	 * @param int $perPage
	 * @param null|string $sort
	 * @param array $facets
	 * @return Mage_Core_Model_Abstract|mixed|null
	 */
	public function searchSolr($needle = null, $customer_id = NULL, $article_group_id = 0, $page = 1, $perPage = 25, $sort = NULL, $facets = array(), $scanned = 0, $promotion_always = 0 ) {

		$offset = ($page - 1) * $perPage;
		$customer = $this->_getCustomer($customer_id);

        if ( $needle == null || strlen($needle) == 0 ) {
            if ( ! $article_group_id ) {
                $collection = $this->_getTopLevelCategories($promotion_always == 1);
                $res = $this->_categories2Xml($collection);
                return $res;
            }
            else {
                $collection = $this->_searchChildCategories($article_group_id);
                if ( $collection && count($collection) > 0 ) {
					if ( count($collection) === 1 && current($collection->getItems())->isPromotionProductsCategory() ) {
						$cat = current($collection->getItems());
						$products = $cat->getProductCollectionWithoutSolr();
						$products->getSelect()->reset(Zend_Db_Select::ORDER);
						$document = Mage::getModel('mobile/document', '1.0', 'utf-8');
						$session = Mage::getSingleton('customer/session');
						if ( $session->isLoggedIn()  ) {
							if ( $products->count() < 1 ) {
								$error = $document->createElement('error','nopromotion');
								$document->appendChild($error);
							} else {
								$this->_fillProductListDocument($cat->getSchrackGroupId(), array(), $document, $products, $customer, $products->count(), array(), array());
							}
						} else {
							$error = $document->createElement('error','nologin');
							$document->appendChild($error);
						}
						return $document;
					} else {
						$res = $this->_categories2Xml($collection);
						return $res;
					}
                }
            }
        }
        
        if ( ! $needle ) {
            $needle = '*';
        }
		// search products
		$solrSearch = Mage::getModel('solrsearch/search')->initData($needle, $article_group_id, $facets);
		$solrProductIds = $solrSearch->getProductIds();

			/* @var $collection Mage_Eav_Model_Entity_Collection_Abstract */
        $collection = Mage::getResourceModel('catalog/product_collection');

		//search products
		if (is_array($solrProductIds)) {
			// Add dummy entity ID if solr result is empty, otherwise mage will return whole category
			if (count($solrProductIds) == 0) {
				$productIds = array(9999999);
			} else {
				$productIds = $solrProductIds;
			}


			$collection->addFieldToFilter(array(array('attribute' => 'status', 'eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED)))
				       ->addFieldToFilter('schrack_sts_statuslocal',array('neq' => 'tot'))
					   ->addAttributeToSelect('*')
				       ->addIdFilter($productIds);
		}

		$products = array();
		$product_counter = 0;
		$filterCategory = null;

		// Load facets & category counts
		$solrFacets = $solrSearch->getSolrResponseFacets();
		$availableCategories = array();
		if (isset($solrFacets['category_stringS'])) {
			foreach ($solrFacets['category_stringS'] as $category => $articleCount) {
				$category = explode('|', $category);
				$availableCategories[$category[1]] = $articleCount;
				// S4Y #8697: If we only have 1 product but multiple categories, use the first category
				// so we automatically redirect the customer to the product instead of all its categories
				if (count($solrProductIds) == 1) {
					break;
				}
			}
			unset($solrFacets['category_stringS']);
		}

		// Check if only got articles in one category
		if ($article_group_id === 0 && count($availableCategories) === 1) {
			reset($availableCategories);
			$article_group_id = key($availableCategories);
			$solrSearch = Mage::getModel('solrsearch/search')->initData($needle, $article_group_id, $facets);
			$solrFacets = $solrSearch->getSolrResponseFacets();
		}

		// Got one category
		if (0 != $article_group_id) {
			$filterCategory = Mage::getModel('catalog/category')->load($article_group_id);
			$collection->addCategoryFilter($filterCategory);
		}
		$product_counter = $collection->getSize();
		$collection->setPage($page, $perPage);
		$products = $collection->load();

		// search old articles via sku
		if ( count($products) == 0 && strlen($needle) >= 4 ) {
			$col = Mage::getModel('catalog/product')->getCollection();
			$col->addAttributeToFilter('sku',array("like" => $needle . '%'));
			$products = array();
			$needleLen = strlen($needle);
			foreach ( $col as $p ) {
				$skuPart = substr($p->getSku(),$needleLen);
				$ok = true;
				$l = strlen($skuPart);
				for ( $i = 0; $i < $l; $i++ ) {
					if ( $skuPart[$i] !== '-' ) {
						$ok = false;
						break;
					}
				}
				if ( $ok ) {
					$products[] = $p;
				}
			}
			$product_counter = count($products);
		}

		// search ean
		if (count($products) == 0) {
			$id = Mage::getModel('catalog/product')->getIdByEan($needle);
			if ($id) {
				$products = array();
				$products[] = Mage::getModel('catalog/product')->load($id);
                
                $pos = 0;
                foreach ($products as $product) {
                    $product = Mage::getModel('catalog/product')->load($product->getId());

                    if( $product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED ) {
                        unset($products[$pos]);
                    }
                    $pos++;
                }
                
				$product_counter = count($products);
			}
		}
		if ($product_counter == 0 && count($products) == 0) {
			$document = Mage::getModel('mobile/document', '1.0', 'utf-8');
			$document->appendChild($document->createElement('articles'));

			return $document;
		}

		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');
		if ((0 == $article_group_id) && ($product_counter > 1)) {

			if (count($availableCategories) > 1) {
				//Produktgruppen

				$articleGroups = $document->createElement('articleGroups');
				$count_all = 0;
				$all_present = 0;
				foreach ($availableCategories as $key => $value) {

					if ($count_all < $offset) {
						$count_all++;
						continue;
					}
					$category = Mage::getModel('catalog/category');
					$category->load($key);
					if ($category->getName() == 'Alle Artikel') { // special category in AT (currentyl disabled, 18.11.2001)
						$all_present++;
						continue;
					}
					if ( ! $this->_showCategory($category) ) {
						continue;
					}
					$articleGroup = $this->_createCategoryNode($document, $category, 'articleGroup');

					$numberOfProducts = $document->createElement('numberOfProducts', $value);
					$articleGroup->appendChild($numberOfProducts);

					$articleGroups->appendChild($articleGroup);
					$count_all++;
					if (($count_all - $offset) >= $perPage) {
						break;
					}
				}
				$articleGroups->setAttribute('totalNumberOfObjects', count($availableCategories) - $all_present);
				$document->appendChild($articleGroups);

				return $document;
			}
		} else {
			//Artikelliste
			$this->_fillProductListDocument($article_group_id, $facets, $document, $products, $customer, $product_counter, $solrFacets, $solrSearch);
			return $document;
		}
	}

	public function getUserInfo( $getCarts = 1, $getShoppingCartCount = 0 ) {
		$session = Mage::getSingleton('customer/session');
		if ($session->isLoggedIn()) {
			$customer = $this->_getCustomer();
			$customer = Mage::getModel('customer/customer')->load($customer->getId());
			/* @var $customer Schracklive_SchrackCustomer_Model_Customer */
			$document = Mage::getModel('mobile/document', '1.0', 'utf-8');

			$userinfo = $document->createElement('userinfo');
			$userinfo->appendChild($document->createElement('userid', $customer->getId()));
			$userinfo->appendChild($document->createElement('employee', $customer->isEmployee()));
			$userinfo->appendChild($document->createElement('customer_id', $customer->getSchrackWwsCustomerId()));
			$userinfo->appendChild($document->createCdataElement('name', $this->decodeXML($customer->getName())));

			if ($customer->isContact()) {
				$company = $document->createCdataElement('company', $this->decodeXML($customer->getAccount()->getName1().' '.$customer->getAccount()->getName2().' '.$customer->getAccount()->getName3()));
				$userinfo->appendChild($company);
			}

			if ($customer->isDemoUser()) {
				$demouser = $document->createElement('demouser', 1);
				$userinfo->appendChild($demouser);
			}

			if (!$customer->isAllowed('customerOrder', 'order')) {
				$noOrderAuthorization = $document->createElement('noOrderAuthorization', 1);
				$userinfo->appendChild($noOrderAuthorization);
			}

			if (!$customer->isAllowed('price', 'view')) {
				$hidePrices = $document->createElement('hidePrices', 1);
				$userinfo->appendChild($hidePrices);
			}

			$personalAdvisors = $document->createElement('personalAdvisors');
			$advisors = array();
			if (is_object($customer->getAdvisor())) {
				$advisors[] = $customer->getAdvisor();
			}
			if (is_array($customer->getAdditionalAdvisors())) {
				$advisors = array_merge($advisors, $customer->getAdditionalAdvisors());
			}
			foreach ($advisors as $advisor) {
				/* @var $advisor Schracklive_SchrackCustomer_Model_Customer */
				$personalAdvisor = $document->createElement('personalAdvisor');

				$personalAdvisor->appendChild($document->createElement('name', $advisor->getName()));
				$personalAdvisor->appendChild($document->createElement('phone', $advisor->getSchrackTelephone()));
				$personalAdvisor->appendChild($document->createElement('fax', $advisor->getSchrackFax()));
				$personalAdvisor->appendChild($document->createElement('mobile', $advisor->getSchrackMobilePhone()));
				$personalAdvisor->appendChild($document->createElement('email', $advisor->getEmail()));

				$picture = $document->createElement('picture', Mage::getStoreConfig('schrack/general/imageserver').'/'.Mage::getStoreConfig('schrack/shop/employee_images').'/'.strtolower($advisor->getEmail()).'.jpg');
				$personalAdvisor->appendChild($picture);

				$personalAdvisors->appendChild($personalAdvisor);
			}

			$userinfo->appendChild($personalAdvisors);

            if ( $getCarts ) {
                $carts = $document->createElement('carts');
                $carts->appendChild($this->_createCartNode($document, 1, $customer));
                $carts->appendChild($this->_createCartNode($document, 2, $customer));

                $userinfo->appendChild($carts);
            }

            if ( $getShoppingCartCount ) {
                $cart = $this->_getPreparedCart($customer);
                // $items = $cart->getAllItems();
                $cnt = $cart->getItemsCount();
                $userinfo->appendChild($document->createCdataElement('shoppingCartItemsCount', $cnt));
            }
            
            if (($customer->getSchrackPickup()) && ($customer->getSchrackPickup() != '')) {
                $warehouse = $this->_createWarehouseNode($document, $customer->getSchrackPickup());
            } else {
                $warehouse = $document->createElement('warehouse');
            }
			$userinfo->appendChild($warehouse);
			$document->appendChild($userinfo);

			return $document;
		}
	}

	public function getCustomerInfo($customer_id) {
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');

		try {
			$account = Mage::getModel('account/account')->loadByWwsCustomerId($customer_id);
			if ($account->getId()) {
				$customer = $account->getSystemContact();
				$customer_xml = $this->_createCustomerNode($document, array(
					'title' => $account->getPrefix(),
					'name1' => $account->getName1(),
					'name2' => $account->getName2(),
					'name3' => $account->getName3(),
					'street' => $account->getStreet(),
					'zipcode' => $account->getPostcode(),
					'city' => $account->getCity(),
					'country' => $account->getCountryId(),
						));

				if (($customer->getId()) && ($customer->getSchrackPickup()) && ($customer->getSchrackPickup() != '')) {
					$warehouse = $this->_createWarehouseNode($document, $customer->getSchrackPickup());
				} else {
					$warehouse = $document->createElement('warehouse');
				}

				$carts = $document->createElement('carts');
				$carts->appendChild($this->_createCartNode($document, 1, $customer));
				$carts->appendChild($this->_createCartNode($document, 2, $customer));

				$customer_xml->appendChild($warehouse);
				$customer_xml->appendChild($carts);
			} else {
				$crm_info = Mage::getSingleton('crm/connector')->getAccount($customer_id);
				$customer_xml = $this->_createCustomerNode($document, array(
					'title' => $crm_info->prefix,
					'name1' => $crm_info->name1,
					'name2' => $crm_info->name2,
					'name3' => $crm_info->name3,
					'street' => $crm_info->street,
					'zipcode' => $crm_info->postcode,
					'city' => $crm_info->city,
					'country' => $crm_info->country_id,
						));
			}
		} catch (Exception $e) {
			$customer_xml = $document->createElement('error');
			$customer_xml->appendChild($document->createElement('message', $e->getMessage()));
		}

		$document->appendChild($customer_xml);

		return $document;
	}

	/**
	 * @param string $article_id
	 * @param null|int $customer_id
	 * @return Mage_Core_Model_Abstract|mixed|null
	 * @throws Mage_Core_Exception
	 */
	public function getArticle($article_id, $customer_id = null) {
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');

		$customer = $this->_getCustomer($customer_id);
		if (!$customer) {
			throw Mage::exception('Mage_Core', "No customer '{$customer_id}' found for this request.");
		}
		$product = Mage::getModel('catalog/product');
		$productId = $product->getIdBySku($article_id);
		if ($productId) {
			$product->load($productId);
		} else {
			throw Mage::exception('Mage_Core', "Product '{$article_id}' not found.");
		}

		$document->appendChild($this->_createArticleNode($document, $product, $customer, 1, true));

		return $document;
	}

	/**
	 * @param string $article_id
	 * @param null|int $file_id
	 * @param null|string $recepient
	 * @return Mage_Core_Model_Abstract|mixed|null
	 */
	public function sendArticle($article_id, $file_id = null, $recepient = null) {
		$customer = $this->_getCustomer();
		if (!$recepient) {
			$recepient = $customer->getEmail();
		}

		$article = Mage::getModel('catalog/product');
		$productId = $article->getIdBySku($article_id);
		if ($productId) {
			$article->load($productId);
		}

		$templateId = Mage::getStoreConfig('schrack/mobile/article_email_template');
		$sender = Mage::getStoreConfig('schrack/mobile/article_email_identity');
		$templateVars = array(
			'customer'        => $customer,
			'article'         => $article,
            'additional_rows' => '',
		);

		$translate = Mage::getSingleton('core/translate');
		$translate->setTranslateInline(false);
		$email = Mage::getModel('core/email_template');

		// add article attachments

		$attachments = $article->getAttachments();
		foreach ($attachments as $attachment) {
			if ($file_id && ($file_id !== $attachment->getAttachmentId())) {
				continue;
			}
            $filetype = $attachment->getFiletype();
			if ($filetype === 'onlinekatalog') {
				$partUrl = $attachment->getUrl();
                $fullUrl = Mage::getStoreConfig('schrack/general/imageserver').$partUrl;
                $label = $attachment->getLabel();
                $templateVars['additional_rows'] .= '<tr><td>'.$this->__($filetype).'</td><td><a href="'.$fullUrl.'">'.$label.'</a></td><tr>';
            }
			else if ($filetype != 'thumbnails') {
				$url = $attachment->getUrl();
				$_fileInfo = $this->_getFileInfo(Mage::getStoreConfig('schrack/general/imageserver').$url);
				if ($_fileInfo['filesize']) {
					$fileContents = file_get_contents(Mage::getStoreConfig('schrack/general/imageserver').$url);
					$slashindex = strpos($url, '/');
					$fileName = substr($url, $slashindex + 1);
					$email->getMail()->createAttachment($fileContents)->filename = $fileName;
				}
			}
		}
        
		$email->setDesignConfig(array('area' => 'frontend'))->sendTransactional($templateId, $sender, $recepient, null, $templateVars);

		$translate->setTranslateInline(true);

		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');

		$success = $document->createElement('sendArticle');
		if ($email->getSentSuccess()) {
			$success->setAttribute('success', true);
		} else {
			$success->setAttribute('success', false);
		}
		$document->appendChild($success);

		return $document;
	}

	/**
	 * @param string $article_id
	 * @param null|string $drum_id
	 * @param int $quantity
	 * @param int $cart_id
	 * @param null|int $customer_id
	 * @return Mage_Core_Model_Abstract|mixed|null
	 * @throws Mage_Core_Exception
	 */
	public function addToCart($article_id, $drum_id = null, $quantity = 1, $cart_id = 1, $customer_id = null, $partslist_id = null) {
        if ($customer_id === null || !strlen($customer_id))
            $customer_id = null;
		$customer = $this->_getCustomer($customer_id);
		if (!$customer) {
			throw Mage::exception('Mage_Core', 'No customer found for this request.');
		}
        $product = Mage::getModel('Schracklive_SchrackCatalog_Model_Product')->setStoreId(Mage::app()->getStore()->getId());
		$productId = $product->getIdBySku($article_id);
        if( ! $productId ) {
            $productId = $product->getIdByEan( $article_id );
        }
		if (!$productId) {
			throw Mage::exception('Mage_Core', 'Product not found.');
		}
		$product = $product->load($productId);
		if (!$product->getId()) {
			throw Mage::exception('Mage_Core', 'Invalid product.');
		}
		$product->setCustomer($customer); // the getFinalPrice observer needs this

		if ($cart_id == 1) {
			$cart = $this->_getPreparedCart($customer);
			if ($drum_id) {
				$cart->addProduct($product, new Varien_Object(array('qty' => $quantity, 'schrack_drum_number' => $drum_id)))->setSchrackDrumNumber($drum_id);
			} else {
				$cart->addProduct($product, $quantity);
			}
			$cart->collectTotals();
			$cart->save();
		} elseif ($cart_id == 2) {
			$wishlist = Mage::getModel('schrackwishlist/wishlist')->loadByCustomer($customer, true);

			$wishlist->addNewItem($product);
			$wishlist->save();
		} elseif ($cart_id == 3) {
            try {
                if ($partslist_id === null)
                    $partslist = Mage::getModel('schrackwishlist/partslist')->loadActiveListByCustomer($customer);
                else
                    $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId($customer, $partslist_id);
                $partslist->addNewItem($product,array('qty' => $quantity));
                $partslist->save();
            } catch (Exception $e) {
                throw Mage::exception('Mage_Core', 'Invalid cart. - ' . $e->getMessage());
            }
		} else {
            throw Mage::exception('Mage_Core', 'Invalid cart.');
        }

		return $this->getCart($cart_id, $customer_id, $partslist_id);
	}

	/**
	 * @param string $article_id
	 * @param null|string $drum_id
	 * @param int $quantity
	 * @param int $cart_id
	 * @param null|int $customer_id
	 * @return Mage_Core_Model_Abstract|mixed|null
	 * @throws Mage_Core_Exception
	 */
	public function removeFromCart($article_id, $drum_id = null, $quantity = 0, $cart_id = 1, $customer_id = null) {
		$customer = $this->_getCustomer($customer_id);
		$productId = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore()->getId())->getIdBySku($article_id);

		if ($cart_id == 1) {
			$cart = $this->_getPreparedCart($customer);
			$items = $cart->getAllItems();
			foreach ($items as $item) {
				if ($item->getProduct()->getId() == $productId) {
					if ($drum_id) {
						if ($item->getSchrackDrumNumber() == $drum_id) {
							$cart->removeItem($item->getId());
						}
					} else {
						$cart->removeItem($item->getId());
					}
				}
			}
            $cart->collectTotals();
			$cart->save();
		} elseif ($cart_id == 2) {
			$wishlist = Mage::getModel('schrackwishlist/wishlist')->loadByCustomer($customer, true);
			$items = $wishlist->getItemCollection();
			foreach ($items as $item) {
				if ($item->getProduct()->getId() == $productId) {
					$item->delete();
					break;
				}
			}
			$wishlist->save();
		} elseif ($cart_id == 3) {
			$partslist = Mage::getModel('schrackwishlist/partslist')->loadActiveListByCustomer($customer);
			$items = $partslist->getItemCollection();
			foreach ($items as $item) {
				if ($item->getProduct()->getId() == $productId) {
					$item->delete();
					break;
				}
			}
			$partslist->save();
		} else {
			throw Mage::exception('Mage_Core', 'Invalid cart.');
		}

		return $this->getCart($cart_id, $customer_id);
	}

	public function test() {
		return 0;
	}

	public function setQuantity($article_id, $quantity, $cart_id = 1, $customer_id = null, $drum_id = null) {
		$customer = $this->_getCustomer($customer_id);

		$productId = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore()->getId())->getIdBySku($article_id);
		if ($cart_id == 1) {
			$cart = $this->_getPreparedCart($customer);
			$items = $cart->getAllItems();
			foreach ($items as $item) {
				if ($item->getProduct()->getId() == $productId) {
					if (!$drum_id || ($drum_id && ($item->getSchrackDrumNumber() == $drum_id))) {
						if ($quantity <= 0) {
							$item->setQty(0);
							$item->isDeleted(true);
							$item->delete();
						} else {
							$item->getProduct()->setCustomer($customer); // the getFinalPrice observer needs this
							$item->setQty($quantity);
							$item->save();
						}
						break;
					}
				}
			}
			$cart->collectTotals();
			$cart->save();
		} elseif ($cart_id == 2) {
			$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer, true);
			$items = $wishlist->getItemCollection();
			foreach ($items as $item) {
				if ($item->getProduct()->getId() == $productId) {
					if ($quantity == 0) {
						$item->delete();
						break;
					}
				}
			}
			$wishlist->save();
		}
        return $this->getCart($cart_id, $customer_id);
	}

	public function getCart($cart_id = 1, $customer_id = null, $partslist_id = null) {
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');

		try {
			$customer = $this->_getCustomer($customer_id);
			if (!$customer) {
				throw Mage::exception('Mage_Core', "No customer '{$customer_id}' found for this request.");
			}

			$document->appendChild($this->_createCartNode($document, $cart_id, $customer, $partslist_id));
		} catch (Exception $e) {
			$document->appendChild($document->createElement('cart')); // empty node to make XML parsers happy
			$document->appendChild($document->createComment('Exception: '.$e->getMessage()));
		}

		return $document;
	}

	public function cleanCart($cart_id = 1, $customer_id = null) {
        /* DLA: seems to be very senseless:
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');

		$cart_xml = $document->createElement('cart');
		$cart_xml->setAttribute('id', $cart_id);
        */
		$customer = $this->_getCustomer($customer_id);
		if ($cart_id == 1) {
			$quote = $this->_getPreparedCart($customer);
			foreach ($quote->getItemsCollection() as $item) {
				$item->isDeleted(true);
			}
			$quote->collectTotals();
			$quote->save();
		} elseif ($cart_id == 2) {
			$wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($customer, true);
			$items = $wishlist->getItemCollection();
			foreach ($items as $item) {
				$item->delete();
			}
			$wishlist->delete();
		} else {
			throw Mage::exception('Mage_Core', 'Invalid cart.');
		}

		return $this->getCart($cart_id, $customer_id);
	}

	public function getCheckoutUrl($cart_id = 1, $customer_id = null) {
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');

		$url_xml = $document->createElement('checkout');

		//create appkey
		$httpd_username = filter_var($_SERVER['PHP_AUTH_USER'], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);
		$httpd_password = filter_var($_SERVER['PHP_AUTH_PW'], FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);
		if ($customer_id === null) {
			$appkey = base64_encode($httpd_username.':'.$httpd_password);
		} else {
			$account = Mage::getModel('account/account')->loadByWwsCustomerId($customer_id);
			if ($account->getId()) {
				$customer = $account->getSystemContact();
				if ($customer->getId()) {
					$appkey = base64_encode($httpd_username.':'.$httpd_password.':'.$customer->getId());
				}
			}
		}

		$baseUrl = Mage::getStoreConfig('web/secure/use_in_frontend') ? Mage::getStoreConfig('web/secure/base_url') : Mage::getStoreConfig('web/unsecure/base_url');

		if ($cart_id == 1) {
			$url_tag = $document->createElement('url', $baseUrl.'mobile/onepage/?appkey='.$appkey);
		} elseif ($cart_id == 2) {
			$url_tag = $document->createElement('url', $baseUrl.'wishlist/?appkey='.$appkey);
		}
		$url_xml->appendChild($url_tag);
		$document->appendChild($url_xml);
		return $document;
	}

	public function makeOffer($cart_id = 1, $customer_id = NULL) {
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');
		$xml = $document->createElement('offer');

		try {
			if ($cart_id != 1) {
				throw Mage::exception('Mage_Core', 'Only cart id 1 is supported.');
			}

			$customer = $this->_getCustomer($customer_id);
			$loggedInCustomer = null;
			if (!$customer) {
				throw Mage::exception('Mage_Core', 'No customer found for this request.');
			}
			if ($customer->isSystemContact()) {
				$loggedInCustomer = Mage::getSingleton('customer/session')->getLoggedInCustomer();
			}

			$quote = Mage::getModel('sales/quote');
			$quote->loadByCustomer($customer);
			if ( ! $quote->getId() ) {
				throw new Exception("No active cart for customer found!");
			}

			if ($this->_makeWwsOffer($quote, $loggedInCustomer)) {
                foreach ($quote->getItemsCollection() as $item) {
                    $item->isDeleted(true);
                }
                $quote->setIsActive(false);
                $quote->delete(); 
                Mage::getSingleton('checkout/session')->clear();
			}
		} catch (Exception $e) {
            Mage::logException($e);
			$xml->appendChild($document->createElement('errorCode', 1));
            $xml->appendChild($document->createElement('errorMessage',$e->getMessage()));
			$document->appendChild($xml);
			Mage::helper('schrack/logger')->error('Could not make offer: '.$e->getMessage());

			return $document;
		}

		$xml->appendChild($document->createElement('errorCode', 0));
		$document->appendChild($xml);

		return $document;
	}

	public function getCategoryTree() {
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');
		$categoryTree = $document->createElement('categoryTree');

		$categories = Mage::getModel('catalog/category')
				->getCollection()
				->addAttributeToSelect('*')
				->addFieldToFilter(array(array('attribute' => 'is_active', 'eq' => '1')))
				->addFieldToFilter(array(array('attribute' => 'level', 'eq' => '2')))
				->setOrder('position', 'ASC')
				->load();

		$lastLevel = 2;
		$i = 0;
		foreach ($categories as $category) {
			if ( !$this->_showCategory($category) ) {
                continue;
            }
			$categoryGroup = $this->_createCategoryNode($document, $category);
			$categoryGroups = $document->createElement('categoryGroups');

			$childCategories = $category->getChildrenCategories();
			foreach ($childCategories as $childCategory) {
				if ( !$this->_showCategory($childCategory) ) {
					continue;
				}
				$childCategoryGroup = $this->_createCategoryNode($document, $childCategory);
				$categoryGroups->appendChild($childCategoryGroup);
			}
			$categoryGroup->appendChild($categoryGroups);
			$categoryTree->appendChild($categoryGroup);
		}
		$document->appendChild($categoryTree);

		return $document;
	}

	public function getWarehouses() {
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');

		$xml = $document->createElement('warehouses');
		foreach (Mage::helper('schrackshipping/pickup')->getWarehouseIds() as $id) {
			$xml->appendChild($this->_createWarehouseNode($document, $id));
		}
		$document->appendChild($xml);

		return $document;
	}

	public function setWarehouse($warehouse_id, $customer_id = NULL) {
		$customer = $this->_getCustomer($customer_id);
		if (!$customer) {
			throw Mage::exception('Mage_Core', 'No customer found for this request.');
		}

		$customer->setSchrackPickup($warehouse_id);
		$customer->save();

		// forward request
		return $this->getWarehouse($warehouse_id);
	}

	public function getWarehouse($warehouse_id) {
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');

		$document->appendChild($this->_createWarehouseNode($document, $warehouse_id));

		return $document;
	}

	/*
	 * Node Helpers
	 *
	 */

	protected function _createCustomerNode(Schracklive_Mobile_Model_Document $document, array $customerData) {
		$customer_xml = $document->createElement('customer');

		$title = $document->createCdataElement('title', $customerData['title']);
		$name1 = $document->createCdataElement('name1', $customerData['name1']);
		$name2 = $document->createCdataElement('name2', $customerData['name2']);
		$name3 = $document->createCdataElement('name3', $customerData['name3']);
		$street = $document->createCdataElement('street', $customerData['street']);
		$zipcode = $document->createCdataElement('zipcode', $customerData['zipcode']);
		$city = $document->createCdataElement('city', $customerData['city']);
		$country = $document->createCdataElement('country', $customerData['country']);
		$customer_xml->appendChild($title);
		$customer_xml->appendChild($name1);
		$customer_xml->appendChild($name2);
		$customer_xml->appendChild($name3);
		$customer_xml->appendChild($street);
		$customer_xml->appendChild($zipcode);
		$customer_xml->appendChild($city);
		$customer_xml->appendChild($country);

		return $customer_xml;
	}

	/**
	 *
	 * @param Schracklive_Mobile_Model_Document $document
	 * @param type $drum
	 * @return type
	 */
	protected function _createDrumNode(Schracklive_Mobile_Model_Document $document, $drum) {
		$node = $document->createElement('drum');
		$_id = $document->createElement('id', $drum->wws_number);
		$_name = $document->createElement('name', $drum->name);
		$_description = $document->createElement('description', $drum->description);
		$_type = $document->createElement('type', $drum->type ? $drum->type : 'F');
		$_size = $document->createElement('size', $drum->size);
		$_qty = $document->createElement('qty', $drum->stock_qty);
		$_lessenDelivery = $document->createElement('lessen_delivery', ((bool)$drum->getLessenDelivery()) ? '1' : '0');
		$_lessenPickup = $document->createElement('lessen_pickup', ((bool)$drum->getLessenPickup()) ? '1' : '0');
		$node->appendChild($_id);
		$node->appendChild($_name);
		$node->appendChild($_description);
		$node->appendChild($_type);
		$node->appendChild($_size);
		$node->appendChild($_qty);
		$node->appendChild($_lessenDelivery);
		$node->appendChild($_lessenPickup);
		return $node;
	}

	/**
	 * Build a new product node.
	 * @param DOMDocument $document
	 * @param Mage_Catalog_Model_Product $product
	 * @param null|Mage_Customer_Model_Customer $customer
	 * @param int $qty
	 * @param bool $details
	 * @return DOMElement
	 */
	protected function _createArticleNode($document, Schracklive_SchrackCatalog_Model_Product $product, Schracklive_SchrackCustomer_Model_Customer $customer = null, $qty = 1, $details = false) {
		if ($customer == null) {
			$customer = $this->_getCustomer();
		}

		$endOfLive = false;
		$predecessor = null;
        $this->_checkEndOfLiveAndPredecessor($product,$endOfLive,$predecessor);

		$article = $document->createElement('article');
		$article->setAttribute('id', $product->getSKU());
		$ean = $document->createElement('ean', $product->getSchrackEan());
		$article->appendChild($ean);
		$description = $document->createElement('description');
		$description->appendChild($document->createCDATASection($this->decodeXML($product->getDescription())));
		$article->appendChild($description);
		//get info from WWS
		//START WWS
		try {
            $productHelper = Mage::helper('schrackcatalog/product');
			$catalogInfo = Mage::helper('schrackcatalog/info');
            $stockHelper = Mage::helper('schrackcataloginventory/stock');
			$pickupWarehouseId = Mage::helper('schrackcustomer')->getPickupWarehouseId($customer);

			$article->appendChild($document->createElement('isSale', $product->isSale() ? 1 : 0));
			$article->appendChild($document->createElement('isRestricted', $product->isRestricted() ? 1 : 0));
			$article->appendChild($document->createElement('isHideStockQantities', $product->isHideStockQantities() ? 1 : 0));
			$lab = $this->getPictureLabel($productHelper,$product,$customer);
			if ( $lab ) {
				$article->appendChild($document->createElement('additionalPictureLabel', $lab));
			}
			if ( $details && ($product->isSale() || $product->isDead()) ) {
				$larp = $product->getLastReplacementProduct();
				if ( $larp ) {
					$article->appendChild($document->createElement('replacementProduct',$larp->getSku()));
				}
			}

			$hasDrumsFlag = false;
			$hasDrumsFlag = $productHelper->hasDrums($product);
			if ($hasDrumsFlag) {
				$hasDrums = $document->createElement('hasdrums', 1);
			} else {
				$hasDrums = $document->createElement('hasdrums', 0);
			}
			$article->appendChild($hasDrums);

			$graduated = 0;
			$prices = $catalogInfo->getGraduatedPricesForCustomer($product, $customer);
			if (count($prices) > 0) {
				$graduated = 1;
			}
			$article->appendChild($document->createElement('graduated', $graduated));
			if ( $graduated && ! $endOfLive ) {
				$graduatedPrices = $document->createElement('graduated_prices');
				foreach ($prices as $value) {
					$step = $document->createElement('step');
					$step->setAttribute('qty', $value['qty']);
					$step->setAttribute('price', $this->_formatPrice($value['price']));
					$graduatedPrices->appendChild($step);
				}
				$article->appendChild($graduatedPrices);
			}
			if ( ! $endOfLive ) {
                $price = $document->createElement('price', $this->_formatPrice($catalogInfo->getBasicTierPriceForCustomer($product, $qty, $customer)));
            } else {
                $price = $document->createElement('price', 'XXX');
            }
            $article->appendChild($price);

			$promotionEndDate = $productHelper->getPromotionEndDate($product,$customer);
			$promotion = $promotionEndDate > '';
			$article->appendChild($document->createElement('isPromotion', $promotion ? 1 : 0));
			if ( $productHelper->isPromotion($product,$customer) && ! $endOfLive ) {
				$article->appendChild($document->createElement('regularPrice', $this->_formatPrice($productHelper->getRegularPrice($product, $customer))));
				if ( $promotion ) {
					$article->appendChild($document->createElement('promotionValidTo', $promotionEndDate));
				}
			}

            $deliveryEl = $document->createElement('delivery_stocks');
            $isQuantityNumeric = true;
            $isQuantityNumeric &= $this->_addStockInfos($details && $hasDrumsFlag,$document,$deliveryEl,$product,$stockHelper->getLocalDeliveryStock(),$productHelper,$catalogInfo,true);
            $isQuantityNumeric &= $this->_addStockInfos($details && $hasDrumsFlag,$document,$deliveryEl,$product,$stockHelper->getForeignDeliveryStock(),$productHelper,$catalogInfo,false);
			if ( ! $product->isSale() ) {
				foreach ( $stockHelper->getThirdPartyDeliveryStocks() as $stock ) {
					$isQuantityNumeric &= $this->_addStockInfos($details && $hasDrumsFlag,$document,$deliveryEl,$product,$stock,$productHelper,$catalogInfo,false);
				}
			}

            $pickupEl = $document->createElement('pickup_stocks');
            $pkStocks = $stockHelper->getPickupStocks();
            $pkStock = $pkStocks[$pickupWarehouseId];
            $isQuantityNumeric &= $this->_addStockInfos($details && $hasDrumsFlag,$document,$pickupEl,$product,$pkStock,$productHelper,$catalogInfo,true,false);

			if (!$isQuantityNumeric ) {
				$qtyUnit = '';
			} else {
				$qtyUnit = $product->getSchrackQtyunit();
			}

			$article->appendChild($deliveryEl);
			$article->appendChild($pickupEl);
			$article->appendChild($document->createElement('unit', $qtyUnit));
			$article->appendChild($document->createElement('priceunit', $product->getSchrackPriceunit($product)));
			$article->appendChild($document->createElement('currency', Mage::getStoreConfig('currency/options/base')));
		} catch (Exception $e) {
			Mage::logException($e);
		}

		$bigImageUrl = $smallImageUrl = '';
        $mainImageUrl = $product->getMainImageUrl();
        if ( $mainImageUrl ) {
            $bigImageUrl = Schracklive_SchrackCatalog_Helper_Image::getImageUrl($mainImageUrl, Schracklive_SchrackCatalog_Helper_Image::PRODUCT_DETAIL_PAGE_MAIN);
            $smallImageUrl = Schracklive_SchrackCatalog_Helper_Image::getImageUrl($mainImageUrl, Schracklive_SchrackCatalog_Helper_Image::PRODUCT_DETAIL_PAGE_THUMBNAIL);
        }
		$image = $document->createElement("image",$bigImageUrl);
		$article->appendChild($image);
		$thumbnail = $document->createElement("thumbnail",$smallImageUrl);
		$article->appendChild($thumbnail);

		if ($predecessor) {
			$article->appendChild($document->createElement('predecessor', $predecessor));
		}
		if ($endOfLive) {
			$article->appendChild($document->createElement('endOfLifecycle', 1));
		}
		//END WWS
		//Files

		if ($details) {

			$attachments = $product->getAttachments();
			$files = $document->createElement('files');
			foreach ($attachments as $attachment) {
                $filetype = $attachment->getFiletype();
				if ($filetype != 'thumbnails') {
					$url = $attachment->getUrl();
					$file = $document->createElement('file');
					$id = $document->createElement('fileid', $attachment->getAttachmentId());
					$value = $document->createElement('value', Mage::getStoreConfig('schrack/general/imageserver').$url);
                    $label = $attachment->getLabel();
					$label = htmlspecialchars($label,ENT_XML1,'UTF-8');
					$name = $document->createElement('name', $label);
					$fileinfo = $this->_getFileInfo(Mage::getStoreConfig('schrack/general/imageserver').$url);
					$mimetype = $document->createElement('mimetype', $fileinfo['mimetype']);
					$filesize = $document->createElement('filesize', $fileinfo['filesize']);
					$file->appendChild($id);
					$file->appendChild($value);
					$file->appendChild($name);
					$file->appendChild($mimetype);
					$file->appendChild($filesize);
					if ($fileinfo['filesize']) {
						$files->appendChild($file);
					}
				}
			}
			$article->appendChild($files);
			$complete = $document->createElement('complete', 1);

            $related = $document->createElement('relatedproducts');

			$related_products_collection = $product->getRelatedProductCollection()
					->addAttributeToSort('position', 'asc')
					->addStoreFilter();

			Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($related_products_collection);

			$related_product_limit = Mage::getStoreConfig("schrack/shop/related_products");

			if ((!is_null($related_product_limit)) && (is_numeric($related_product_limit)) && $related_product_limit> 0) {
				$related_products_collection->setPageSize($related_product_limit);
			}

			$related_products_collection->load();

            foreach ( $related_products_collection as $related_product_item ) {

                try {
					$related_product_item->setDoNotUseCategoryId(true);
					$related_product_item->load($related_product_item->getId());
                    if ( !is_null($related_product_item) && ( $related_product_item->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED )) {
                        $related->appendChild($this->_createArticleNode($document, $related_product_item, $customer, 1, false));
                    }
                }
                catch( Exception $e )
                {
                    Mage::logException($e);
                }

            }
            $article->appendChild($related);

		} else {
			$complete = $document->createElement('complete', 0);
		}
		$article->appendChild($complete);
		return $article;
	}

    private function _addStockInfos ( $showDrums, $document, $parentEl, $product, $stock, $productHelper, $catalogInfo, $addEmptyStock, $asDelivery = true ) {
        if ( ! isset($stock) )
            return true;

        if ( $asDelivery ) {
			if ( $product->isSale() ) {
				$numQty = $productHelper->getSummarizedStockQuantities($product);
				$strQty = $productHelper->formatQty($product,$numQty);
				$qty = array( $numQty, $strQty );
			} else {
				$qty = $productHelper->getFormattedAndUnformattedDeliveryQuantity($product, $stock->getStockNumber(), false, $stock->getStockLocation());
			}
        }
        else {
            $qty = $productHelper->getFormattedAndUnformattedPickupQuantity($product, $stock->getStockNumber(), false);
        }
        if ( $qty[0] == 0 && ! $addEmptyStock )
            return true;

        $el = $document->createElement('stock');
        $el->appendChild($document->createElement('number',$stock->getStockNumber()));
        if ( $asDelivery ) {
			if ( ! $product->isSale() ) {
				$el->appendChild($document->createElement('delivery_time_abbr',$stock->getDeliveryTimeAbbreviation()));
				$el->appendChild($document->createElement('delivery_hours',$stock->getDeliveryHours()));
			}
            $el->appendChild($document->createElement('state', $catalogInfo->getDeliveryState($product,$stock->getStockNumber())));
			//$el->appendChild($document->createElement('salesunit', $catalogInfo->getDeliverySalesUnit($product,$stock->getStockNumber())));
			$quantityProductData = $product->calculateClosestHigherQuantityAndDifference(1, true);
			$el->appendChild($document->createElement('salesunit', $quantityProductData['closestHigherQuantity']));
        }
        else {
			$el->appendChild($document->createElement('delivery_time_abbr',$stock->getDeliveryTimeAbbreviation()));
            $el->appendChild($document->createElement('state', $catalogInfo->getPickupState($product,$stock->getStockNumber())));
			$el->appendChild($document->createElement('salesunit', $catalogInfo->getPickupSalesUnit($product,$stock->getStockNumber())));
        }
        $el->appendChild($document->createElement('quantity_num',$qty[0]));
        $el->appendChild($document->createElement('quantity',$qty[1]));

        if ( $showDrums ) {
            $drumsAvailable = $catalogInfo->getAvailableDrums($product, array($stock->getStockNumber()));
            $available = $document->createElement('drums_available');
            foreach ($drumsAvailable as $warehouseId => $warehouseDrums) {
                foreach ($warehouseDrums as $drum) {
                    $available->appendChild($this->_createDrumNode($document, $drum));
                }
            }
            $el->appendChild($available);

            $drumsPossible = $catalogInfo->getPossibleDrums($product, array($stock->getStockNumber()));
            $possible = $document->createElement('drums_possible');
            foreach ($drumsPossible as $warehouseId => $warehouseDrums) {
                foreach ($warehouseDrums as $drum) {
                    $possible->appendChild($this->_createDrumNode($document, $drum));
                }
            }
            $el->appendChild($possible);
        }

        $parentEl->appendChild($el);

        return is_numeric($qty[0]);
    }

	/**
	 * Build a new cart node.
	 *
	 * @param DOMDocument $document
	 * @param integer $cartId
	 * @param Mage_Customer_Model_Customer $customer
	 * @return DOMElement
	 */
	protected function _createCartNode($document, $cartId, $customer, $partslistId = null) {
		$node = $document->createElement('cart');
		$node->setAttribute('id', $cartId);
		$catalogInfo = Mage::helper('schrackcatalog/info');

		if ($cartId == 1) {
			// TODO: move this magic to the caller (or a helper)
			$cart = $this->_getPreparedCart($customer);
			$items = $cart->getAllItems();
			$catalogInfo->preloadProductsInfo($items, $customer);
			$qtys = array();
			foreach ($items as $item) {
				$qtys[$item->getSku()] = (int)$item->getQty();
			}
			$catalogInfo->preloadProductsInfo($items, $customer, false, $qtys);

			foreach ($items as $item) {
				$product = Mage::getModel('catalog/product')->load($item->getProductId());

				$cart_item = $document->createElement('cartarticle');
				$cart_item->setAttribute('item_id', $item->getId());
				$cart_articleid = $document->createElement('articleid', $item->getSku());
				$cart_name = $document->createCdataElement('name', $this->decodeXML($item->getName()));
				$cart_qty = $document->createElement('quantity', (int)$item->getQty());
				if ($item->getSchrackDrumNumber()) {
					$cart_drum_id = $document->createElement('drum_id', $item->getSchrackDrumNumber());
					$cart_item->appendChild($cart_drum_id);
				}
				$cart_pricetype = $document->createElement('pricetype');
				$cart_price = $document->createElement('price', $this->_formatPrice($item->getSchrackBasicPrice()));
				$cart_surcharge = $document->createElement('surcharge',$this->_formatPrice($item->getSchrackRowTotalSurcharge()));
				$priceunit = 1;
				if ($product->getSchrackPriceunit() && $product->getSchrackPriceunit() != '') {
					$priceunit = intval($product->getSchrackPriceunit());
				}
				$cart_totalprice = $document->createElement('totalprice', $this->_formatPrice($item->getRowTotal()));

				if ( $url = $product->getMainImageUrl() ) {
                    $cart_icon = $document->createElement('icon',Schracklive_SchrackCatalog_Helper_Image::getImageUrl($url,Schracklive_SchrackCatalog_Helper_Image::CART_APP));
				} else {
    				$cart_icon = $document->createElement('icon', '');
                }

				$cart_item->appendChild($cart_articleid);
				$cart_item->appendChild($cart_name);
				$cart_item->appendChild($cart_price);
				$cart_item->appendChild($cart_surcharge);
				$cart_item->appendChild($cart_pricetype);
				$cart_item->appendChild($cart_qty);
				$cart_item->appendChild($cart_totalprice);
				$cart_item->appendChild($cart_icon);

				$cart_item->appendChild($this->_createArticleNode($document, $product, $customer, (int)$item->getQty(), true));

				$node->appendChild($cart_item);
			}

			$vat = (float)Mage::getStoreConfig('schrack/sales/vat');

			// TODO: get tax and grand total from the quote
			$total_price = $cart->getSubtotal();
			$total_tax = $vat / 100 * $total_price;
			$node->appendChild($document->createElement('totalprice', $this->_formatPrice($total_price)));
			$node->appendChild($document->createElement('tax', $this->_formatPrice($total_tax)));
			$node->appendChild($document->createElement('grossprice', $this->_formatPrice($total_price + $total_tax)));
			$node->appendChild($document->createElement('vat', $this->_formatNumber($vat)));
			$node->appendChild($document->createElement('currency', Mage::getStoreConfig('currency/options/base')));
		} elseif ($cartId == 2) {
			$wishlist = Mage::getModel('schrackwishlist/wishlist')->loadByCustomer($customer, true);
			$items = $wishlist->getItemCollection();
			$catalogInfo->preloadProductsInfo($items, $customer);

			foreach ($items as $item) {
				$product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());

				$cart_item = $document->createElement('cartarticle');
				$cart_item->setAttribute('item_id', $item->getId());
				$cart_articleid = $document->createElement('articleid', $item->getProduct()->getSKU());
				$cart_name = $document->createElement('name');
				if ( $url = $product->getMainImageUrl() ) {
                    $cart_icon = $document->createElement('icon',Schracklive_SchrackCatalog_Helper_Image::getImageUrl($url,Schracklive_SchrackCatalog_Helper_Image::CART_APP));
				} else {
    				$cart_icon = $document->createElement('icon', '');
                }
				$cart_name->appendChild($document->createCDATASection($this->decodeXML($item->getProduct()->getName())));
				$cart_item->appendChild($cart_name);
				$cart_item->appendChild($cart_articleid);
				$cart_item->appendChild($cart_icon);
				$cart_item->appendChild($this->_createArticleNode($document, $product, $customer, 1, true));

				$node->appendChild($cart_item);
			}
		} elseif ($cartId == 3) {
            if ($partslistId === null)
                $partslist = Mage::getModel('schrackwishlist/partslist')->loadActiveListByCustomer($customer, true);
            else
                $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId($customer, $partslistId);
			$items = $partslist->getItemCollection();
			$catalogInfo->preloadProductsInfo($items, $customer);

			foreach ($items as $item) {
				$product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());

				$cart_item = $document->createElement('cartarticle');
				$cart_item->setAttribute('item_id', $item->getId());
				$cart_articleid = $document->createElement('articleid', $item->getProduct()->getSKU());
				$cart_name = $document->createElement('name');
				if ( $url = $product->getMainImageUrl() ) {
                    $cart_icon = $document->createElement('icon',Schracklive_SchrackCatalog_Helper_Image::getImageUrl($url,Schracklive_SchrackCatalog_Helper_Image::CART_APP));
				} else {
    				$cart_icon = $document->createElement('icon', '');
                }
				$cart_name->appendChild($document->createCDATASection($this->decodeXML($item->getProduct()->getName())));
				$cart_item->appendChild($cart_name);
				$cart_item->appendChild($cart_articleid);
				$cart_item->appendChild($cart_icon);
				$cart_item->appendChild($this->_createArticleNode($document, $product, $customer, 1, true));

				$node->appendChild($cart_item);
			}
        } else {
            throw new Exception('no such cart_id as '.$cartId);
        }

		return $node;
	}

	/**
	 * Build a new warehouse node.
	 *
	 * @param DOMDocument $document
	 * @param integer $warehouseId
	 * @return DOMElement
	 */
	protected function _createWarehouseNode($document, $warehouseId) {
		$warehouse = Mage::helper('schrackshipping/pickup')->getWarehouse($warehouseId);
		$node = $document->createElement('warehouse');

		$node->setAttribute('id', $warehouseId);
		$name = $document->createElement('name', $warehouse ? $warehouse->getName() : '');
		$node->appendChild($name);
		if ($warehouse && $warehouse->getAddress()) {
			$address = $document->createDocumentFragment();
			$address->appendXML($warehouse ? $warehouse->getAddress() : '');
			$node->appendChild($address);
		}

		return $node;
	}

	protected function _createCategoryNode($document, $category, $nodeName = 'categoryGroup') {
		$categoryGroup = $document->createElement($nodeName);
		$categoryGroup->setAttribute('id', $category->getId());

		$name = $document->createElement('name');
		$nametext = $category->getName();
		$stsID = $category->getSchrackGroupId();
		if ( $stsID === '_PROMOS_' ) {
			$nametext = $this->__($nametext);
		}
		$name->appendChild($document->createCDATASection($this->decodeXML($nametext)));
		$categoryGroup->appendChild($name);

        $mainImageUrl = $category->getSchrackThumbnailUrl();
		$thumbnail = $document->createElement('thumbnail',Schracklive_SchrackCatalog_Helper_Image::getImageUrl($mainImageUrl,Schracklive_SchrackCatalog_Helper_Image::PRODUCT_CATEGORY_PAGE_APP_THUMBNAIL));
		$categoryGroup->appendChild($thumbnail);

		$image = $document->createElement('image',Schracklive_SchrackCatalog_Helper_Image::getImageUrl($mainImageUrl,Schracklive_SchrackCatalog_Helper_Image::PRODUCT_CATEGORY_PAGE_APP_BIG));
		$categoryGroup->appendChild($image);

		$description = $document->createElement('description');
		$description->appendChild($document->createCDATASection($this->decodeXML($nametext)));

		$categoryGroup->appendChild($description);

		if ( $stsID === '_PROMOS_' || strlen($stsID) > 5 && substr($stsID,-3) === '999' ) {
			$highlight = $document->createElement('highlight','1');
			$categoryGroup->appendChild($highlight);
		}

		return $categoryGroup;
	}

	/*
	 * Helpers
	 *
	 */

	protected function decodeXML($data) {
		// TODO: looks like a dupe of htmlspecialchars_decode($data, ENT_QUOTES)
		$res = $data;
		$res = str_replace('&lt;', '<', $res);
		$res = str_replace('&gt;', '>', $res);
		$res = str_replace('&quot;', '\'', $res);
		$res = str_replace('&amp;', '&', $res);
		return $res;
	}

	protected function _getFileInfo($url) {
		$fileData = Mage::getModel('schrackcatalog/filedata');
		$fileInfo = array();
		$fileInfo['mimetype'] = '';
		$fileInfo['filesize'] = '';

		$fileData->loadByUrl($url);
		if ($fileData->getId()) {
			$fileInfo['mimetype'] = $fileData->getMimetype();
			$fileInfo['filesize'] = $fileData->getFilesize();
			if ($fileInfo['mimetype'] && $fileInfo['filesize']
			) {
				return $fileInfo;
			}
		}

		if (($url != null) && ($url != '')) {
			$file = @fopen($url, 'r');
			if ($file) {
				$headers = stream_get_meta_data($file);
				$fileData->setUrl($url);
				foreach ($headers['wrapper_data'] as $header) {
					if (strpos(strtolower($header), 'content-type') !== FALSE) {
						$fileInfo['mimetype'] = trim(substr($header, strpos($header, ':') + 1));
						$fileData->setMimetype($fileInfo['mimetype']);
					}
					if (strpos(strtolower($header), 'content-length') !== FALSE) {
						$fileInfo['filesize'] = trim(substr($header, strpos($header, ':') + 1));
						$fileData->setFilesize($fileInfo['filesize']);
					}
				}
				$fileData->save();
			}
		}

		return $fileInfo;
	}

	/*
	 * Phase 2.5 Kundensuche
	 */

	public function searchCustomers($needle) {
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');

		$customers = $document->createElement('customers');
		if (strlen($needle) < 3) {
			$document->appendChild($customers);
			return $document;
		}
		$customer = Mage::getModel('customer/customer');
		$customerFilter = array();
		foreach ($this->searchableCustomerAttributes as $attribute) {
			$customerFilter['concat'][] = '{{'.$attribute.'}}';
			$customerFilter['fields'][] = $attribute;
		}

		$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$userEmail = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
		$p = strpos($userEmail,'@');
		$mailName = substr($userEmail,0,$p);
		$principalMask = $mailName . '@%schrack%';
		// TODO: clearify if additional advisors should be used as well
		// $principalsMaskBetween = '%,' . $mailName . '@%';
		// $accountIDs = $connection->fetchCol("select account_id from account where advisor_principal_name like ? or advisors_principal_names like ? or advisors_principal_names like ?",array($principalMask,$principalMask,$principalsMaskBetween));
		$accountIDs = $connection->fetchCol("select account_id from account where advisor_principal_name like ?",$principalMask);

		$genders = Mage::getResourceSingleton('customer/customer')->getAttribute('gender')->getSource()->getAllOptions();
		$crmRoles = Mage::getResourceSingleton('customer/customer')->getAttribute('schrack_crm_role_id')->getSource()->getAllOptions();
		$customerCollection = $customer->getCollection()->addAttributeToSelect('*')->setAnyContactFilter();
		$customerCollection->addExpressionAttributeToFilter('full_name', 'CONCAT_WS(" ",'.implode(',', $customerFilter['concat']).')', $customerFilter['fields'], "LIKE '%".$needle."%'");
		$customerCollection->addAttributeToFilter('schrack_account_id', array('in' => $accountIDs));
		$foundCustomers = array();
		foreach ($customerCollection as $customer) {
			$schrackWwsCustomerId = $customer->getSchrackWwsCustomerId();
			if (isset($foundCustomers[$schrackWwsCustomerId])) {
				continue;
			}
			if (!$customer->isAnyWwsContact()) {
				continue;
			}
			$foundCustomers[$schrackWwsCustomerId] = $schrackWwsCustomerId;
			$contactCollection = Mage::getModel('customer/customer')->getCollection()->setRealContactAndProspectFilter()->addAttributeToFilter('schrack_wws_customer_id', array('eq' => $schrackWwsCustomerId))->addAttributeToSelect('*');
			$addressCollection = $customer->getAddressesCollection();
			$account = $customer->getAccount();
			$addressBilling = $customer->getPrimaryBillingAddress();
			$customer_xml = $document->createElement('customer');
			$customer_xml->appendChild($document->createElement('customernumber', $customer->getSchrackWwsCustomerId()));
			$customer_xml->appendChild($document->createCdataElement('title', $account->getPrefix()));
			$customer_xml->appendChild($document->createCdataElement('name1', $account->getName1()));
			$customer_xml->appendChild($document->createCdataElement('name2', $account->getName2()));
			$customer_xml->appendChild($document->createCdataElement('name3', $account->getName3()));
			$customer_xml->appendChild($document->createCdataElement('street', $account->getStreet()));
			$customer_xml->appendChild($document->createCdataElement('zipcode', $account->getPostcode()));
			$customer_xml->appendChild($document->createCdataElement('city', $account->getCity()));
			$customer_xml->appendChild($document->createCdataElement('country', $account->getCountryId()));
			$customer_xml->appendChild($document->createElement('telephone', $addressBilling->getTelephone()));
			$customer_xml->appendChild($document->createElement('fax', $addressBilling->getFax()));
			$customer_xml->appendChild($document->createCdataElement('email', $account->getEmail()));
			$customer_xml->appendChild($document->createCdataElement('comment', $account->getDescription()));
			$customer_xml->appendChild($document->createCdataElement('ordercomment', $account->getInformation()));
			$customer_xml->appendChild($document->createCdataElement('leadadvisor', $account->getAdvisorPrincipalName()));
			$customer_xml->appendChild($document->createCdataElement('advisors', $account->getAdvisorsPrincipalNames()));
			$customer_xml->appendChild($document->createCdataElement('matchcode', $account->getMatchCode()));

			$contacts = $document->createElement('contacts');
			foreach ($contactCollection as $contact) {
				$contact_xml = $document->createElement('contact');
				$contact_xml->appendChild($document->createCdataElement('lastname', $contact->getLastname()));
				$contact_xml->appendChild($document->createCdataElement('firstname', $contact->getFirstname()));
				$contact_xml->appendChild($document->createCdataElement('salution', $genders[$contact->getGender()]['label']));
				$contact_xml->appendChild($document->createCdataElement('title', $contact->getPrefix()));
				$contact_xml->appendChild($document->createElement('telephone', $contact->getSchrackTelephone()));
				$contact_xml->appendChild($document->createElement('fax', $contact->getSchrackFax()));
				$contact_xml->appendChild($document->createElement('mobile', $contact->getSchrackMobilePhone()));
				$contact_xml->appendChild($document->createCdataElement('email', $contact->getEmailAddress()));
                $label = ((isset($crmRoles[$contact->getSchrackCrmRoleId()]) && isset($crmRoles[$contact->getSchrackCrmRoleId()]['label'])) 
                    ? $crmRoles[$contact->getSchrackCrmRoleId()]['label'] : null);
				$contact_xml->appendChild($document->createCdataElement('function', $label));
				$contact_xml->appendChild($document->createElement('maincontact', $contact->getSchrackMainContact()));
				$contact_xml->appendChild($document->createCdataElement('division', $contact->getSchrackDepartment()));
				$contacts->appendChild($contact_xml);
			}
			$customer_xml->appendChild($contacts);

			$customer_addresses = $document->createElement('customer_addresses');
			foreach ($addressCollection as $address) {
				$customer_address = $document->createElement('customer_address');
				$customer_address->appendChild($document->createElement( 'typ', $address->getSchrackType()));
				$customer_address->appendChild($document->createCdataElement('street', $address->getStreet(1)));
				$customer_address->appendChild($document->createElement('zipcode', $address->getPostcode()));
				$customer_address->appendChild($document->createCdataElement('city', $address->getCity()));
				$customer_address->appendChild($document->createCdataElement('country', $address->getCountryId()));
				$customer_address->appendChild($document->createCdataElement('name1', $address->getFirstname()));
				$customer_address->appendChild($document->createCdataElement('name2', $address->getMiddlename()));
				$customer_address->appendChild($document->createCdataElement('name3', $address->getLastname()));
				$customer_address->appendChild($document->createElement('telephone1', $address->getTelephone()));
				$customer_address->appendChild($document->createElement('telephone2', $address->getSchrackAdditionalPhone()));
				$customer_address->appendChild($document->createElement('fax', $address->getFax()));
				$customer_address->appendChild($document->createCdataElement('comment', $address->getSchrackComments()));
				$customer_addresses->appendChild($customer_address);
			}
			$customer_xml->appendChild($customer_addresses);
			$customers->appendChild($customer_xml);
		}
		$document->appendChild($customers);
		return $document;
	}

    private function _getTopLevelCategories ( $alwaysPromotions = false ) {
        $helper = Mage::helper('catalog/category');
        $categories = $helper->getStoreCategories();

        $res = array();
        foreach ($categories as $category) {
            $category->setProductCount(0);
			$stsID = $category->getSchrackGroupId();
			if ( $stsID === '_PROMOS_' ) {
				if ( $alwaysPromotions || Mage::getSingleton('customer/session')->isLoggedIn() ) {
					$res = array_reverse($res);
					$res[] = $category;
					$res = array_reverse($res);
				}
			} else {
                $res[] = $category;
            }
        }

        return $res;
    }
    
    private function _searchChildCategories ( $article_group_id ) {
        /*
        $parentCat = Mage::getModel('catalog/category')->load($article_group_id);
        $res = $parentCat->getChilderen();
        return $res;
         */
        $collection = Mage::getModel('catalog/category')
             ->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('parent_id',$article_group_id)
            ->addAttributeToFilter('is_active',true)
            ->load();

        $sql = " SELECT category_id, count(product_id) AS product_count FROM catalog_category_product ccp"
             . " JOIN catalog_category_entity cat ON ccp.category_id = cat.entity_id"
             . " WHERE cat.parent_id = :parent"
             . " GROUP BY ccp.category_id;";
        $resSql = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql,array('parent' => $article_group_id));
        $id2cnt = array();
        foreach ( $resSql as $row ) {
            $id2cnt[$row['category_id']] = $row['product_count'];
        }
        $res = array();
        foreach ( $collection as $category ) {
            $id = $category->getId();
            $cnt = isset($id2cnt[$id]) ? $id2cnt[$id] : 0;
            $category->setProductCount($cnt);
            $res[] = $category;
        }

        return $res;
    }
    

    private function _categories2Xml ( $categories ) {
		$document = Mage::getModel('mobile/document', '1.0', 'utf-8');
        $articleGroups = $document->createElement('articleGroups');
		$countAll = 0;
        foreach ( $categories as $category ) {
            /* @var $category Schracklive_SchrackCatalog_Model_Category */
			if ( $this->_showCategory($category) ) {
				$articleGroup = $this->_createCategoryNode($document, $category, 'articleGroup');
				$numberOfProducts = $document->createElement('numberOfProducts', $category->getProductCount());
				$articleGroup->appendChild($numberOfProducts);
				$articleGroups->appendChild($articleGroup);
				++$countAll;
			}
        }
        $articleGroups->setAttribute('totalNumberOfObjects', $countAll);
        $document->appendChild($articleGroups);
        return $document;
    }

	private function _showCategory ( $category ) {
		$stsID = $category->getSchrackGroupId();
		if ( strlen($stsID) > 5 && substr($stsID,-3) === '999' && $category->getProductCount() < 1 ) {
			return false;
		}
		return true;
	}

	/**
	 * @param $article_group_id
	 * @param $facets
	 * @param $document
	 * @param $products
	 * @param $customer
	 * @param $product_counter
	 * @param $solrFacets
	 * @param $solrSearch
	 */
	private function _fillProductListDocument($article_group_id, $facets, $document, $products, $customer, $product_counter, $solrFacets, $solrSearch) {
		$articlesRoot = $document->createElement('articlesRoot');
		$articles = $document->createElement('articles');
		$count = 0;
		$xmlProducts = array();
		Mage::helper('schrackcatalog/info')->preloadProductsInfo($products, $customer);
		foreach ($products as $product) {
			if (isset($xmlProducts[$product->getSKU()])) {
				continue;
			}
			$product = Mage::getModel('catalog/product')->load($product->getId()); //load product to have FULL product
			$xmlProducts[$product->getSKU()] = $product;
			$articles->appendChild($this->_createArticleNode($document, $product, $customer, 1, false));
			$count++;
		}
		$articles->setAttribute('totalNumberOfObjects', $product_counter);

		$articles->setAttribute('groupId', $article_group_id);
		$articlesRoot->appendChild($articles);

		$facets = $document->createElement('facets');
		if ( ! empty($solrFacets) && (count($solrFacets) > 1 || array_keys($solrFacets)[0] != 'Unknown' ) ) {
			$splitQueryFacets = $solrSearch->getSplitQueryFacets();
			foreach ($solrFacets as $facetKey => $solrFacet) {
				if ( count($solrFacet) < 2 ) {
					continue;
				}
				$facet = $document->createElement('facet');
				$facet->setAttribute('key', $facetKey);
				if ( $facetKey === 'sts_forsale' ) {
					$facet->setAttribute('label', $this->__('Discontinued'));
				} else {
					$facet->setAttribute('label', $solrSearch->getLabel($facetKey));
				}
				if (isset($splitQueryFacets[$facetKey]) && is_array($splitQueryFacets[$facetKey])) {
					$facet->setAttribute('selected', implode('|', $splitQueryFacets[$facetKey]));
				}
				$last = null;
				foreach ($solrFacet as $termKey => $facetCount) {
					$facetTerm = $document->createElement('facetTerm', $facetCount);
					$facetTerm->setAttribute('key', $termKey);
					if ( $facetKey === 'sts_forsale' ) {
						if ( $termKey === 'true' ) {
							$facetTerm->setAttribute('label', $this->__('Yes'));
						} else {
							$facetTerm->setAttribute('label', $this->__('No'));
						}
					} else if ( $termKey === 'Unknown' ) {
						$facetTerm->setAttribute('label', $this->__('Unknown'));
					} else {
						$facetTerm->setAttribute('label', $termKey);
					}
					if (isset($splitQueryFacets[$facetKey]) && is_array($splitQueryFacets[$facetKey]) && isset($splitQueryFacets[$facetKey][$termKey])) {
						$facetTerm->setAttribute('isSelected', 1);
					} else {
						$facetTerm->setAttribute('isSelected', 0);
					}
					if ( $termKey == 'Unknown' ) {
						$last = $facetTerm;
					} else {
						$facet->appendChild($facetTerm);
					}
				}
				if ( $last ) {
					$facet->appendChild($last);
				}
				$facets->appendChild($facet);
			}
			$articlesRoot->appendChild($facets);
		}
		$document->appendChild($articlesRoot);
	}
}
