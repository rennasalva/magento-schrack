<?php

require_once 'templates/menu_base_layer.class.php';


class Menubuilder extends Schracklive_Shell {

    private $_readConnection;
    private $_writeConnection;
    private $_storeId;
    private $_baseURL;
    private $_versionTimestamp;
    private $_translator;
    private $_thumbResolution = 'menu_pics'; // 65x65


    public function run () {}


    public function __construct () {
        parent::__construct();

        $this->_storeId         = Mage::app()->getStore()->getStoreId();
        $this->_readConnection  = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_translator = Mage::getModel('core/translate')
                                ->setLocale(Mage::getStoreConfig('general/locale/code', Mage::getStoreConfig('schrack/shop/store')))
                                ->init('frontend', true);

        $query = "SELECT value FROM core_config_data WHERE path LIKE 'web/secure/base_url' AND scope LIKE 'default'";
        $this->_baseURL = $this->_readConnection->fetchOne($query);
    }


    public function setVersion($versionTimestamp) {
        $this->_versionTimestamp = $versionTimestamp;
    }


    public function loadPreviousTypoMenu() {
        $typoResponseAsArray = array();

        $query = "SELECT content FROM shop_navigation WHERE source = 'partial_nav' ORDER BY created_at DESC LIMIT 1";
        $result = $this->_readConnection->fetchAll( $query );

        if (count($result)) {
            foreach ($result as $row) {
                $originalTypoJsonResponse = base64_decode($row['content']);
            }
            $typoResponseAsArray = json_decode($originalTypoJsonResponse);
        }
        return $typoResponseAsArray;
    }


    public function persistMenuStructure(array $typoCategoryNavigationStructure, $versionTimstamp) {
        $shopCategoryNavigationStructure = $this->getShopCategoryNavigationStructure();
        $arrShopCategoryNavigationStructure =
            array('top-navigation' => array(
                                        'top_nav_products' => array(
                                                'name' => $this->_translator->translate( array('products-menu-navigation') ),
                                                'url' => null,
                                                'thumb_url' => null,
                                                'children' => $shopCategoryNavigationStructure)));

        //Mage::log($typoResponseAsArray, null, 'menubuilder.structure.log');

        if (isset($typoCategoryNavigationStructure['top-navigation'])
             && !empty($typoCategoryNavigationStructure['top-navigation'])) {
             $arrShopNav = $arrShopCategoryNavigationStructure['top-navigation'];
             $arrTypoNav = $typoCategoryNavigationStructure['top-navigation'];
             $arrShopTypoCategoryNavigationStructure['top-navigation'] = array_merge($arrShopNav, $arrTypoNav);
            //Mage::log('top-navigation structure found and merged', null, 'menubuilder.structure.log');
            //Mage::log($arrShopTypoCategoryNavigationStructure, null, 'menubuilder.structure.log');
            $completeDomMenuStructure = $this->createDomStructure($arrShopTypoCategoryNavigationStructure);
        } else {
            //Mage::log('top-navigation structure NOT found', null, 'menubuilder.structure.log');
            //Mage::log($arrShopCategoryNavigationStructure, null, 'menubuilder.structure.log');
            $completeDomMenuStructure = $this->createDomStructure($arrShopCategoryNavigationStructure);
        }

        //Mage::log($completeDomMenuStructure, null, 'menubuilder.test.log');
        $completeMenuHTML = $this->renderDesktopMegaMenu($completeDomMenuStructure);
        $numberOfMainMenuDropdowns = substr_count ($completeMenuHTML, 'top_navigation_main');
        switch ($numberOfMainMenuDropdowns) {
            case 4:
                $completeMenuHTML = str_replace('top_navigation_main','top_navigation_main top_navigation_main_25', $completeMenuHTML);
                break;
            case 5:
                $completeMenuHTML = str_replace('top_navigation_main','top_navigation_main top_navigation_main_20', $completeMenuHTML);
                break;
            case 6:
                $completeMenuHTML = str_replace('top_navigation_main','top_navigation_main top_navigation_main_16', $completeMenuHTML);
                break;
            case 7:
                $completeMenuHTML = str_replace('top_navigation_main','top_navigation_main top_navigation_main_14', $completeMenuHTML);
                break;
        }
        $this->persistCompleteMenuAsBase64( $completeMenuHTML, $versionTimstamp );

        if ($completeMenuHTML) {
            return true;
        } else {
            return false;
        }
    }


    private function createMegaMenuRecord ( $name, $addText, $url, $thumb_url = null, $schrackID = null ) {
        $rec = new stdClass();
        $rec->name = $name;
        $rec->addText = $addText;
        $rec->url = $url;
        $rec->thumb_url = $thumb_url;
        $rec->schrackCategoryID = $schrackID;
        $rec->schrackID = $schrackID;
        if ( is_string($rec->addText) && $rec->addText > '' ) { // limit sizes of name and addText only when we have an addText
            $rec->name = $this->limitTextSize($rec->name,30);
            $rec->addText = $this->limitTextSize($rec->addText,90);
        }
        return $rec;
    }

    private function limitTextSize ( $text, $maxSize ) {
        if ( strlen($text) > $maxSize  ) {
            $splitChars = " ,-_\n\r";
            // first try removing text in brackets:
            if ( ($p = strpos($text,'(')) !== false && $p <= $maxSize ) {
                return rtrim(substr($text, 0, $p),$splitChars);
            }
            // then find try find last possible split character
            for ( $i = $maxSize; $i > 0; --$i ) {
                if ( strpos($splitChars,$text[$i]) !== false ) {
                    return rtrim(substr($text, 0, $i),$splitChars);
                }
            }
        }
        return $text;
    }


    // This is the main work:
    private function renderDesktopMegaMenu($arrCategoryDomStructure) {
        $arrTopNavigation     = $arrCategoryDomStructure['arrTopNavigation'];
        $arrCategoriesSection = $arrCategoryDomStructure['arrCategoriesSection'];

        if (strtoupper(Mage::getStoreConfig('schrack/general/country')) == 'SI') {
            $logo = 'schrack-SI-logo-30let.png';
        } else {
            $logo = 'schrack-logo.png';
        }

        $completeNavigationHTML = '';
        $navigationEnvelope  = '<div id="navigationEnvelope" class="navigation_envelope">';
        $navigationEnvelope .= '<div id="closeMobileMainLayer" class="closeMobileMainLayer">&times;</div>';
        $imgSource = $this->_baseURL . 'skin/frontend/schrack/default/schrackdesign/Public/Images/' . $logo;
        $navigationEnvelope .= '<img id="imageMobileMainLayer" class="imageMobileMainLayer" src="' . $imgSource . '">';
        $navigationEnvelopeClose  = '<div id="quick_add_button_menu" class="top_navigation_main"'; // btn btn-quickAdd"
        $navigationEnvelopeClose .= ' data-toggle="modal" data-target="#quickaddpopup" data-target="">';
        $navigationEnvelopeClose .=     '<div class="backfiller_right"></div>';
        $navigationEnvelopeClose .=     '<nav class="top_navigation_item top_navigation_item_alternate top_navigation_item_quickadd">';
        $navigationEnvelopeClose .=         '<span class="top_navigation_item_text">';
        $navigationEnvelopeClose .= $this->_translator->translate(array('Quick-Add'));
        $navigationEnvelopeClose .=         '</span>';
        $navigationEnvelopeClose .=     '</nav>';
        $navigationEnvelopeClose .= '</div>';
        $navigationEnvelopeClose .= '</div>';

        $baseLayerHtmlShop = '';
        $topNavigationShop = new BaseLayer();
        foreach($arrTopNavigation as $index => $arrTopNavigationSections) {
            $runLevel = 1;
            foreach ($arrTopNavigationSections as $navigationSection => $navigationContent) {
                $attributes = null;
                if (isset($navigationContent['attributes'])) {
                    $attributes = $navigationContent['attributes'];
                }
                $innerHtml = null;
                if (isset($navigationContent['innerHtml'])) {
                    $innerHtml = $navigationContent['innerHtml'];
                }
                if ($runLevel == 1) {
                    $arrCategoriesSectionData = $arrCategoriesSection;
                } else {
                    $arrCategoriesSectionData = null;
                }

                $baseLayerHtmlShop .= $topNavigationShop->createTopLayer(
                    $navigationSection, $arrCategoriesSectionData, $attributes, $innerHtml
                );
                $topNavigationShop->resetToplayer();
                $runLevel++;
            }
        }

        // TODO: TYPO:
        // $topNavigationShop->createTopLayer('know-how', $navigationSection, $attributes, $innerHtml);
        // $topNavigationShop->createTopLayer('tools-and-services', $navigationSection, $attributes, $innerHtml);
        // $topNavigationShop->createTopLayer('events', $navigationSection, $attributes, $innerHtml);
        // $topNavigationShop->createTopLayer('about-us', $navigationSection, $attributes, $innerHtml);

        $completeNavigationHTML = $navigationEnvelope . $baseLayerHtmlShop . $navigationEnvelopeClose;
        //Mage::log($completeNavigationHTML, null, 'menubuilder.test.log');

        return $completeNavigationHTML;
    }


    public function persistJsonFromTypo(string $typoResponseAsJSON, $versionTimstamp) {
        //Mage::log($typoResponseAsJSON, null, 'menubuilder.test.log');
        $query = "INSERT INTO shop_navigation SET ";
        $query .= " type = 'partial_nav',";
        $query .= " source = 'typo',";
        $query .= " content = '" . base64_encode($typoResponseAsJSON) . "',";
        $query .= " created_at = '" . $versionTimstamp . "'";
        $this->_writeConnection->query( $query );

        // Deleting deprecated data (old recordsets -> old menu data) - CLEANUP:
        $query = "SELECT * FROM shop_navigation WHERE type LIKE 'partial_nav' AND source LIKE 'typo' ORDER BY created_at DESC";
        $dbRes = $this->_readConnection->fetchAll( $query );

        $index = 1;
        foreach ($dbRes as $row) {
            if($index > 2) {
                $deleteQuery = "DELETE FROM shop_navigation WHERE type LIKE 'partial_nav' AND source LIKE 'typo' AND id = " . $row['id'];
                $this->_writeConnection->query( $deleteQuery );
            }
            $index++;
        }

        return true;
    }


    private function createDomStructure($arrCategoryStructure) {
        //Mage::log($arrCategoryStructure, null, 'menubuilder.test.log');

        $arrDomStructureOfCompleteMenu                         = array();
        $arrDomStructureOfCompleteMenu['arrTopNavigation']     = array();
        $arrDomStructureOfCompleteMenu['arrCategoriesSection'] = array();
        $topNav                                                = array();
        $completeCatNav                                        = array();
        $mainCats                                              = array();
        $completeSubCatNav                                     = array();
        $backStr                                               = $this->_translator->translate( array('Back') ) . ' | ';
        $setFakeImages                                         = false;

        // Get valid values from the database:
        $query = "SELECT source, content FROM shop_navigation WHERE type LIKE 'top-nav' AND active = 1";
        $result = $this->_readConnection->fetchAll( $query );

        if ($result) {
            foreach ($result as $row) {
                $validSections[] = $row['content'];
                $sectionMap[$row['content']] = $row['source'];
            }
        }

        // Testing:
        //unset($arrCategoryStructure['top-navigation']['top_nav_products']);
        //unset($arrCategoryStructure['top-navigation']['top_nav_1_typo']);
        //unset($arrCategoryStructure['top-navigation']['top_nav_2_typo']);
        //unset($arrCategoryStructure['top-navigation']['top_nav_3_typo']);

        //Mage::log($arrCategoryStructure, null, 'menubuilder.test.log');

        if (is_array($arrCategoryStructure) && !empty($arrCategoryStructure)) {
            foreach($arrCategoryStructure as $topNavigationSection => $singleNavigationStructure) {
                //Mage::log($topNavigationSection, null, 'menubuilder.test.log');
                //Mage::log($singleNavigationStructure, null, 'menubuilder.test.log');
                //Mage::log('Top-Navigation Section: ' . key($singleNavigationStructure), null, 'menubuilder.test.log');

                $index = 1;
                foreach($singleNavigationStructure as $section => $objPayload) {
                    //Mage::log($section, null, 'menubuilder.test.log');
                    //Mage::log($sectionMap, null, 'menubuilder.test.log');
                    if ($section && in_array($section, $validSections)) {
                        //Mage::log('valid section:' . $section, null, 'menubuilder.test.log');
                        // Valid: do nothing!
                        $sectionType = $sectionMap[$section];
                        //Mage::log('section type:' . $sectionType, null, 'menubuilder.test.log');
                        // $sectionType: values: 'shop' or 'typo'
                    } else {
                        $errMsg = 'KEY = "' . $section . '" is not allowed navigation section';
                        Mage::log($errMsg, null, 'menubuilder.error.log');
                        continue;
                    }

                    //Mage::log($sectionType, null, 'menubuilder.test.log');
                    // TODO: remove the following line (for testing insert it):
                    //continue;

                    //Mage::log($section, null, 'menubuilder.test.log');
                    //Mage::log($objPayload, null, 'menubuilder.test.log');
                    $singleNavigationPayload = (array) $objPayload;
                    $payloadCategoryStructure = $singleNavigationPayload['children'];

                    $dirtyFlag = 0;
                    $singleTopNavSpecialCaseUrl = '';

                    if (count($payloadCategoryStructure) == 1) {
                        foreach ($payloadCategoryStructure as $subCategory => $fields) {
                            foreach ($fields as $fieldItemKey => $fieldItemValue) {
                                //Mage::log($fieldItemKey, null, 'menu_cats.log');
                                if ($fieldItemKey == 'url') {
                                    $singleTopNavSpecialCaseUrl = $fieldItemValue;
                                    $dirtyFlag = 1;
                                }
                            }
                        }
                    }

                    // Create Top-Level navigation:
                    $attr = array(
                        'id' => $section,
                        'class' => 'top_navigation_main',
                        'data-target' => 'layer_' . $index
                    );
                    if ( $index == 1 ) {
                        $attr['class'] = 'top_navigation_first top_navigation_main';
                    }

                    if ($dirtyFlag == 1 && $singleTopNavSpecialCaseUrl != '') {
                        $attr['class'] = $attr['class'] . ' single_top_nav_special_case';
                        $attr['data-directurl'] = $singleTopNavSpecialCaseUrl;
                        unset($attr['data-target']);
                    }

                    if ($sectionType == 'typo') {
                        $attr['class'] = $attr['class'] . ' typo_top_nav';
                    }

                    $topNav[$index]['div']['attributes'] = $attr;

                    if ( $index == 1 ) {
                        $innerText  = '<span class="top_navigation_item_text">';
                        $innerText .=  $singleNavigationPayload['name'];
                        $innerText .=  '</span>';
                        $innerHtml = array(
                            'attributes' => array(
                                'class' => 'top_navigation_item top_navigation_item_first'
                            ),
                            'innerHtml' => array(
                                'text' => $innerText
                            )
                        );
                        $topNav[$index]['div']['innerHtml']['div']['attributes'] = array('class' => 'backfiller_left');
                    } else {
                        $innerText  = '<span class="top_navigation_item_text">';
                        $innerText .=  $singleNavigationPayload['name'];
                        $innerText .=  '</span>';
                        if ($sectionType == 'typo') {
                            $class = 'top_navigation_item top_navigation_item_alternate typo_type';
                        } else {
                            $class = 'top_navigation_item top_navigation_item_alternate';
                        }
                        $innerHtml = array(
                            'attributes' => array(
                                'class' => $class
                            ),
                            'innerHtml' => array(
                                'text' => $innerText
                            )
                        );
                    }
                    $topNav[$index]['div']['innerHtml']['nav'] = $innerHtml;
                    // Mage::log($topNav, null, 'menubuilder.test.log');

                    // Create Main Panel:
                    $subIndexInt = 1;
                    $layerName = 'layer_' . $index;

                    $headerTemplate = array(
                        "div" => array(
                            'attributes' => array(
                                'class' => 'main_navigation_header',
                            ),
                            'innerHtml' => array(
                                'section' => array(
                                    'attributes' => array(
                                        'class' => 'main_navigation_header_frame'
                                    ),
                                    'innerHtml' => array(
                                        'nav' => array(
                                            'attributes' => array(
                                                'class' => 'main_nav_mobile_back nav_back_to_top_cats'
                                            ),
                                            'innerHtml' => array(
                                                'text' => '&larr; ' . $backStr
                                            )
                                        ),
                                        'span' => array(
                                            'attributes' => array(
                                                'class' => 'main_nav_description nav_back_to_top_cats'
                                            ),
                                            'innerHtml' => array(
                                                'text' => $singleNavigationPayload['name']
                                            )
                                        ),
                                    )
                                ),
                                'div' => array(
                                    'attributes' => array(
                                        'class' => 'main_nav_closing'
                                    ),
                                    'innerHtml' => array(
                                        'text' => '&times;'
                                    )
                                )
                            )
                        )
                    );
                    if ($dirtyFlag == 0) {
                        $mainCats['item_0']['div']['attributes'] = array('class' => 'main_navigation_header_container');
                        $mainCats['item_0']['div']['innerHtml'] = $headerTemplate;
                    }

                    foreach ($payloadCategoryStructure as $subIndex => $objMainCatPaylod) {
                        if ($dirtyFlag == 1) continue;
                        //Mage::log($objMainCatPaylod, null, 'menubuilder.test.log');
                        $mainCatPayload = (array) $objMainCatPaylod;
                        //Mage::log($mainCatPayload, null, 'menubuilder.test.log');
                        $mainCatItem = 'item_' . $subIndexInt;
                        $attr = array(
                            'class' => 'maincat_itemclass'
                        );
                        $mainCats[$mainCatItem]['div']['attributes'] = $attr;
                        if (isset($mainCatPayload['url']) && $mainCatPayload['url'] != '') {
                            $url = $mainCatPayload['url'];
                        } else {
                            $url = '';
                        }

                        if (isset($mainCatPayload['schrackCategoryID'])) {
                            $mainCatSchrackCategoryID = $mainCatPayload['schrackCategoryID'];
                        } else {
                            $mainCatSchrackCategoryID = '';
                        }
                        $mainCatPayloadThumbUrl = $mainCatPayload['thumb_url'];
                        ////////////////////////////////////////////////////////////////////
                        /////          Small hack for testing image dimensions:       //////
                        ////////////////////////////////////////////////////////////////////
                        if ($setFakeImages == true) {                                    ///
                            if ($sectionType == 'shop') {                                ///
                                // Shop-Image-Dimension (65x65px)                        ///
                                $mainCatPayloadThumbUrl = 'https://fakeimg.pl/65x65/';   ///
                            } else {                                                     ///
                                // TYPO-Image-Dimension (120x70px)                       ///
                                $mainCatPayloadThumbUrl = 'https://fakeimg.pl/120x70/';  ///
                            }                                                            ///
                        }                                                                ///
                        ////////////////////////////////////////////////////////////////////
                        $innerHtml = array(
                            'div' => array(
                                'attributes' => array(
                                    'class' => 'main_catInner main_catInner_' . $sectionType,
                                    'data-tracking-cat' => $mainCatSchrackCategoryID,
                                    'data-target-url' => $url,
                                    'data-target-subpanel' => 'layer_' . $index . '_' . $subIndexInt,
                                    'data-source' => 'layer_' . $index
                                ),
                                'innerHtml' => array(
                                    'img' => array(
                                        'attributes' => array(
                                            'src' => $mainCatPayloadThumbUrl,
                                            'class' => 'main_catInner_img_' . $sectionType
                                            //'src' => 'https://image.schrack.com/thumb66x66/f_bc034103--.jpg'
                                        )
                                    ),
                                    'div' => array(
                                        'attributes' => array(
                                            'class' => 'maincat_itemText_' . $sectionType
                                        ),
                                        'innerHtml' => array(
                                            'div' => array(
                                                'innerHtml' => array(
                                                    'div' => array(
                                                        'innerHtml' => array(
                                                            'text' => $mainCatPayload['name']
                                                        )
                                                    ),
                                                    'nav' => array(
                                                        'attributes' => array(
                                                            'class' => 'maincat_itemText_addText'
                                                        ),
                                                        'innerHtml' => array(
                                                            'text' => $mainCatPayload['addText']
                                                        )
                                                    )
                                                )
                                            ),
                                        ),
                                    ),
                                )
                            )
                        );

                        $mainCats[$mainCatItem]['div']['innerHtml'] = $innerHtml;
                        //Mage::log($innerHtml, null, 'menubuilder.test.log');
                        //Mage::log($mainCats, null, 'menubuilder.test.log');
                        //Mage::log($mainCatPayload, null, 'menubuilder.test.log');
                        unset($innerHtml);
                        unset($headerTemplate);

                        if (isset($mainCatPayload['children'])) {
                            // Building subCats (which will have direct links as a data attribute)
                            // The links itself are not anchors!!
                            $backProducts = $this->_translator->translate( array('products') );
                            $headerTemplateSubCats = array(
                                "div" => array(
                                    'attributes' => array(
                                        'class' => 'sub_navigation_header',
                                    ),
                                    'innerHtml' => array(
                                        'div' => array(
                                            'attributes' => array(
                                                'class' => 'sub_navigation_header_frame'
                                            ),
                                            'innerHtml' => array(
                                                'span' => array(
                                                    'attributes' => array(
                                                        'class' => 'sub_nav_description subNav_back',
                                                        'data-target-show' => 'layer_' . $index,
                                                        'data-target-hide' => 'layer_' . $index . '_' . $subIndexInt,
                                                    ),
                                                    'innerHtml' => array(
                                                        'text' => '&larr; ' . $backStr . $mainCatPayload['name']
                                                        //'text' => '&larr; ' . $backStr . $backProducts
                                                    )
                                                ),
                                                'div' => array(
                                                    'attributes' => array(
                                                        'class' => 'sub_nav_closing'
                                                    ),
                                                    'innerHtml' => array(
                                                        'text' => '&times;'
                                                    )
                                                )
                                            )
                                        )
                                    )
                                )
                            );
                            $subCats['item_0']['div']['attributes'] = array('class' => 'sub_navigation_header_container');
                            $subCats['item_0']['div']['innerHtml'] = $headerTemplateSubCats;

                            $subLayerPanelName = 'layer_' . $index . '_' . $subIndexInt;
                            $subCatItemIndex = 1;

                            foreach ($mainCatPayload['children'] as $subCatIndex => $objMainCatPaylod) {
                                $subCatItemPaylod = (array) $objMainCatPaylod;
                                $subCatItemName = 'item_' . $subCatItemIndex;
                                $attr = array(
                                    'class' => 'subcat_itemclass',
                                    'data-source' => 'layer_' . $index . '_' . $subIndexInt
                                );
                                $subCats[$subCatItemName]['div']['attributes'] = $attr;

                                if (isset($subCatItemPaylod['url']) && $subCatItemPaylod['url'] != '') {
                                    $url = $subCatItemPaylod['url'];
                                } else {
                                    $url = '';
                                }

                                if (isset($subCatItemPaylod['schrackCategoryID'])) {
                                    $subCatSchrackCategoryID = $subCatItemPaylod['schrackCategoryID'];
                                } else {
                                    $subCatSchrackCategoryID = '';
                                }

                                // Check for Category "Abverkauf"
                                $cssClassAbverkauf = '';
                                $pathAbverkaufPNG  = '';
                                $imgClassAbverkauf = '';
                                if (stristr($url, 'catalogsearch')) {
                                    $cssClassAbverkauf = ' redAbverkauf';
                                    $imgClassAbverkauf = 'imgAbverkauf ';
                                    $pathAbverkaufPNG .= $this->_baseURL . 'skin/frontend/schrack/default/schrackdesign';
                                    $pathAbverkaufPNG .= '/Public/Images/rwd/Sale_Stopwatch.png';
                                    $subCatItemPaylod['thumb_url'] = $pathAbverkaufPNG;
                                }

                                $subCatItemPaylodThumbUrl = $subCatItemPaylod['thumb_url'];
                                ////////////////////////////////////////////////////////////////////
                                /////          Small hack for testing image dimensions:       //////
                                ////////////////////////////////////////////////////////////////////
                                if ($setFakeImages == true) {                                    ///
                                    if ($sectionType == 'shop') {                                ///
                                        // Shop-Image-Dimension (65x65px)                        ///
                                        $subCatItemPaylodThumbUrl = 'https://fakeimg.pl/65x65/';   ///
                                    }                                                      ///
                                }                                                                ///
                                ////////////////////////////////////////////////////////////////////

                                $innerHtml = array(
                                    'div' => array(
                                        'attributes' => array(
                                            'class' => 'sub_catInner',
                                            'data-tracking-cat' => $subCatSchrackCategoryID,
                                            'data-target-url' => $url
                                        ),
                                        'innerHtml' => array(
                                            'img' => array(
                                                'attributes' => array(
                                                    'src' => $subCatItemPaylodThumbUrl,
                                                    'class' => $imgClassAbverkauf . 'sub_catInner_img_' . $sectionType
                                                    //'src' => 'https://image.schrack.com/thumb66x66/f_bc034103--.jpg'
                                                )
                                            ),
                                            'div' => array(
                                                'attributes' => array(
                                                    'class' => 'subcat_itemText' . $cssClassAbverkauf
                                                ),
                                                'innerHtml' => array(
                                                    'div' => array(
                                                        'innerHtml' => array(
                                                            'div' => array(
                                                                'innerHtml' => array(
                                                                    'text' => $subCatItemPaylod['name']
                                                                )
                                                            ),
                                                            'nav' => array(
                                                                'attributes' => array(
                                                                    'class' => 'maincat_itemText_addText'
                                                                ),
                                                                'innerHtml' => array(
                                                                    'text' => $subCatItemPaylod['addText']
                                                                )
                                                            )
                                                        )
                                                    ),
                                                ),
                                            ),
                                        )
                                    )
                                );

                                $subCats[$subCatItemName]['div']['innerHtml'] = $innerHtml;
                                $innerHtml = array();
                                $subCatItemIndex++;
                                //Mage::log($subCats, null, 'menubuilder.test.log');
                            }
                            $completeSubCatNav[$subLayerPanelName] = $subCats;
                            // Adding sub layer to all-layers-array:
                            $completeCatNav[$subLayerPanelName] = $subCats;
                            $subCats = array(); // reset the sublayer data
                            $subIndexInt++;
                        } else {
                            $subIndexInt++;
                        }
                    }
                    //Mage::log($mainCats, null, 'menubuilder.test.log');
                    //Mage::log($completeSubCatNav, null, 'menubuilder.test.log');

                    $completeCatNav[$layerName] = $mainCats;
                    $mainCats = array(); // reset the layer data
                    $subCats = array();
                    $index++;
                }
            }
        }
        //Mage::log($topNav, null, 'menubuilder.test.log');
        $arrDomStructureOfCompleteMenu['arrTopNavigation']     = $topNav;
        //Mage::log($completeCatNav, null, 'menubuilder.test.log');
        $arrDomStructureOfCompleteMenu['arrCategoriesSection'] = $completeCatNav;
        //Mage::log($arrDomStructureOfCompleteMenu, null, 'menubuilder.test.log');
        return $arrDomStructureOfCompleteMenu;
    }


    private function getShopCategoryNavigationStructure() {
        $res       = array();
        $id2rec    = array();
        $baseUrl = Mage::getStoreConfig('web/secure/base_url', Mage::getStoreConfig('schrack/shop/store'));


        echo $baseUrl; die();

        $query = " SELECT prod.sku AS article_number, cat.*, attrName.value AS name, attrUrlPath.value AS url, attrThumbUrlPath.value AS thumb_url, attrID.value AS schrack_id, attrAddText.value AS add_text, count(prod.entity_id) AS products_on_sale FROM catalog_category_entity AS cat"
            . " LEFT JOIN catalog_category_entity_varchar attrName    ON (cat.entity_id = attrName.entity_id    AND attrName.attribute_id    IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'name'))"
            . " LEFT JOIN catalog_category_entity_varchar attrUrlPath ON (cat.entity_id = attrUrlPath.entity_id AND attrUrlPath.attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'url_path'))"
            . " LEFT JOIN catalog_category_entity_varchar attrThumbUrlPath ON (cat.entity_id = attrThumbUrlPath.entity_id AND attrThumbUrlPath.attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_image_url'))"
            . " LEFT JOIN catalog_category_entity_varchar attrID      ON (cat.entity_id = attrID.entity_id      AND attrID.attribute_id      IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id'))"
            . " LEFT JOIN catalog_category_entity_varchar attrAddText ON (cat.entity_id = attrAddText.entity_id AND attrAddText.attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_add_text'))"
            . " JOIN catalog_category_product_index ndx ON cat.entity_id = ndx.category_id"
            . " LEFT JOIN catalog_product_entity prod ON prod.entity_id = ndx.product_id AND prod.schrack_sts_forsale = 1"
            . " WHERE attrUrlPath.store_id = {$this->_storeId} AND level >= 2 AND level <= 3 AND attrName.value <> 'PROMOTIONS_TOP' AND attrName.value <> 'Discontinued'"
            . " GROUP BY cat.entity_id"
            . " ORDER BY level, position;";

        //Mage::log(base64_encode($query), null, 'menubuilder.test.log');

        $dbRes = $this->_readConnection->fetchAll( $query );

        if (is_array($dbRes) && !empty($dbRes)) {
            foreach ($dbRes as $row) {
                $totalUrl = $baseUrl . $row['url'];

                if ($row['thumb_url']) {
                    $row['thumb_url'] = str_replace('foto', $this->_thumbResolution, $row['thumb_url']);
                    $totalThumbUrl = Mage::getStoreConfig('schrack/general/imageserver') . $row['thumb_url'];
                } else {
                    $totalThumbUrl = '';
                }
                $rec = $this->createMegaMenuRecord( $row['name'], $row['add_text'], $totalUrl, $totalThumbUrl, $row['schrack_id']);

                $id2rec[intval( $row['entity_id'] )] = $rec;
                $rec->parentID = intval( $row['parent_id'] );
                $rec->hasSale = intval( $row['products_on_sale'] ) > 0;
                if ($rec->parentID > 2) {
                    $parent = $id2rec[$rec->parentID];
                    if ( !isset( $parent->children ) ) {
                        $parent->children = array();
                    }
                    $parent->children[] = $rec;
                }
            }

            foreach ( $id2rec as $id => $rec ) {
                $parentID = $rec->parentID;
                $schrackID = $rec->schrackID;
                $hasSale = $rec->hasSale;
                unset($rec->parentID);
                unset($rec->schrackID);
                //unset($rec->schrackCategoryID);

                unset($rec->hasSale);
                if ( $parentID === 2 ) {
                    // No link arithmethik for the top-navigation in shop:
                    unset($rec->url);
                    if ( ! Schracklive_SchrackCatalog_Model_Category::isCatalogCategoryID($schrackID) ) {
                        if ( $hasSale ) {
                            // create sales link:
                            $str  = 'catalogsearch/result?cat=' . $id . '&fq=%7B%22facets%22%3A%7B%7D%2C%22';
                            $str .= 'general_filters%22%3A%7B%22sale%22%3A1%7D%7D';
                            $url = $baseUrl . $str;
                            $discontinued = $this->_translator->translate( array('Discontinued') );
                            // Don't generate Abverkaufs-Link (in case of reactivation, just uncomment the following 2 lines):
                            $salesRec = $this->createMegaMenuRecord($discontinued, null, $url);
                            $rec->children[] = $salesRec;
                        }
                        // create all cats link:
                        //$allSubsRec = $this->createMegaMenuRecord( $this->_translator->translate( array('All sub-categories') ) ,$rec->url);
                        //array_unshift($rec->children, $allSubsRec);
                    }
                    // add to result:
                    $res[] = $rec;
                }
            }
        } else {
            Mage::helper('schrack/email')->sendDeveloperMail('ATTENTION: please do reindexing for','Reindexing has to be done again');
        }

        return $res;
    }


    private function persistCompleteMenuAsBase64(string $completeMenuHTML, $versionTimstamp) {
        //Mage::log($completeMenuHTML, null, 'menu_cats.log', false, false);
        // Create new base64-encoded recordset fpr menu-HTML:
        $query  = "INSERT INTO shop_navigation SET";
        $query .= " type = 'main_nav',";
        $query .= " content = '" . base64_encode($completeMenuHTML) . "',";
        $query .= " created_at = '" . $versionTimstamp . "'";
        $this->_writeConnection->query( $query );

        // Deleting deprecated data (old recordsets -> old menu data) - CLEANUP:
        $query = "SELECT * FROM shop_navigation WHERE type LIKE 'main_nav' ORDER BY created_at DESC";
        $dbRes = $this->_readConnection->fetchAll( $query );

        $index = 1;
        foreach ($dbRes as $row) {
            if($index > 2) {
                $deleteQuery = "DELETE FROM shop_navigation WHERE type LIKE 'main_nav' AND id = " . $row['id'];
                $this->_writeConnection->query( $deleteQuery );
            }
            $index++;
        }

        // After creating menu, writing also timestamp update (Shop-Update):
        $latestRefreshDatetime = date('Y-m-d H:i:s');

        $queryString  = "UPDATE core_config_data SET value = '" . $latestRefreshDatetime . "'";
        $queryString .= " WHERE path LIKE 'schrack/performance/mega_menu_latest_refresh_datetime'";

        $this->_writeConnection->query($queryString);

        // Also Updating TYPO:
        $url = Mage::getStoreConfig('schrack/typo3/typo3url') . Mage::getStoreConfig('schrack/typo3/clearmegamenuurl');
        $url .= '&menuTs=';
        $url .= strtotime($latestRefreshDatetime);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $response = curl_exec($ch);
        curl_close($ch);
        if ( ! $response ) {
            Mage::log('!!! ERROR: typo call failed (Updating Menu Timestamp) !!!', null, 'menubuilder.error.log');
        }
    }
}
