<?php

class Schracklive_Wws_Helper_Map2wws {

	const SHIPPING_DELIVERY = 0;
	const SHIPPING_PICKUP = 1;
    const SHIPPING_CONTAINER = 2;
    const SHIPPING_INPOST = 3;

	const PAYMENT_PURCHASE_ORDER = 0;
	const PAYMENT_CASH_ON_DELIVERY = 1;
	const PAYMENT_MONEY_ORDER = 2;
	const PAYMENT_PAYPAL = 3;
	const PAYMENT_VISA = 4;
	const PAYMENT_MASTERCARD = 5;
    const PAYMENT_FREE = 9;
	// const PAYMENT_PAYUNITY = 6; PayUnitiy remove action
    // const PAYMENT_PAYUNITYCW_VISA = 61;
	// const PAYMENT_PAYUNITYCW_MASTERCARD = 62;

	public function getShippingMethod(Mage_Shipping_Model_Rate_Abstract $shopShippingRate) {
		$shippingMethod = -1;
		switch ($shopShippingRate->getCarrier()) {
			case 'schrackdelivery':
			case 'freeshipping': // Magento - valid for Austria (phase 2 only?)
				$shippingMethod = self::SHIPPING_DELIVERY;
				break;
			case 'pickup':  // Magento - fake mapping (the carrier is not fully implemented)
			case 'schrackpickup':
				$shippingMethod = self::SHIPPING_PICKUP;
				break;
            case 'schrackcontainer':
            case 'container':
                $shippingMethod = self::SHIPPING_CONTAINER;
                break;
            case 'schrackinpost':
            case 'inpost':
                $shippingMethod = self::SHIPPING_INPOST;
                break;
			case 'flatrate': // Magento
			case 'tablerate': // Magento
			case 'ups': // Magento
			case 'usps': // Magento
			case 'fedex': // Magento
			case 'dhl': // Magento
				throw Mage::exception('Schracklive_Wws', 'Unexpected shipping method/rate: '.$shopShippingRate->getCode());
			default:
				throw Mage::exception('Schracklive_Wws', 'Invalid shipping method/rate: '.$shopShippingRate->getCode());
		}
		return $shippingMethod;
	}

	public function getPaymentMethod(Mage_Payment_Model_Method_Abstract $shopPayment) {
		$paymentMethod = -1;
		switch ($shopPayment->getCode()) {
			case 'purchaseorder': // Magento - Rechnung / Lieferschein
			case 'schrackpo': // Rechnung / Lieferschein
				$paymentMethod = self::PAYMENT_PURCHASE_ORDER;
				break;
			case 'schrackcash': // // Cash - Barzahlung
			case 'schrackcod': // // Cash/Collect on delivery - Nachnahme
				$paymentMethod = self::PAYMENT_CASH_ON_DELIVERY;
				break;
			case 'checkmo': // Magento - Check / Money order / Vorauszahlung
				$paymentMethod = self::PAYMENT_MONEY_ORDER;
				break;
			case 'paypal_standard': // Magento
				$paymentMethod = self::PAYMENT_PAYPAL;
				break;
			case 'ccsave': // Magento - Credit Card
				switch ($shopPayment->getCcType()) {
					case 'VI':
						$paymentMethod = self::PAYMENT_VISA;
						break;
					case 'MC':
						$paymentMethod = self::PAYMENT_MASTERCARD;
						break;
					default:
						throw Mage::exception('Schracklive_Wws', 'Unknown credit card: '.$shopPayment->getCcType());
				}
				break;
			case 'payunitycw_visa':
				$paymentMethod = self::PAYMENT_VISA;  // PayUnity customweb GmbH Payment Extension
				break;
			case 'payunitycw_mastercard':
				$paymentMethod = self::PAYMENT_MASTERCARD;  // PayUnity customweb GmbH Payment Extension
				break;
            // case 'pupay_cc':  $paymentMethod = self::PAYMENT_PAYUNITY; break; PayUnitiy remove action
			case 'free':
                $paymentMethod = self::PAYMENT_FREE;  // PayUnity customweb GmbH Payment Extension
                break;// Magento - Zero Subtotal Checkout (ie no charge at all, only free items)
			case 'paypal_direct': // Magento
			case 'paypal_express': // Magento
			case 'paypaluk_direct': // Magento
			case 'paypaluk_express': // Magento
			case 'authorizenet': // Magento
			case 'verisign': // Magento - Payflow Pro
				throw Mage::exception('Schracklive_Wws', 'Unexpected payment method: '.$shopPayment->getCode());
			default:
				throw Mage::exception('Schracklive_Wws', 'Invalid payment method: '.$shopPayment->getCode());
		}
		return $paymentMethod;
	}

	public function getWarehouseId(Mage_Shipping_Model_Rate_Abstract $shopShippingRate) {
		$warehouseId = 0;
		if ($shopShippingRate->getCarrier() == 'schrackpickup') {
			$warehouseId = Mage::helper('schrackshipping/pickup')->getWarehouseIdFromMethod($shopShippingRate->getMethod());
		}
		return $warehouseId;
	}

	public function getPaymentReference(Mage_Payment_Model_Method_Abstract $shopPayment) {
		$reference = '';
		if ($shopPayment->getCode() == 'purchaseorder') {
			$reference = $shopPayment->getInfoInstance()->getPoNumber();
		}
		return $reference;
	}

}
