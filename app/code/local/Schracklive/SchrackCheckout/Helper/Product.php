<?php

class Schracklive_SchrackCheckout_Helper_Product extends Mage_Core_Helper_Abstract {

	/**
	 * @param int $qty
	 * @param int $drumNumber -1 for no drum check
	 * @param int $salesUnit
	 * @param array[Schracklive_SchrackCatalog_Model_Drum] $drums
	 * @return bool
	 */
	public function isQtyAndDrumAllowed($qty, $drumNumber, $salesUnit, array $drums) {
		if ($salesUnit > 1) {
			return $this->isQtyAllowedForSalesUnit($qty, $salesUnit);
		} elseif ($drumNumber >= 0) {
			return $this->isFittingDrumAvailable($qty, $drumNumber, $drums);
		} else {
			return true;
		}
	}

	/**
	 * @param int $qty
	 * @param int $salesUnit
	 * @return bool
	 */
	public function isQtyAllowedForSalesUnit($qty, $salesUnit) {
		if ($qty > 0) {
			$remainder = fmod($qty, $salesUnit);
			return $remainder == 0;
		} else {
			return false;
		}
	}

	/**
	 * @param int $qty
	 * @param int $drumNumber
	 * @param array[Schracklive_SchrackCatalog_Model_Drum] $drums
	 * @param int $shippingType Schracklive_SchrackShipping_Type::DELIVERY|PICKUP
	 * @return bool
	 */
	public function isFittingDrumAvailable($qty, $drumNumber, array $drums, $shippingType = Schracklive_SchrackShipping_Type::DELIVERY) {
		foreach ($drums as $drum) {
			if ($drum->getWwsNumber() == $drumNumber) {
				if ($drum->mayBeLessenedForShipping($shippingType)) {
					return true;
				}

                if (fmod($qty, $drum->getSize()) == 0) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @param Mage_Catalog_Model_Product $product
	 * @param                            $qty
	 * @param                            $drumNumber
	 * @param                            $salesUnit
	 * @param array                      $drums
	 * @param int                        $shippingType
	 * @return array keys: (int)newQty, (Schracklive_SchrackCatalog_Model_Drum)newDrum, (array)messages
	 */
	public function suggestQtyAndDrum(Mage_Catalog_Model_Product $product, $qty, $drumNumber, $salesUnit, array $drums, $shippingType = Schracklive_SchrackShipping_Type::DELIVERY) {
        $catalogProductHelper = Mage::helper('schrackcatalog/product');
		if ($catalogProductHelper->hasDrums($product)) {
			return $this->_suggestDrum($product, $qty, $drumNumber, $drums, $shippingType);
		} else if ($salesUnit > 1) {
			return $this->_suggestQty($product, $qty, $salesUnit);
		}
        else {
            return array(
                'newQty' => $qty,
                'newDrum' => null,
                'newDrumIsSmallest' => null,
                'messages' => array(),
            );
        }
	}

	protected function _suggestQty(Mage_Catalog_Model_Product $product, $qty, $salesUnit) {
		$newQty = null;
		$messages = array();
		if (!$this->isQtyAllowedForSalesUnit($qty, $salesUnit)) {
			$newQty = $qty + $salesUnit - ($qty % $salesUnit);
			$messages[] = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $product->getSku(), $newQty, $salesUnit);
		}
		return array(
			'newQty' => $newQty,
			'newDrum' => null,
			'newDrumIsSmallest' => null,
			'messages' => $messages,
		);
	}

	protected function _suggestDrum(Mage_Catalog_Model_Product $product, $qty, $drumNumber, $drums, $shippingType) {
		$selection = Mage::getModel('schrackcatalog/drum_selector', array('drums' => $drums, 'qty' => $qty, 'shippingType' => $shippingType, 'currentDrumNo' => $drumNumber))->pick();
		$messages = array();
		if (!is_null($selection['newQty'])) {
			$messages[] = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $product->getSku(), $selection['newQty'], $selection['newDrum']->getSize());
		}
		if (!is_null($selection['newDrum'])) {
			if ($drumNumber) {
				if (!$selection['newDrum']->getWwsNumber()) {
					$messages[] = sprintf($this->__('Packaging type of %1s has been reset.'), $product->getSku());
				} elseif ($selection['newDrum']->getWwsNumber() != $drumNumber) {
					$messages[] = sprintf($this->__('Packaging type of %1s has been set to "%2s".'), $product->getSku(), $selection['newDrum']->getDescription());
				}
			} else {
				if ($selection['newDrum']->getWwsNumber()) {
					$messages[] = sprintf($this->__('Packaging type of %1s has been set to "%2s".'), $product->getSku(), $selection['newDrum']->getDescription());
				} else {
					$messages[] = $this->__('Quantity warning:').
						' '.
						sprintf($this->__('Your selected quantity for %1s is not a multiple of the packaging unit. Please select a multiple of %2$d.'), $product->getSku(), $selection['newDrum']->getSize()).
						' '.
						$this->__('Hint: Use the feature "find available units" to select an actual deliverable length.');
				}
			}
		}
		return array(
			'newQty' => $selection['newQty'],
			'newDrum' => $selection['newDrum'],
			'newDrumIsSmallest' => $selection['newDrumIsSmallest'],
			'messages' => $messages,
		);
	}

}
