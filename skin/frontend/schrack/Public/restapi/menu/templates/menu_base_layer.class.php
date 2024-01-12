<?php

class BaseLayer {

    private $_completeHtml = "";
    private $_count = 0;

    public function __construct()
    {
    }

    public function createTopLayer(
        $topLayerElement,
        $categoriesSection = null,
        $arrAttributes = null,
        $arrInnerHTMLNode = null) {
            $this->createNodes( $topLayerElement, $arrAttributes, $arrInnerHTMLNode);
            if ($categoriesSection) {
                $this->createCategoriesHTML($categoriesSection);
            }

            return $this->_completeHtml;
    }


    private function createNodes($element, $arrAttributes = null, $arrInnerHTMLNode = null) {
        $this->_completeHtml .= '<' . $element;

        if ($arrAttributes != null) {
            $attributeString = $this->setAttributes($arrAttributes);
            $this->_completeHtml .= $attributeString;
        }

        $this->_completeHtml .= '>';

        if ($arrInnerHTMLNode != null) {
            $this->setInnerHTML($arrInnerHTMLNode);
        }

        if ($element != 'img')  {
            $this->_completeHtml .= '</' . $element . '>';
        }
    }

    private function setAttributes($arrAttributes) {
        $attributeString= '';
        //Mage::log($arrAttributes, null, 'menubuilder.class.test.log');
        foreach ($arrAttributes as $attributeName => $attributeValue) {
            $attributeString .= ' ' . $attributeName . '="' . $attributeValue . '"';
        }

        return $attributeString;
    }

    private function setInnerHTML($arrInnerHTMLNode) {
        foreach($arrInnerHTMLNode as $nodeName => $subStructure) {
            if ($this->isElement($nodeName) == true) {
                $attributes = null;
                if (isset($subStructure['attributes'])) {
                    $attributes = $subStructure['attributes'];
                }
                $innerHTML = null;
                if (isset($subStructure['innerHtml'])) {
                    $innerHTML = $subStructure['innerHtml'];
                }
                $this->createNodes($nodeName, $attributes, $innerHTML);
            } else {
                // Text-Node -> $subStructure = string
                $this->_completeHtml .= $subStructure;
            }
        }
    }


    private function createCategoriesHTML($categoriesSection) {
        $this->_count++;
        if (is_array($categoriesSection) && !empty($categoriesSection)){
            foreach ($categoriesSection as $categoryKey => $categoryData) {
                if ($this->_count > 1) continue;
                //Mage::log($categoryData, null, 'menubuilder.class.test.log');
                // Check for layername-size (eg: layer_1, layer_2)
                // main top-navigation base-layer is beyond 8 chars:
                $arrLevels = explode('_', $categoryKey);
                $numberOfLevel = count($arrLevels) - 1;
                //Mage::log($categoryKey . ' -> ' . $numberOfLevel, null, 'menubuilder.class.test.log');
                if ($numberOfLevel == 1) {
                    $innerWrapperScrollingMarker = 1;
                    $this->_completeHtml .= '<div id="' . $categoryKey . '" class="nav-panel main-categories_panel" data-source="' . $categoryKey . '">';
                    foreach ($categoryData as $categoryItemIndex => $categoryItemData) {
                        Mage::log($categoryItemData, null, 'zeppelin.log');
                        //Mage::log($categoryItemData['div']['attributes'], null, 'menubuilder.class.test.log');
                        //Mage::log($categoryItemData['div']['innerHtml'], null, 'menubuilder.class.test.logg');
                        if ($innerWrapperScrollingMarker == 2) {
                            // Inject Element, after the header inside the panel (first element inside panel: header)
                            $injection = '<div class="injectedScrollWrapper innerScroll">';
                            $this->injectHtml($injection);
                        }
                        $attributes = null;
                        if (isset($categoryItemData['div']['attributes'])) {
                            $attributes = $categoryItemData['div']['attributes'];
                        }
                        $this->createNodes('div', $attributes, $categoryItemData['div']['innerHtml']);
                        $innerWrapperScrollingMarker++;
                    }
                    $this->_completeHtml .= '</div>'; // finish scroll wrapper
                    $this->_completeHtml .= '</div>'; // finish main panel
                }

                if ($numberOfLevel == 2) {
                    $innerWrapperScrollingMarker = 1;
                    $this->_completeHtml .= '<div class="nav-panel sub-categories_panel" data-source="' . $categoryKey . '">';
                    foreach ($categoryData as $subCategoryItemIndex => $subCategoryItemData) {
                        //Mage::log($subCategoryItemData['div']['attributes'], null, 'menubuilder.class.test.log');
                        //Mage::log($subCategoryItemData['div']['innerHtml'], null, 'menubuilder.class.test.log');
                        if ($innerWrapperScrollingMarker == 2) {
                            // Inject Element, after the header inside the panel (first element inside panel: header)
                            $injection = '<div class="injectedScrollWrapper subInnerScroll">';
                            $this->injectHtml($injection);
                        }
                        $attributes = null;
                        if (isset($subCategoryItemData['div']['attributes'])) {
                            $attributes = $subCategoryItemData['div']['attributes'];
                        }
                        $this->createNodes('div', $attributes, $subCategoryItemData['div']['innerHtml']);
                        $innerWrapperScrollingMarker++;
                    }
                    $this->_completeHtml .= '</div>'; // finish scroll wrapper
                    $this->_completeHtml .= '</div>'; // finish main panel
                }
            }
        }
    }


    public function injectHtml($injection) {
        $this->_completeHtml .= $injection;
    }


    public function resetToplayer() {
        $this->_completeHtml = '';
    }


    private function isElement($node) {
        if ($node == 'img') return true;
        if ($node == 'div') return true;
        if ($node == 'section') return true;
        if ($node == 'nav') return true;
        if ($node == 'span') return true;
        if ($node == 'a') return true;

        if ($node == 'text') return false;
    }

}
