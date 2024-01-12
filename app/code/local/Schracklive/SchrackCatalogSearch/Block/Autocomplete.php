<?php

/**
 * Autocomplete queries list
 */
class Schracklive_SchrackCatalogSearch_Block_Autocomplete extends Mage_CatalogSearch_Block_Autocomplete {

	// sh@plan2.net: Copied from parent
	protected function _toHtml() {
		$html = '';

		if (!$this->_beforeToHtml()) {
			return $html;
		}

		$suggestData = $this->getSuggestData();
		if (!($count = count($suggestData))) {
			return $html;
		}
                
                $isAjaxSuggestionCountResultsEnabled = (bool) Mage::app()->getStore()
                    ->getConfig(Mage_CatalogSearch_Model_Query::XML_PATH_AJAX_SUGGESTION_COUNT);    // Nagarro added new condition from 1.9.x core
                
		$count--;

		$html = '<ul><li style="display:none"></li>';
		foreach ($suggestData as $index => $item) {
			if ($index == 0) {
				$item['row_class'] .= ' first';
			}

			if ($index == $count) {
				$item['row_class'] .= ' last';
			}
			// sh@plan2.net: Changed output formatting
			$html .= '<li title="'.$this->escapeHtml($item['title']).'" class="'.$item['row_class'].'">'.$this->escapeHtml($item['title']).'</li>';
		}

		$html.= '</ul>';

		return $html;
	}

	public function getSuggestData() {
		if (!$this->_suggestData) {
			$suggestions = Mage::helper('schrackcatalogsearch')->getSuggestions();
                        $query = $this->helper('catalogsearch')->getQueryText();    // Nagarro added new code from 1.9.x core
			$counter = 0;
			$data = array();
			foreach ($suggestions as $item) {
				$_data = array(
					'title' => $item->getQueryText(),
					'row_class' => (++$counter) % 2 ? 'odd' : 'even',
					'num_of_results' => $item->getNumResults(),
				);
				if ($item->getQueryText() == $query) {   // Nagarro added new code from 1.9.x core
                                    array_unshift($data, $_data);
                                }
                                else {
                                    $data[] = $_data;
                                }
                                
			}
			$this->_suggestData = $data;
		}
		return $this->_suggestData;
	}

}

?>