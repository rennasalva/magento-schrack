<?php

class Schracklive_SolrSearch_Model_Autocomplete extends Schracklive_SolrSearch_Model_Search {

	public function getSuggestions() {
		$suggestions = $this->_getSolrSuggestions();
		$result = array();
		foreach ($suggestions as $title => $count) {
			$result[] = array(
				'title' => $title,
				'count' => $count,
			);
		}
		return $result;
	}

	protected function _getSolrSuggestions() {
		$facetQueryBase = array(
			'appKey:mage',
		);
		if (Mage::app()->getRequest()->getParam('searchcategoryid')) {
			$facetQueryBase[] = 'category_id_intS:'.intval(Mage::app()->getRequest()->getParam('searchcategoryid'));
		}
		$facetQuery = array_merge($facetQueryBase, $this->getFacetsFromRequest());
		$extra['fl'] = 'entity_id,entity_id_intS';
		$extra['facet'] = 'true';
		$extra['facet.field'] = array('spell');
		$extra['facet.mincount'] = 1;
		$extra['facet.limit'] = 30;
		$extra['facet.prefix'] = Mage::helper('schrackcatalogsearch')->escapeSpecialCharacters(strtolower(Mage::app()->getRequest()->getParam('q')));
		if (!empty($facetQuery)) {
			$selectedFacets = array();
			foreach ($facetQuery as $fq) {
				$fq = explode(':', $fq);
				$selectedFacets[$fq[0]][] = '"'.$fq[1].'"';
			}
			$facetQuery = array('schrack_sts_statuslocal_facet:(std OR istausl OR wirdausl OR gesperrt)');
			foreach ($selectedFacets as $key => $val) {
				$facetQuery[] = $key.':('.implode(' OR ', $val).')';
			}
			$extra['fq'] = $facetQuery;
		}
		$solrReply = $this->_getSolrData("", 'solrserver', $extra, 1, 1);
		if (is_object($solrReply)) {
			return $this->_objectToArray($solrReply->facet_counts->facet_fields->spell);
		}
		return array();
	}
}

?>
