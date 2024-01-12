<?php
/**
  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */



/**
 * This interface provides some constants for the PayUnity service.
 */
interface Customweb_PayUnity_IConstants
{
	// Payment Types
	const PAYMENT_TYPE_PREAUTHORIZATION		= 'PA';
	const PAYMENT_TYPE_DEBIT				= 'DB';
	const PAYMENT_TYPE_RISK_BASED			= 'PA.CP';
	const PAYMENT_TYPE_CAPTURE				= 'CP';
	const PAYMENT_TYPE_CREDIT				= 'CD';
	const PAYMENT_TYPE_REVERSAL				= 'RV';
	const PAYMENT_TYPE_REFUND				= 'RF';
}