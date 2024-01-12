<?php

class Schracklive_SchrackCatalog_Model_Drum_Selector {

	protected $_drums = array();
	protected $_result = array(
		'newDrum' => null,
		'newDrumIsSmallest' => null,
		'newQty' => null,
		'case' => '', // sensing variable for unit tests
	);

	/**
	 * @param array $parameters [drums] => array(Schracklive_SchrackCatalog_Model_Drum), [qty] => int
	 */
	public function __construct(array $parameters) {
		$this->_drums = $parameters['drums'];
		$this->_qty = $parameters['qty'];
		$this->_shippingType = isset($parameters['shippingType']) ? $parameters['shippingType'] : Schracklive_SchrackShipping_Type::DELIVERY;
		$this->currentDrumNumber = $parameters['currentDrumNo'];
	}

	public function pick() {
		if ($this->_onlySingleDrumWhichMayNotBeLessened()) {
			$this->_pickOnlyDrumAndAdjustQuantity();
			$this->_result['case'] = '1';
		} elseif ($this->_onlyDrumsWhichMayNotBeLessened()) {
			$this->_pickLargestFittingDrumIfPossible();
			$this->_result['case'] = '2';
		} elseif ($this->_atLeastOneDrumWhichMayBeLessened()) {
			$this->_pickExactlyFittingDrumIfPossible();
			$this->_result['case'] = '3';
		} else {
			$this->_result['case'] = '4';
		}

		if ( ! isset($this->_result['newDrum']) && ! isset($this->_result['newQty']) ) {
			$this->_possibleAdjustQty();
		}
		return $this->_result;
	}

	protected function _possibleAdjustQty () {
		$drum = $this->_getCurrentDrum();
		if ( ! isset($drum) ) {
			return;
		}
		if ( $drum->getSize() > $this->_qty ) {
			$this->_result['newQty'] = $drum->getSize();
			$this->_result['newDrum'] = $drum;
		} else if ( $drum->getSize() < $this->_qty ) {
			$this->_result['newQty'] = intval(floor((intval($this->_qty) / $drum->getSize())) + 1) * $drum->getSize();
			$this->_result['newDrum'] = $drum;
		}
	}

	protected function _getCurrentDrum () {
		if ( ! isset($this->currentDrumNumber) ) {
			return null;
		}
		foreach ( $this->_drums as $unit => $drum ) {
			if ( $drum->getWwsNumber() == $this->currentDrumNumber ) {
				return $drum;
			}
		}
		return null;
	}

	protected function _onlySingleDrumWhichMayNotBeLessened() {
		return count($this->_drums) == 1 && !$this->_mayDrumBeLessened(current($this->_drums));
	}

	protected function _mayDrumBeLessened(Schracklive_SchrackCatalog_Model_Drum $drum) {
		return $drum->mayBeLessenedForShipping($this->_shippingType);
	}

	protected function _pickOnlyDrumAndAdjustQuantity() {
		$drum = current($this->_drums);
		$this->_result['newDrum'] = $drum;
		if (is_float($this->_qty)) {
			$modulo = fmod($this->_qty, $drum->getSize());
		} else {
			$modulo = $this->_qty % $drum->getSize();
		}
		if ($modulo) {
			$this->_result['newQty'] = $this->_qty + $drum->getSize() - $modulo;
		}
	}

	protected function _onlyDrumsWhichMayNotBeLessened() {
		if (!count($this->_drums)) {
			return false;
		}
		foreach ($this->_drums as $drum) {
				if ($this->_mayDrumBeLessened($drum)) {
				return false;
			}
		}
		return true;
	}

	protected function _pickLargestFittingDrumIfPossible() {
		$drums = $this->_getDrumsWhichMayNotBeLessenedSortedByUnit();
		$newDrum = $this->_findFittingDrum($drums);
		if ($newDrum) {
			$this->_result['newDrumIsSmallest'] = true;
			foreach (array_keys($drums) as $drumSize) {
                if ( $drumSize == 0 ) { // avoid division by zero
                    continue;
                }
				if ($drumSize < $newDrum->getSize() && $this->_qty % $drumSize == 0) {
					$this->_result['newDrumIsSmallest'] = false;
					break;
				}
			}
			$this->_result['newDrum'] = $newDrum;
		} else {
			$this->_result['newDrum'] = Mage::getModel('schrackcatalog/drum'); // null object
		}
	}

	protected function _getDrumsWhichMayNotBeLessenedSortedByUnit() {
		$drums = array();
		foreach ($this->_drums as $drum) {
			if (!$this->_mayDrumBeLessened($drum)) {
				$drums[$drum->getSize()] = $drum;
			}
		}
		krsort($drums, SORT_NUMERIC);
		return $drums;
	}

	protected function _findFittingDrum(array $drums) {
		// Prefer exactly fitting
		foreach ($drums as $unit => $drum) {
            if ( $unit == null || intval($unit) == 0 ) {
                continue;
            }
			if (is_float($this->_qty)) {
				$modulo = fmod($this->_qty, $unit);
			} else {
				$modulo = $this->_qty % $unit;
			}
			if ($modulo == 0) {
				return $drum;
			}
		}
		// Round up to next smallest
		if (is_array($drums)) {
			$drumSizes = array_keys($drums);
			sort($drumSizes);
            $ds = $i = 0;
            $cnt = count($drumSizes);
            do {
                $ds = $drumSizes[$i];
            } while ( $ds == 0 && ++$i < $cnt );
            if ( $ds == 0 ) {
                $ds = 1;
            }
			$modulo = $this->_qty % $ds;
			if ($modulo) {
				$this->_result['newQty'] = $this->_qty + $ds - $modulo;
			}
			return $drums[$drumSizes[0]];
		}
		return null;
	}

	protected function _atLeastOneDrumWhichMayBeLessened() {
		if (!count($this->_drums)) {
			return false;
		}
		return !$this->_onlyDrumsWhichMayNotBeLessened();
	}

	protected function _pickExactlyFittingDrumIfPossible() {
		$drums = array();
		foreach ($this->_drums as $drum) {
			if ($drum->isLessened()) {
				$index = $drum->getStockQty();
			} else {
				$index = $drum->getSize();
			}
			$drums[$index] = $drum;
		}
		krsort($drums, SORT_NUMERIC);
		$newDrum = $this->_findExactlyFittingDrum($drums);
		if ($newDrum) {
			$this->_result['newDrum'] = $newDrum;
		}
	}

	protected function _findExactlyFittingDrum(array $drums) {
		foreach ($drums as $qty => $drum) {
			if ($drum->isLessened()) {
				if ($this->_qty == $qty) {
					return $drum;
				}
			} else {
                if ($qty <= 1) {
                    continue;
                }
				if (is_float($this->_qty)) {
					$modulo = fmod($this->_qty, $qty);
				} else {
					$modulo = $this->_qty % $qty;
				}
				if ($modulo == 0) {
					return $drum;
				}
			}
		}
		return null;
	}

}

