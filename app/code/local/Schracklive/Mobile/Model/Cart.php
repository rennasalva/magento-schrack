<?php

class Cart
{

	const TYPE_CART     = 1;
	const TYPE_QUOTE    = 2;
	const TYPE_WISHLIST = 3;

	var $type;
	var $list;

	/**
	 * don't use directly, use factory
	 *
	 * @param $type
	 * @param $list
	 * @see      Schracklive_Mobile_Helper_Data::getCart()
	 */
	public function __construct($type, $list) {
		$this->type = $type;
		$this->list = $list;
	}

	/**
	 *
	 */
	public function addProduct($product, $quantity=1) {
		switch($this->type) {
			case TYPE_CART:
				return $list->addProduct($product, array('qty' => $quantity));
			case TYPE_QUOTE:
				return $list->addProduct($product, $quantity);
			case TYPE_WISHLIST:
				return $wishlist->addNewItem($product->getId());
		}
	}

	function removeProduct($product) {
		foreach ($list->getItems() as $item) {
			if ($item->getProduct()->getId() == $product->getId()) {
				if ($type == TYPE_WISHLIST) {
					$list->removeItem($item->getId());
				} else {
					$item->delete();
				}
			}
		}
	}

	public function save() {
		$list->save();
		if ($type == TYPE_CART) {
			Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
		}
	}

	protected function getItems() {
		switch($this->type) {
			case TYPE_CART:
				return $list->getItems();
			case TYPE_QUOTE:
				return $list->getAllItems();
			case TYPE_WISHLIST:
				return $list->getItemCollection();
		}
	}

}
