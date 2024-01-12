<?php

class Schracklive_SchrackCheckout_Block_Cart_Totals extends Mage_Checkout_Block_Cart_Totals {

	public function renderSubtotal($area = null, $colspan = 1) {
		$totals = $this->getTotals();
		if (isset($totals['subtotal'])) {
			// todo: add area check
			$html = $this->renderTotal($totals['subtotal'], $area, $colspan);
		}
		return $html;
	}

}
