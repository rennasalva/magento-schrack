<?php

class Schracklive_SchrackCatalog_Model_Drum extends Varien_Object {

	const TYPE_FULL = 'F';
	const TYPE_PARTIAL = 'P';

	public function mayBeLessenedForShipping($type) {
		switch ($type) {
			case Schracklive_SchrackShipping_Type::DELIVERY;
				$mayBeLessened = (bool)$this->getLessenDelivery();
				break;
			case Schracklive_SchrackShipping_Type::PICKUP;
				$mayBeLessened = (bool)$this->getLessenPickup();
				break;
			default:
				throw new InvalidArgumentException('Unknown shipping type.');		
		}
		return $mayBeLessened;
	}

	public function isLessened() {
		return ($this->getType() == self::TYPE_PARTIAL);
	}

}
