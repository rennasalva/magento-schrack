/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    design
 * @package     base_default
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

var counter = 0;
var count = 1;

function timer()
{
    count = count - 1;
    if (count <= 0)
    {
        clearInterval(counter);
        jQuery('#progressBlocker').val(0);
        return;
    }
}

function checkoutActionOne () {
    localStorage.newCheckoutActionPath = '';

    jQuery('#checkout_saveShippingMethodButtonContinueAlternate').hide();
    localStorage.newCheckoutProcessLastUpdateTime = Math.floor(Date.now() / 1000);
    jQuery('#shipping-method-buttons-container-alternate').css('opacity', 0.5);
    jQuery('#shipping-method-please-wait-alternate').show();
    localStorage.newCheckoutProcessCurrentStep = 'opc-payment';
    localStorage.newCheckoutShippingMethod = 'pickup';
    localStorage.newCheckoutPickupLocation = localStorage.newCheckoutSelectedPickupLocation;

    shipping.save();
    shippingMethod.save();
}

function checkoutActionTwo () {
    localStorage.newCheckoutActionPath = '';

    jQuery('#checkout_saveShippingMethodButtonContinueAlternate').hide();
    localStorage.newCheckoutProcessLastUpdateTime = Math.floor(Date.now() / 1000);
    jQuery('#shipping-method-buttons-container-alternate').css('opacity', 0.5);
    jQuery('#shipping-method-please-wait-alternate').show();
    localStorage.newCheckoutProcessCurrentStep = 'opc-payment';
    localStorage.newCheckoutShippingMethod = 'pickup';
    localStorage.newCheckoutPickupLocation = localStorage.newCheckoutSelectedPickupLocation;

    shipping.save();
    shippingMethod.save();
}

function checkoutActionThree () {
    localStorage.newCheckoutActionPath = '';

    shippingMethod.save();
}

function checkoutActionFour () {
    localStorage.newCheckoutActionPath = '';

    jQuery('#checkout_saveShippingMethodButtonContinueAlternate').hide();
    localStorage.newCheckoutProcessLastUpdateTime = Math.floor(Date.now() / 1000);
    jQuery('#shipping-method-buttons-container-alternate').css('opacity', 0.5);
    jQuery('#shipping-method-please-wait-alternate').show();
    localStorage.newCheckoutProcessCurrentStep = 'opc-payment';
    localStorage.newCheckoutShippingMethod = 'pickup';
    localStorage.newCheckoutPickupLocation = localStorage.newCheckoutSelectedPickupLocation;
    if (localStorage.newCheckoutProcessCurrentRole == 'guest' && localStorage.newCheckoutMagicFlag1 == 1) {
        console.log ('opcheckout.js -> CHECKPOINT available.phtml #002');
        shippingMethod.save();
    } else {
        console.log ('opcheckout.js -> CHECKPOINT available.phtml #003');
        shipping.save();
        //billing.save();
        shippingMethod.save();
    }
}


function getProductAvailabilityInStore (actionURL, pickupStore, formKey, action) {
    console.log('opcheckout.js -> chosed action = ' + action);

    localStorage.newCheckoutActionPath = action;

    jQuery.ajax(actionURL, {
        'dataType' : 'json',
        'type': 'POST',
        'data': {
            'form_key' : formKey,
            'pickup_store' : pickupStore
        },
        'success': function (data) {
            var parsedData = data;

            if (data != null && data.length > 0 ) {
                console.log('opcheckout.js -> we found products which are not available, manageable or not enough amount in selected store');

                var table1 = '<table class="checkout_modal_result_not_available_product_list_table">';
                var table2 = '<table class="checkout_modal_result_not_available_product_list_table">';
                var tempString = '';
                var showTable1 = false;
                var showTable2 = false;

                tempString  = '<tr><td class="checkoutSkuNotAvailableHeader">';
                tempString += Translator.translate('Product');
                tempString += '</td><td class="checkoutCartQtySelectedHeader">';
                tempString += Translator.translate('In Cart');
                tempString += '</td><td class="checkoutStoreQtyAvailableHeader">';
                tempString += Translator.translate('Pickupable');
                tempString += '</td></tr>';
                table1 = table1 + tempString;
                table2 = table1;

                for (var j = 0; j < data.length; j++) {
                    if (data[j].OnlyAmountMarker == 1 || data[j].OnlyAmountMarker == 2) {
                        tempString  = '<tr><td class="checkoutSkuNotAvailable">';
                        tempString += data[j].sku + '</td><td class="checkoutCartQtySelected">';
                        tempString += data[j].selectedAmountInCart;
                        tempString += '</td><td class="checkoutStoreQtyAvailable">';
                        tempString += data[j].availableAmountInSelectedStock;
                        tempString += '</td></tr>';
                        table1 = table1 + tempString;
                        showTable1 = true;
                    }
                    if (data[j].OnlyAmountMarker == 2 || data[j].OnlyAmountMarker == 3) {
                        tempString  = '<tr><td class="checkoutSkuNotAvailable">';
                        tempString += data[j].sku + '</td><td class="checkoutCartQtySelected">';
                        tempString += data[j].selectedAmountInCart;
                        tempString += '</td><td class="checkoutStoreQtyAvailable">';
                        tempString += data[j].availableAmountInSelectedStock;
                        tempString += '</td></tr>';
                        table2 = table2 + tempString;
                        showTable2 = true;
                    }
                }

                if (showTable1 == true) {
                    table1 = table1 + '</table>';
                    jQuery('#checkout_modal_result_not_available_product_list').html(table1);
                    jQuery('#checkout_modal_result_not_available_product_list_container').show();
                }
                if (showTable2 == true) {
                    table2 = table2 + '</table>';
                    jQuery('.checkout_modal_right_button').hide();
                    jQuery('#checkout_modal_result_not_manageable_product_list').html(table2);
                    jQuery('#checkout_modal_result_not_manageable_product_list_container').show();
                }
                jQuery('#checkout_modal_pickup_warning').modal();
            } else {
                console.log('opcheckout.js -> all products avasilable in selected store and amount');

                if (action == 'action_one') {
                    checkoutActionOne();
                }
                if (action == 'action_two') {
                    checkoutActionTwo();
                }
                if (action == 'action_three') {
                    checkoutActionThree();
                }
                if (action == 'action_four') {
                    checkoutActionFour();
                }
            }
        },
        'error': function (data) {

        }
    });
}

var Checkout = Class.create();
Checkout.prototype = {
    initialize: function(accordion, urls){
        this.accordion = accordion;
        this.progressUrl = urls.progress;
        this.reviewUrl = urls.review;
        this.saveMethodUrl = urls.saveMethod;
        this.failureUrl = urls.failure;
        this.billingForm = false;
        this.shippingForm= false;
        this.syncBillingShipping = false;
        this.method = '';
        this.payment = '';
        this.loadWaiting = false;
        this.steps = ['login', 'shipping_method', 'billing', 'shipping', 'payment', 'review'];

        //this.onSetMethod = this.nextStep.bindAsEventListener(this);

        this.accordion.disallowAccessToNextSections = true;
    },

    ajaxFailure: function(){
        location.href = this.failureUrl;
    },

    reloadProgressBlock: function(){
        var updater = new Ajax.Updater('checkout-progress-wrapper', this.progressUrl, {
            evalScripts:true,
            method: 'get',
            onFailure: this.ajaxFailure.bind(this)
        });
    },

    reloadReviewBlock: function(){
        var updater = new Ajax.Updater('checkout-review-load', this.reviewUrl, {
            method: 'get',
            onFailure: this.ajaxFailure.bind(this)
        });
    },

    _disableEnableAll: function(element, isDisabled) {
        var descendants = element.descendants();
        for (var k in descendants) {
            descendants[k].disabled = isDisabled;
        }
        element.disabled = isDisabled;
    },

    setLoadWaiting: function(step, keepDisabled) {
        if (step) {
            if (this.loadWaiting) {
                this.setLoadWaiting(false);
            }
            var container = $(step+'-buttons-container');
            container.addClassName('disabled');
            container.setStyle({opacity:.5});
            this._disableEnableAll(container, true);
            Element.show(step+'-please-wait');
        } else {
            if (this.loadWaiting) {
                var container = $(this.loadWaiting+'-buttons-container');
                var isDisabled = (keepDisabled ? true : false);
                if (!isDisabled) {
                    container.removeClassName('disabled');
                    container.setStyle({opacity:1});
                }
                this._disableEnableAll(container, isDisabled);
                Element.hide(this.loadWaiting+'-please-wait');
            }
        }
        this.loadWaiting = step;
    },

    gotoSection: function(section) {
        try {
            var loc = window.location.pathname;
            var dir = loc.substring(0, loc.lastIndexOf('/'));
            if (typeof(ga) !== 'undefined' && ga) {
                gaLocation = dir + '/' + section + '/';
                switch (section) {
                    case "shipping_method":
                        localStorage.newCheckoutProcessCurrentStep = 'opc-shipping_method';
                        console.log('opcheckout.js -> Current Active Step = SHIPPING METHOD');
                        break;
                    case "billing":
                        localStorage.newCheckoutProcessCurrentStep = 'opc-billing';
                        console.log('opcheckout.js -> Current Active Step = BILLING');
                        // Progress bar refresh:
                        jQuery('#step_one_progress_circle').html('&#10122;');
                        jQuery('#step_one_progress_circle').addClass('checkout_progress_color_active');
                        // Reset progress bar-display to current status (remove deprecated CSS-Classes):
                        jQuery('#step_one_progress_circle').removeClass('checkout_progress_link');
                        jQuery('#step_two_progress_circle').removeClass('checkout_progress_link');
                        jQuery('#step_two_progress_circle').html('&#10123;');
                        jQuery('#step_three_progress_circle').removeClass('checkout_progress_link');
                        jQuery('#step_three_progress_circle').html('&#10124;');
                        jQuery('#step_four_progress_circle').html('&#10125;');
                        jQuery('#step_one_two_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                        jQuery('#step_two_three_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                        jQuery('#step_three_four_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                        jQuery('#step_two_progress_circle').removeClass('checkout_progress_color_active');
                        jQuery('#step_three_progress_circle').removeClass('checkout_progress_color_active');
                        jQuery('#step_four_progress_circle').removeClass('checkout_progress_color_active');
                        // Set tab header styles:
                        jQuery('#checkout_tab_header_shipping_method').removeClass('checkout_step_active_tab ');
                        jQuery('#checkout_tab_header_shipping_method').addClass('checkout_step_inactive_tab ');
                        jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_untouched_tab ');
                        jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_tab ');
                        jQuery('#checkout_tab_header_shipping').addClass('checkout_step_active_tab ');
                        break;
                    case "shipping":
                        if (localStorage.newCheckoutShippingMethod == 'pickup'
                            && ( (localStorage.newCheckoutProcessSpecialAction != 'full-register-prospect-application' && localStorage.newCheckoutProcessCurrentRole != 'guest')
                                || (localStorage.newCheckoutProcessCurrentRole == 'guest' && localStorage.newCheckoutMagicFlag1 == 1) ) ) {
                            section = 'payment';
                            localStorage.newCheckoutProcessCurrentStep = 'opc-payment';
                            console.log('opcheckout.js -> Current Active Step = PAYMENT METHOD (in SHIPPING CASE)');
                            jQuery('#step_one_progress_circle').html('&#10112;');
                            jQuery('#step_one_progress_circle').addClass('checkout_progress_link');
                            jQuery('#step_one_progress_circle').addClass('checkout_progress_color_active');
                            jQuery('#step_one_two_progress_circle_line').addClass('checkout_progress_hr_line_active');
                            jQuery('#step_two_progress_circle').html('&#10113;');
                            jQuery('#step_two_progress_circle').addClass('checkout_progress_link');
                            jQuery('#step_two_progress_circle').addClass('checkout_progress_color_active');
                            jQuery('#step_two_three_progress_circle_line').addClass('checkout_progress_hr_line_active');
                            jQuery('#step_three_progress_circle').addClass('checkout_progress_color_active');
                            // Set tab header styles:
                            jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_untouched_tab ');
                            jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_active_tab ');
                            jQuery('#checkout_tab_header_shipping').addClass('checkout_step_inactive_tab ');
                            jQuery('#checkout_tab_header_payment').removeClass('checkout_step_inactive_untouched_tab  ');
                            jQuery('#checkout_tab_header_payment').removeClass('checkout_step_inactive_tab ');
                            jQuery('#checkout_tab_header_payment').addClass('checkout_step_active_tab ');
                            jQuery('#checkout_select_store_pickup_container').hide();
                        }

                        if (localStorage.newCheckoutShippingMethod == 'pickup'
                            && localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application') {
                            localStorage.newCheckoutProcessCurrentStep = 'opc-shipping';
                            if (localStorage.newCheckoutProspectRole == 'prospect-light') {
                                localStorage.newCheckoutProcessCurrentStep = 'opc-shipping';
                                console.log('opcheckout.js -> Current Active Step = SHIPPING (Light Prospect)');

                                console.log('Status : light prospect needs to be complete all company address data and other company related data (pickup route 2)');
                                jQuery('#new_address_button').click();

                                // Progress bar refresh:
                                jQuery('#step_one_progress_circle').html('&#10122;');
                                jQuery('#step_one_progress_circle').addClass('checkout_progress_color_active');
                                // Reset progress bar-display to current status (remove deprecated CSS-Classes):
                                jQuery('#step_one_progress_circle').removeClass('checkout_progress_link');
                                jQuery('#step_two_progress_circle').removeClass('checkout_progress_link');
                                jQuery('#step_two_progress_circle').html('&#10123;');
                                jQuery('#step_three_progress_circle').removeClass('checkout_progress_link');
                                jQuery('#step_three_progress_circle').html('&#10124;');
                                jQuery('#step_four_progress_circle').html('&#10125;');
                                jQuery('#step_one_two_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                                jQuery('#step_two_three_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                                jQuery('#step_three_four_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                                jQuery('#step_two_progress_circle').removeClass('checkout_progress_color_active');
                                jQuery('#step_three_progress_circle').removeClass('checkout_progress_color_active');
                                jQuery('#step_four_progress_circle').removeClass('checkout_progress_color_active');
                                // Set tab header styles:
                                jQuery('#checkout_tab_header_shipping_method').removeClass('checkout_step_active_tab ');
                                jQuery('#checkout_tab_header_shipping_method').addClass('checkout_step_inactive_tab ');
                                jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_untouched_tab ');
                                jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_tab ');
                                jQuery('#checkout_tab_header_shipping').addClass('checkout_step_active_tab ');
                            } else {
                                console.log('opcheckout.js -> Current Active Step = BILLING (in SHIPPING CASE)');

                                jQuery('#step_one_progress_circle').html('&#10122;');
                                jQuery('#step_one_progress_circle').addClass('checkout_progress_color_active');
                                // Reset progress bar-display to current status (remove deprecated CSS-Classes):
                                jQuery('#step_one_progress_circle').removeClass('checkout_progress_link');
                                jQuery('#step_two_progress_circle').removeClass('checkout_progress_link');
                                jQuery('#step_two_progress_circle').html('&#10123;');
                                jQuery('#step_three_progress_circle').removeClass('checkout_progress_link');
                                jQuery('#step_three_progress_circle').html('&#10124;');
                                jQuery('#step_four_progress_circle').html('&#10125;');
                                jQuery('#step_one_two_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                                jQuery('#step_two_three_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                                jQuery('#step_three_four_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                                jQuery('#step_two_progress_circle').removeClass('checkout_progress_color_active');
                                jQuery('#step_three_progress_circle').removeClass('checkout_progress_color_active');
                                jQuery('#step_four_progress_circle').removeClass('checkout_progress_color_active');
                                // Set tab header styles:
                                jQuery('#checkout_tab_header_shipping_method').removeClass('checkout_step_active_tab ');
                                jQuery('#checkout_tab_header_shipping_method').addClass('checkout_step_inactive_tab ');
                                jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_untouched_tab ');
                                jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_tab ');
                                jQuery('#checkout_tab_header_shipping').addClass('checkout_step_active_tab ');
                                if (localStorage.newCheckoutRunningProcess != 'processCheckoutAsNonLoggedInUser') {
                                    jQuery('#shipping-form-container').show();
                                }
                            }
                        }

                        if (localStorage.newCheckoutShippingMethod == 'delivery') {
                            localStorage.newCheckoutProcessCurrentStep = 'opc-shipping';
                            console.log('opcheckout.js -> Current Active Step = SHIPPING');

                            if (localStorage.customerNotLoggedIn == "0"
                                && localStorage.newCheckoutProcessCurrentRole == 'prospect-user'
                                && localStorage.newCheckoutProspectRole == 'prospect-light'
                                && localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application'
                                && localStorage.newCheckoutProcessCurrentStep == 'opc-shipping') {
                                console.log('Status : light prospect needs to be complete all company address data and other company related data (delivery route)');
                                jQuery('#new_address_button').click();
                            }

                            // Progress bar refresh:
                            jQuery('#step_one_progress_circle').html('&#10122;');
                            jQuery('#step_one_progress_circle').addClass('checkout_progress_color_active');
                            // Reset progress bar-display to current status (remove deprecated CSS-Classes):
                            jQuery('#step_one_progress_circle').removeClass('checkout_progress_link');
                            jQuery('#step_two_progress_circle').removeClass('checkout_progress_link');
                            jQuery('#step_two_progress_circle').html('&#10123;');
                            jQuery('#step_three_progress_circle').removeClass('checkout_progress_link');
                            jQuery('#step_three_progress_circle').html('&#10124;');
                            jQuery('#step_four_progress_circle').html('&#10125;');
                            jQuery('#step_one_two_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                            jQuery('#step_two_three_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                            jQuery('#step_three_four_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                            jQuery('#step_two_progress_circle').removeClass('checkout_progress_color_active');
                            jQuery('#step_three_progress_circle').removeClass('checkout_progress_color_active');
                            jQuery('#step_four_progress_circle').removeClass('checkout_progress_color_active');
                            // Set tab header styles:
                            jQuery('#checkout_tab_header_shipping_method').removeClass('checkout_step_active_tab ');
                            jQuery('#checkout_tab_header_shipping_method').addClass('checkout_step_inactive_tab ');
                            jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_untouched_tab ');
                            jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_tab ');
                            jQuery('#checkout_tab_header_shipping').addClass('checkout_step_active_tab ');
                            if (localStorage.newCheckoutRunningProcess != 'processCheckoutAsNonLoggedInUser') {
                                jQuery('#shipping-form-container').show();
                            }
                        }
                        if (localStorage.newCheckoutShippingMethod == 'container') {
                            localStorage.newCheckoutProcessCurrentStep = 'opc-shipping';
                            console.log('opcheckout.js -> Current Active Step = SHIPPING');

                            // Progress bar refresh:
                            jQuery('#step_one_progress_circle').html('&#10122;');
                            jQuery('#step_one_progress_circle').addClass('checkout_progress_color_active');
                            // Reset progress bar-display to current status (remove deprecated CSS-Classes):
                            jQuery('#step_one_progress_circle').removeClass('checkout_progress_link');
                            jQuery('#step_two_progress_circle').removeClass('checkout_progress_link');
                            jQuery('#step_two_progress_circle').html('&#10123;');
                            jQuery('#step_three_progress_circle').removeClass('checkout_progress_link');
                            jQuery('#step_three_progress_circle').html('&#10124;');
                            jQuery('#step_four_progress_circle').html('&#10125;');
                            jQuery('#step_one_two_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                            jQuery('#step_two_three_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                            jQuery('#step_three_four_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                            jQuery('#step_two_progress_circle').removeClass('checkout_progress_color_active');
                            jQuery('#step_three_progress_circle').removeClass('checkout_progress_color_active');
                            jQuery('#step_four_progress_circle').removeClass('checkout_progress_color_active');
                            // Set tab header styles:
                            jQuery('#checkout_tab_header_shipping_method').removeClass('checkout_step_active_tab ');
                            jQuery('#checkout_tab_header_shipping_method').addClass('checkout_step_inactive_tab ');
                            jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_untouched_tab ');
                            jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_tab ');
                            jQuery('#checkout_tab_header_shipping').addClass('checkout_step_active_tab ');
                            if (localStorage.newCheckoutRunningProcess != 'processCheckoutAsNonLoggedInUser') {
                                jQuery('#shipping-form-container').show();
                            }
                        }
                        if (localStorage.newCheckoutShippingMethod == 'inpost') {
                            localStorage.newCheckoutProcessCurrentStep = 'opc-shipping';
                            console.log('opcheckout.js -> Current Active Step = SHIPPING');

                            // Progress bar refresh:
                            jQuery('#step_one_progress_circle').html('&#10122;');
                            jQuery('#step_one_progress_circle').addClass('checkout_progress_color_active');
                            // Reset progress bar-display to current status (remove deprecated CSS-Classes):
                            jQuery('#step_one_progress_circle').removeClass('checkout_progress_link');
                            jQuery('#step_two_progress_circle').removeClass('checkout_progress_link');
                            jQuery('#step_two_progress_circle').html('&#10123;');
                            jQuery('#step_three_progress_circle').removeClass('checkout_progress_link');
                            jQuery('#step_three_progress_circle').html('&#10124;');
                            jQuery('#step_four_progress_circle').html('&#10125;');
                            jQuery('#step_one_two_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                            jQuery('#step_two_three_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                            jQuery('#step_three_four_progress_circle_line').removeClass('checkout_progress_hr_line_active');
                            jQuery('#step_two_progress_circle').removeClass('checkout_progress_color_active');
                            jQuery('#step_three_progress_circle').removeClass('checkout_progress_color_active');
                            jQuery('#step_four_progress_circle').removeClass('checkout_progress_color_active');
                            // Set tab header styles:
                            jQuery('#checkout_tab_header_shipping_method').removeClass('checkout_step_active_tab ');
                            jQuery('#checkout_tab_header_shipping_method').addClass('checkout_step_inactive_tab ');
                            jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_untouched_tab ');
                            jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_tab ');
                            jQuery('#checkout_tab_header_shipping').addClass('checkout_step_active_tab ');
                            if (localStorage.newCheckoutRunningProcess != 'processCheckoutAsNonLoggedInUser') {
                                jQuery('#shipping-form-container').show();
                            }
                        }
                        break;
                    case "payment":
                        console.log('opcheckout.js -> Current Active Step = PAYMENT METHOD');

                        if (localStorage.newCheckoutProspectRole == 'prospect-light'
                            && localStorage.newCheckoutProcessCurrentStep == 'opc-shipping_method') {
                            console.log('Alternative route to pickup selection (light prospect)');

                            jQuery('#checkout_saveShippingMethodButtonAlternate').removeClass('checkout_saveShippingMethodButtonAlternate');
                            jQuery('#checkout_saveShippingMethodButtonAlternate').addClass('checkout_saveShippingMethodButtonAlternatePickup');

                            localStorage.newCheckoutMagicFlag1 = 1;

                            jQuery('#checkout-step-billing').hide();
                            jQuery('#co-billing-form').hide();

                            // Open tab with shipping information, but only showing store pickup selection options:
                            jQuery('#co-shipping-form').hide();
                            jQuery("#checkout_select_store_pickup_container").appendTo("#shipping-form-container");
                            jQuery('#co-shipping-method-form').hide();
                            jQuery('#checkout-step-shipping').show();
                            jQuery('#shipping-form-container').show();
                            jQuery('#checkout_select_store_pickup').removeClass('hidden');
                            jQuery('#checkout_select_store_pickup_container').show();

                            localStorage.newCheckoutProcessCurrentStep = 'opc-shipping';
                            localStorage.newCheckoutShippingMethod = 'pickup';
                            localStorage.newCheckoutPickupLocation = localStorage.newCheckoutSelectedPickupLocation;
                        } else {
                            localStorage.newCheckoutProcessCurrentStep = 'opc-payment';
                            jQuery('#step_one_progress_circle').html('&#10112;');
                            jQuery('#step_one_progress_circle').addClass('checkout_progress_link');
                            jQuery('#step_one_progress_circle').addClass('checkout_progress_color_active');
                            jQuery('#step_one_two_progress_circle_line').addClass('checkout_progress_hr_line_active');
                            jQuery('#step_two_progress_circle').html('&#10113;');
                            jQuery('#step_two_progress_circle').addClass('checkout_progress_link');
                            jQuery('#step_two_progress_circle').addClass('checkout_progress_color_active');
                            jQuery('#step_two_three_progress_circle_line').addClass('checkout_progress_hr_line_active');
                            jQuery('#step_three_progress_circle').addClass('checkout_progress_color_active');
                            // Set tab header styles:
                            jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_inactive_untouched_tab ');
                            jQuery('#checkout_tab_header_shipping').removeClass('checkout_step_active_tab ');
                            jQuery('#checkout_tab_header_shipping').addClass('checkout_step_inactive_tab ');
                            jQuery('#checkout_tab_header_payment').removeClass('checkout_step_inactive_untouched_tab  ');
                            jQuery('#checkout_tab_header_payment').removeClass('checkout_step_inactive_tab ');
                            jQuery('#checkout_tab_header_payment').addClass('checkout_step_active_tab ');
                            jQuery('#checkout_select_store_pickup_container').hide();
                            jQuery('#shipping-method-please-wait-alternate').hide();
                            jQuery('#shipping-method-buttons-container-alternate').css('opacity', 1);
                        }
                        break;
                    case "review":
                        localStorage.newCheckoutProcessCurrentStep = 'opc-review';
                        console.log('opcheckout.js -> Current Active Step = REVIEW');
                        jQuery('#step_one_progress_circle').html('&#10112;');
                        jQuery('#step_one_progress_circle').addClass('checkout_progress_link');
                        jQuery('#step_one_progress_circle').addClass('checkout_progress_color_active');
                        jQuery('#step_one_two_progress_circle_line').addClass('checkout_progress_hr_line_active');
                        jQuery('#step_two_progress_circle').html('&#10113;');
                        jQuery('#step_two_progress_circle').addClass('checkout_progress_link');
                        jQuery('#step_two_progress_circle').addClass('checkout_progress_color_active');
                        jQuery('#step_two_three_progress_circle_line').addClass('checkout_progress_hr_line_active');
                        jQuery('#step_three_progress_circle').html('&#10114;');
                        jQuery('#step_three_progress_circle').addClass('checkout_progress_link');
                        jQuery('#step_three_progress_circle').addClass('checkout_progress_color_active');
                        jQuery('#step_three_four_progress_circle_line').addClass('checkout_progress_hr_line_active');
                        jQuery('#step_four_progress_circle').addClass('checkout_progress_color_active');
                        // Set tab header styles:
                        jQuery('#checkout_tab_header_payment').removeClass('checkout_step_inactive_untouched_tab ');
                        jQuery('#checkout_tab_header_payment').removeClass('checkout_step_active_tab ');
                        jQuery('#checkout_tab_header_payment').addClass('checkout_step_inactive_tab ');
                        jQuery('#checkout_tab_header_review').removeClass('checkout_step_inactive_untouched_tab  ');
                        jQuery('#checkout_tab_header_review').removeClass('checkout_step_inactive_tab ');
                        jQuery('#checkout_tab_header_review').addClass('checkout_step_active_tab ');
                        break;
                }
            }
        } catch(err) {}
        section = $('opc-'+section);
        section.addClassName('allow');
        this.accordion.openSection(section);
    },

    setMethod: function(){
        if ($('login:guest') && $('login:guest').checked) {
            this.method = 'guest';
            var request = new Ajax.Request(
                this.saveMethodUrl,
                {method: 'post', onFailure: this.ajaxFailure.bind(this), parameters: {method:'guest'}}
            );
            Element.hide('register-customer-password');
            this.gotoSection('billing');
        }
        else if($('login:register') && ($('login:register').checked || $('login:register').type == 'hidden')) {
            this.method = 'register';
            var request = new Ajax.Request(
                this.saveMethodUrl,
                {method: 'post', onFailure: this.ajaxFailure.bind(this), parameters: {method:'register'}}
            );
            Element.show('register-customer-password');
            this.gotoSection('billing');
        }
        else{
            alert(Translator.translate('Please choose to register or to checkout as a guest'));
            return false;
        }
    },

    setBilling: function() {
        if (($('billing:use_for_shipping_yes')) && ($('billing:use_for_shipping_yes').checked)) {
            shipping.syncWithBilling();
            $('opc-shipping').addClassName('allow');
            this.gotoSection('payment');
        } else if (($('billing:use_for_shipping_no')) && ($('billing:use_for_shipping_no').checked)) {
            $('shipping:same_as_billing').checked = false;
            this.gotoSection('shipping');
        } else {
            $('shipping:same_as_billing').checked = true;
            this.gotoSection('shipping');
        }

        // this refreshes the checkout progress column
        this.reloadProgressBlock();

//        if ($('billing:use_for_shipping') && $('billing:use_for_shipping').checked){
//            shipping.syncWithBilling();
//            //this.setShipping();
//            //shipping.save();
//            $('opc-shipping').addClassName('allow');
//            this.gotoSection('shipping_method');
//        } else {
//            $('shipping:same_as_billing').checked = false;
//            this.gotoSection('shipping');
//        }
//        this.reloadProgressBlock();
//        //this.accordion.openNextSection(true);
    },

    setShipping: function() {
        this.reloadProgressBlock();
        //this.nextStep();
        this.gotoSection('payment');
        //this.accordion.openNextSection(true);
    },

    setShippingMethod: function() {
        this.reloadProgressBlock();
        //this.nextStep();
        this.gotoSection('payment');
        //this.accordion.openNextSection(true);
    },

    setPayment: function() {
        this.reloadProgressBlock();
        //this.nextStep();
        this.gotoSection('review');
        //this.accordion.openNextSection(true);
    },

    setReview: function() {
        this.reloadProgressBlock();
        //this.nextStep();
        //this.accordion.openNextSection(true);
    },

    back: function(){
        if (this.loadWaiting) return;
        this.accordion.openPrevSection(true);
    },

    setStepResponse: function(response){
        if (response.update_section) {
            $('checkout-'+response.update_section.name+'-load').update(response.update_section.html);
        }
        if (response.allow_sections) {
            response.allow_sections.each(function(e){
                $('opc-'+e).addClassName('allow');
            });
        }

//        if(response.duplicateBillingInfo)
//        {
//            shipping.setSameAsBilling(true);
//        }

        if (response.goto_section) {
            this.reloadProgressBlock();
            if (response.goto_section == 'shipping_method' && localStorage.newCheckoutRunningProcess == 'processCheckoutAsNonLoggedInUser') {
                if (jQuery('#checkout_saveBillingButton').hasClass('checkout_saveBillingButtonAlternate')) {
                    console.log('Special Case #1 (new Customer Application or guest)');

                    jQuery('#checkout_saveShippingMethodButtonAlternate').removeClass('checkout_saveShippingMethodButtonAlternate');
                    jQuery('#checkout_saveShippingMethodButtonAlternate').addClass('checkout_saveShippingMethodButtonAlternatePickup');

                    localStorage.newCheckoutMagicFlag1 = 1;

                    jQuery('#checkout-step-billing').hide();
                    jQuery('#co-billing-form').hide();

                    // Open tab with shipping information, but only showing store pickup selection options:
                    jQuery('#co-shipping-form').hide();

                    if (jQuery('#shipping-form-container #checkout_select_store_pickup_container').length > 0) {
                        console.log('Number of Buttons = ' + jQuery('#shipping-form-container #checkout_select_store_pickup_container').length);
                    } else {
                        console.log('Number of Buttons = 0');
                        jQuery("#checkout_select_store_pickup_container").appendTo("#shipping-form-container");
                    }

                    jQuery('#co-shipping-method-form').hide();
                    jQuery('#checkout-step-shipping').show();
                    jQuery('#shipping-form-container').show();
                    jQuery('#checkout_select_store_pickup').removeClass('hidden');
                    jQuery('#checkout_select_store_pickup_container').show();
                    localStorage.newCheckoutProcessCurrentStep = 'opc-shipping';
                    localStorage.newCheckoutShippingMethod = 'pickup';
                    localStorage.newCheckoutPickupLocation = localStorage.newCheckoutSelectedPickupLocation;
                } else {
                    console.log('gotoSection = payment (1)');
                    localStorage.newCheckoutProcessCurrentStep = 'opc-payment';
                    this.gotoSection('payment');
                    jQuery('#checkout-step-billing').hide();
                    jQuery('#co-billing-form').hide();
                }
            } else {
                if (localStorage.newCheckoutProspectRole == 'prospect-light'
                    && localStorage.newCheckoutProcessCurrentStep == 'opc-shipping_method') {
                    console.log('Alternative route to pickup selection (light prospect 222)');

                    jQuery('#checkout_saveShippingMethodButtonAlternate').removeClass('checkout_saveShippingMethodButtonAlternate');
                    jQuery('#checkout_saveShippingMethodButtonAlternate').addClass('checkout_saveShippingMethodButtonAlternatePickup');

                    localStorage.newCheckoutMagicFlag1 = 1;

                    jQuery('#checkout-step-billing').hide();
                    jQuery('#co-billing-form').hide();

                    // Open tab with shipping information, but only showing store pickup selection options:
                    jQuery('#co-shipping-form').hide();
                    jQuery("#checkout_select_store_pickup_container").appendTo("#shipping-form-container");
                    jQuery('#co-shipping-method-form').hide();
                    jQuery('#checkout-step-shipping').show();
                    jQuery('#shipping-form-container').show();
                    jQuery('#checkout_select_store_pickup').removeClass('hidden');
                    jQuery('#checkout_select_store_pickup_container').show();

                    localStorage.newCheckoutProcessCurrentStep = 'opc-shipping';
                    localStorage.newCheckoutShippingMethod = 'pickup';
                    localStorage.newCheckoutPickupLocation = localStorage.newCheckoutSelectedPickupLocation;
                } else {
                    if (localStorage.newCheckoutProcessCurrentRole == 'guest' && localStorage.newCheckoutMagicFlag2 != 1) {
                        console.log('Alternative route to pickup selection (guest)');

                        jQuery('#checkout_saveShippingMethodButtonAlternate').removeClass('checkout_saveShippingMethodButtonAlternate');
                        jQuery('#checkout_saveShippingMethodButtonAlternate').addClass('checkout_saveShippingMethodButtonAlternatePickup');

                        localStorage.newCheckoutMagicFlag1 = 1;

                        jQuery('#checkout-step-billing').hide();
                        jQuery('#co-billing-form').hide();

                        // Open tab with shipping information, but only showing store pickup selection options:
                        jQuery('#co-shipping-form').hide();
                        jQuery("#checkout_select_store_pickup_container").appendTo("#shipping-form-container");
                        jQuery('#co-shipping-method-form').hide();
                        jQuery('#checkout-step-shipping').show();
                        jQuery('#shipping-form-container').show();
                        jQuery('#checkout_select_store_pickup').removeClass('hidden');
                        jQuery('#checkout_select_store_pickup_container').show();

                        localStorage.newCheckoutProcessCurrentStep = 'opc-shipping';
                        localStorage.newCheckoutShippingMethod = 'pickup';
                        localStorage.newCheckoutPickupLocation = localStorage.newCheckoutSelectedPickupLocation;
                    } else {
                        console.log('Normal Case: gotoSection = ' + response.goto_section);
                        if (response.goto_section == 'payment') {
                            localStorage.newCheckoutProcessCurrentStep = 'opc-payment';
                        }
                        this.gotoSection(response.goto_section);
                    }
                }
            }
            return true;
        }
        if (response.redirect) {
            location.href = response.redirect;
            return true;
        }
        return false;
    }
}

// billing
var Billing = Class.create();
Billing.prototype = {
    initialize: function(form, addressUrl, saveUrl){
        this.form = form;
        if ($(this.form)) {
            $(this.form).observe('submit', function(event){this.save();Event.stop(event);}.bind(this));
        }
        this.addressUrl = addressUrl;
        this.saveUrl = saveUrl;
        this.onAddressLoad = this.fillForm.bindAsEventListener(this);
        this.onSave = this.nextStep.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    setAddress: function(addressId){
        if (addressId) {
            request = new Ajax.Request(
                this.addressUrl+addressId,
                {method:'get', onSuccess: this.onAddressLoad, onFailure: checkout.ajaxFailure.bind(checkout)}
            );
        }
        else {
            this.fillForm(false);
        }
    },

    newAddress: function(isNew){
        if (isNew) {
            this.resetSelectedAddress();
            Element.show('billing-new-address-form');
        } else {
            Element.hide('billing-new-address-form');
        }
    },

    resetSelectedAddress: function(){
        var selectElement = $('billing-address-select')
        if (selectElement) {
            selectElement.value='';
        }
    },

    fillForm: function(transport){
        var elementValues = {};
        if (transport && transport.responseText){
            try{
                elementValues = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                elementValues = {};
            }
        }
        else{
            this.resetSelectedAddress();
        }
        arrElements = Form.getElements(this.form);
        for (var elemIndex in arrElements) {
            if (arrElements[elemIndex].id) {
                var fieldName = arrElements[elemIndex].id.replace(/^billing:/, '');
                arrElements[elemIndex].value = elementValues[fieldName] ? elementValues[fieldName] : '';
                if (fieldName == 'country_id' && billingForm){
                    billingForm.elementChildLoad(arrElements[elemIndex]);
                }
            }
        }
    },

//    setUseForShipping: function(flag) {
//        $('shipping:same_as_billing').checked = flag;
//    },

    save: function(){
        //----------------------------------------------------------------------
        if (checkout.loadWaiting!=false) return;

        var validator = new Validation(this.form);
        console.log('opcheckout.js -> billing.save() -- step #1');
        if (validator.validate()) {
            checkout.setLoadWaiting('billing');

            // Aditional Info: (old prospect or new prospect-application or guest):
            var newCheckoutCustomerType = 'normal';

            if (localStorage.newCheckoutProcessSpecialAction == 'none' &&
                localStorage.newCheckoutProspectRole == 'prospect-full' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsLoggedInUser') {
                newCheckoutCustomerType = 'oldFullProspect';
            }
            if (localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application' &&
                localStorage.newCheckoutProspectRole == 'prospect-light' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsLoggedInUser') {
                newCheckoutCustomerType = 'oldLightProspect';
            }
            if (localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application' &&
                localStorage.newCheckoutProcessCurrentRole == 'prospect-user' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsNonLoggedInUser') {
                newCheckoutCustomerType = 'newProspect';
            }
            if (localStorage.newCheckoutProcessSpecialAction == 'none' &&
                localStorage.newCheckoutProcessCurrentRole == 'guest' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsNonLoggedInUser') {
                newCheckoutCustomerType = 'guest';
            }

            var newCheckoutCustomerTypeParameterURLencoded = '';
            newCheckoutCustomerTypeParameterURLencoded = 'billing%5Bcustomer_type%5D=' + newCheckoutCustomerType + '&';

//            if ($('billing:use_for_shipping') && $('billing:use_for_shipping').checked) {
//                $('billing:use_for_shipping').value=1;
//            }

            console.log('opcheckout.js -> billing.save() -- step #2');

            var request = new Ajax.Request(
                this.saveUrl,
                {
                    method: 'post',
                    onComplete: this.onComplete,
                    onSuccess: this.onSave,
                    onFailure: checkout.ajaxFailure.bind(checkout),
                    parameters: newCheckoutCustomerTypeParameterURLencoded+ Form.serialize(this.form)
                }
            );
        } else {
            jQuery('#checkout_saveBillingButtonContinue').show();
            console.log('opcheckout.js -> billing.save() -- validator.validate() returned false !!!');
        }
    },

    resetLoadWaiting: function(transport){
        checkout.setLoadWaiting(false);
    },

    /**
     This method recieves the AJAX response on success.
     There are 3 options: error, redirect or html with shipping options.
     */
    nextStep: function(transport){
        if (transport && transport.responseText){
            try{
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
        }

        if (response.error){
            if ((typeof response.message) == 'string') {
                alert(response.message);
            } else {
                if (window.billingRegionUpdater) {
                    billingRegionUpdater.update();
                }

                alert(response.message.join("\n"));
            }

            return false;
        } else {
            if (jQuery('.usebilling_address_for_shipping_no').is(':checked')) {
                localStorage.newCheckoutProcessCurrentStep = 'opc-shipping';
            }
            if (jQuery('.usebilling_address_for_shipping_yes').is(':checked')) {
                localStorage.newCheckoutProcessCurrentStep = 'opc-shipping_method';
            }
        }

        checkout.setStepResponse(response);
        payment.initWhatIsCvvListeners();

        // DELETE
        //alert('error: ' + response.error + ' / redirect: ' + response.redirect + ' / shipping_methods_html: ' + response.shipping_methods_html);
        // This moves the accordion panels of one page checkout and updates the checkout progress
        //checkout.setBilling();
    }
}

// shipping
var Shipping = Class.create();
Shipping.prototype = {
    initialize: function(form, addressUrl, saveUrl, methodsUrl){
        this.form = form;
        if ($(this.form)) {
            $(this.form).observe('submit', function(event){this.save();Event.stop(event);}.bind(this));
        }
        this.addressUrl = addressUrl;
        this.saveUrl = saveUrl;
        this.methodsUrl = methodsUrl;
        this.onAddressLoad = this.fillForm.bindAsEventListener(this);
        this.onSave = this.nextStep.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    setAddress: function(addressId){
        if (addressId) {
            request = new Ajax.Request(
                this.addressUrl+addressId,
                {method:'get', onSuccess: this.onAddressLoad, onFailure: checkout.ajaxFailure.bind(checkout)}
            );
        }
        else {
            this.fillForm(false);
        }
    },

    newAddress: function(isNew){
        if (isNew) {
            this.resetSelectedAddress();
            Element.show('shipping-new-address-form');
        } else {
            Element.hide('shipping-new-address-form');
        }
//        shipping.setSameAsBilling(false);
    },

    resetSelectedAddress: function(){
        var selectElement = $('shipping-address-select')
        if (selectElement) {
            selectElement.value='';
        }
    },

    fillForm: function(transport){
        var elementValues = {};
        if (transport && transport.responseText){
            try{
                elementValues = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                elementValues = {};
            }
        }
        else{
            this.resetSelectedAddress();
        }
        arrElements = Form.getElements(this.form);
        for (var elemIndex in arrElements) {
            if (arrElements[elemIndex].id) {
                var fieldName = arrElements[elemIndex].id.replace(/^shipping:/, '');
                arrElements[elemIndex].value = elementValues[fieldName] ? elementValues[fieldName] : '';
                if (fieldName == 'country_id' && shippingForm){
                    shippingForm.elementChildLoad(arrElements[elemIndex]);
                }
            }
        }
    },

//     setSameAsBilling: function(flag) {
//         $('shipping:same_as_billing').checked = flag;
// // #5599. Also it hangs up, if the flag is not false
// //        $('billing:use_for_shipping_yes').checked = flag;
//         if (flag) {
//             this.syncWithBilling();
//         }
//     },

    syncWithBilling: function () {
        $('billing-address-select') && this.newAddress(!$('billing-address-select').value);
        $('shipping:same_as_billing').checked = true;
        if (!$('billing-address-select') || !$('billing-address-select').value) {
            arrElements = Form.getElements(this.form);
            for (var elemIndex in arrElements) {
                if (arrElements[elemIndex].id) {
                    var sourceField = $(arrElements[elemIndex].id.replace(/^shipping:/, 'billing:'));
                    if (sourceField){
                        arrElements[elemIndex].value = sourceField.value;
                    }
                }
            }
            //$('shipping:country_id').value = $('billing:country_id').value;
            shippingRegionUpdater.update();
            $('shipping:region_id').value = $('billing:region_id').value;
            $('shipping:region').value = $('billing:region').value;
            //shippingForm.elementChildLoad($('shipping:country_id'), this.setRegionValue.bind(this));
        } else {
            $('shipping-address-select').value = $('billing-address-select').value;
        }
    },

    setRegionValue: function(){
        $('shipping:region').value = $('billing:region').value;
    },

    save: function(){
        if (checkout.loadWaiting!=false) return;
        var validator = new Validation(this.form);
        if (validator.validate()) {
            // Aditional Info: (old prospect or new prospect-application or guest):
            var newCheckoutCustomerType = 'normal';
            var newCheckoutShippingMethod = '';
            var newCheckoutPickupLocation = '';
            var newCheckoutInpostLocation = '';
            var newCheckoutContainerLocation = '';
            var newCheckoutNewAdress = 'no';
            var oldShippingAddresId = '';

            if (localStorage.newCheckoutProcessSpecialAction == 'none' &&
                localStorage.newCheckoutProspectRole == 'prospect-full' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsLoggedInUser') {
                newCheckoutCustomerType = 'oldFullProspect';
            } else if (localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application' &&
                localStorage.newCheckoutProspectRole == 'prospect-light' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsLoggedInUser') {
                newCheckoutCustomerType = 'oldLightProspect';
            } else if (localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application' &&
                localStorage.newCheckoutProcessCurrentRole == 'prospect-user' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsNonLoggedInUser') {
                newCheckoutCustomerType = 'newProspect';
            } else if (localStorage.newCheckoutProcessSpecialAction == 'none' &&
                localStorage.newCheckoutProcessCurrentRole == 'guest' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsNonLoggedInUser') {
                newCheckoutCustomerType = 'guest';
            }
            switch (localStorage.newCheckoutShippingMethod) {
                case 'pickup':
                    newCheckoutShippingMethod = 'pickup';
                    // Get pickup location from selection:
                    newCheckoutPickupLocation = localStorage.newCheckoutPickupLocation;
                    break;
                case 'container':
                    newCheckoutShippingMethod = 'container';
                    // Get container location from selection:
                    newCheckoutContainerLocation = localStorage.newCheckoutWwsIdContainer;
                    break;
                case 'inpost':
                    newCheckoutShippingMethod = 'inpost';
                    // Get inpost location from selection:
                    newCheckoutInpostLocation = localStorage.newCheckoutWwsIdInpost;
                default:
                    newCheckoutShippingMethod = 'shipping';
                    newCheckoutPickupLocation = 'none';
            }

            // if (localStorage.newCheckoutShippingMethod == 'pickup') {
            //     newCheckoutShippingMethod = 'pickup';
            //     // Get pickup location from selection:
            //     newCheckoutPickupLocation = localStorage.newCheckoutPickupLocation;
            // }
            // if (localStorage.newCheckoutShippingMethod == 'container') {
            //     newCheckoutShippingMethod = 'container';
            //     // Get container location from selection:
            //     newCheckoutContainerLocation = localStorage.newCheckoutWwsIdContainer;
            // } else {
            //     newCheckoutShippingMethod = 'shipping';
            //     newCheckoutPickupLocation = 'none';
            // }

            if ((jQuery('#shipping-address-select') && jQuery('#shipping-address-select').val() == '') || typeof jQuery('#shipping-address-select').val() === 'undefined') {
                newCheckoutNewAdress = 'yes';
            } else {
                oldShippingAddresId = 'shipping%5Bold_address_id%5D=' + jQuery('#shipping-address-select').val() + '&';
            }

            var newCheckoutCustomerTypeParameterURLencoded = '';
            newCheckoutCustomerTypeParameterURLencoded = oldShippingAddresId + 'shipping%5Bnew_address%5D=' + newCheckoutNewAdress + '&' +
                'shipping%5Bcustomer_type%5D=' + newCheckoutCustomerType + '&' + 'shipping%5Bshipping_method%5D=' + newCheckoutShippingMethod + '&' +
                'shipping%5Bpickup_location%5D=' + newCheckoutPickupLocation + '&' + 'container_id=' + newCheckoutContainerLocation + '&' + 'inpost_id=' + newCheckoutInpostLocation + '&';

            checkout.setLoadWaiting('shipping');
            var request = new Ajax.Request(
                this.saveUrl,
                {
                    method:'post',
                    onComplete: this.onComplete,
                    onSuccess: this.onSave,
                    onFailure: checkout.ajaxFailure.bind(checkout),
                    parameters: newCheckoutCustomerTypeParameterURLencoded + Form.serialize(this.form)
                }
            );
        }
    },

    resetLoadWaiting: function(transport){
        checkout.setLoadWaiting(false);
    },

    nextStep: function(transport){
        if (transport && transport.responseText){
            try{
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
        }
        if (response.error){
            if ((typeof response.message) == 'string') {
                alert(response.message);
            } else {
                if (window.shippingRegionUpdater) {
                    shippingRegionUpdater.update();
                }
                alert(response.message.join("\n"));
            }

            return false;
        }

        checkout.setStepResponse(response);

        /*
        var updater = new Ajax.Updater(
            'checkout-shipping-method-load',
            this.methodsUrl,
            {method:'get', onSuccess: checkout.setShipping.bind(checkout)}
        );
        */
        //checkout.setShipping();
    }
}

// shipping method
var ShippingMethod = Class.create();
var shippingMethodCheck = localStorage.shippingMethodCheck;

ShippingMethod.prototype = {
    initialize: function(form, saveUrl){
        this.form = form;
        if ($(this.form)) {
            $(this.form).observe('submit', function(event){this.save();Event.stop(event);}.bind(this));
        }
        this.saveUrl = saveUrl;
        this.validator = new Validation(this.form);
        this.onSave = this.nextStep.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    validate: function() {
        var methods = document.getElementsByName('shipping_method[type]');
        if (methods.length==0) {
            alert(Translator.translate('Your order can not be completed at this time as there is no shipping methods available for it. Please make neccessary changes in your shipping address.'));
            return false;
        }

        if(!this.validator.validate()) {
            return false;
        }

        for (var i=0; i<methods.length; i++) {
            if (methods[i].checked) {
                return true;
            }
        }
        if (shippingMethodCheck == 'delivery' || shippingMethodCheck == 'pickup' || shippingMethodCheck == 'container' || shippingMethodCheck == 'inpost') {
            console.log('Opcheckout -> shippingMethodCheck if ture= ' + shippingMethodCheck);
            return true;
        } else {
            console.log('Opcheckout -> shippingMethodCheck if false= ' + shippingMethodCheck);
            jQuery('#shipping-method-warning').text(Translator.translate('Please specify shipping method.'));
            jQuery('#shipping-method-warning').show();
            jQuery('#shipping-method-warning').fadeOut(5000);
            localStorage.newCheckoutProcessCurrentStep = 'opc-shipping_method';
            console.log('Hier ist der fehler!');
            jQuery('#checkout_saveShippingMethodButtonContinue').show();
            // jQuery('#checkout_saveShippingMethodButtonContinue').text('Continue');


            return false;
        }
    },

    save: function(){
        console.log('opcheckout.js -> shippingMethod.save() -- step #1');
        if (checkout.loadWaiting != false) return;
        var resultValidation = false;

        if (localStorage.newCheckoutProcessCurrentRole == 'guest' && localStorage.newCheckoutMagicFlag1 == 1) {
            console.log("opcheckout.js -> role = guest + magicFlag = 1");
            resultValidation = true;
        } else {
            console.log("opcheckout.js -> must be validated to shippingMethod");
            resultValidation = this.validate();
        }
        if (resultValidation) {
            checkout.setLoadWaiting('shipping-method');
            if (typeof(ga) !== 'undefined' && ga) {
                ga('ec:setAction', 'checkout_option', {
                    'step': 3,
                    'option': jQuery("label[for='" + jQuery('input[name="shipping_method[type]"]:checked', '#' + this.form).attr('id')+"']").text()
                });
                ga('send', 'event', 'Checkout', 'Option');
            }

            // Aditional Info: (old prospect or new prospect-application or guest):
            var newCheckoutCustomerType = 'normal';

            if (localStorage.newCheckoutProcessSpecialAction == 'none' &&
                localStorage.newCheckoutProspectRole == 'prospect-full' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsLoggedInUser') {
                newCheckoutCustomerType = 'oldFullProspect';
            }
            if (localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application' &&
                localStorage.newCheckoutProspectRole == 'prospect-light' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsLoggedInUser') {
                newCheckoutCustomerType = 'oldLightProspect';
            }
            if (localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application' &&
                localStorage.newCheckoutProcessCurrentRole == 'prospect-user' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsNonLoggedInUser') {
                newCheckoutCustomerType = 'newProspect';
            }
            if (localStorage.newCheckoutProcessSpecialAction == 'none' &&
                localStorage.newCheckoutProcessCurrentRole == 'guest' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsNonLoggedInUser') {
                newCheckoutCustomerType = 'guest';
            }

            var newCheckoutCustomerTypeParameterURLencoded = '';
            newCheckoutCustomerTypeParameterURLencoded = 'shipping_method%5Bcustomer_type%5D=' + newCheckoutCustomerType + '&';

            var request = new Ajax.Request(
                this.saveUrl,
                {
                    method:'post',
                    onComplete: this.onComplete,
                    onSuccess: this.onSave,
                    onFailure: checkout.ajaxFailure.bind(checkout),
                    parameters: newCheckoutCustomerTypeParameterURLencoded + Form.serialize(this.form)
                }
            );
        }
    },

    resetLoadWaiting: function(transport){
        checkout.setLoadWaiting(false);
    },

    nextStep: function(transport){
        if (transport && transport.responseText){
            try{
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
        }

        if (response.error) {
            alert(response.message);
            return false;
        }

        if (response.update_section) {
            $('checkout-'+response.update_section.name+'-load').update(response.update_section.html);
            response.update_section.html.evalScripts();
        }

        payment.initWhatIsCvvListeners();

        if (response.goto_section) {
            checkout.gotoSection(response.goto_section);
            checkout.reloadProgressBlock();
            return;
        }

        if (response.payment_methods_html) {
            $('checkout-payment-method-load').update(response.payment_methods_html);
        }

        checkout.setShippingMethod();
    }
}


// payment
var Payment = Class.create();
Payment.prototype = {
    beforeInitFunc:$H({}),
    afterInitFunc:$H({}),
    beforeValidateFunc:$H({}),
    afterValidateFunc:$H({}),
    initialize: function(form, saveUrl){
        this.form = form;
        this.saveUrl = saveUrl;
        this.onSave = this.nextStep.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    addBeforeInitFunction : function(code, func) {
        this.beforeInitFunc.set(code, func);
    },

    beforeInit : function() {
        (this.beforeInitFunc).each(function(init){
            (init.value)();
        });
    },

    init : function () {
        this.beforeInit();
        var elements = Form.getElements(this.form);
        if ($(this.form)) {
            $(this.form).observe('submit', function(event){this.save();Event.stop(event);}.bind(this));
        }
        var method = null;
        for (var i=0; i<elements.length; i++) {
            if (elements[i].name=='payment[method]' || elements[i].name == 'form_key') {
                if (elements[i].checked) {
                    method = elements[i].value;
                }
            } else if (elements[i].name!='payment[schrack_custom_order_number]') {
                elements[i].disabled = true;
            }
            elements[i].setAttribute('autocomplete','off');
        }
        if (method) this.switchMethod(method);
        this.afterInit();
    },

    addAfterInitFunction : function(code, func) {
        this.afterInitFunc.set(code, func);
    },

    afterInit : function() {
        (this.afterInitFunc).each(function(init){
            (init.value)();
        });
    },

    switchMethod: function(method){
        if (this.currentMethod && $('payment_form_'+this.currentMethod)) {
            var form = $('payment_form_'+this.currentMethod);
            form.style.display = 'none';
            var elements = form.select('input', 'select', 'textarea');
            for (var i=0; i<elements.length; i++) elements[i].disabled = true;
        }
        if ($('payment_form_'+method)){
            var form = $('payment_form_'+method);
            form.style.display = '';
            var elements = form.select('input', 'select', 'textarea');
            for (var i=0; i<elements.length; i++) elements[i].disabled = false;
        }
        this.currentMethod = method;
        jQuery(document).ready(function() {
            var schrackCustomerOrderNumber = jQuery('payment-schrack-custom-order-number');
            if (schrackCustomerOrderNumber) {
                schrackCustomerOrderNumber.disabled = false;
            }
        });
    },

    addBeforeValidateFunction : function(code, func) {
        this.beforeValidateFunc.set(code, func);
    },

    beforeValidate : function() {
        var validateResult = true;
        var hasValidation = false;
        (this.beforeValidateFunc).each(function(validate){
            hasValidation = true;
            if ((validate.value)() == false) {
                validateResult = false;
            }
        }.bind(this));
        if (!hasValidation) {
            validateResult = false;
        }
        return validateResult;
    },

    validate: function() {
        var result = this.beforeValidate();
        if (result) {
            return true;
        }
        var methods = document.getElementsByName('payment[method]');
        if (methods.length==0) {
            alert(Translator.translate('Your order cannot be completed at this time as there is no payment methods available for it.'));
            return false;
        }
        for (var i=0; i<methods.length; i++) {
            if (methods[i].checked) {
                return true;
            }
        }
        result = this.afterValidate();
        if (result) {
            return true;
        }
        //alert(Translator.translate('Please specify payment method.'));
        console.log(Translator.translate('Please specify payment method.'));
        return false;
    },

    addAfterValidateFunction : function(code, func) {
        this.afterValidateFunc.set(code, func);
    },

    afterValidate : function() {
        var validateResult = true;
        var hasValidation = false;
        (this.afterValidateFunc).each(function(validate){
            hasValidation = true;
            if ((validate.value)() == false) {
                validateResult = false;
            }
        }.bind(this));
        if (!hasValidation) {
            validateResult = false;
        }
        return validateResult;
    },

    save: function(){
        console.log('skin/frontend/schrack/default/schrackdesign/Public/Javascript/opcheckout.js' + '  -> payment.save()');
        if (checkout.loadWaiting!=false) return;
        var validator = new Validation(this.form);
        if (this.validate() && validator.validate()) {
            checkout.setLoadWaiting('payment');
            if (typeof(ga) !== 'undefined' && ga) {
                ga('ec:setAction', 'checkout_option', {
                    'step': 4,
                    'option': jQuery("label[for='"+jQuery('input[name=payment\\[method\\]]:checked', '#' + this.form).attr('id')+"']").text()
                });
                ga('send', 'event', 'Checkout', 'Option');
            }
            // Aditional Info: (old prospect or new prospect-application or guest):
            var newCheckoutCustomerType = 'payment%5Bcustomer_type%5D=normal';
            var newCheckoutVATNumberParameterURLencoded = '';
            var newLocalVAT = '';
            var newCheckoutCustomerTypeParameterURLencoded = '';
            var newCheckoutCompanyRegistrationNumberParameterURLencoded = '';
            var newCheckoutStreet1 = '';

            if (localStorage.newCheckoutLocalVAT != '') {
                newLocalVAT = 'payment%5Blocal_vat%5D=' + localStorage.newCheckoutLocalVAT + '&';
            }

            if (localStorage.newCheckoutProcessCompanyRegistrationNumber != '') {
                newCheckoutCompanyRegistrationNumberParameterURLencoded = 'payment%5Breg_number%5D=' + localStorage.newCheckoutProcessCompanyRegistrationNumber + '&';
            }

            if (localStorage.newCheckoutProcessVATIdentificationNumber != '') {
                newCheckoutVATNumberParameterURLencoded = 'payment%5Bvat_number%5D=' + localStorage.newCheckoutProcessVATIdentificationNumber + '&';
            }

            if (localStorage.newCheckoutProcessStreet1 != '') {
                newCheckoutStreet1 = 'payment%5Bstreet1%5D=' + localStorage.newCheckoutProcessStreet1 + '&';
            }

            if (localStorage.newCheckoutProcessSpecialAction == 'none' &&
                localStorage.newCheckoutProspectRole == 'prospect-full' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsLoggedInUser') {
                newCheckoutCustomerType = 'payment%5Bcustomer_type%5D=oldFullProspect';
            }
            if (localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application' &&
                localStorage.newCheckoutProspectRole == 'prospect-light' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsLoggedInUser') {
                newCheckoutCustomerType = 'payment%5Bcustomer_type%5D=oldLightProspect';
            }
            if (localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application' &&
                localStorage.newCheckoutProcessCurrentRole == 'prospect-user' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsNonLoggedInUser') {
                newCheckoutCustomerType = 'payment%5Bcustomer_type%5D=newProspect';
            }
            if (localStorage.newCheckoutProcessSpecialAction == 'none' &&
                localStorage.newCheckoutProcessCurrentRole == 'guest' &&
                localStorage.newCheckoutRunningProcess == 'processCheckoutAsNonLoggedInUser') {
                newCheckoutCustomerType = 'payment%5Bcustomer_type%5D=guest';
            }

            newCheckoutCustomerTypeParameterURLencoded = newLocalVAT + newCheckoutCompanyRegistrationNumberParameterURLencoded + newCheckoutVATNumberParameterURLencoded + newCheckoutStreet1 + newCheckoutCustomerType;

            var request = new Ajax.Request(
                this.saveUrl,
                {
                    method:'post',
                    onComplete: this.onComplete,
                    onSuccess: this.onSave,
                    onFailure: checkout.ajaxFailure.bind(checkout),
                    parameters: newCheckoutCustomerTypeParameterURLencoded + '&' + Form.serialize(this.form)
                }
            );
        }
    },

    resetLoadWaiting: function(){
        checkout.setLoadWaiting(false);
    },

    nextStep: function(transport){
        if (transport && transport.responseText){
            try{
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
        }
        /*
        * if there is an error in payment, need to show error message
        */
        if (response.error) {
            if (response.fields) {
                var fields = response.fields.split(',');
                for (var i=0;i<fields.length;i++) {
                    var field = null;
                    if (field = $(fields[i])) {
                        Validation.ajaxError(field, response.error);
                    }
                }
                return;
            }
            alert(response.error);
            return;
        }

        localStorage.newCheckoutProcessCurrentStep = 'opc-review';
        checkout.setStepResponse(response);

        //checkout.setPayment();
    },

    initWhatIsCvvListeners: function(){
        $$('.cvv-what-is-this').each(function(element){
            Event.observe(element, 'click', toggleToolTip);
        });
    }
}

var Review = Class.create();
Review.prototype = {
    initialize: function(saveUrl, successUrl, agreementsForm){
        console.log("saveUrl = " + saveUrl);
        this.saveUrl = saveUrl;
        this.successUrl = successUrl;
        this.agreementsForm = agreementsForm;
        this.onSave = this.nextStep.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    save: function(){

        if (checkout.loadWaiting!=false) return;
        checkout.setLoadWaiting('review');
        if (typeof(ga) !== 'undefined' && ga) {
            ga('ec:setAction', 'checkout_option', {
                'step': 5
            });
            ga('send', 'event', 'Forms', 'submit', 'checkout_done');
        }
        var params = [];
        if (typeof(payment) !== 'undefined') {
            params = Form.serialize(payment.form);
        }
        if (this.agreementsForm) {
            params += '&'+Form.serialize(this.agreementsForm);
        }

        // Aditional Info: (old prospect or new prospect-application or guest):
        var newCheckoutCustomerType = 'normal';

        if (localStorage.newCheckoutProcessSpecialAction == 'none' &&
            localStorage.newCheckoutProspectRole == 'prospect-full' &&
            localStorage.newCheckoutRunningProcess == 'processCheckoutAsLoggedInUser') {
            newCheckoutCustomerType = 'oldFullProspect';
        }
        if (localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application' &&
            localStorage.newCheckoutProspectRole == 'prospect-light' &&
            localStorage.newCheckoutRunningProcess == 'processCheckoutAsLoggedInUser') {
            newCheckoutCustomerType = 'oldLightProspect';
        }
        if (localStorage.newCheckoutProcessSpecialAction == 'full-register-prospect-application' &&
            localStorage.newCheckoutProcessCurrentRole == 'prospect-user' &&
            localStorage.newCheckoutRunningProcess == 'processCheckoutAsNonLoggedInUser') {
            newCheckoutCustomerType = 'newProspect';
        }
        if (localStorage.newCheckoutProcessSpecialAction == 'none' &&
            localStorage.newCheckoutProcessCurrentRole == 'guest' &&
            localStorage.newCheckoutRunningProcess == 'processCheckoutAsNonLoggedInUser') {
            newCheckoutCustomerType = 'guest';
        }

        var newCheckoutProcessGender                                = '';
        var newCheckoutProcessEmail                                 = '';
        var newCheckoutProcessPassword                              = '';
        var newCheckoutCustomerTypeParameterURLencoded              = '';
        var newCheckoutVATNumberParameterURLencoded                 = '';
        var newCheckoutCompanyRegistrationNumberParameterURLencoded = '';
        var newCheckoutLastname                                     = '';
        var newCheckoutFirstname                                    = '';
        var newCheckoutName1                                        = '';
        var newCheckoutName2                                        = '';
        var newCheckoutName3                                        = '';
        var newCheckoutStreet1                                      = '';
        var newCheckoutPostcode                                     = '';
        var newCheckoutCity                                         = '';
        var newCheckoutRegionId                                     = '';
        var newCheckoutCountryId                                    = '';
        var newCheckoutHomepage                                     = '';
        var newCheckoutTelephone                                    = '';
        var newCheckoutFax                                          = '';
        var newCheckoutTelephoneCompany                             = '';
        var newCheckoutNewsletter                                   = '';
        var newLocalVAT                                             = '';
        var newCheckoutCollectedParams                              = '';

        if (localStorage.newCheckoutProcessEmail != '') {
            newCheckoutProcessEmail = 'order%5Bemail%5D=' + localStorage.newCheckoutProcessEmail + '&';
        }
        if (localStorage.newCheckoutProcessPassword != '') {
            newCheckoutProcessPassword = 'order%5Bpassword%5D=' + localStorage.newCheckoutProcessPassword + '&';
        }
        if (localStorage.newCheckoutProcessGender != '') {
            newCheckoutProcessGender = 'order%5Bgender%5D=' + localStorage.newCheckoutProcessGender + '&';
        }

        if (localStorage.newCheckoutProcessVATIdentificationNumber != '') {
            newCheckoutVATNumberParameterURLencoded = 'order%5Bvat_number%5D=' + localStorage.newCheckoutProcessVATIdentificationNumber + '&';
        }

        if (localStorage.newCheckoutProcessCompanyRegistrationNumber != '') {
            newCheckoutCompanyRegistrationNumberParameterURLencoded = 'order%5Breg_number%5D=' + localStorage.newCheckoutProcessCompanyRegistrationNumber + '&';
        }

        if (localStorage.newCheckoutProcessPersonFirstname != '') {
            newCheckoutFirstname = 'order%5Bfirstname%5D=' + localStorage.newCheckoutProcessPersonFirstname + '&';
        }

        if (localStorage.newCheckoutProcessPersonLastname != '') {
            newCheckoutLastname = 'order%5Blastname%5D=' + localStorage.newCheckoutProcessPersonLastname + '&';
        }

        if (localStorage.newCheckoutProcessName1 != '') {
            newCheckoutName1 = 'order%5Bname1%5D=' + localStorage.newCheckoutProcessName1 + '&';
        }

        if (localStorage.newCheckoutProcessName2 != '') {
            newCheckoutName2 = 'order%5Bname2%5D=' + localStorage.newCheckoutProcessName2 + '&';
        }

        if (localStorage.newCheckoutProcessName3 != '') {
            newCheckoutName3 = 'order%5Bname3%5D=' + localStorage.newCheckoutProcessName3 + '&';
        }

        if (localStorage.newCheckoutProcessStreet1 != '') {
            newCheckoutStreet1 = 'order%5Bstreet1%5D=' + localStorage.newCheckoutProcessStreet1 + '&';
        }

        if (localStorage.newCheckoutProcessPostcode != '') {
            newCheckoutPostcode = 'order%5Bpostcode%5D=' + localStorage.newCheckoutProcessPostcode + '&';
        }

        if (localStorage.newCheckoutProcessCity != '') {
            newCheckoutCity = 'order%5Bcity%5D=' + localStorage.newCheckoutProcessCity + '&';
        }

        if (localStorage.newCheckoutProcessRegionId != '') {
            newCheckoutRegionId = 'order%5Bregion_id%5D=' + localStorage.newCheckoutProcessRegionId + '&';
        }

        if (localStorage.newCheckoutProcessCountryId != '') {
            newCheckoutCountryId = 'order%5Bcountry_id%5D=' + localStorage.newCheckoutProcessCountryId + '&';
        }

        if (localStorage.newCheckoutHomepage != '') {
            newCheckoutHomepage = 'order%5Bhomepage%5D=' + localStorage.newCheckoutHomepage + '&';
        }

        if (localStorage.newCheckoutProcessTelephone != '') {
            newCheckoutTelephone = 'order%5Btelephone%5D=' + encodeURIComponent(localStorage.newCheckoutProcessTelephone) + '&';
        }

        if (localStorage.newCheckoutProcessTelephoneCompany != '') {
            newCheckoutTelephoneCompany = 'order%5Btelephone_company%5D=' + encodeURIComponent(localStorage.newCheckoutProcessTelephoneCompany) + '&';
        }

        if (localStorage.newCheckoutProcessFax != '') {
            newCheckoutFax = 'order%5Bfax%5D=' + encodeURIComponent(localStorage.newCheckoutProcessFax) + '&';
        }

        if (localStorage.newCheckoutNewsletter != '') {
            newCheckoutNewsletter = 'order%5Bnewsletter%5D=' + localStorage.newCheckoutNewsletter + '&';
        }

            if (localStorage.newCheckoutLocalVAT != '') {
            newLocalVAT = 'order%5Blocal_vat%5D=' + localStorage.newCheckoutLocalVAT + '&';
        }

        newCheckoutCustomerTypeParameterURLencoded = 'order%5Bcustomer_type%5D=' + newCheckoutCustomerType + '&';

        newCheckoutCollectedParams =
            newCheckoutVATNumberParameterURLencoded
            + newCheckoutCompanyRegistrationNumberParameterURLencoded
            + newCheckoutCustomerTypeParameterURLencoded
            + newCheckoutProcessGender
            + newCheckoutProcessEmail
            + newCheckoutProcessPassword
            + newCheckoutFirstname
            + newCheckoutLastname
            + newCheckoutName1
            + newCheckoutName2
            + newCheckoutName3
            + newCheckoutStreet1
            + newCheckoutPostcode
            + newCheckoutCity
            + newCheckoutRegionId
            + newCheckoutCountryId
            + newCheckoutHomepage
            + newCheckoutTelephone
            + newCheckoutTelephoneCompany
            + newCheckoutFax
            + newCheckoutNewsletter
            + newLocalVAT;

        params.save = true;
        var request = new Ajax.Request(
            this.saveUrl,
            {
                method:'post',
                parameters: newCheckoutCollectedParams + params,
                onComplete: this.onComplete,
                onSuccess: this.onSave,
                onFailure: checkout.ajaxFailure.bind(checkout)
            }
        );
    },

    resetLoadWaiting: function(transport){
        checkout.setLoadWaiting(false, this.isSuccess);
    },

    nextStep: function(transport){
        if (transport && transport.responseText) {
            try{
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
            if (response.redirect) {
                location.href = response.redirect;
                return;
            }
            if (response.success) {
                this.isSuccess = true;
                window.location=this.successUrl;
            } else {
                var msg = response.error_messages;
                if (typeof(msg)=='object') {
                    msg = msg.join("\n");
                }
                //alert(msg);
                console.log(msg);
                jQuery('#review-warning').text(msg);
                jQuery('#review-warning').show();
                jQuery('#review-warning').fadeOut(5000);
            }

            if (response.update_section) {
                $('checkout-'+response.update_section.name+'-load').update(response.update_section.html);
                response.update_section.html.evalScripts();
            }

            if (response.goto_section) {
                checkout.gotoSection(response.goto_section);
                checkout.reloadProgressBlock();
            }
        }
    },

    isSuccess: false
};


// address
var Address = Class.create();
Address.prototype = {
    beforeInitFunc:$H({}),
    afterInitFunc:$H({}),
    beforeValidateFunc:$H({}),
    afterValidateFunc:$H({}),
    initialize: function(form, saveUrl){
        this.form = form;
        this.saveUrl = saveUrl;
        this.onSave = this.nextStep.bindAsEventListener(this);
        this.onComplete = this.resetLoadWaiting.bindAsEventListener(this);
    },

    addBeforeInitFunction : function(code, func) {
        this.beforeInitFunc.set(code, func);
    },

    beforeInit : function() {
        (this.beforeInitFunc).each(function(init){
            (init.value)();
        });
    },

    init : function () {
        this.beforeInit();
        var elements = Form.getElements(this.form);
        if ($(this.form)) {
            $(this.form).observe('submit', function(event){this.save();Event.stop(event);}.bind(this));
        }
        this.afterInit();
    },

    addAfterInitFunction : function(code, func) {
        this.afterInitFunc.set(code, func);
    },

    afterInit : function() {
        (this.afterInitFunc).each(function(init){
            (init.value)();
        });
    },

    addBeforeValidateFunction : function(code, func) {
        this.beforeValidateFunc.set(code, func);
    },

    beforeValidate : function() {
        var validateResult = true;
        var hasValidation = false;
        (this.beforeValidateFunc).each(function(validate){
            hasValidation = true;
            if ((validate.value)() == false) {
                validateResult = false;
            }
        }.bind(this));
        if (!hasValidation) {
            validateResult = false;
        }
        return validateResult;
    },

    validate: function() {
        var result = this.beforeValidate();
        if (result) {
            return true;
        }
        return true;
    },

    addAfterValidateFunction : function(code, func) {
        this.afterValidateFunc.set(code, func);
    },

    afterValidate : function() {
        var validateResult = true;
        var hasValidation = false;
        (this.afterValidateFunc).each(function(validate){
            hasValidation = true;
            if ((validate.value)() == false) {
                validateResult = false;
            }
        }.bind(this));
        if (!hasValidation) {
            validateResult = false;
        }
        return validateResult;
    },

    save: function(){
        if (checkout.loadWaiting!=false) return;
        var validator = new Validation(this.form);
        if (this.validate() && validator.validate()) {
            checkout.setLoadWaiting('address');
            var request = new Ajax.Request(
                this.saveUrl,
                {
                    method:'post',
                    onComplete: this.onComplete,
                    onSuccess: this.onSave,
                    onFailure: checkout.ajaxFailure.bind(checkout),
                    parameters: Form.serialize(this.form)
                }
            );
        }
    },

    resetLoadWaiting: function(){
        checkout.setLoadWaiting(false);
    },

    nextStep: function(transport){
        if (transport && transport.responseText){
            try{
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
        }
        /*
        * if there is an error in payment, need to show error message
        */
        if (response.error) {
            if (response.fields) {
                var fields = response.fields.split(',');
                for (var i=0;i<fields.length;i++) {
                    var field = null;
                    if (field = $(fields[i])) {
                        Validation.ajaxError(field, response.error);
                    }
                }
                return;
            }
            alert('Error ' + response.error_messages);
            return;
        }

        checkout.setStepResponse(response);

        //checkout.setPayment();
    },

    initWhatIsCvvListeners: function(){
        $$('.cvv-what-is-this').each(function(element){
            Event.observe(element, 'click', toggleToolTip);
        });
    }
}
