var d = new Date();
var s = '';
var e = '';
var loading = false;
var firstLoadForLoadMore = true;
var dataArray = {};
var filter = false;
var needToUpdateBreadcrumbs = false;
var defaultFilterOpen = true;
var urlappend = '';
var filterTabState = {};
var filterCheckedState = [];
var filterArray = {};
var generalFilterArray = {};
var tempArray = {};
var tempFilterState ={};
var previousCatId = '';
var fq = {};
var filters = {};
var topfilters = {};
var templist = [];
var toggleFilterState = 'topFilter';
var touchDisableForDesktop = true;
var searchDefaultText = '';
var availabilityInfo = [];
var priceInfo = [];
var agentTitle = agentFN = agentLN = agentMail = agentTel = agentMobile = agentFax = agentImgUrl = partListData = customerId = customerName = customerImage = customerAclRole = siteBaseUrlJs = '';
var agentOneImageUrl = '';
var agentOneName = '';
var agentOneTitle = '';
var agentOneMail = '';
var agentOnePhone = '';
var agentOneMobile = '';
var agentOneFax = '';
var agentOneBranch = '';

var agentTwoImageUrl = '';
var agentTwoName = '';
var agentTwoTitle = '';
var agentTwoMail = '';
var agentTwoPhone = '';
var agentTwoMobile = '';
var agentTwoFax = '';
var agentTwoBranch = '';

var agentThreeImageUrl = '';
var agentThreeName = '';
var agentThreeTitle = '';
var agentThreeMail = '';
var agentThreePhone = '';
var agentThreeMobile = '';
var agentThreeFax = '';
var agentThreeBranch = '';

var dashboardDeskTabMobPageSize = 15;
var dashFilterTempList =  '';

var __suppressAjaxDispatcherCalls = false;

var shopCountry = localStorage.getItem("actualShopCountry") !== null ? localStorage.getItem("actualShopCountry") : 'UNKNOWN';

//-------------------------------------------- Act as customer translations init
var aac_AddFavouriteUrl = '';
var actAsCustomerBtnLoginTxt = '';
var actAsCustomerBtnCancelTxt = '';
var actAsCustomerSearchHeadline = '';
var actAsCustomerSearchbarHeadline = '';
var actAsCustomerSearchbarPlaceholder = '';
var actAsCustomerFavouritesHeadline = '';
var actAsCustomerMyCustomersOnlyTxt = '';
var actAsCustomerSearchMyCustomersOnlyTxt = '';
var actAsCustomerNoFavouritesDefinedTxt = '';
var actAsCustomerLoginWithUserTxt = '';
var actAsCustomerNotSupervisedTxt = '';
var actAsCustomerAddAllToFavouritListTxt = '';
var actAsCustomerAddToFavouritesOkMsgTxt = '';
var actAsCustomerNoneAddToFavouritesMsgTxt = '';
var actAsCustomerNoListAddableToFavouritesMsgTxt = '';
//------------------------------------------------------------------ REGRUNTTEST
var startrek = 'wrathofkahn';
//------------------------------------------------------------------------------
var actAsCustomerActionMsgInterval = '';
//-------------------------------------------------- Actual session user's email
var aac_realEmail = '';
//------------------------------------------------------- actAsCustomerLoginForm
var actAsCustomerLoginForm = '';
//-------------------------------- Visual "Act as customer" favourite list items
var aac_FavListItems = '';
//--------------------------------- "Act as Customer" Searchbar Input validation
function isNumber(inputCharacter) {
    return /^-?[\d.]+(?:e-?\d+)?$/.test(inputCharacter);
}

function refreshBreadcrumbOnSale(gRCB) { // refreshBreadcrumbOnSale
    /**************************************************************** PARAM INFO
     [gRCB:boolean]:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

         gRCB = true/false;  * Is used as switch to avoid double call of
                               getRenderedCategoryBlocks

     ---------------------------------------------------------------------------
     **************************************************************************/
    //------------- updating breadcrumb on selecting sales products in main menu
    //------------------------------------------ get filter information from url
    checkFilterFromUrl();
    //------------------------------------------------- get category id from url
    var catId = getParameterByName('cat');
    //-------------------------------------- check if sales products ar selected
    var saleLi = "<li class='sale_red_breadcrumbitem'>" + Translator.translate('sales') + "</li>";
    if (generalFilterArray['sale'] == 1) {
        if(gRCB) {
            needToUpdateBreadcrumbs = true;
            console.log("refreshBreadcrumbOnSale getRCB !");
            dataArray.getRenderedCategoryBlocks = {
                'data': {
                    'query': '',
                    'start': 0,
                    'limit': 50,
                    'accessory': 0,
                    'category': catId,
                    'facets': filterArray,
                    'general_filters': generalFilterArray
                }
            };
        }
        if (!jQuery("#breadcrumb_block").children(".sale_red_breadcrumbitem").get(0)) {
            jQuery("#breadcrumb_block").append(saleLi);
        }
    } else {
        if (jQuery("#breadcrumb_block").children(".sale_red_breadcrumbitem").get(0)) {
            jQuery("#breadcrumb_block .sale_red_breadcrumbitem").remove();
        }
    }
} //========================================== refreshBreadcrumbOnSale ***END***

//var siteBaseUrlJs = window.location.origin;
if (!window.location.origin) {
    siteBaseUrlJs = window.location.origin = window.location.protocol + "//" + window.location.hostname + (window.location.port ? ':' + window.location.port : '');
}
if (jQuery(window).width() > 992) {
    touchDisableForDesktop = false;
}

setTimeout(function() {
    globalFRESH_LOAD = false;
}, 4000);

jQuery(document).ready(function () {
    //----------------------------------------- Act as customer translations set
    actAsCustomerBtnLoginTxt = Translator.translate('Login');
    actAsCustomerBtnCancelTxt = Translator.translate('Cancel');
    actAsCustomerSearchHeadline = Translator.translate('Customer Search');
    actAsCustomerSearchbarHeadline = Translator.translate('Act As Customer');
    actAsCustomerSearchbarPlaceholder = Translator.translate('Search for customer name or number');
    actAsCustomerFavouritesHeadline = Translator.translate('Favourites');
    actAsCustomerMyCustomersOnlyTxt = Translator.translate('Only Show My Contacts');
    actAsCustomerSearchMyCustomersOnlyTxt = Translator.translate('Search my customers only');
    actAsCustomerNoFavouritesDefinedTxt = Translator.translate('Actually you have no favourites defined. If you´d like to define one, use the [+] icon in the result list of the customer search.');
    actAsCustomerLoginWithUserTxt = Translator.translate('Login with selected user?');
    actAsCustomerNotSupervisedTxt = Translator.translate('not supervised');
    actAsCustomerAddAllToFavouritListTxt  = Translator.translate('Add all customers to favourites');
    actAsCustomerAddToFavouritesOkMsgTxt = Translator.translate('Customer(s) added to favourites');
    actAsCustomerNoneAddToFavouritesMsgTxt = Translator.translate("Customer(s) allready in favourites");
    actAsCustomerNoListAddableToFavouritesMsgTxt = Translator.translate("Adding the complete result list to favourites can only be done up to an limit of 25 entries");

    function openAccordionElement(elementId) {
        console.log(elementId);
        var accordionToOpen = jQuery('#toggle-content-' + elementId);
        if (accordionToOpen) {
            accordionToOpen.collapse('show');
        }
    }

    // Automatically open up accordion if linked to anchor
    if (window.location.hash) {
        openAccordionElement(window.location.hash.replace('#c', ''));
    }

    // Open accordion element on click; Used for anchor links
    jQuery('a').each(function () {
        var href = jQuery(this).attr('href');
        if (href && href.include('#c')) {
            jQuery(this).click(function () {
                openAccordionElement(href.replace('#c', ''));
            });
        }
    });

    jQuery('.filterPopup .panel-heading a').click(function(){
      filterState[jQuery(this).attr('aria-controls')] = jQuery(this).parent().parent().parent().attr('id');
    });

    /* add Multiple email validation to prototypejs  */
    Validation.add('validate-multi-email', Translator.translate('Please enter a valid email address. For example johndoe@domain.com.'), function(emails) {
        emails = emails.split(',');
        for (var i = 0; i < emails.length; i++) {
            if (Validation.get('validate-email').test(emails[i]) == false) {
                return false;
            }
        }
        return true;
    });

	//Accordian Plus minus
	function toggleIcon(e) {
	jQuery(e.target)
		.parent().find('div')
		.find('.more-less')
		.toggleClass('glyphicon-plus glyphicon-minus');
	jQuery(e.target).parent().find('div')[0].scrollIntoView({ behavior: 'smooth' });
	}
	jQuery('.accordianTypo').on('hidden.bs.collapse', toggleIcon);
	jQuery('.accordianTypo').on('shown.bs.collapse', toggleIcon);

    // For left navigation accordian (used on CMS pages)
    jQuery('.leftNavSec .leftNav li span.arrow').click(function(){
        jQuery(this).parent('li').toggleClass('active');
        //jQuery(this).parent().toggleClass('active');
        // code to open
        if(jQuery(this).hasClass('glyphicon-chevron-down')){

            jQuery(this).removeClass('glyphicon-chevron-down');
            jQuery(this).addClass('glyphicon-chevron-up');

        }else{
            jQuery(this).removeClass('glyphicon-chevron-up');
            jQuery(this).addClass('glyphicon-chevron-down');
        }
        if(jQuery(this).parent('li').siblings('li').children('span').hasClass('glyphicon-chevron-up')){
            jQuery(this).parent('li').siblings('li').children('span').removeClass('glyphicon-chevron-up');
            jQuery(this).parent('li').siblings('li').children('span').addClass('glyphicon-chevron-down');
        }
        // show the child ul
        jQuery(this).siblings('ul.subNav').toggle();
        //jQuery(this).parent('li').siblings('li').children('ul.subNav').hide();
        // hide other child ul having differnt li parent
        jQuery(this).parent('li').siblings('li').children('ul.subNav').hide();
        // inactive other li items and child ul
        jQuery(this).parent('li').siblings('.active').removeClass('active');

    });
    jQuery('.leftNavSec .leftNav li').find('ul').prev().addClass('glyphicon-chevron-down');

    // Open up active accordions
    jQuery.each(jQuery('.leftNavSec .leftNav li.current'), function (i, val) {
        jQuery(val).find('span.arrow').first().click();
    });

    // if more than one tabs on page
    if(jQuery('.tabSlider').length > 1){
        jQuery('.tabSlider').each(function(index){
            jQuery(this).attr('id', 'tab' + index);
            jQuery(this).children('li:first-child').addClass('active');
            //jQuery(this).parent('.tabSection').children('.tab-content').children(':first-child').addClass('active in');
            jQuery(this).parent('.tabSection').find('.tab-pane').first().addClass('active in');
        });
    } else {
		// console.log('only one or none tabs');
    }

    //Tabs slider
    jQuery('.tabSlider').bxSlider({
        touchEnabled: touchDisableForDesktop,
        auto: false,
        pager: false,
        infiniteLoop: false,
        hideControlOnEnd: true,
        autoControls: true,
        slideWidth: 190,
        minSlides: 1,
        maxSlides: 5,
        moveSlides: 1,
        slideMargin: 0
    });

    //Main Banner Slider; Init on load due to CSS media queries interfering otherwise
    jQuery( window ).load(function () {
        jQuery('.sliderBigImage .bxslider').bxSlider({
            touchEnabled: touchDisableForDesktop,
            auto: true,
            pager: true,
            autoControls: false,
            mode: 'fade',
            pause: 8000,
            speed: 500
        });
    });
    //Dashboard Banner Slider
    if(jQuery('.deshboardSlider li').size() > 1){
        jQuery('.deshboardSlider').bxSlider({
            touchEnabled: touchDisableForDesktop,
            auto: true,
			pager: true,
            autoControls: false,
			mode: 'fade',
            slideZIndex: 0
        });
    }
    //Feature Product Slider
    /*jQuery('.featureProdCont').bxSlider({
        auto: false,
        pager: false,
        slideWidth: 300,
        minSlides: 1,
        maxSlides: 4,
        moveSlides: 1,
        slideMargin: 15
    });*/

    jQuery('.gallerySlider').bxSlider({
        touchEnabled: touchDisableForDesktop,
        auto: false,
        pager: false,
        slideWidth: 175,
        minSlides: 1,
        maxSlides: 5,
        moveSlides: 1,
        slideMargin: 10,
        infiniteLoop: false,
        hideControlOnEnd: true,
        onSliderLoad: function (currentIndex) {
            jQueryLazyLoader.update();
        }

    });
    var sliderWidth = 347;
    if(jQuery(window).width() <= 992) {
        sliderWidth = 280;
    }
    jQuery('.landingPageSlider').bxSlider({
        touchEnabled: touchDisableForDesktop,
        auto: false,
        pager: false,
        slideWidth: sliderWidth,
        minSlides: 1,
        maxSlides: 4,
        moveSlides: 1,
        slideMargin: 5,
        infiniteLoop: false,
        hideControlOnEnd: true,
        onSliderLoad: function (currentIndex) {
            jQueryLazyLoader.update();
        }
    });
    //New Launches Slider
    var newLaunchSliderWidth = 500;
    if(jQuery(window).width() <= 767) {
        newLaunchSliderWidth = 350;
    }
    jQuery('.newLaunches').bxSlider({
        touchEnabled: touchDisableForDesktop,
        auto: false,
        pager: false,
        slideWidth: newLaunchSliderWidth,
        minSlides: 1,
        maxSlides: 4,
        moveSlides: 1,
        slideMargin: 15,
        infiniteLoop: true,
        hideControlOnEnd: true,
        onSliderLoad: function (currentIndex) {
            jQueryLazyLoader.update();
        }
    });

    // for mobile device
    if(jQuery(".bx-viewport").length){
        jQuery('body').on({
            'touchmove': function(e) {
                setTimeout(function() { jQuery(window).trigger("scroll"); }, 100);
            }
        });
    }

    if(jQuery('.product-thumbnail-slider li').size() > 1){
        jQuery('.product-thumbnail-slider').bxSlider({
            touchEnabled: touchDisableForDesktop,
            pagerCustom: '#bx-pager'
        });
    }
    if (jQuery(window).width() > 767) {
        //if(jQuery('.pager-thumbnail li').size() > 1){
            jQuery('.pager-thumbnail').bxSlider({
                touchEnabled: touchDisableForDesktop,
                auto: false,
                pager: false,
                slideWidth: 85,
                minSlides: 1,
                maxSlides: 5,
                moveSlides: 1,
                slideMargin: 6,
                infiniteLoop: false,
            hideControlOnEnd: true,
            onSliderLoad: function (currentIndex) {
                jQueryLazyLoader.update();
            }
            });
        //}
    }

    // On login/my account container click close mega menu & agent
    jQuery('#customerProfileLink').click(function () {
        jQuery('.dropdown-menu .online-contact').hide();
    });

    // On header search icon click submit search form at CMS end
    jQuery('#srchiconclick').click(function () {
        jQuery("#tx-solr-search-form-pi-search").submit();
    });

    // On header search icon click submit search form at shop end
    jQuery('#srchiconclickshop').click(function () {
        var query = jQuery('#search').val();
        if(query != '' && query != searchDefaultText){
            // If only only product found (SOLR-Autosuggest), go directly to PDP:
            if (localStorage.searchResultIsSingleProduct != '') {
                var singleProductURL = localStorage.searchResultIsSingleProduct;
                localStorage.searchResultIsSingleProduct = '';
                window.location = singleProductURL + '?q=' + query;
            } else {
                jQuery("#search_mini_form").submit();
            }
        }else{
            document.getElementById("search").focus();
        }
    });
    jQuery(document).on('keypress', function(ev) {
        if ( jQuery(ev.target).attr('id') === 'search' && ev.which == 13 ) {
            ev.preventDefault();
            var query = jQuery('#search').val();
            if(query != '' && query != searchDefaultText){
                if (localStorage.searchResultIsSingleProduct != undefined && localStorage.searchResultIsSingleProduct != '') {
                    var singleProductURL = localStorage.searchResultIsSingleProduct;
                    localStorage.searchResultIsSingleProduct = '';
                    window.location = singleProductURL + '?q=' + query;
                } else {
                    jQuery("#search_mini_form").submit();
                }
            } else {
                document.getElementById("search").focus();
            }
        }
    });
    jQuery(document).on('paste', function(ev) {
        if ( jQuery(ev.target).attr('id') === 'search' && ev.which == 13 ) {
            ev.preventDefault();
            var query = jQuery('#search').val();
            if(query != '' && query != searchDefaultText){
                if (localStorage.searchResultIsSingleProduct != undefined && localStorage.searchResultIsSingleProduct != '') {
                    var singleProductURL = localStorage.searchResultIsSingleProduct;
                    localStorage.searchResultIsSingleProduct = '';
                    window.location = singleProductURL + '?q=' + query;
                } else {
                    jQuery("#search_mini_form").submit();
                }
            } else {
                document.getElementById("search").focus();
            }
        }
    });

    //home breadcrumb url redirection
    jQuery('#breadcrumbs ul li.home').click(function(event){
        event.preventDefault();
        window.location = BASE_URL.replace('/shop', '');
    });

    /* Radio Button click for Pricelists/Datanorm page content */
    jQuery('.priceFormatCont input[type="radio"]').click(function () {
        var inputValue = jQuery(this).attr("value");
        var targetBox = jQuery("." + inputValue);
        jQuery(".box").not(targetBox).hide();
        jQuery(targetBox).show();
    });

    // Desktop View Agent Hover
    jQuery('#agentInfoHead').removeAttr('data-toggle');
    //jQuery(".user-login").hover(function () {
    jQuery(".user-login").click(function () {
        jQuery('.user-login').addClass('open');
        jQuery(".grayout").show();
        jQuery('body').css('overflow','hidden');
    });
    // Desktop View Logged In User Hover
    //if (jQuery(window).width() < 992) {
        jQuery('#customerLogInContainer').removeAttr('data-toggle');
    //}

	jQuery('.grayout').click(function(){
            //for adviser popup
            jQuery(".grayout").hide();
            jQuery('.user-login').removeClass('open');
            jQuery('body').css('overflow','auto');

            //for autosuggest
            jQuery('#search_autocomplete').css('display', 'none');
            jQuery('.searchContiner .input-group').css('z-index', 'auto');
	});

    // Hack for google Tablet Nexus:
    var nexusDeviceWidth = jQuery(window).width();
    jQuery(window).resize(function() {
        if(jQuery(window).width() == 600 && jQuery(window).width() != nexusDeviceWidth){
            location.reload();
        }else if(jQuery(window).width() == 960 && jQuery(window).width() != nexusDeviceWidth){
            location.reload();
        }
    });
});

function checkWidth() {
   console.log('Please remove checkWidth()-function from your code');
}

//========================================================== updatePriceAndStock
function updatePriceAndStock() {
//==============================================================================
    if (typeof productSKUForPrice != 'undefined' || typeof productSKUForStock != 'undefined') {
        var dataArray = {};
        dataArray.form_key = formKey;
        if(typeof productSKUForPrice != 'undefined' && typeof productSKUForStock != 'undefined'){
            dataArray.getProductPricesAndAvailabilities = productSKUForStock;
        } else {
            if(typeof productSKUForPrice != 'undefined'){
                dataArray.getProductPrices = productSKUForPrice;
            }
            if(typeof productSKUForStock != 'undefined'){
                dataArray.getProductAvailabilities = productSKUForStock;
            }
        }

        jQuery.ajax(ajaxUrl, {
            'dataType': 'json',
            'type': 'POST',
            'data': dataArray,
            'success': function (data) {
                var parsedData = data;
                // debugger;
                // TODO : do something here with proxessed response data!
                var ajaxDispatcher = new AjaxDispatcher();
                jQuery.each(parsedData, function (key, value) {
                    if (key == 'getProductPricesAndAvailabilities') {
                        if (value.result.availibility) {
                            ajaxDispatcher['getProductAvailabilities'](value.result.availibility);
                        }
                        if (value.result.prices) {
                            ajaxDispatcher['getProductPrices'](value.result.prices);
                        }
                    } else {
                        ajaxDispatcher[key](value.result);
                    }
                });
            },
            'error': function (data) {
                var parsedData = data;
                //debugger;
            }
        });
    }
} //============================================== updatePriceAndStock ***END***

function updateAgentHtml(mode) {
    /* Top Popup */
    //htmlData = '<div class="agentFrm"></div><img src="' + agentImgUrl + '" alt="' + agentTitle + ' ' + agentFN + ' ' + agentLN + '"/>';
    console.log('Running updateAgentHtml() -> ' + mode);

	if (mode == 'multipleAdvisorMode') {
        jQuery('#advisor_heading_normalmode_block').hide();
        jQuery('#advisor_detail_normalmode_block').hide();

        jQuery('#agentInfoHead').css({"border": "none", "padding": 0, "border-radius": "20px"});
        jQuery('#agentInfoHead').html('<i id="advisor_telephone_symbol" class="fa fa-phone-square"></i>');

	    if (agentOneMail) {
            jQuery('#advisor_one_block').show();
            jQuery('#multiple_advisor_one_pic').attr('src', agentOneImageUrl);
            jQuery('#multiple_advisor_one_name').html(agentOneName);
            jQuery('#multiple_advisor_one_title').html(agentOneTitle);
            jQuery('#multiple_advisor_one_mail').html('<a href="' + EncryptMailto(agentOneMail) + '">' + agentOneMail.replace('@', '(at)') + '</a>');
            jQuery('#multiple_advisor_one_phone').html('<a href="tel:' + agentOnePhone + '"><i class="advisor_mobile_icon glyphicon glyphicon-phone-alt" />:&nbsp;' + agentOnePhone + '</a>');
            jQuery('#multiple_advisor_one_mobile').hide().next().remove();
            if(agentTwoMobile.length > 0) {
                jQuery('#multiple_advisor_one_mobile').html('<a href="tel:' + agentOneMobile + '"><i class="advisor_mobile_icon glyphicon glyphicon-phone" />:&nbsp;' + agentOneMobile + '</a>').show().after('<br />');
            }
            jQuery('#multiple_advisor_one_fax').html(agentOneFax);
            jQuery('#multiple_advisor_one_branch').html(agentOneBranch);
        } else {
            jQuery('#advisor_one_block').hide();
            console.log('Multiple Advisor One: DEACTIVATED');
        }

        if (agentTwoMail) {
            jQuery('#advisor_two_block').show();
            jQuery('#multiple_advisor_two_pic').attr('src', agentTwoImageUrl);
            jQuery('#multiple_advisor_two_name').html(agentTwoName);
            jQuery('#multiple_advisor_two_title').html(agentTwoTitle);
            jQuery('#multiple_advisor_two_mail').html('<a href="' + EncryptMailto(agentTwoMail) + '">' + agentTwoMail.replace('@', '(at)') + '</a>');
            jQuery('#multiple_advisor_two_phone').html('<a href="tel:' + agentTwoPhone + '"><i class="advisor_mobile_icon glyphicon glyphicon-phone-alt" />:&nbsp;' + agentTwoPhone + '</a>');
            jQuery('#multiple_advisor_two_mobile').hide().next().remove();
            if(agentTwoMobile.length > 0) {
                jQuery('#multiple_advisor_two_mobile').html('<a href="tel:' + agentTwoMobile + '"><i class="advisor_mobile_icon glyphicon glyphicon-phone" />:&nbsp;' + agentTwoMobile + '</a>').show().after('<br />');
            }
            jQuery('#multiple_advisor_two_fax').html(agentTwoFax);
            jQuery('#multiple_advisor_two_branch').html(agentTwoBranch);
        } else {
            jQuery('#advisor_two_block').hide();
            console.log('Multiple Advisor Two: DEACTIVATED');
        }

        if (agentThreeMail) {
            jQuery('#advisor_three_block').show();
            jQuery('#multiple_advisor_three_pic').attr('src', agentThreeImageUrl);
            jQuery('#multiple_advisor_three_name').html(agentThreeName);
            jQuery('#multiple_advisor_three_title').html(agentThreeTitle);
            jQuery('#multiple_advisor_three_mail').html('<a href="' + EncryptMailto(agentThreeMail) + '">' + agentThreeMail.replace('@', '(at)') + '</a>');
            jQuery('#multiple_advisor_three_phone').html('<a href="tel:' + agentThreePhone + '"><i class="advisor_mobile_icon glyphicon glyphicon-phone-alt" />:&nbsp;' + agentThreePhone + '</a>');
            jQuery('#multiple_advisor_three_mobile').hide().next().remove();
            if(agentThreeMobile.length > 0){
                jQuery('#multiple_advisor_three_mobile').html('<a href="tel:' + agentThreeMobile + '"><i class="advisor_mobile_icon glyphicon glyphicon-phone" />:&nbsp;' + agentThreeMobile + '</a>').show().after('<br />');
            }
            jQuery('#multiple_advisor_three_fax').html(agentThreeFax);
            jQuery('#multiple_advisor_three_branch').html(agentThreeBranch);
        } else {
            jQuery('#advisor_three_block').hide();
            console.log('Multiple Advisor Three: DEACTIVATED');
        }
	} else {
        jQuery('#advisor_one_block').hide();
        jQuery('#advisor_two_block').hide();
        jQuery('#advisor_three_block').hide();

        jQuery('#advisor_heading_normalmode_block').show();
        jQuery('#advisor_detail_normalmode_block').show();

        htmlData = '<img src="' + agentImgUrl + '" alt="' + agentTitle + ' ' + agentFN + ' ' + agentLN + '"/>';
        jQuery('#agentInfoHead').html(htmlData);
        jQuery('#mailto').attr('href', EncryptMailto(agentMail));
        /*if(agentTel != 'null'){
        jQuery('#callto').attr('href', 'tel:' +agentTel.replace(/\s+/g, ""));
        } */
        jQuery('#callto').attr('href', 'tel:' + agentTel);
        jQuery('.user-detail .user-pic').html('<img src="' + agentImgUrl + '" alt="' + agentTitle + ' ' + agentFN + ' ' + agentLN + '"/>');
        jQuery('.user-detail .name').html(agentFN + ' ' + agentLN);
        jQuery('.user-detail .designation').html(agentTitle);
        jQuery('.user-detail .contact').html(agentMail.replace('@', '(at)') + '<br>' + agentTel + '<br>Fax: ' + agentFax);

        console.log("updateAgentHtml: localStorage.customerNotLoggedIn = " + localStorage.customerNotLoggedIn);
        if ( localStorage.getItem('itemCustomerNotLoggedIn') == "0" ) {
            var tmpHref = jQuery('#vcard').attr('href');
            tmpHref = tmpHref + "?email=" + agentMail;
            jQuery('#vcard').attr('href', tmpHref);
            jQuery('#vcard').show();
        } else {
            jQuery('#vcard').hide();
        }

        /*PDP*/
        if (jQuery('.dead-article-contact')) {
            var deadArticleAontact = '<div class="row"><div class="col-sm-3 col-xs-4"><img class="foto" src="' + agentImgUrl.replace('mab58', 'mab95') + '" alt="' + agentTitle + ' ' + agentFN + ' ' + agentLN + '"/></div><div class="col-sm-9 col-xs-8"><div class="contact bold bottompadding">' + Translator.translate("Your Contact Person") + '</div><div class="contact bold">' + agentFN + ' ' + agentLN + '</div><div class="contact">' + agentTitle + '</div><div class="contact">&nbsp;</div><div class="contact blue"><a href="tel:' + agentTel + '">' + agentTel + '<br>Fax: ' + agentFax + '</a></div><div class="contact blue"><a href="' + EncryptMailto(agentMail) + '">' + agentMail.replace('@', '(at)') + '</a></div></div></div>';
            jQuery('.dead-article-contact').html(deadArticleAontact);
        }
        /* agentFax */
        if(jQuery('.inquiryPopup')){
            jQuery('.inquiryPopup .contactBx .user-pic').html('<img class="foto" src="' + agentImgUrl.replace('mab58', 'mab95') + '" alt="' + agentTitle + ' ' + agentFN + ' ' + agentLN + '"/><br><h3 class="subHeading paddingT10">' + agentFN + ' ' + agentLN + '</h3>');
            jQuery('.inquiryPopup .contactBx .contact').append(' <a href="tel:' + agentTel + '">' + agentTel + '</a>');
            jQuery('.inquiryPopup .contactBx .mail').append(' <a href="' + EncryptMailto(agentMail) + '">' + agentMail.replace('@', '(at)') + '</a>');
            jQuery('.inquiryPopup .contactBx .fax').append(' <a href="javascript:void(0);">' + agentFax + '</a>');
        }
    }
}
function loadCachedSearchBarCategories() {
    localStorage.refreshMegaMenuForceTimeCurrent = MEGA_MENU_LATEST_REFRESH_TIMESTAMP;
    if (!localStorage.refreshMegaMenuForceTimeLastChangeDropdownMenu || localStorage.refreshMegaMenuForceTimeCurrent > localStorage.refreshMegaMenuForceTimeLastChangeDropdownMenu) {
        // Remove content from localstorage, to set semaphore to refill with new content:
        localStorage.searchBarCategoriesContent = '';

        // ...and set the current forcetime to last change
        localStorage.refreshMegaMenuForceTimeLastChangeDropdownMenu = localStorage.refreshMegaMenuForceTimeCurrent;
        return false;
    } else {
        if (localStorage.searchBarCategoriesContent && localStorage.searchBarCategoriesContent != '') {
            jQuery('#searchDropdownBox').append(localStorage.searchBarCategoriesContent);
            return true;
        } else {
            return false;
        }
    }
}
/* filter */

function registerSeeMoreClickHandler(caseAndFacet) {
    jQuery("#" + caseAndFacet).on('click', function(e){
        e.preventDefault();
        showMoreFilter(caseAndFacet);
    });
}


function showMoreFilter(caseAndFacet){
    var arrCaseAndFacet = caseAndFacet.split("__");
    var strCase         = arrCaseAndFacet[1];
    var facet           = arrCaseAndFacet[2];
    var newStatus       = '';

    if (strCase == '01' || strCase == '03') {
        if (jQuery('#' + facet + '_showMoreFilter').hasClass('moreBttnFilter'))
        {
            // Switch from 'See More' to 'See Less' Case:
            console.log('>>> Aufklappen');
            jQuery('#' + facet + ' ul li:nth-child(5)').nextAll().css( "display", "block" );
            jQuery("[name='case__01__" + facet + "_showMoreFilter']").css( "display", "none" );
            jQuery("[name='case__03__" + facet + "_showMoreFilter']").css( "display", "block" );
            newStatus = 'lessBttnFilter';
        }

        if (jQuery('#' + facet + '_showMoreFilter').hasClass('lessBttnFilter')) {
            // Switch 'See Less' to 'See More' Case:
            console.log('>>> Zuklappen');
            jQuery('#' + facet + ' ul li:nth-child(5)').nextAll().css( "display", "none" );
            jQuery("[name='case__01__" + facet + "_showMoreFilter']").css( "display", "block" );
            jQuery("[name='case__03__" + facet + "_showMoreFilter']").css( "display", "none" );
            newStatus = 'moreBttnFilter';
        }
    } else {
        if (jQuery('#' + facet + '_showMoreFilter').hasClass('moreBttnFilter'))
        {
            // Switch from 'See More' to 'See Less' Case:
            console.log('>>> Aufklappen');
            jQuery('#' + facet + ' ul li:nth-child(5)').nextAll().css( "display", "block" );
            jQuery("[name='case__04__" + facet + "_showMoreFilter']").css( "display", "none" );
            newStatus = 'lessBttnFilter';
        }

        if (jQuery('#' + facet + '_showMoreFilter').hasClass('lessBttnFilter')) {
            // Switch 'See Less' to 'See More' Case:
            console.log('>>> Zuklappen');
            jQuery('#' + facet + ' ul li:nth-child(5)').nextAll().css( "display", "none" );
            jQuery("[name='case__04__" + facet + "_showMoreFilter']").css( "display", "block" );
            newStatus = 'moreBttnFilter';
        }
    }

    if (newStatus == 'lessBttnFilter') {
        // Switch from 'See More' to 'See Less' Case:
        jQuery('#' + facet + '_showMoreFilter').removeClass('moreBttnFilter')
        jQuery('#' + facet + '_showMoreFilter').addClass('lessBttnFilter')
    }

    if (newStatus == 'moreBttnFilter') {
        // Switch 'See Less' to 'See More' Case:
        jQuery('#' + facet + '_showMoreFilter').removeClass('lessBttnFilter')
        jQuery('#' + facet + '_showMoreFilter').addClass('moreBttnFilter')
    }
    //jQuery('#' + facet + '_showMoreFilter').css( "display", "block" );
}

/* filter */
function AjaxDispatcher() {
console.log("### activeproductcontainer #01 = " + jQuery('#activeproductcontainer').val());

    this.getRenderedCategoryBlocks = function (data) {
        console.log("getRCB START!");

        parsedData = data;
        filterArray = {};
        generalFilterArray = {};
        jQuery('ul.messages').empty();
        jQuery('ul.errors').empty();
        jQuery('.firstViewLoader').empty();
        if(parsedData.isCatalogCategory == false){
            if(parsedData.breadcrumbsBlock && needToUpdateBreadcrumbs == true){
                jQuery('#breadcrumbs').html(parsedData.breadcrumbsBlock);
                needToUpdateBreadcrumbs = false;
            }
            jQuery('#category-header .headline-container').html(parsedData.headlineBlock);
            jQuery('.description-container').html(parsedData.descriptionBlock);

            console.log("### activeproductcontainer #02 = " + jQuery('#activeproductcontainer').val());
            if (jQuery(window).width() > 1182) {
                if(jQuery('#activeproductcontainer').val() != 'accessories'){
                    jQuery('.search-attributes-mob').html(parsedData.filterBlock);
                    copyFilter = jQuery('.search-attributes').html();
                    jQuery('.search-attributes').html('');
                    if(parsedData.productListBlock){
                        jQuery('.search-attributes-desktop1').html('');
                        jQuery('.search-attributes-desktop').html(copyFilter);
                        jQuery('.search-attributes-desktop').parent().attr('id', 'solrsearch-container');
                    }else{
                        jQuery('.search-attributes-desktop').html('');
                        jQuery('.search-attributes-desktop1').html(copyFilter);
                        jQuery('.search-attributes-desktop1').parent().attr('id', 'solrsearch-container');
                    }
                    jQuery('.search-attributes-mob').attr('id', 'solrsearch-container-mob');
                }
            } else {
                if(jQuery('#activeproductcontainer').val() != 'accessories'){
                    jQuery('.search-attributes-mob').html(parsedData.filterBlock);
                }
                jQuery(document).click(function(e){
                    // Check if click was triggered on or within #
                    if( jQuery(e.target).closest(".search-attributes-mob").length > 0 ) {

                    } else {
                        jQuery('.search-attributes-mob .filterPopup').hide();
                    }
                });
            }
            if(customerId != '' || customerName != ''){
                jQuery('#general_filters li:nth-child(1)').removeClass('hide');
                jQuery('#general_filters li:nth-child(3)').removeClass('hide');
            }else{
                jQuery('#general_filters li:nth-child(1)').addClass('hide');
                jQuery('#general_filters li:nth-child(3)').addClass('hide');
            }
            jQuery('.subcats-container').html(parsedData.subcatsBlock);
            jQuery('.attachments-container').html(parsedData.attachmentsBlock);
            jQuery('.cms-container').html(parsedData.cmsBlock);
            if(parsedData.productListBlock){
                jQuery('.product-list-frame').removeClass('hide');
                jQuery('#accessoriesBtn .badge').html(parsedData.accessoryCount);
                jQuery('#' + jQuery('#activeproductcontainer').val() + 'Btn .badge').html(parsedData.count);
                if (loading == true){
                    jQuery('#' + jQuery('#activeproductcontainer').val()).append(parsedData.productListBlock);
                    if (parsedData.start + 50 < parsedData.count) {
                        jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.start + 50);
                    } else {
                        jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.count);
                    }
                    loading = false;
                }else{
                    jQuery('#'+jQuery('#activeproductcontainer').val()).html(parsedData.productListBlock);
                    jQuery('#' + jQuery('#activeproductcontainer').val() + '_count').val(parsedData.count);
                    jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(50);

                }
            } else {
                if(!jQuery('.product-list-frame').hasClass('hide') && jQuery('#activeproductcontainer').val() != 'accessories'){
                    jQuery('.product-list-frame').addClass('hide');
                }
                if(!parsedData.subcatsBlock && filter == true){
                    jQuery('.product-list-frame').removeClass('hide');
                }
                // Search result page with no results:
                var backtofilterslink = '&nbsp;<a style="font-weight: bold;" href="' + localStorage.LastSuccessfulUrlOnPlpPage + '">' + Translator.translate('Back') + '</a>';
                jQuery('#' + jQuery('#activeproductcontainer').val()).html(Translator.translate('There are no products matching the selection.') + backtofilterslink); // @@@
                jQuery('#' + jQuery('#activeproductcontainer').val() + 'Btn .badge').html(0);
                jQuery('#' + jQuery('#activeproductcontainer').val() + '_count').val(parsedData.count);
                jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.count);
            }
            if(jQuery('#activeproductcontainer').val() == 'accessories'){
                jQuery('#accessoriesBtn').attr('onclick', "jQuery('#activeproductcontainer').val('accessories'); jQuery('#solrsearch-container').hide();");
            }
            if(parsedData.accessoryCount == 0){
                jQuery('#accessoriesBtn').addClass('hide');
            }else{
                jQuery('#accessoriesBtn').removeClass('hide');
            }
            var flag = false;
            var selectedFilterCount = 0;

            if(jQuery('#activeproductcontainer').val() != 'accessories') {
                jQuery('#solrsearch-container .facet').each(function () {
                    var value = '';
                    var filterPosition = 0;
                    facet = jQuery(this).attr('id');
                    if ( jQuery('#selectedFilterTemp-' + facet).text().trim().length ) {
                        jQuery('#selectedFilter-' + facet + ' span').html(jQuery('#selectedFilterTemp-' + facet).html());
                        jQuery('#selectedFilter-' + facet).css('display', 'block');
                        var $p = jQuery('#selectedFilter-' + facet + ' span');
                        var divh = jQuery('#selectedFilter-' + facet).height();
                        var cnt = 10;
                        while ( $p.outerHeight() > divh && --cnt > 0 ) {
                            $p.text(function ( index, text ) {
                                return text.replace(/\W*\s(\S)*$/, '...');
                            });
                        }
                        jQuery('#selectedFilter-' + facet).css('height', 'auto');
                    }
                    if ( jQuery('#' + facet + ' ul li').size() > 5 ) {
                        // Check, if HTML Element already exists:
                        jQuery('#' + facet + ' ul li:nth-child(5)').nextAll().css("display", "none");
                        jQuery('#' + facet + ' ul').append('<li name="case__01__' + facet + '_showMoreFilter" id="' + facet + '_showMoreFilter" class="moreBttnFilter"><!-- see-more-button : case #01 --><a id="case__01__' + facet + '" class="moreBttnFilterAction"><i id="case__01__' + facet + '__Expander" class="fa fa-plus-square-o fa-lg"></i> <span id="case__01__' + facet + '__Linktext">' + Translator.translate('See More') + '</span></a></li>');
                        registerSeeMoreClickHandler('case__01__' + facet);
                    }
                    jQuery('#' + facet + ' input:checkbox:checked').each(function ( index ) {
                        flag = true;
                        value = jQuery(this).closest('.facet').attr('id');
                        filterPosition = jQuery(this).parent().attr('position');
                    });
                    if ( value != '' ) {
                        if ( value != 'general_filters' ) {
                            jQuery('#' + value + ' .panel-heading a').click();
                        }
                        jQuery('#' + value + ' .panel-heading a').addClass('blueTxt');
                        jQuery('#' + value + ' .filter-deselect').removeClass('hide');
                        selectedFilterCount = selectedFilterCount + 1;
                    }
                    if ( jQuery('#' + facet + ' ul li').size() > 5 ) {
                        jQuery('#' + facet + ' ul').append('<li name="case__03__' + facet + '_showMoreFilter" id="' + facet + '_showMoreFilter" class="moreBttnFilter" style="display: none;"><!-- see-more-button : case #03 --><a id="case__03__' + facet + '" class="moreBttnFilterAction"><i id="case__03__' + facet + '__Expander" class="fa fa-minus-square-o fa-lg"></i> <span id="case__03__' + facet + '__Linktext">' + Translator.translate('See Less') + '</span></a></li>');
                        registerSeeMoreClickHandler('case__03__' + facet);
                    }
                    if ( filterPosition >= 5 ) {
                        // Filter selecetd, which is per default hidden, because the "see more" hides the elements, which are greater than position = 5:
                        jQuery("#case__01__" + facet).click();
                    }
                });
                jQuery('#' + toggleFilterState + 'Btn').click();
                jQuery('#filterMenu .count').text(selectedFilterCount);
            }
            if(flag && defaultFilterOpen){
                if (jQuery(window).width() > 1024) {

                }else{
                   jQuery('#filterMenu').click();
                    console.log('filter click #11');
                }
                jQuery('#clearFilter').removeClass('hide');
            } else if(getParameterByName('q') && defaultFilterOpen){
                if (jQuery(window).width() > 1024) {

                }else{
                   jQuery('#filterMenu').click();
                    console.log('filter click #12');
                }
                jQuery('#clearFilter').removeClass('hide');
            }else{
                if(flag || getParameterByName('q')){
                    jQuery('#clearFilter').removeClass('hide');
                }else{
                    jQuery('#clearFilter').addClass('hide');
                }
            }
            if( !jQuery.trim( jQuery('#solrsearch-container').html() ).length && jQuery('#activeproductcontainer').val() != 'accessories' && filter == true){
                jQuery('#solrsearch-container').html('<button id="clearFilter" class="btn btn-default marginTB10" onclick="clearFilter()">'+Translator.translate('Clear Filters')+'</button>');
                //jQuery('.filterPopup').toggle();
            }
            filter == false;
            defaultFilterOpen = true;
            //partlist

            var trackingFeatureSource = '-';

            if (typeof PAGETYPE !== 'undefined') {
                //console.log('PAGETYPE = ' + PAGETYPE);
                if (PAGETYPE == 'CART') {
                    trackingFeatureSource = 'cart';
                } else if (PAGETYPE == 'DETAIL_VIEW') {
                    trackingFeatureSource = 'product detail view';
                } else if (PAGETYPE == 'PRODUCT_LIST_VIEW') {
                    trackingFeatureSource = 'product list view';
                } else {
                    //console.log('(#1) PAGETYPE is assigned to unknown value or null : ' + PAGETYPE);
                }
            } else {
                //console.log('(#1) PAGETYPE is NOT explicitly defined');
            }

            jQuery('.product-list .product-tab-content').each(function(){
                i = jQuery(this).attr('id').replace('product_','');
                if (partListData != '' && partListData != 'error') {
                    htmlData  = "<!-- position : commonJs.js #1332863 -->";
                    htmlData += "<li class='add-to-new-partslist' onclick='partslistFE.addItemToNewList(\"New parts list\", new ListRequestManager.Product(jQuery(\"#productId-" + i + "\").val(), jQuery(\"#qty-" + i + "\").val(), \"" + i + "\"), \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + i + "\" ><span class='glyphicon glyphicon-plus-sign plusIcon darkGray'></span> "+Translator.translate("Add to new parts list")+"</li>";
                    jQuery.each(partListData, function (j, item) {
                        // console.log(item);
                        j = j.replace('\0', '');
                        htmlData += "<li onclick='partslistFE.addItemToList(" + j + ", new ListRequestManager.Product(jQuery(\"#productId-" + i + "\").val(), jQuery(\"#qty-" + i + "\").val(), \"" + i + "\"), false, \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + i + "\" title='" + item + "'>" + Translator.translate("Add to") + " " + item + "</li>";
                    });
                    jQuery('#product_' + i + ' .dropdown-list').html(htmlData);
                    jQuery('#product_' + i + ' .dropdown-list').removeClass('withoutLgn');
                    jQuery('#parlistdropdownbtn-' + i).removeClass('lgtGray');
                } else if(customerId != '' || customerName != ''){
                    htmlData  = "<!-- position : commonJs.js #9823479873 -->";
                    htmlData += "<li class='add-to-new-partslist' onclick='partslistFE.addItemToNewList(\"New parts list\", new ListRequestManager.Product(jQuery(\"#productId-" + i + "\").val(), jQuery(\"#qty-" + i + "\").val(), \"" + i + "\"), \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + i + "\" ><span class='glyphicon glyphicon-plus-sign plusIcon darkGray'></span> "+Translator.translate("Add to new parts list")+"</li>";
                    jQuery('#product_' + i + ' .dropdown-list').html(htmlData);
                    jQuery('#product_' + i + ' .dropdown-list').removeClass('withoutLgn');
                    jQuery('#parlistdropdownbtn-' + i).removeClass('lgtGray');
                    jQuery('#product_' + i + ' .dropdown-list').css("height", "auto");
                    jQuery('#product_' + i + ' .dropdown-list').css("width", "auto");
                } else {
                    //jQuery('#product_' + i + ' .dropdown-list').html("");
                    jQuery('#product_' + i + ' .dropdown-list').css("height", "auto");
                    jQuery('#product_' + i + ' .dropdown-list').css("width", "auto");
                }
                //hide add to cart
                console.log('Current Role #1 = ' + customerAclRole);
                if(customerAclRole == 'staff' || customerAclRole == 'projectant'){
                    jQuery('#product_' + i + ' .addtocart').addClass('hide');
                }
            });
            //end partlist

        } else {
            if(parsedData.breadcrumbsBlock && loading == false && filter == false){
                jQuery('#breadcrumbs').html(parsedData.breadcrumbsBlock);
            }
            jQuery('.subcats-container').html(parsedData.subcatsBlock);
            jQuery('.catalogBnrCont').html(parsedData.cmsBlock1);
            jQuery('#solrsearch-container').html(parsedData.filterBlock);
            if(parsedData.productListBlock){
                if (loading == true){
                    jQuery('#product-list-block').append(parsedData.productListBlock);
                    if (parsedData.start + 50 < parsedData.count) {
                        jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.start + 50);
                    } else {
                        jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.count);
                    }
                    loading = false;
                }else{
                    jQuery('#product-list-block').html(parsedData.productListBlock);
                    jQuery('#' + jQuery('#activeproductcontainer').val() + '_count').val(parsedData.count);
                    jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(50);

                }
            }else{
                //jQuery('#product-list-block').html(Translator.translate('There are no products matching the selection.'));
                jQuery('#' + jQuery('#activeproductcontainer').val() + '_count').val(parsedData.count);
                jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.count);
            }
            jQuery('.bxslider').bxSlider({
                touchEnabled: touchDisableForDesktop,
                auto: true,
                autoControls: true,
				mode: 'fade'
            });
            //for css issue SCHRAC-789
            var itemCount = jQuery('#product-list-block .catalogLstCont').length;
            if(itemCount % 2 != 0){
                jQuery('#product-list-block .catalogLstCont').last().css( "border-bottom", "0px" );
            }

            //hide add to cart
            console.log('Current Role #2 = ' + customerAclRole);
            if(customerAclRole == 'staff' || customerAclRole == 'projectant'){
                jQuery('.addCart').addClass('hide');
            }
        }
        jQuery('#search-attr-full').val(getParameterByName('q'))

        jQueryLazyLoader.update();

        /*
        if (jQuery(window).width() > 992) {
            imagePreviewHover();
        }
        */
        //add event for breadcrumb
        jQuery('#breadcrumbs ul li').click(function(event){
            if(!jQuery(this).hasClass('home')){
            event.preventDefault();
            url = jQuery(this).find('a').attr('href');
            if(url){
                filterArray = {};
                generalFilterArray = {};
                urlappend = '';
                jQuery('#productsBtn').click();
                catId = jQuery(this).attr('class');
                catId = catId.replace('category', '');
                jQuery('#category_id').val(catId);
                var sign = ( url.indexOf('?') > -1 ) ? '&' : '?';
                url = url + sign + 'catId=' + catId;
                history.pushState('data', '', url);
                needToUpdateBreadcrumbs = true;
                dataArray.getRenderedCategoryBlocks = {'data' : {'query': '', 'start': 0, 'limit': 50, 'accessory':0, 'category': catId, 'facets': filterArray, 'general_filters': generalFilterArray}};
                ajaxDispatcherCall();
            }
            }
        });

    };

    this.getPrevMessage = function (messageText) {

      if(messageText !== "") {
          var message = '<ul class="messages"><li class="success-msg"><span class="glyphicon glyphicon-ok"></span> <span>' + messageText + '</span></li></ul>';
          jQuery("#content").html(message);
      }

    };
    this.getRenderedSearchBlocks = function (data){
        parsedData = data;

        if(parsedData.redirectToSingleSearchResult && parsedData.redirectToSingleSearchResult != ''){
            window.location = parsedData.redirectToSingleSearchResult;
            return true;
        }
        jQuery('ul.messages').empty();
        jQuery('ul.errors').empty();


        if (loading == true){
            var parsed = jQuery.parseHTML(parsedData.searchResultBlock);
            result = jQuery(parsed).find('#' + jQuery('#activeproductcontainer').val()).html();
            jQuery('#' + jQuery('#activeproductcontainer').val()).append(result);
            if(jQuery('#activeproductcontainer').val() == 'gridListView'){
                if (parsedData.productStatus.start + 21 < parsedData.productStatus.count) {
                    jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.productStatus.start + 21);
                } else {
                    jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.productStatus.count);
                }
            }
            if(jQuery('#activeproductcontainer').val() == 'articleCont'){
                if (parsedData.pagesStatus.start + 20 < parsedData.pagesStatus.count) {
                    jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.pagesStatus.start + 20);
                } else {
                    jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.pagesStatus.count);
                }
            }
            if(jQuery('#activeproductcontainer').val() == 'prodListingSales'){
                if (parsedData.saleStatus.start + 7 < parsedData.saleStatus.count) {
                    jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.saleStatus.start + 7);
                } else {
                    jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(parsedData.saleStatus.count);
                }
            }
            loading = false;
        } else {
            jQuery('#searchResult').html(parsedData.searchResultBlock);
            //jQuery('#'+jQuery('#activeproductcontainer').val()).html(parsedData.productListBlock);

            jQuery('#' + jQuery('#activeproductcontainer').val() + '_count').val(parsedData.productStatus.count);
            jQuery('#' + jQuery('#activeproductcontainer').val() + '_next').val(21);

            jQuery('#articleCont_count').val(parsedData.pagesStatus.count);
            jQuery('#articleCont_next').val(20);

            jQuery('#prodListingSales_count').val(parsedData.saleStatus.count);
            jQuery('#prodListingSales_next').val(7);
            if (jQuery(window).width() > 1215) {
                jQuery('.search-attributes-desktop').html(jQuery('.search-attributes').html());
                jQuery('.search-attributes').html('');
                jQuery('.search-attributes-desktop').parent().attr('id', 'solrsearch-container');
                jQuery('.search-attributes').parent().attr('id', 'solrsearch-container-mob');
                jQuery('#moreDataDesktop .moreData').html(jQuery('#moreDataMob .moreData').html());
                jQuery('#moreDataMob .moreData').html();
            } else {
                 if(jQuery(window).width() < 767) {
                    jQuery('#stockCleranceMobCon').html(jQuery('#stockCleranceMob').html());
                    jQuery('#stockCleranceMob').html('');
                }
                jQuery('#filterMenu').click(function(){
                    console.log('filter click #13');
                    jQuery('.filterPopup').toggle();
                });
            }

            var flag = false;
            jQuery('#solrsearch-container .facet').each(function(){
                var value = '';
                var filterPosition = 0;
                facet = jQuery(this).attr('id');
                if (jQuery('#selectedFilterTemp-'+facet).text().trim().length) {
                    jQuery('#selectedFilter-'+facet+' span').html(jQuery('#selectedFilterTemp-'+facet).html());
                    jQuery('#selectedFilter-'+facet).css('display', 'block');
                    var $p = jQuery('#selectedFilter-'+facet+' span');
                    var divh = jQuery('#selectedFilter-'+facet).height();
                    while ($p.outerHeight() > divh) {
                        $p.text(function (index, text) {
                            return text.replace(/\W*\s(\S)*$/, '...');
                        });
                    }
                    jQuery('#selectedFilter-' + facet).css('height', 'auto');
                }
                if ( jQuery('#' + facet + ' ul li').size() > 5 ) {
                    jQuery('#' + facet + ' ul li:nth-child(5)').nextAll().css( "display", "none" );
                    jQuery('#' + facet +' ul').append('<li name="case__02__' + facet + '_showMoreFilter" id="'+ facet + '_showMoreFilter" class="moreBttnFilter"><!-- see-more-button : case #02 --><a id="case__02__' + facet + '" class="moreBttnFilterAction"><i id="case__02__' + facet + '__Expander" class="fa fa-minus-square-o fa-lg"></i> <span id="case__02__' + facet + '__Linktext">' + Translator.translate('See Less') + '</span></a></li>');
                    registerSeeMoreClickHandler('case__02__' + facet);
                }
                jQuery('#' + facet + ' input:checkbox:checked').each(function(index){
                    flag = true;
                    value = jQuery(this).closest('.facet').attr('id');
                    filterPosition = jQuery(this).parent().attr('position');
                });
                if(value != ''){
                    if(value != 'general_filters'){
                        jQuery('#' + value + ' .panel-heading a').click();
                    }
                    jQuery('#' + value + ' .panel-heading a').addClass('blueTxt');
                    jQuery('#' + value + ' .filter-deselect').removeClass('hide');
                }
            });
        }
        //------------------------------------------------------- call for price
        skuListOfNormalProducts = parsedData.skuListOfNormalProducts;
        skuListOfSaleProducts = parsedData.skuListOfSaleProducts;
        //----------------------------------------------------------------------
        productSKUForPrice = {'data' : {'skus' : skuListOfNormalProducts.concat(skuListOfSaleProducts) }};
        productSKUForStock = {'data' : {'skus' : skuListOfNormalProducts.concat(skuListOfSaleProducts) }};
        //------------------------- ajax call for add to cart and delivery infos
        updatePriceAndStock();
        //----------------------------------------------------------------------
        if(jQuery('#checkbox-1-5').attr('checked') == 'checked' || parsedData.saleStatus.count == 0){
            jQuery('#chnageOnSaleFilter').attr('class', 'col-xs-12 col-sm-12 col-md-12 left');
            jQuery('#stockCleranceMob').remove();
            jQuery('#gridListView').addClass('noStock');
        }else{
            jQuery('#chnageOnSaleFilter').attr('class', 'col-xs-12 col-sm-8 col-md-9 left');
            jQuery('#stockCleranceMob').removeClass('hide');
            jQuery('#gridListView').removeClass('noStock');
        }
        if(customerId != '' || customerName != ''){
            jQuery('#general_filters li:nth-child(1)').removeClass('hide');
            jQuery('#general_filters li:nth-child(3)').removeClass('hide');
        }else{
            jQuery('#general_filters li:nth-child(1)').addClass('hide');
             jQuery('#general_filters li:nth-child(3)').addClass('hide');
        }

        var flag = false;
        var selectedFilterCount = 0;
        jQuery('#solrsearch-container .facet').each(function(){
            var value = '';
            var filterPosition = 0;
            facet = jQuery(this).attr('id');
            if ( jQuery('#' + facet + ' ul li').size() > 5 ) {
                jQuery('#' + facet + ' ul li:nth-child(5)').nextAll().css( "display", "none" );
                jQuery('#' + facet + ' ul li:nth-child(5)').after('<li name="case__04__' + facet + '_showMoreFilter" id="' + facet + '_showMoreFilter" class="moreBttnFilter"><!-- see-more-button : case #04 --><a id="case__04__' + facet + '" class="moreBttnFilterAction"><i id="case__04__' + facet + '__Expander" class="fa fa-plus-square-o fa-lg"></i>  <span id="case__04__' + facet + '__Linktext">' + Translator.translate('See More') + '</a></li>');
                registerSeeMoreClickHandler('case__04__' + facet);
            }
            jQuery('#' + facet + ' input:checkbox:checked').each(function(index){
                flag = true;
                value = jQuery(this).closest('.facet').attr('id');
                filterPosition = jQuery(this).parent().attr('position');
            });
            if(value != ''){
                jQuery('#' + value + ' .panel-heading a').click();
                jQuery('#' + value + ' .panel-heading a').addClass('blueTxt');
                selectedFilterCount = selectedFilterCount + 1;
            }
            if(filterPosition >= 5){
                // Filter selecetd, which is per default hidden, because the "see more" hides the elements, which are greater than position = 5:
                jQuery("#case__04__" + facet).click();
            }
        });

        jQuery('#'+toggleFilterState+'Btn').click();
        jQuery('#filterMenu .count').text(selectedFilterCount);
        if(flag || (filter == true && parsedData.productStatus.count == 0)){
            if (globalFRESH_LOAD == false) {
                console.log('Status 2 -> ' + globalFRESH_LOAD);
                jQuery('#filterMenu').click();
                console.log('filter click #14');
                jQuery('#clearFilter').removeClass('hide');
            }
        }
        else{
            jQuery('#clearFilter').addClass('hide');
        }
        filter = false;
        //see more for category section
        if ( jQuery('#moreDataDesktop ul li.level11').size() > 5 || jQuery('#moreDataMob ul li.level11').size() > 5) {
            jQuery('#moreDataDesktop ul li.level11:nth-child(5)').nextAll().css( "display", "none" );
            jQuery('#moreDataDesktop .moreBttnToggle').removeClass('hide');
            jQuery('#moreDataMob ul li.level11:nth-child(5)').nextAll().css( "display", "none" );
            jQuery('#moreDataMob .moreBttnToggle').removeClass('hide');
        }
        jQueryLazyLoader.update();
        if (jQuery(window).width() > 992) {
            imagePreviewHover();
        }
        if (dataLayer) {
            dataLayer.push({
                'event' : 'search',
                'count' : parsedData.productStatus.count,
                'page-title': document.title
            });
        }

    };

    this.getSearchBarCategories = function (data) {
        var htmlData = '';
        var selectedCat = '';
        var selectedId = '';
        htmlData += '<li class="dropdown-item" onclick="assignCurrCat(this)" value="'+Translator.translate('All Categories')+'" catid="">'+Translator.translate('All')+'</li>';
        jQuery.each(data, function (i, item) {
            htmlData += '<li class="dropdown-item" onClick="assignCurrCat(this)" value="' + data[i].name + '" catid="' + data[i].id + '">' + data[i].name + '</li>';
            if(getParameterByName('cat') == data[i].id){
                selectedCat = data[i].name;
            }
        });
        jQuery('#searchDropdownBox').append(htmlData);
        if(selectedCat != ''){
            jQuery("#selSrchHidden").val(selectedId);
            jQuery("#allSrchCat").text(selectedCat);
        }
        if (htmlData) {
            localStorage.searchBarCategoriesContent = htmlData;
        }
    };
    this.getAdvisorData = function (data) {
        var htmlData = '';
        if(Object.keys(data).length === 0) {
            jQuery('.user-detail').hide();
        } else {
            //---------------------------- preperation for normalMode -> #1 + #2
            if (data.title != 'null' && data.title != 'undefined') {
                agentTitle = data.title;
            }
            if (data.firstname != 'null' && data.firstname != 'undefined') {
                agentFN = data.firstname;
            }
            if (data.lastname != 'null' && data.lastname != 'undefined') {
                agentLN = data.lastname;
            }
            if (data.mail && data.mail != 'null' && data.title != 'undefined') {
                agentMail = data.mail;
            }
            if (data.telephonenumber != 'null' && data.telephonenumber != 'undefined') {
                agentTel = data.telephonenumber;
            }
            if (data.mobilephonenumber != 'null' && data.mobilephonenumber != 'undefined') {
                agentMobile = data.mobilephonenumber;
            }
            if (data.faxnumber != 'null' && data.faxnumber != 'undefined') {
                agentFax = data.faxnumber;
            }
            if (data.imageurl != 'null' && data.imageurl != 'undefined') {
                agentImgUrl = data.imageurl;
                if (typeof agentImgUrl === "undefined") {
                    //-------- temp fix for broken sales person after logout
                    agentImgUrl = '';
                    jQuery('#headerLinks li.user-login').hide();
                } else {
                    jQuery('#headerLinks li.user-login').show();
                    agentImgUrl = agentImgUrl.toLowerCase();
                }
            }
            //------------------------------------------------------------------
            if(!localStorage.getItem('customerNotLoggedIn') || localStorage.getItem('customerNotLoggedIn') == 1) {
                console.log('multipleAdvisorMode');
                jQuery('#headerLinks li.user-login').show();

                if (data.multiple_advisor_feature != 'null' && data.multiple_advisor_feature != 'undefined' && data.multiple_advisor_feature == 'enabled') {

                    // Advisor One:
                    if (data.advisor_one_name != 'null' && data.advisor_one_name != 'undefined') {
                        agentOneName = data.advisor_one_name;
                    }
                    if (data.advisor_one_title != 'null' && data.advisor_one_title != 'undefined') {
                        agentOneTitle = data.advisor_one_title;
                    }
                    if (data.advisor_one_mail != 'null' && data.advisor_one_mail != 'undefined') {
                        agentOneMail = data.advisor_one_mail;
                    }
                    if (data.advisor_one_imageurl != 'null' && data.advisor_one_imageurl != 'undefined') {
                        agentOneImageUrl = data.advisor_one_imageurl;
                    }
                    if (data.advisor_one_telephonenumber != 'null' && data.advisor_one_telephonenumber != 'undefined') {
                        agentOnePhone = data.advisor_one_telephonenumber;
                    }
                    if (data.advisor_one_mobile != 'null' && data.advisor_one_mobile != 'undefined') {
                        agentOneMobile = data.advisor_one_mobile;
                    }
                    if (data.advisor_one_faxnumber != 'null' && data.advisor_one_faxnumber != 'undefined') {
                        agentOneFax = 'FAX: ' + data.advisor_one_faxnumber;
                    }
                    if (data.advisor_one_branch != 'null' && data.advisor_one_branch != 'undefined') {
                        agentOneBranch = data.advisor_one_branch;
                    }

                    // Advisor Two:
                    if (data.advisor_two_name != 'null' && data.advisor_two_name != 'undefined') {
                        agentTwoName = data.advisor_two_name;
                    }
                    if (data.advisor_two_title != 'null' && data.advisor_two_title != 'undefined') {
                        agentTwoTitle = data.advisor_two_title;
                    }
                    if (data.advisor_two_mail != 'null' && data.advisor_two_mail != 'undefined') {
                        agentTwoMail = data.advisor_two_mail;
                    }
                    if (data.advisor_two_imageurl != 'null' && data.advisor_two_imageurl != 'undefined') {
                        agentTwoImageUrl = data.advisor_two_imageurl;
                    }
                    if (data.advisor_two_telephonenumber != 'null' && data.advisor_two_telephonenumber != 'undefined') {
                        agentTwoPhone = data.advisor_two_telephonenumber;
                    }
                    if (data.advisor_two_mobile != 'null' && data.advisor_two_mobile != 'undefined') {
                        agentTwoMobile = data.advisor_two_mobile;
                    }
                    if (data.advisor_two_faxnumber != 'null' && data.advisor_two_faxnumber != 'undefined') {
                        agentTwoFax = 'FAX: ' + data.advisor_two_faxnumber;
                    }
                    if (data.advisor_two_branch != 'null' && data.advisor_two_branch != 'undefined') {
                        agentTwoBranch = data.advisor_two_branch;
                    }

                    // Advisor Three:
                    if (data.advisor_three_name != 'null' && data.advisor_three_name != 'undefined') {
                        agentThreeName = data.advisor_three_name;
                    }
                    if (data.advisor_three_title != 'null' && data.advisor_three_title != 'undefined') {
                        agentThreeTitle = data.advisor_three_title;
                    }
                    if (data.advisor_three_mail != 'null' && data.advisor_three_mail != 'undefined') {
                        agentThreeMail = data.advisor_three_mail;
                    }
                    if (data.advisor_three_imageurl != 'null' && data.advisor_three_imageurl != 'undefined') {
                        agentThreeImageUrl = data.advisor_three_imageurl;
                    }
                    if (data.advisor_three_telephonenumber != 'null' && data.advisor_three_telephonenumber != 'undefined') {
                        agentThreePhone = data.advisor_three_telephonenumber;
                    }
                    if (data.advisor_three_mobile != 'null' && data.advisor_three_mobile != 'undefined') {
                        agentThreeMobile = data.advisor_three_mobile;
                    }
                    if (data.advisor_three_faxnumber != 'null' && data.advisor_three_faxnumber != 'undefined') {
                        agentThreeFax = 'FAX: ' + data.advisor_three_faxnumber;
                    }
                    if (data.advisor_three_branch != 'null' && data.advisor_three_branch != 'undefined') {
                        agentThreeBranch = data.advisor_three_branch;
                    }

                    updateAgentHtml('multipleAdvisorMode');
                } else {
                    console.log('normalMode -> #1');
                    updateAgentHtml('normalMode');
                }
            } else {
                console.log('normalMode -> #2');
                updateAgentHtml('normalMode');
            }
        }
    };
    this.getCustomerInformation = function (data) {
		setOverlayLoaderToLockScreen();
        var htmlData = '';
        if (data.customer_id != '' && data.name != null) {
			jQuery("#headerPLCaptionIcon").removeClass("partlistGrayIcon");
			jQuery("#headerPLCaptionIcon").addClass("partlistBlueIcon");
			jQuery("#headerPLCaptionText").removeClass("gray");
			jQuery("#headerPLCaptionText").addClass("blueTxt");
            if (data.customer_id != 'null') {
                customerId = data.customer_id;
                //sessionStorage.setItem("customerId", customerId);
            }
            if (data.name != 'null') {
                customerName = data.name;
                //sessionStorage.setItem("customerName", customerName);
            }
            if (data.acl_role != 'null') {
                customerAclRole = data.acl_role;
                console.log('Current Role #3 = ' + customerAclRole);
                if(customerAclRole == 'staff' || customerAclRole == 'projectant'){
                    jQuery('.btn-quickAdd').addClass('hide');
                    jQuery('.MyCart').addClass('hide');
                }
                //sessionStorage.setItem("customerName", customerName);
            }
            if (data.image != 'null') {
                customerImage = data.image;
                //sessionStorage.setItem("customerImage", customerImage);
            }
            if (customerImage === null) {
                customerImage = BASE_URL + 'skin/frontend/schrack/default/schrackdesign/Public/Images/dmmuuserImg.png';
            }

            jQuery('#my_account_image_link_in_menu').on('click', function() {
                if (dataLayer) {
                    dataLayer.push({
                        'event' : 'generalLinkblockActions',
                        'eventLabel' : 'My Account Icon Menu',
                    });
                }
            });

            jQuery('#my_account_text_link_in_menu').on('click', function() {
                if (dataLayer) {
                    dataLayer.push({
                        'event' : 'generalLinkblockActions',
                        'eventLabel' : 'My Account Menu',
                    });
                }
            });

            jQuery('#logout_link_in_menu').on('click', function() {
                forceFetchMegaMenu();

                if (dataLayer) {
                    dataLayer.push({
                        'event' : 'generalLinkblockActions',
                        'eventLabel' : 'Logout Menu',
                    });
                }
            });

            var custName = '';
            if (data.name != null) {
                custName = data.name;
            }
            var CID = '';
            if (data.customer_id != null) {
                CID = data.translation_cid + ': ' + data.customer_id;
            }
            var compName = '';
            if (data.company_name != null) {
                compName = data.company_name;
            }

            var fav_customer_list = '';
            if (data.fav_customer_list != null) {
                fav_customer_list = data.fav_customer_list;
            }

            var imagePath = 'skin/frontend/schrack/default/schrackdesign/' +
                            'Public/Images/';

            var imageUrl1 = BASE_URL + imagePath + 'dmmuuserImg_blue_small.png';
            var imageUrl2 = BASE_URL + imagePath +
                            'logout_rectangle_blue_small.png';

            //------------------------------ Menu with "act as Customer" feature
            var customerProfileLinkHTML =
                '<div id="customerAccContainer" class="pull-left">' +
                    '<div id="customerLogInContainer" ' +
                        'class="userLogdIn pull-left" ' +
                        'title="' + Translator.translate('My Account') + '">' +
                        '<img src="' + customerImage + '" ' +
                            'alt="' + customerName + '"/>' +
                    '</div>' +
                '</div>' +
                '<div class="headerActionsLayer">' +
                    '<div class="arrow_up"></div>' +
                    '<div class="headerActionsLayerContentWrapper">' +
                        //--------------------------------- Personal Information
                        '<div class="headerActionsLayerContentRow1">' +
                            custName +
                        '</div>' +
                        '<div class="headerActionsLayerContentRow2">' +
                            CID +
                        '</div>' +
                        '<div class="headerActionsLayerContentRow3">' +
                            compName +
                        '</div>' +
                        //------------------------- My Account + Logout Btn Area
                        '<div class="headerActionsLayerContentRow4">' +
                            '<div class="headerActionsLayerContentRow4Wrapper1">' +
                                '<img src="' + imageUrl1 + '" ' +
                                    'class="my_account_image">' +
                                '<span id="my_account_text">' +
                                    Translator.translate('My Account') +
                                '</span>' +
                                '<div style="clear: both;"></div>' +
                            '</div>' +
                            '<div class="headerActionsLayerContentRow4Wrapper2">' +
                                '<img src="' + imageUrl2 + '" ' +
                                    'class="my_logout_link_image">' +
                                '<span id="my_logout_text">' +
                                    Translator.translate('Logout') +
                                '</span>' +
                                '<div style="clear: both;"></div>' +
                            '</div>' +
                        '</div>'+
                        //----------- actAsCustomer searchbar and favourite list
                        //--- variables with translation stored in header.php!!!
                        '<div class="headerActionsLayerContentRow5">' +
                            '<span class="aac_headline">' +
                                actAsCustomerSearchbarHeadline +
                            '</span>' +
                            //------------------ Act as customer Search Headline
                            '<span class="aac_headline_L2">' +
                                actAsCustomerSearchHeadline +
                            '</span>' +
                            '<div class="actAsCustomerSearchbarContainer" >' +
                                //-------------- "Show my customers only" Switch
                                '<div class="aac_favouriteListFilterSearch">' +
                                    '<label class="aac_favouriteListFilterLabel" ' +
                                        'for="actAsCustomerListMyCustomersOnlySearchbar">' +
                                        actAsCustomerSearchMyCustomersOnlyTxt +
                                    '</label>' +
                                    '<input class="form-check-input" type="checkbox" ' +
                                        'id="actAsCustomerListMyCustomersOnlySearchbar" value="on" ' + (localStorage.actAsCustomerSearchMyCustomersOnly == 'on' ? 'checked' : '') + ' />' +
                                '</div>' +
                                //----------------------------------- Search Bar
                                '<input id="actAsCustomerSearchbar" autocomplete="off" />' +
                                '<span id="aac_clearSearchbarIcon">X</span>' +
                                //-------------------------------- Search Button
                                '<span class="actAsCustomerMagnifierButton" >' +
                                    '<span id="aac_loadingSpinner" class="hide" />' +
                                    '<span id="aac_searchMagnifierImg" ' +
                                        'class="glyphicon glyphicon-search show_ib" />' +
                                '</span>' +
                            '</div>' +
                            '<div id="actAsCustomerSearchbarResultContainer" >' +
                                    //----- "Act as customer" Search Result List
                                '<ul id="actAsCustomerSearchbarResultList"></ul>' +
                            '</div>' +
                            '<div class="actAsCustomerListContainer" >' +
                                //--------------- Act as customer Favourite List
                                '<span class="aac_headline_L2">' +
                                    actAsCustomerFavouritesHeadline +
                                '</span>' +
                                //-------------- "Show my customers only" Switch
                                '<div class="aac_favouriteListFilter">' +
                                    '<label class="aac_favouriteListFilterLabel" ' +
                                    'for="actAsCustomerListMyCustomersOnly">' +
                                        actAsCustomerMyCustomersOnlyTxt +
                                    '</label>' +
                                    '<input class="form-check-input" type="checkbox" ' +
                                        'id="actAsCustomerListMyCustomersOnly" value="on" ' + (localStorage.actAsCustomerFavouritesMyCustomersOnly == 'on' ? 'checked' : '') + ' />' +
                                '</div>' +
                                '<div class="actAsCustomerList" >' +
                                    //------------------- "Act as customer" List
                                    '<ul id="actAsCustomerFavouritesList"></ul>' +
                                '</div>' +
                            '</div>' +
                        '</div>'+
                    '</div>'+
                '</div>';

            //---------------------- Act as customer form appending to container
            jQuery("#customerProfileLink").html(customerProfileLinkHTML);
            jQuery(actAsCustomerLoginForm).appendTo('#customerProfileLink');
            jQuery('.headerActionsLayerContentRow5').show();
            //-------------------- Hide "act as customer" searchbar & favourites
            //-------------------------------- if allready logged in as customer
            if ( localStorage.actAsACustomer == 1 || localStorage.actAsACustomerAllowed == 0 ) {
                jQuery('.headerActionsLayerContentRow5').hide();
            }
            //---------------------------------- no favourites added to list msg
            var aac_no_fav_msg = '<li class="aac_no_fav_li">' +
                                    actAsCustomerNoFavouritesDefinedTxt +
                                 '</li>';
            //-------------------------------------------- append results or msg
            aac_FavListItems = aac_FavListItems === '' ? aac_no_fav_msg : aac_FavListItems;
            jQuery(aac_FavListItems).appendTo('#actAsCustomerFavouritesList');
            if(localStorage.actAsCustomerFavouritesMyCustomersOnly == 'on'){
                jQuery("#actAsCustomerFavouritesList .defaultCustomer").hide();
            }

            //-------------------- add "Act as customer" entry to favourite list
            jQuery('#actAsCustomerSearchbarResultList').on('click', '.addFavouriteCustomer', function(){
            //------------------------------------------------------------------
                var arrayContent = jQuery(this).data('aacFavobject');
                var doubleEntryFound = false;
                var addList = false;
                //---------------------------- stop hide animation if set before
                clearTimeout(actAsCustomerActionMsgInterval);
                //------- check if add result list to favourites btn was clicked
                if(jQuery(this).is('#addAllFavouriteCustomer')){
                    addList = true;
                }
                //--------------------------------------- prevent double entries
                //----------------------------------------------------- for list
                if(addList){
                    for (var index = 0; index < aac_searchResultList.customers.length; index++) {
                        for (var index2 = 0; index2 < aac_favouriteList.customers.length; index2++) {
                            //-------- if found, clear entry for later clearance
                            if(aac_favouriteList.customers[index2].aac_id == aac_searchResultList.customers[index].aac_id){
                                doubleEntryFound = true;
                                aac_searchResultList.customers[index] = "";
                            }
                        }
                    }
                    if(doubleEntryFound){
                        var listlen = aac_searchResultList.customers.length;
                        //---------------------- check for non empty entries and
                        //------------------------- add them to the end of array
                        for (var index = 0; index < listlen; index++) {
                            if(aac_searchResultList.customers[index]){
                                aac_searchResultList.customers.push(aac_searchResultList.customers[index]);
                            }
                        }
                        //------------------------------- cut all origin entries
                        aac_searchResultList.customers.splice(0 , listlen);
                    }
                    /***********************************************************
                         if all entries were removed because they are
                         already existing in favourites,
                         [doubleEntryFound] stays true
                         and [addList] will set back to false
                         this combination is preventing "execute upadte"
                    ***********************************************************/
                    if(typeof aac_searchResultList.customers === "undefined" || aac_searchResultList.customers.length < 1){
                        aac_searchResultList = {"customers":[]};
                        //--------------------------------------- deactivate btn
                        jQuery('#addAllFavouriteCustomer').toggleClass('addFavouriteCustomer addFavouriteCustomerInactive');
                        //----------------------------- no entries added msg txt
                        jQuery('#Aac_AddFavouritesMsgWrapper .messages .success-msg .success-msg-txt').text(actAsCustomerNoneAddToFavouritesMsgTxt);
                        //--------------------- display and auto hide action msg
                        jQuery('#Aac_AddFavouritesMsgWrapper').removeAttr("style").removeClass('hide');
                        actAsCustomerActionMsgInterval = setTimeout(function(){ jQuery('#Aac_AddFavouritesMsgWrapper').fadeOut(1000).addClass('hide'); },5000);
                        //------------------------------------------------- exit
                        return false;
                    }
                } else { //------------------------------------ for single entry
                    for (var index = 0; index < aac_favouriteList.customers.length; index++) {
                        if(aac_favouriteList.customers[index].aac_id == arrayContent.aac_id){
                            doubleEntryFound = true;
                        }
                    }
                    if(doubleEntryFound){
                        //----------------------------- no entries added msg txt
                        jQuery("#actAsCustomerSearchbarResultContainer").animate({scrollTop: 0});
                        jQuery('#Aac_AddFavouritesMsgWrapper .messages .success-msg .success-msg-txt').text(actAsCustomerNoneAddToFavouritesMsgTxt);
                        //--------------------- display and auto hide action msg
                        jQuery('#Aac_AddFavouritesMsgWrapper').removeAttr("style").removeClass('hide');
                        actAsCustomerActionMsgInterval = setTimeout(function(){ jQuery('#Aac_AddFavouritesMsgWrapper').fadeOut(1000).addClass('hide'); },5000);
                    }
                }
                //----------------------------------------------- execute update
                if(!doubleEntryFound || addList){
                    //--------------------------------------------- merge data's
                    if(addList){
                        //--------------------------------------- deactivate btn
                        jQuery('#addAllFavouriteCustomer').toggleClass('addFavouriteCustomer addFavouriteCustomerInactive');
                        aac_favouriteList.customers = aac_favouriteList.customers.concat(aac_searchResultList.customers);
                    } else {
                        aac_favouriteList.customers.push(arrayContent);
                    }
                    //------------------------------------------ success msg txt
                    jQuery("#actAsCustomerSearchbarResultContainer").animate({scrollTop: 0});
                    jQuery('#Aac_AddFavouritesMsgWrapper .messages .success-msg .success-msg-txt').text(actAsCustomerAddToFavouritesOkMsgTxt);
                    //--------------------------------------- display action msg
                    jQuery('#Aac_AddFavouritesMsgWrapper').removeAttr("style").removeClass('hide');
                    hideMsg = setTimeout(function(){ jQuery('#Aac_AddFavouritesMsgWrapper').fadeOut(1000).addClass('hide'); },5000);
                    //------------------------------------------------ ajax call
                    jQuery.ajax(aac_AddFavouriteUrl,{
                        'type': 'post',
                        'data': aac_favouriteList,
                        'success': function () {
                            aac_FavListItems = '';
                            for(var index = 0; index < aac_favouriteList.customers.length; index++){
                                aac_FavListItems += actAsCustomerResultListItem(aac_favouriteList.customers[index], '');
                            }
                            jQuery('#actAsCustomerFavouritesList').empty();
                            jQuery(aac_FavListItems).appendTo( jQuery('#actAsCustomerFavouritesList') );
                         }
                    });
                }
            });


            //--------------- remove "Act as customer" entry from favourite list
            jQuery('#actAsCustomerFavouritesList').on('click', '.removeFavouriteCustomer', function(){
                var arrayContent = jQuery(this).data('aacFavobject');
                var entryFound = -1;
                var lastExistingEntry = false;
                //--------------------------------------- prevent double entries
                for (var index = 0; index < aac_favouriteList.customers.length; index++) {
                    if(aac_favouriteList.customers[index].aac_id == arrayContent.aac_id){
                        if(aac_favouriteList.customers.length == 1){
                            lastExistingEntry = true;
                        }
                        aac_favouriteList.customers.splice(index, 1);
                        entryFound = index;
                    }
                }
                if(lastExistingEntry){
                    aac_favouriteList = '';
                }
                //----------------------------------------------- execute update
                if(entryFound >= 0){
                    jQuery.ajax(aac_AddFavouriteUrl,{
                        'type': 'post',
                        'data': aac_favouriteList,
                        'success': function () {
                            jQuery('#actAsCustomerFavouritesList').empty();
                            aac_FavListItems = '';
                            if(!lastExistingEntry) {
                                for (var index = 0; index < aac_favouriteList.customers.length; index++) {
                                    aac_FavListItems += actAsCustomerResultListItem(aac_favouriteList.customers[index], '');
                                }
                            } else {
                                aac_FavListItems = aac_no_fav_msg;
                                // re initiate after deleting last entry from favourite list
                                if(typeof aac_favouriteList.customers === "undefined" || aac_favouriteList.customers.length < 1){
                                    aac_favouriteList = {"customers":[]};
                                }
                            }
                            jQuery(aac_FavListItems).appendTo( jQuery('#actAsCustomerFavouritesList') );
                        }
                    });
                }
            });

            //---------------- select Customer for login overlay with transition
            jQuery("#actAsCustomerFavouritesList, #actAsCustomerSearchbarResultList").on('click',
                '.aac_text_rows', function () {
                    jQuery(this).parent().hide().next().addClass("active").fadeTo( "fast", 1.00 );
            });

            //-------------------------------- login as Customer selection ABORT
            jQuery("#actAsCustomerFavouritesList, #actAsCustomerSearchbarResultList").on('click',
                '.aac_login_abort', function () {
                jQuery(this).parent().fadeOut(500).removeClass("active").prev().fadeIn(500);
                localStorage.actAsACustomer = 0;
                localStorage.actAsACustomerRealEmail = '';
                //--------------------------------------------- clear form field
                jQuery('#aac_customer_id').val('');
            });

            //---------------------------------------- login as Customer in list
            jQuery("#actAsCustomerFavouritesList, #actAsCustomerSearchbarResultList").on('click', '.aac_login', function () {
                var aac_id = jQuery(this).data("aacCustomerid");
                localStorage.actAsACustomer = 1;
                localStorage.actAsACustomerRealEmail = aac_realEmail;
                //------------------------------------------- fill and send form
                console.log("typeof jQuery('#aac_customer_id')");
                console.log(typeof jQuery('#aac_customer_id'));
                jQuery('#aac_customer_id').val(aac_id);
                jQuery('#aac_act_as_a_customer_form').submit();
            });


            jQuery('#customerLogInContainer').on('click', function() {
                // actAsCustomerSearchbarHeadline = Translator.translate('Act As Customer');

                if( jQuery('.overlay').length == 0 ){
                    jQuery('body').append('<div class="overlay"></div>');
                }
                jQuery('.overlay').show();
                jQuery('.overlay').on('click', function(){
                    if (localStorage.darkBackground == 'true') {
                        jQuery('.overlay').hide();
                        jQuery('.headerActionsLayer').hide();
                    }
                });
                localStorage.darkBackground = 'true';
                jQuery('.headerActionsLayer').show();
                if (dataLayer) {
                    dataLayer.push({
                        'event' : 'generalLinkblockActions',
                        'eventLabel' : 'My Account Icon Header',
                    });
                }
            });

            jQuery('.headerActionsLayerContentRow4Wrapper1').on('click', function() {
                window.location = BASE_URL + 'customer/account';
                if (dataLayer) {
                    dataLayer.push({
                        'event' : 'generalLinkblockActions',
                        'eventLabel' : 'My Account Header',
                    });
                }
            });

            jQuery('.headerActionsLayerContentRow4Wrapper2').on('click', function() {
                forceFetchMegaMenu();
                window.location = BASE_URL + 'customer/account/logout';
                if (dataLayer) {
                    dataLayer.push({
                        'event' : 'generalLinkblockActions',
                        'eventLabel' : 'Logout Header',
                    });
                }
            });

            jQuery("#agentInfoHead").addClass("userLogdIn");
            jQuery("#headerLinks").fadeIn(500);

            jQuery("#dashCustName").text(customerName);
            jQuery("#dashCusID").text(customerId);

            //----------------------------------------------- jQuery Switchboxes
            jQuery.switcher('#actAsCustomerListMyCustomersOnly, #actAsCustomerListMyCustomersOnlySearchbar');
            //--- DISPLAY my customers in act as a custmomer favourite list only

            jQuery("#actAsCustomerListMyCustomersOnly").on('change', function() {
                if(jQuery(this).is(':checked')) {
                    localStorage.actAsCustomerFavouritesMyCustomersOnly = 'on';
                    jQuery("#actAsCustomerFavouritesList .defaultCustomer").hide();  // checked
                } else {
                    localStorage.actAsCustomerFavouritesMyCustomersOnly = 'off';
                    jQuery("#actAsCustomerFavouritesList .defaultCustomer").show();  // unchecked
                }
            });
            //------ SEARCH for my customers in act as a customer searchbar only
            //----------------------------------- trigger search again on change
            jQuery("#actAsCustomerListMyCustomersOnlySearchbar").on('change', function() {
                if(jQuery(this).is(':checked')){
                    localStorage.actAsCustomerSearchMyCustomersOnly = 'on';
                } else {
                    localStorage.actAsCustomerSearchMyCustomersOnly = 'off';
                }
                jQuery('#actAsCustomerSearchbar').trigger('keyup');
            });
            //-------------------------------- Clear "Act as customer" Searchbar
            jQuery("#aac_clearSearchbarIcon").on('click', function() {
                jQuery('#actAsCustomerSearchbar').val('');
                jQuery('#actAsCustomerSearchbarResultList').removeClass('active').html('');
                jQuery('#aac_clearSearchbarIcon').removeClass('show');
            });
            //-------------------------------------- "Act as customer" Searchbar
            var timeout;
            var delay = 1000;
            jQuery('#actAsCustomerSearchbar').on('keyup paste', function(evt) {
                jQuery('#aac_clearSearchbarIcon').addClass('show');
                jQuery('#actAsCustomerSearchbar').css('color', 'black');
                /*******************************************************
                 if search input is numeric and length > 3 (4 CHAR)
                 OR is string and length > 2 (3 CHAR)
                 => send request
                 *******************************************************/
                if ((isNumber(jQuery('#actAsCustomerSearchbar').val()) && jQuery('#actAsCustomerSearchbar').val().length > 3) ||
                    (!isNumber(jQuery('#actAsCustomerSearchbar').val()) && jQuery('#actAsCustomerSearchbar').val().length > 2)) {
                    jQuery('#actAsCustomerSearchbar').val(jQuery('#actAsCustomerSearchbar').val().replace(/["';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/g, ""));
                    //-------- reset timeout if allready initiated on prev keyup
                    if(timeout) { clearTimeout(timeout); }
                    //----------------- start timeout and execute search request
                    timeout = setTimeout(function () {
                        actAsCustomerSearchRequest();
                    }, delay);
                } else { //--------------------------- clear result list
                    jQuery('#actAsCustomerSearchbarResultList').removeClass('active').html('');
                    if(jQuery('#actAsCustomerSearchbar').val().length == 0){
                        jQuery('#aac_clearSearchbarIcon').removeClass('show');
                    }
                }
            });
            //----------------- Add "Act as customer" searchbar placeholder text
            jQuery('#actAsCustomerSearchbar').attr("placeholder", actAsCustomerSearchbarPlaceholder);
        } else {
            jQuery("#headerPLCaptionIcon").removeClass("partlistBlueIcon");
            jQuery("#headerPLCaptionIcon").addClass("partlistGrayIcon");
			jQuery("#headerPLCaptionText").removeClass("blueTxt");
			jQuery("#headerPLCaptionText").addClass("gray");

            jQuery('#link-nav-login-button').on('click', function () {
                window.location = BASE_URL + 'customer/account/login';
                if (dataLayer) {
                    dataLayer.push({
                        'event' : 'generalLinkblockActions',
                        'eventLabel' : 'Login Menu',
                    });
                }
            });

            var login_img_mobile = BASE_URL +
                                   'skin/frontend/schrack/default/schrackdesign/' +
                                   'Public/Images/rwd/login_icon_mobile.png';

            var customerProfileLinkHTML =
                '<div id="link_header_login_button" ' +
                    'class="square_header_login_button" ' +
                    'title="' + Translator.translate('Login') + '">' +
                        '<img id="login_image_mobile" ' +
                            'src="' + login_img_mobile + '" />' +
                    '</div>' +
                    '<ul class="dropdown-menu"></ul>'+
                '</div>';

            jQuery("#customerProfileLink").html(customerProfileLinkHTML);

            jQuery("#agentInfoHead").addClass("userLogdIn");
            jQuery("#headerLinks").fadeIn(500);

            jQuery('#link_header_login_button').on('click', function () {
                localStorage.customerLoggedInEmail = '';
                window.location = BASE_URL + 'customer/account/login';
                if (dataLayer) {
                    dataLayer.push({
                        'event' : 'generalLinkblockActions',
                        'eventLabel' : 'Login Header',
                    });
                }
            });
        }

        jQuery('.logout_action').on('click', function(){
            localStorage.customerNotLoggedIn = "1";
            localStorage.actAsACustomer = 0;
            localStorage.actAsACustomerRealEmail = '';
            localStorage.customerLoggedInEmail = '';
            console.log('Logout Button #1 Clicked');
        });

		if (data.site_header_info_status == 1) {
			jQuery("#siteHeaderInfoIconLink").attr("href", data.site_header_info_link);
			jQuery("#siteHeaderInfoIcon").show();
		} else {
			jQuery("#siteHeaderInfoIcon").hide();
		}
		unsetOverlayLoader();
    };
    this.getCartItemCount = function (data) {
        if (data != '' && data != null) {
            jQuery('.MyCart').append('<div id="cartNoBxItemCount" class="cartNoBx">' + data + '</div>');
        }
    };

    this.getFeaturedProducts = function (data) {
        var translation_service_url = BASE_URL + '/onlinetools/commonTools/getTranslations';
        var requiredTranslationsObject = new Object();
        requiredTranslationsObject.key001 = 'NOT AVAILABLE';
        var that = this;
        jQuery.ajax(translation_service_url, {
            'dataType': 'json',
            'type': 'POST',
            'data': requiredTranslationsObject,
            'success': function (responseData) {
                if (responseData != '' && responseData != null) {
                    var parsedData = responseData;
                    jQuery.each(parsedData, function (key, value) {
                        Translator.add(key, value);
                    });
                    that.getSliderElements(data, jQuery('.featureProdCont'), jQuery('#featureSliderCon'));
                }
            }
        });
    };

    this.setListPrices = function(data, targetContainerUlClassName) {
        if (typeof (data) === 'object') {
            var containerElement = jQuery('.'+targetContainerUlClassName);
            jQuery.each(data, function (i, item) {
                var htmlData = '';
                htmlData =
                    data[i].currency +
                    ' <span class="general_current_price">' + data[i].currentprice + '</span>' +
                    '/' + data[i].priceunit + ' ' + data[i].qtyunit;
                jQuery(containerElement.find('.price_' + i)).html(htmlData);
                jQuery(containerElement.find('#' + i)).data('price', data[i].currentprice).data('currencycode', data[i].currency);
            });
            jQuery(containerElement.find('.slider-cart')).show();
        }
    };

    this.getSliderElements = function (data, targetList, targetContainer) {
        var htmlData = '';
        var imageLightbox = '';
        var addToCartImage = BASE_URL + 'skin/frontend/schrack/default/schrackdesign/Public/Images/rwd/cartIconWht.png';
        var classes = jQuery.trim(targetList.attr('class')).split(/\s+/);

        var sliderContext = '';
        if (targetList) {
            var startPage = false;
            var currentPage = window.location.href;
            var possibleTypoStartpages = [
                "https://www.schrack.at/",
                "https://www.schrack.ba/",
                "https://www.schrack.be/",
                "https://www.schrack.bg/",
                "http://127.0.0.1/",
                "https://www.schrack.cz/",
                "https://www.schrack-technik.de/",
                "https://www.schrack.hr/",
                "https://www.schrack.hu/",
                "https://www.schrack-technik.nl/",
                "https://www.schrack.pl/",
                "https://www.schrack.ro/",
                "https://www.schrack.rs/",
                "https://www.schrack-technik.ru/",
                "https://www.schrack.sa/",
                "https://www.schrack.si/",
                "https://www.schrack.sk/",
                "https://test-at.schrack.com/",
                "https://test-ba.schrack.com/",
                "https://test-be.schrack.com/",
                "https://test-bg.schrack.com/",
                "https://test-com.schrack.com/",
                "https://test-cz.schrack.com/",
                "https://test-de.schrack.com/",
                "https://test-hr.schrack.com/",
                "https://test-hu.schrack.com/",
                "https://test-nl.schrack.com/",
                "https://test-pl.schrack.com/",
                "https://test-ro.schrack.com/",
                "https://test-rs.schrack.com/",
                "https://test-ru.schrack.com/",
                "https://test-sa.schrack.com/",
                "https://test-si.schrack.com/",
                "https://test-sk.schrack.com/"
            ];

            if (possibleTypoStartpages.indexOf(currentPage) > -1) {
                startPage = true;
            }
            if (typeof targetList != 'undefined'
                && typeof targetList.attr('class') != 'undefined'
                && typeof targetList.attr('class').indexOf('featureProdCont') != 'undefined'
                && targetList.attr('class').indexOf('featureProdCont') != -1
                && startPage == true) {
                sliderContext = 'TYPO Slider Startpage Last Viewed';
            }
            if (typeof targetList != 'undefined'
                && typeof targetList.attr('class') != 'undefined'
                && typeof targetList.attr('class').indexOf('promotionProdCont') != 'undefined'
                && targetList.attr('class').indexOf('promotionProdCont') != -1
                && startPage == true) {
                sliderContext = 'TYPO Slider Startpage Promotions';
            }
            if (typeof targetList != 'undefined'
                && typeof targetList.attr('class') != 'undefined'
                && typeof targetList.attr('class').indexOf('solrProdCont') != 'undefined'
                && targetList.attr('class').indexOf('solrProdCont')
                && startPage == false) {
                sliderContext = 'TYPO Slider Content Page';
            }
        }

        if (typeof (data) === 'object') {
            jQuery.each(data, function (i, item) {
                if (typeof (data[i].url) == 'undefined' || data[i].url == 'undefined' || data[i].url == null || data[i].url == false) {
                    console.log('No Slider Elements Found');
                } else {
                    imageLightbox = data[i].imageLightbox;
                    htmlCart = '';
                    if (!imageLightbox) {
                        imageLightbox = data[i].image;
                    }
                    var validForWebshop = true;
                    if (data[i].statuslocal == 'strategic_no' ||
                        data[i].statuslocal == 'unsaleable' ||
                        data[i].statuslocal == 'gesperrt' ||
                        data[i].statuslocal == 'tot') {
                        validForWebshop = false;
                    }
                    if (data[i].saleable && validForWebshop == true) {
                        htmlCart = '<div><input type="number" maxlength="12" class="qty-' + data[i].sku + '" onkeydown="if (event.which === 13){orderItem(\'' + data[i].sku + '\',  \'' + classes.join('.') + '\', \'' + sliderContext + '\', \'' + data[i].name + '\', \'' + data[i].category + '\')}"></div>' +
                            '<div> <button class="addToCartButton" onclick="orderItem(\'' + data[i].sku + '\',  \'' + classes.join('.') + '\', \'' + sliderContext + '\', \'' + data[i].name + '\', \'' + data[i].category + '\')"><img class="addToCartImage" src="' + addToCartImage + '" /></button></div>';
                    }
                    htmlData += '' +
                        '<li class="slide product-details productSliderList">' +
                        '<div class="imgBox other-actions product_' + data[i].sku + '">' +
                        '<div>' + data[i].sku + '</div>';
                         if (typeof targetList.attr('class') != 'undefined' && targetList.attr('class').indexOf('ddmProdCont') === -1 && validForWebshop == true) {
                              htmlData +=  '<div class="wishListDropdown"> ' +
                             '<div class="glyphicon glyphicon-pushpin pin-icon" data-toggle="dropdown"></div> ' +
                             '<ul class="dropdown-list dropdown-menu wishListDropdown" aria-labelledby="parlistdropdownbtn-' + data[i].sku + '"></ul>' +
                             '</div>';
                         }
                         htmlData +='<a class="previewImageHover" data-preview-path="' + imageLightbox + '" title="' + data[i].name + '" href="' + data[i].url + '">' +
                        '<img data-src="' + data[i].image + '" class="lazy productImage" src="' + data[i].image + '" alt="' + data[i].name + '" title="' + data[i].name + '"/>' +
                        '</a>' +
                        '</div>' +
                        '<div class="productName"><a href="' + data[i].url + '">' + data[i].name + '</a></div>';
                        if (validForWebshop == true) {
                            if (typeof targetList.attr('class') != 'undefined' && targetList.attr('class').indexOf('ddmProdCont') !== -1) {
                                if(data[i].offer !== data[i].price) {
                                    htmlData += '<div class="price">  ' + data[i].currency + ' <span class="general_current_price offer">' + data[i].offer+ '</span> ' + data[i].priceunit + '/' + data[i].qtyunit + ' </div>' +
                                                '<div class="price not_actual">  ' + data[i].currency + '  <span class="general_current_price">' + data[i].price + '</span> ' + data[i].priceunit + '/' + data[i].qtyunit + ' </div>';
                                } else {
                                    htmlData += '<div class="price only_offer">  ' + data[i].currency + ' <span class="general_current_price">' + data[i].price + '</span> ' + data[i].priceunit + '/' + data[i].qtyunit + ' </div>';
                                }
                                htmlData += '<a class="addToCartButton" href="' + data[i].url + '"> ' + Translator.translate('To offer') + ' </a>';
                            } else {
                                htmlData +=
                                    '<div class="price price_'+ data[i].sku +'"><img class="ajaxSpinnerListPriceLoading" src="' + data[i].ajaxLoader + '"/> </div>' +
                                    '<div style="display:hidden" id="' + data[i].sku + '" data-tracking-enabled="enabled" data-name="' + data[i].name + '" data-category="' + data[i].category + '" data-price="" data-currencyCode=""></div>' +
                                    '<div class="slider-cart" style="display: none">' + htmlCart + '</div>';
                            }
                        } else {
                            htmlData += '<div style="color: #aaa;">' + Translator.translate('NOT AVAILABLE'); + '</div>';
                        }
                        htmlData += '</li>';
                }
            });
        }

        if (htmlData != '') {
            targetList.html(htmlData);
            // We have to show the container before slider init, or the pagination randomly messes up
            targetContainer.show();
            //if(jQuery('.featureProdCont li').size() > 1){
            //Feature Product Slider
            targetList.bxSlider({
                touchEnabled: touchDisableForDesktop,
                useCSS: false,
                auto: false,
                pager: false,
                slideWidth: 210,
                minSlides: 1,
                maxSlides: 5,
                moveSlides: 1,
                infiniteLoop: false,
                hideControlOnEnd: true,
                onSliderLoad: function (currentIndex) {
                    jQueryLazyLoader.update();
                }
            });
            //}
        }

        if (jQuery(window).width() > 992) {
            imagePreviewHover();
        }

    };

    this.getPromotionProducts = function (data) {
        if (typeof (data) === 'object' && (customerId != '' || customerName != '')) {
            this.getSliderElements(data, jQuery('.promotionProdCont'), jQuery('#promotionSliderCon'));
        }
    };


    //========================================================= getProductPrices
    this.getProductPrices = function (data) {
    //==========================================================================
        jQuery.each(data, function (sku, item) {
            if ( !data[sku].mayseeprices ) {
                var htmlDataOnRequest = '<span class="span_on_request">' + Translator.translate('Price') + '&nbsp;' + Translator.translate('on request') + '</span>';
                if (jQuery('#product_' + sku + ' .product-price').length) {
                    jQuery('#product_' + sku + ' .product-price').empty();
                    jQuery('#product_' + sku + ' .product-price').prepend(htmlDataOnRequest);
                    jQuery('#product_' + sku + ' .product-price').removeClass('hide');
                } else {

                    var addToCartContainerHTML = '<div class="general_current_addtocart_container" ';
                    addToCartContainerHTML    += 'style="margin-top: 11px" ';
                    addToCartContainerHTML    += 'id="general_current_addtocart_container' + sku + '" >';

                    var quantityAddToCartHTML = '<input type="text" class="qtyaddtocartfield qty-' + sku + '" ';
                    quantityAddToCartHTML    += 'id="qtyaddtocartfield' + sku + '" name="qty-' + sku + '" data-sku="' + sku + '">';
                    var addToCartButtonImageHTML = '<img class="addToCartImage loading cartButtonImage"';
                    addToCartButtonImageHTML    += ' src="' + globalADD_TO_CART_BUTTON_IMAGE_SRC + '" >';
                    var salesUnitQtyHTML = 'data-salesunitqty="' + data[sku].priceunit + '"';
                    var addToCartButtonHMTML = '<button id="addtocart-' + sku + '" class="bttn-sm qtyaddtocartbutton vtc"';
                    addToCartButtonHMTML    += ' data-sku="' + sku + '" ' + salesUnitQtyHTML + ' >'
                    addToCartButtonHMTML    += addToCartButtonImageHTML + '</button>';
                    addToCartContainerHTML += quantityAddToCartHTML + addToCartButtonHMTML + '</div>';

                    PAGETYPE = 'SEARCH_RESULTS'; // Sets the value for set featureSource ("search result page")
                    var hiddenItemDataQtyHTML = '<input type="hidden" id="qty-' + sku + '" value="' + data[sku].priceunit + '">';
                    var hiddenItemDataProductHTML = '<input type="hidden" id="productId-' + sku + '" value="' + sku + '">';
                    var hiddenDataHTML = hiddenItemDataQtyHTML + hiddenItemDataProductHTML;

                    var priceAddToCartContainerHTML = htmlDataOnRequest + addToCartContainerHTML + hiddenDataHTML;

                    jQuery('#product_' + sku + ' .modified_price_label').html(priceAddToCartContainerHTML);
                    jQuery('#product_' + sku + ' .modified_price_label_new').html(priceAddToCartContainerHTML);

                }
                jQuery('.sml').addClass('hide');
                jQuery('.finalPrice').addClass('hide');
            } else {
                /******************************************* data[sku].promotype
                    values => "normal" | "promotion" | "sales"
                ***************************************************************/
                var promoType = data[sku].promotype ? data[sku].promotype : "unknown";
                //------------------------------------------------ sales article
                var salesArticle = (promoType == 'sales') ? true : false;
                /***************************************************************
                                                       (P)roduct (D)etail (P)age
                ***************************************************************/
                var priceString = data[sku].currency + '&nbsp;<span class="span_currentprice3 general_current_price">' + data[sku].currentprice + '</span>/' + data[sku].formattedPriceunit;
                //--------------- additional instead price if product is in sale
                if ( salesArticle && parseFloat(data[sku].saving) > 0 ) {
                    priceString += ' <div class="general_current_price instead_price">' + Translator.translate('instead') + ' ' + data[sku].currency + '&nbsp;' + data[sku].regularprice + '</div>';
                }
                //--------------------------------------------- add to container
                jQuery('#product_' + sku + ' .product-price').prepend(priceString);
                /***************************************************************
                             (P)roduct (L)ist (P)age + (P)roduct (S)earch (P)age
                ***************************************************************/
                //----------------------------------------- price currency block
                var priceCurrencyHTML = '<span class="general_current_price_currency">' +
                                            data[sku].currency +
                                        '</span>';
                //--------------------------------------------- price view block
                var priceViewHTML = '<span class="span_currentprice5 general_current_price_view">' +
                                        data[sku].currentprice +
                                    '</span>';
                //--------------------------------------------- price unit block
                var formattedPriceUnitHTML = '<span class="general_current_priceunit">' +
                                                 ' / ' + data[sku].formattedPriceunit +
                                             '</span>';
                //--------------------------------- price popup icon + container
                var popupIconContainer = '<span>' +
                                            //----------------------------- icon
                                            '<span class="glyphicon glyphicon-info-sign info-icon"' +
                                                ' id="product-price-icon-' + sku + '"' +
                                                ' data-toggle="dropdown"' +
                                                ' aria-haspopup="true"' +
                                                ' aria-expanded="true">' +
                                            '</span>' +
                                            //---------------------- price popup
                                            '<div class="popupBox qtyBoxCont dropdown-menu"' +
                                                ' aria-labelledby="product-price-icon-' + sku + '">' +
                                            '</div>' +
                                        '</span>';
                //----- if product is restricted hide price info and add to cart
                var cssHide = data[sku].isRestricted ? ' hide' : '';
                //---------------------------------------------- price container
                var priceContainerHTML = '<div class="general_current_price_container' + cssHide + '"' +
                                             ' id="general_current_price_container' + sku + '">' +
                                            priceCurrencyHTML +
                                            priceViewHTML +
                                            formattedPriceUnitHTML +
                                            popupIconContainer +
                                         '</div>';
                //---------------------------------------- quantityAddToCartHTML
                var quantityAddToCartHTML = '<input type="text" class="qtyaddtocartfield qty-' + sku + '"' +
                                                ' id="qtyaddtocartfield' + sku + '" name="qty-' + sku + '" data-sku="' + sku + '" />';
                //------------------------------------- addToCartButtonImageHTML
                var addToCartButtonImageHTML = '<img class="addToCartImage loading cartButtonImage"' +
                                                    ' src="' + globalADD_TO_CART_BUTTON_IMAGE_SRC + '" />';
                //---------------------------------------------------- vtc check
                var vtcDataAttr;
                if(data[sku].vtcMaxQty !== 'undefined'){
                    vtcDataAttr = "data-vtcMaxQty='" + data[sku].vtcMaxQty + "'";
                }
                //----------------------------------------- addToCartButtonHMTML
                var addToCartButtonHMTML = '<button id="addtocart-' + sku + '" class="bttn-sm qtyaddtocartbutton" ' +
                                                vtcDataAttr +
                                               ' data-sku="' + sku + '" data-salesunitqty="' + data[sku].priceunit + '" >' +
                                                addToCartButtonImageHTML +
                                           '</button>';
                //--------------------------------------- addToCartContainerHTML
                var sales_ID_Class = 'general_current_addtocart_container ';
                if (salesArticle == true) {
                    sales_ID_Class = 'general_current_addtocart_container_sales ';
                }
                //--------------------------------------------------------------
                var addToCartContainerHTMLContent = quantityAddToCartHTML + addToCartButtonHMTML;
                //--------------------------------------------------------------
                if(data[sku].isRestricted){
                    addToCartContainerHTMLContent = '<div class="onrequest">' +
                                                        Translator.translate('Currently not orderable') +
                                                    '</div>';
                }
                //--------------------------------------------------------------
                var addToCartContainerHTML = '<div class="' + sales_ID_Class + '"' +
                                                 ' id="' + sales_ID_Class +  sku + '" >' +
                                                 addToCartContainerHTMLContent +
                                             '</div>';
                //--------------------------------------------------------------
                PAGETYPE = 'SEARCH_RESULTS'; // Sets the value for set featureSource ("search result page")
                //--------------------------------------------------------------
                var hiddenItemDataQtyHTML = '<input type="hidden" id="qty-' + sku + '" value="' + data[sku].priceunit + '">';
                var hiddenItemDataProductHTML = '<input type="hidden" id="productId-' + sku + '" value="' + sku + '">';
                var hiddenDataHTML = hiddenItemDataQtyHTML + hiddenItemDataProductHTML;
                //--------------------------------------------------------------
                var priceAddToCartContainerHTML = priceContainerHTML;
                //--------------------------------------------- add to container
                jQuery('#product_' + sku + ' .modified_price_label').html(priceAddToCartContainerHTML + addToCartContainerHTML + hiddenDataHTML);
                jQuery('#product_' + sku + ' .modified_price_label_new').html(priceAddToCartContainerHTML);
                jQuery('#product_' + sku + ' .add_to_cart_wrapper').html(addToCartContainerHTML + hiddenDataHTML);

                //--------------------------------------- partslist (Merkzettel)
                var dropdownPartslistIcon    = '';
                var disabledClass            = '';
                var dropdownPartslistContent = '<ul class="dropdown-list dropdown-menu partslist_dropdown_menu"' +
                                                   ' id="ulDropdownElementSearchResult_' + sku + '"' +
                                                   ' aria-labelledby="parlistdropdownbtn-' + sku + '">' +
                                               '</ul>';
                //--------------------------------------------------------------
                if (localStorage.customerNotLoggedIn == 1) {
                    var url = BASE_URL + 'customer/account/login';
                    disabledClass = ' withoutLgn lgtGray';
                    dropdownPartslistContent = '<ul class="dropdown-list dropdown-menu loginSuggest">' +
                                                   '<li class="add-to-new-partslist">' +
                                                       '<a href="' + url + '">' +
                                                           Translator.translate('Please login first!') +
                                                       '</a>' +
                                                   '</li>' +
                                               '</ul>';
                }
                //--------------------------------- dropdown icon with container
                dropdownPartslistIcon += '<div class="searchResultPartslistIcon" id="searchResultPartslist' + sku + '">' +
                                             '<span class="glyphicon glyphicon-pushpin blueTxt' + disabledClass + '"' +
                                                 ' id="parlistdropdownbtn-' + sku + '" data-toggle="dropdown"' +
                                                 ' aria-haspopup="true" aria-expanded="true"' +
                                                 ' title="' + Translator.translate('Add to partslist') + '" style="cursor:pointer;">' +
                                             '</span>' +
                                             dropdownPartslistContent +
                                         '</div>';
                //------------------------------------------------ add partslist
                if (salesArticle == true && jQuery('#product_' + sku + ' .modified_partslist_icon_sales').length) {
                    jQuery('#product_' + sku + ' .modified_partslist_icon_sales').html(dropdownPartslistIcon);
                }
                jQuery('#product_' + sku + ' .modified_partslist_icon').html(dropdownPartslistIcon);
                jQuery('#product_' + sku + ' .modified_partslist_icon_new').html(dropdownPartslistIcon);

                /*??????????????????????????????????????????????????????????????
                                                      not found in other scripts
                ??????????????????????????????????????????????????????????????*/
                priceInfo[sku] = item;
                //??????????????????????????????????????????????????????????????
                //------------------------------------- price popup content list
                var listItems = '';
                //----------------------------------------------------- headline
                listItems += '<li class="hd">' +
                                Translator.translate('Price Information') +
                                '<span>' +
                                    Translator.translate('For') + ': ' +
                                    data[sku].formattedPriceunit +
                                '</span>' +
                            '</li>';
                //---------------------------------------------------- listprice
                if (typeof(data[sku].listprice) != "undefined") {
                    listItems += '<li>' +
                                    Translator.translate('List Price') +
                                    ':<span class="span_listprice">' +
                                        data[sku].listprice +
                                    '</span>' +
                                '</li>';
                }
                //-------------------------------------------- sale or promotion
                if (promoType == 'promotion' || promoType == 'sales') {
                    listItems += '<li>' +
                                    Translator.translate('Your Regular Price') +
                                    ':<span class="span_regularprice">' +
                                        data[sku].regularprice +
                                    '</span>' +
                                '</li>';
                    //--------------------------------- mark promotion price red
                    //---------------------------------------------- mark in PDP
                    jQuery('#product_' + sku + ' .product-price').addClass('red');
                    var item1 = jQuery('#product_' + sku + ' .product-price span')[0];
                    jQuery('#product_' + sku + ' .product-price').find(item1).addClass('red');
                    //---------------------------------------------- mark in PSP
                    jQuery('#product_' + sku + ' .general_current_price_currency').addClass('redColor'); //for search page
                    jQuery('#product_' + sku + ' .general_current_price_view').addClass('redColor'); //for search page
                    jQuery('#product_' + sku + ' .general_current_priceunit').addClass('redColor'); //for search page
                }

                //-------------------- add price information based on promo type
                switch(promoType){
                    case "promotion": //------------------------------ promotion
                        listItems += '<li>' +
                                        Translator.translate('Your Promotion Price') +
                                        ':<span class="span_currentprice general_current_price">' +
                                            data[sku].currentprice +
                                        '</span>' +
                                    '</li>' +
                                    '<li>' +
                                        Translator.translate('Saving') +
                                        ':<span>' +
                                            data[sku].saving +
                                        '</span>' +
                                    '</li>' +
                                    '<li>' +
                                        Translator.translate('valid till') +
                                        ' :<span>' +
                                            data[sku].promovalidto +
                                        '</span>' +
                                    '</li>';
                        break;
                    case "sales": //-------------------------------------- sales
                        listItems += '<li>' +
                                        Translator.translate('Sales Price') +
                                        ':<span class="span_currentprice4 general_current_price">' +
                                            data[sku].currentprice +
                                        '</span>' +
                                    '</li>' +
                                    '<li>' +
                                        Translator.translate('Saving') +
                                        ':<span>' +
                                            data[sku].saving +
                                        '</span>' +
                                    '</li>';
                        break;
                    default: //-------------------------------- normal / default
                        listItems += '<li>' +
                                        Translator.translate('Your Price') +
                                        ':<span class="span_currentprice6 general_current_price">' +
                                            data[sku].currentprice +
                                        '</span>' +
                                    '</li>';
                        break;
                }
                //------------------------------------------------ cutting costs
                if (typeof (data[sku].cuttingcosts) != "undefined" && data[sku].cuttingcosts !== null) {
                    listItems += '<li>' +
                                    Translator.translate('Possible cutting costs') +
                                    ':<span class="span_cuttingcosts">' +
                                        data[sku].cuttingcosts +
                                    '</span>' +
                                '</li>';
                }
                //--------------------------------------------- price scales ???
                if (typeof (data[sku].prices) != "undefined" && data[sku].prices !== null) {
                    //------------------------------------------------- headline
                    listItems += '<li class="hd">' +
                                    Translator.translate('Price Scale') +
                                    ':<span></span>' +
                                '</li>';
                    //----------------------------------------------- add scales
                    jQuery.each(data[sku].prices, function (j, scalePrice) {
                        listItems += '<li>' +
                                        Translator.translate('from') +
                                        ' :<span class="span_scale_price">' +
                                            scalePrice.qty + '' +
                                            data[sku].qtyunit + '=' +
                                            scalePrice.price +
                                        '</span>' +
                                    '</li>';
                    });
                }
                //------------------------------------------------- surcharge(s)
                if (typeof (data[sku].surcharge) != "undefined") {
                    listItems += '<li class="bt">' +
                                    '∑ ' + Translator.translate('Surcharges') +
                                    '<span class="span_surcharge">' +
                                        data[sku].surcharge +
                                    '</span>' +
                                '</li>' +
                                '<li>' +
                                    Translator.translate('Price incl. surcharge') +
                                    ': <span class="span_price_plus_surcharges">' +
                                        data[sku].priceplussurcharge +
                                    '</span>' +
                                '</li>';
                }
                // Add Extra Transport Costs text for PV and Battery
                if ((data[sku].pvrate != null && data[sku].pvrate !== '0') || (data[sku].batteryrate != null && data[sku].batteryrate !== '0')) {
                    listItems += '<li>' + Translator.translate('Additional transport costs are due for this product') + '</li>';
                }
                //---------------------------------------------- list with items
                var popupContentList = '<ul>' + listItems + '</ul>';
                //---------------------------------------- add list to container
                jQuery('#product_' + sku + ' .product-price .popupBox').append(popupContentList);
                jQuery('#product_' + sku + ' .modified_price_label_new .general_current_price_container .popupBox').append(popupContentList);
                jQuery('#product_' + sku + ' .product-price').removeClass('hide');
                //------------------------------------------------ add sale mark
                if (promoType == 'sales') {
                    jQuery('#product_' + sku + ' .sale_mark').append(Translator.translate(data[sku].promotype));
                    jQuery('#product_' + sku + ' .sale_mark').removeClass('hide');
                }

                // cart items -> html(amount) changed to -> html(data[sku].amount)
                if ( typeof(data[sku].amounts) != "undefined") {
                    jQuery.each(data[sku].amounts, function(cart_id, amount) {
                        jQuery('#product-item-total-currency-' + cart_id).html(data[sku].currency);
                        jQuery('#product-item-total-mobile-currency-' + cart_id).html(data[sku].currency);
                        jQuery('#product-item-total-price-' + cart_id).html(data[sku].amount);
                        jQuery('#product-item-total-mobile-price-' + cart_id).html(data[sku].amount);
                    });
                }

                if ( typeof(data[sku].offerref) != "undefined" ) {
                    // jQuery('.productItemWrapper[data-sku="' + sku + '"]').css('background','#e8f0fa');
                    jQuery('.productItemWrapper[data-sku="' + sku + '"]').css('background','#e8f0fa');
                    var offerHRef = '<a href="' + data[sku].offerrefurl + '">' + data[sku].offerref + '</a>';
                    var offerDiv = '<div class="cartOfferRef">' + Translator.translate('from offer') + ' ' + offerHRef + '</div>';
                    jQuery('#product_' + sku + ' .product-col').append(offerDiv);
                    // jQuery('#product_' + sku).append(offerDiv);
                    // jQuery('#product_XC01010101 .product-col').append('<div class="cartOfferRef">aus Angebot 1234567');
                }

                //jQuery('#product_'+sku+' .product-price .info-icon').click(function(){
                //  jQuery('#product_'+sku+' .product-price .popupBox').toggle();
                //});
                var trackingFeatureSource = '-';

                if (typeof PAGETYPE !== 'undefined') {
                    //console.log('PAGETYPE = ' + PAGETYPE);
                    if (PAGETYPE == 'CART') {
                        trackingFeatureSource = 'cart';
                    } else if (PAGETYPE == 'DETAIL_VIEW') {
                        trackingFeatureSource = 'product detail view';
                    } else if (PAGETYPE == 'SEARCH_RESULTS') {
                        trackingFeatureSource = 'search result page';
                    } else {
                        //console.log('(#2) PAGETYPE is assigned to unknown value or null : ' + PAGETYPE);
                    }
                } else {
                    //console.log('(#2) PAGETYPE is NOT explicitly defined');
                }


                //partlist
                if (partListData != '' && partListData != 'error') {
                    htmlData  = "<!-- position : commonJs.js #124298328 -->";
                    htmlData += "<li class='add-to-new-partslist' onclick='partslistFE.addItemToNewList(\"New parts list\", new ListRequestManager.Product(jQuery(\"#productId-" + sku + "\").val(), jQuery(\"#qty-" + sku + "\").val(), \"" + sku + "\"), \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + sku + "\" ><span class='glyphicon glyphicon-plus-sign plusIcon'></span> " + Translator.translate('Add to new parts list') + "</li>";
                    jQuery.each(partListData, function (j, item) {
                        j = j.replace('\0', '');
                        htmlData += "<li class='partslistRow' onclick='partslistFE.addItemToList(" + j + ", new ListRequestManager.Product(jQuery(\"#productId-" + sku + "\").val(), jQuery(\"#qty-" + sku + "\").val(), \"" + sku + "\"), false, \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + sku + "\" title='" + item + "'>" + Translator.translate("Add to") + " " + item + "</li>";
                    });

                    jQuery('#product_' + sku + ' .dropdown-list').html(htmlData);
                    jQuery('#product_' + sku + ' .dropdown-list').removeClass('withoutLgn');
                    jQuery('#parlistdropdownbtn-' + sku).removeClass('lgtGray');
                } else if (customerId != '' || customerName != '') {
                    htmlData  = "<!-- position : commonJs.js #12263052 -->";
                    htmlData += "<li class='add-to-new-partslist' onclick='partslistFE.addItemToNewList(\"New parts list\", new ListRequestManager.Product(jQuery(\"#productId-" + sku + "\").val(), jQuery(\"#qty-" + sku + "\").val(), \"" + sku + "\"), \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + sku + "\"><span class='glyphicon glyphicon-plus-sign plusIcon darkGray'></span> " + Translator.translate('Add to new parts list') + "</li>";
                    jQuery('#product_' + sku + ' .dropdown-list').html(htmlData);
                    jQuery('#product_' + sku + ' .dropdown-list').removeClass('withoutLgn');
                    jQuery('#parlistdropdownbtn-' + sku).removeClass('lgtGray');
                    jQuery('#product_' + sku + ' .dropdown-list').css("height", "auto");
                    jQuery('#product_' + sku + ' .dropdown-list').css("width", "auto");
                } else {
                    //jQuery('#product_' + sku + ' .dropdown-list').html("<li><a href='"+BASE_URL + 'customer/account/login' title='Login'>Please login first!</a></li>");
                    jQuery('#product_' + sku + ' .dropdown-list').css("height", "auto");
                    jQuery('#product_' + sku + ' .dropdown-list').css("width", "auto");
                }
                console.log('Current Role #4 = ' + customerAclRole);

                if (globalSEARCH_RESULT_VIEW_MODE == 'listView') {
                    jQuery('.general_current_addtocart_container').removeClass('addToCartListViewOffset');
                    jQuery('.general_current_addtocart_container').addClass('addToCartListViewOffset');
                }

                //hide add to cart
                if (customerAclRole == 'staff' || customerAclRole == 'projectant') {
                    jQuery('#product_' + sku + ' .product-quantity').addClass('hide');
                    jQuery('#product_' + sku + ' .add-cart-btn').addClass('hide');
                    jQuery('#product_' + sku + ' .form-inline').addClass('hide');
                    jQuery('#product_' + sku + ' .bttnAddCrt').addClass('hide');
                    jQuery('#product_' + sku + ' .gtm-partlist-addtocart').addClass('hide');
                }

                //replacing product
                jQuery('.dead-product .product-price').html(data[sku].currency + '&nbsp;<span class="span_currentprice2 general_current_price">' + data[sku].currentprice + '</span>/' + data[sku].formattedPriceunit);
            }
        }); //======================================= getProductPrices ***END***


        function qtyAddToCart ( that ) {
            var sku = jQuery(that).attr('data-sku');
            var lastDefaultMinPurchasedQuantity = jQuery(that).attr('data-salesunitqty');
            var insertedQuantityOfSearchList = jQuery('#qtyaddtocartfield' + sku).val();
            var selectedQuantityOfSearchList = 0;

            if (insertedQuantityOfSearchList > 0) {
                selectedQuantityOfSearchList = insertedQuantityOfSearchList;
            } else {
                selectedQuantityOfSearchList = lastDefaultMinPurchasedQuantity;
                jQuery('#qtyaddtocartfield' + sku).val(lastDefaultMinPurchasedQuantity);
            }

            //--------------- clear errors and messages in messagebar (sandwich)
            jQuery('ul.messages').empty();
            jQuery('ul.errors').empty();

            //-------------------------------------------------------- VTC check
            if(typeof jQuery(that).data("vtcmaxqty") !== 'undefined'){
                var maxAvailDeliveryQty = jQuery(that).data("vtcmaxqty");
                var computedQty = jQuery('#qtyaddtocartfield' + sku).val();
                console.log('appendMessageUl #vtc before check');
                //--------------------------------------------------------------
                if(computedQty > maxAvailDeliveryQty){
                    console.log('appendMessageUl #vtc');
                    appendMessageUl([Translator.translate("Your selected quantity may result in a longer delivery time. Please select the available quantity currently in stock or pick an alternative item. Get in touch if you would like us to recommend a suitable article.")], 'messages_hidden', 'error-msg', 'glyphicon glyphicon-exclamation-sign');
                    unsetOverlayLoader();
                    jQuery('.error-msg').scrollTop();
                    return;
                }
            }

            var ajaxgetCategoryUrl = globalSHOP_CATEGORY_AJAX_URL;

            jQuery.ajax(ajaxgetCategoryUrl, {
                    'dataType' : 'json',
                    'type': 'POST',
                    'data': {
                        'form_key' : globalFORM_KEY,
                    'sku' : sku
                    },
                'success': function (categoryFetch) {
                    var parsedCategoryData = categoryFetch;
                    var category = parsedCategoryData.result;
                    var setAddToCartFromSliderData = {'data' : {
                            'sliderClass' : 'general_current_addtocart_container',
                            'sku' : sku,
                            'quantity' : selectedQuantityOfSearchList,
                            'drum' : ''}};
                    console.log( setAddToCartFromSliderData + " setAddToCartFromSliderData");

                    var query = (location.search.split('q' + '=')[1] || '').split('&')[0];
                    if ( typeof query == 'string' && query > '' ) {
                        setAddToCartFromSliderData.data.query = query;
                    }

                    jQuery.ajax(ajaxUrl, {
                        'dataType' : 'json',
                        'type': 'POST',
                        'data': {
                            'form_key' : globalFORM_KEY,
                            'setAddToCartFromSlider' : setAddToCartFromSliderData
                        },
                        'success': function (data) {
                            unsetOverlayLoader();
                            var parsedData = data;
                            var result = parsedData.setAddToCartFromSlider.result;
                            if(result.showPopup == true) {	// Open Inquiry Popup
                                jQuery('#quantitywarningpopup').html(result.popupHtml);
                                jQuery('#quantitywarningpopupBtn').click();
                            } else {
                                // jQuery("html, body").animate({ scrollTop: 0 }, "slow");
                                // console.log('ScrollTop #3');
                                if(result.numberOfDifferentItemsInCart){
                                    jQuery('.MyCart').append('<div id="cartNoBxItemCount" class="cartNoBx">'+result.numberOfDifferentItemsInCart+'</'+'div'+'>');
                                }
                                var newQuantityDetected = false;
                                if (result.data.newQty && result.data.newQty > 0) {
                                    jQuery('#qtyaddtocartfield' + sku).val(result.data.newQty);
                                    selectedQuantityOfSearchList = result.data.newQty;
                                    newQuantityDetected = true;
                                }

                                var messageArray = result.data.messages;
                                if(result.result.indexOf("SUCCESS") == -1){
                                    appendMessageUl(messageArray, 'messages_hidden', 'error-msg', 'glyphicon glyphicon-exclamation-sign');
                                    console.log('appendMessageUl #40');
                                } else {
                                    if (newQuantityDetected == false) {
                                        var linkText = jQuery('#textLink_' + sku).text();
                                        linkText = linkText.replace('<span class="results-highlight">', '');
                                        linkText = linkText.replace('</span>', '');
                                        linkText = linkText.replace(/(\r\n|\n|\r)/gm, "");
                                        linkText = linkText.trim();
                                        var trackingData = new Object();
                                        trackingData.trackingEnabled = globalTRACKING_ENABLED;
                                        trackingData.pagetype        = 'search results';
                                        trackingData.sku             = sku;
                                        trackingData.name            = linkText;
                                        //trackingData.price           = jQuery('.addToCartLink').attr("data-price");
                                        trackingData.category        = category;
                                        trackingData.currencyCode    = globalCURRENCY_CODE;
                                        trackingData.quantity        = selectedQuantityOfSearchList;

                                        // Writing some EE product values to locaStorage:
                                        localStorage.setItem('trackingData_pagetype', trackingData.pagetype);
                                        localStorage.setItem('trackingData_name', trackingData.name);
                                        localStorage.setItem('trackingData_category', trackingData.category );
                                        localStorage.setItem('trackingData_featureSrc', 'Search Result Page Product');

                                        addToCartTracking(trackingData, 'Search');
                                    }
                                    appendMessageUl(messageArray, 'messages_hidden', 'success-msg', 'glyphicon glyphicon-ok');
                                    console.log('appendMessageUl #41');
                                }
                            }
                        },
                        'error': function (data) {
                            var parsedData = data;
                            //debugger;
                        }
                    });
                },
                'error': function (data) {
                var parsedData = data;
                //debugger;
                }
            });
        }

        jQuery('.qtyaddtocartbutton').on('click', function() {
            setOverlayLoader('search-page-container', globalAJAX_LOADER_GIF_PATH);
            qtyAddToCart(this);
        });

        jQuery('.qtyaddtocartfield').keyup( function(e) {
            if ( e.keyCode == 13 ) {
                var sku = jQuery(this).data('sku');
                var that = jQuery('#addtocart-' + sku);
                setOverlayLoader('search-page-container', globalAJAX_LOADER_GIF_PATH);
                qtyAddToCart(that);
            }
        });

    };

    this.addToWishlist = function (url, data, partListData, targetList, trackingFeatureSource) {
        var translation_service_url = BASE_URL + '/onlinetools/commonTools/getTranslations';

        var requiredTranslationsObject = new Object();
        requiredTranslationsObject.key001 = 'Please login first!';
        requiredTranslationsObject.key002 = 'Add to new parts list';
        requiredTranslationsObject.key003 = 'Add to';

        jQuery.ajax(translation_service_url, {
            'dataType': 'json',
            'type': 'POST',
            'data': requiredTranslationsObject,
            'success': function (responseData) {
                if (responseData != '' && responseData != null) {
                    var parsedData = responseData;
                    jQuery.each(parsedData, function (key, value) {
                        Translator.add(key, value);
                    });

                    var currentPage = window.location.href;
                    var possibleTypoStartpages = [
                        "https://www.schrack.at/",
                        "https://www.schrack.ba/",
                        "https://www.schrack.be/",
                        "https://www.schrack.bg/",
                        "http://127.0.0.1/",
                        "https://www.schrack.cz/",
                        "https://www.schrack-technik.de/",
                        "https://www.schrack.hr/",
                        "https://www.schrack.hu/",
                        "https://www.schrack-technik.nl/",
                        "https://www.schrack.pl/",
                        "https://www.schrack.ro/",
                        "https://www.schrack.rs/",
                        "https://www.schrack-technik.ru/",
                        "https://www.schrack.sa/",
                        "https://www.schrack.si/",
                        "https://www.schrack.sk/",
                        "https://test-at.schrack.com/",
                        "https://test-ba.schrack.com/",
                        "https://test-be.schrack.com/",
                        "https://test-bg.schrack.com/",
                        "https://test-com.schrack.com/",
                        "https://test-cz.schrack.com/",
                        "https://test-de.schrack.com/",
                        "https://test-hr.schrack.com/",
                        "https://test-hu.schrack.com/",
                        "https://test-nl.schrack.com/",
                        "https://test-pl.schrack.com/",
                        "https://test-ro.schrack.com/",
                        "https://test-rs.schrack.com/",
                        "https://test-ru.schrack.com/",
                        "https://test-sa.schrack.com/",
                        "https://test-si.schrack.com/",
                        "https://test-sk.schrack.com/"
                    ];

                    if (possibleTypoStartpages.indexOf(currentPage) > -1) {
                        // Detected: TYPO Startpage:
                        trackingFeatureSource = 'listname_form_typo3';
                    } else {
                        // Detected: TYPO Contentpyge:
                        trackingFeatureSource = 'listname_form_typo3_content';
                    }

                    jQuery.each(data, function (i, value) {
                        var dropDownList =jQuery('.' + targetList + ' .product_' + i + ' .dropdown-list');

                        var url = BASE_URL + "customer/account/login";

                        if(customerId === '') {
                            htmlData  = "<li class='add-to-new-partslist'><a href='" + url + "'> ";
                            htmlData += Translator.translate('Please login first!') + " </a></li> ";
                            dropDownList.html(htmlData);
                            dropDownList.addClass("withoutLgn");
                            jQuery(".wishListDropdown").addClass("lgtGray");
                        }
                        else if (typeof partListData !== 'undefined' && partListData != '' && partListData != 'error') {
                            htmlData  = "<!-- position : commonJs.js #124298298 -->";
                            htmlData += "<li class='add-to-new-partslist' onclick='partslistFE.addItemToNewList(\"New parts list\", new ListRequestManager.Product(" + value.id + ", jQuery(\"." + targetList +" .qty-" + i + "\").val(), \"" + i + "\"), \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + i + "\" ><span class='glyphicon glyphicon-plus-sign plusIcon'></span>" + Translator.translate('Add to new parts list') + "</li>";
                            jQuery.each(partListData, function (j, item) {
                                j = j.replace('\0', '');
                                htmlData += "<li onclick='partslistFE.addItemToList(" + j + ", new ListRequestManager.Product(" + value.id + ", jQuery(\"." + targetList +" .qty-" + i + "\").val(), \"" + i + "\"), false, \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + i + "\" title='" + item + "'>" + Translator.translate("Add to") + " " + item + "</li>";
                            });

                            dropDownList.html(htmlData);
                            jQuery('#parlistdropdownbtn-' + i).removeClass('lgtGray');
                        } else {
                            htmlData  = "<!-- position : commonJs.js #12263052 -->";
                            htmlData += "<li class='add-to-new-partslist' onclick='partslistFE.addItemToNewList(\"New parts list\", new ListRequestManager.Product(" + value.id + ", jQuery(\"." + targetList +" .qty-" + i + "\").val(), \"" + i + "\"), \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + i + "\"><span class='glyphicon glyphicon-plus-sign plusIcon darkGray'></span> " + Translator.translate('Add to new parts list') + "</li>";
                            dropDownList.html(htmlData);
                            jQuery('#parlistdropdownbtn-' + i).removeClass('lgtGray');
                        }
                    });
                }
            },
            'error': function (data) {
                var parsedData = data;
                //debugger;
            }
        });
    };

    this.getSolrSearchList = function (url, data, partListData) {
        Object.keys(data).forEach(function(key) {
            this.getSliderElements(data[key], jQuery('#solr-slider-list-'+key), jQuery('#solr-slider-'+key));
            this.addToWishlist(url, data[key], partListData, 'solrProdCont');
        }.bind(this));
    };



    this.getProductAvailabilities = function (data) {
        jQuery.each(data, function (i, item) {
            availabilityInfo[i] = item; //for search page
            var langDeliverablePrefix = '<span class="nds-delivery">' + Translator.translate('deliverable') + '</span>';
            var htmlData = '';
            var htmlDataPickup = '';
            //------------------------------------------------------------------
            var isStsAvailable = data[i].isStsAvailable;
            var isAuslaufartikel = data[i].isDiscontinuation;
            var isBestellartikel = data[i].isForcedOrder;
            //------------------------------------------------------------------
            var deliveryQtySum = data[i].deliveryQtySum;
            var pickupQtySum = data[i].pickupQtySum;
            //------------------------------------------------------------------
            var nearestDeliveryStock = '';
            var nearestDeliveryData = data[i].nearestDeliveryQty;
            var nearestDeliveryQty = 0;
            var formattedDeliveryQtySum = '';
            //----------------------------------- check for nearest availability
            nearestDeliveryStock = Translator.translate('Currently in production');
            //------------------------------------------------------------------
            // Implemented new feature: combination of special case
            // of inventory and special flag from STS:
            // ATTENTION !!!! Affects only deliverable Products
            //------------------------------------------------------------------
            if ( (isStsAvailable == 0 && isAuslaufartikel == true) || isBestellartikel) {
                nearestDeliveryStock = Translator.translate('on request');
            }
            //----------------------------------------------- 3rd party provider
            if(deliveryQtySum > 0) {
                if (typeof nearestDeliveryData['provider'] === "object") {
                    nearestDeliveryStock = '<span class="nds-time sucubus">' + nearestDeliveryData['provider']['formattedDeliveryTime'] +  '</span> ' +
                        '<span class="nds-qty">' + nearestDeliveryData['provider']['formattedQty'] + '</span> ';
                    //----------------------------------------------------------
                    nearestDeliveryQty = nearestDeliveryData['provider']['qty'];
                    //----------------------------------------- popup list entry
                }
                //------------------------------------------------------ central
                if (typeof nearestDeliveryData['central'] === "object") {
                    nearestDeliveryStock = '<span class="nds-time sucubus">' + nearestDeliveryData['central']['formattedDeliveryTime'] + '</span> ' +
                        '<span class="nds-qty">' + nearestDeliveryData['central']['formattedQty'] + '</span> ';
                    //----------------------------------------------------------
                    nearestDeliveryQty = nearestDeliveryData['central']['qty'];
                }
                //-------------------------------------------------------- local
                if (typeof nearestDeliveryData['local'] === "object") {
                    nearestDeliveryStock = '<span class="nds-time sucubus">' + nearestDeliveryData['local']['formattedDeliveryTime'] + '</span> '
                    + '<span class="nds-qty">' + nearestDeliveryData['local']['formattedQty'] + '</span> ';
                    //----------------------------------------------------------
                    nearestDeliveryQty = nearestDeliveryData['local']['qty'];
                    //----------------------------------------- popup list entry
                }
            }
            /*******************************************************************
             * Delivery Quantity Summary will be only shown
             * if product is in stock and overall availability
             * differs from nearest availability
             ******************************************************************/
            if(deliveryQtySum > 0){
                formattedDeliveryQtySum = '<span class="nds-ext-qty">(' + data[i].formattedDeliveryQtySum + ')</span> ' + langDeliverablePrefix;
                //---------------- if nearest and overall delivery do not differ
                if(nearestDeliveryQty == deliveryQtySum){
                    formattedDeliveryQtySum = ' ' + langDeliverablePrefix;
                }
            }
            //--------------------------------------- add to stock delivery info
            nearestDeliveryStock += formattedDeliveryQtySum;
            //------------------------------------------------------------------
            var formattedPickupQtySum = data[i].formattedPickupQtySum;
            formattedPickupQtySum = data[i].formattedPickupQtySum + ' <span class="pickup-sum-stores-txt">' + Translator.translate('in store') + '</span>';
            formattedPickupQtySum = formattedPickupQtySum.replace('∑', '&Sigma;');
            //------------------------------------------------------------------
            jQuery.each(item, function (j, childItem) {
                if (childItem && childItem.pickup && typeof childItem.pickup != 'undefined' ) {
                    if (childItem.pickup.isDefaultPickup == true && (customerId != '' || customerName != '')) {
                        formattedPickupQtySum = childItem.formattedQty + ' <span class="pickup-sum-stores-txt">' +  Translator.translate('In') + ' ' + childItem.pickup.stockName + '</span>';
                        formattedPickupQtySum = formattedPickupQtySum.replace('∑', '&Sigma;');
                    }
                }
            });
            //------------------------------------------------------------------
            var leavingsHtml = false;
            jQuery.each(item.leavings, function (k, leaving) {
                //--------------- we do not dsiplay leavings in availability-"i"
                if ( ! leavingsHtml ) {
                    jQuery('#cableGroup').removeClass('hide'); // ### cable new: remove that
                    jQuery('#leavings').removeClass('hide');
                    leavingsHtml = Translator.translate('Ready packaging') + ': ';
                } else {
                    leavingsHtml += ',';
                }
                //--------------------------------------------------------------
                leavingsHtml += (' <span><a class="addCableLeavingToCart"'
                                        + ' data-sku="' + i + '"'
                                        + ' data-qty="' + leaving.qty + '"'
                                        + ' onclick="simpleAddToCartDispacher(\'' + i + '\',' + leaving.qty + ',true); return false;"'
                                    +'>' + leaving.formattedQty + '</a></span>');
            });
            //------------------------------------------------------------------
            if ( leavingsHtml ) { jQuery('#leavings').html(leavingsHtml); }
            //------------------------------------------------------------------
            jQuery('#salesunit-val-id').html(data[i].formattedSalesUnitQty);
            jQuery('#minqty-val-id').html(data[i].formattedMinOrderQty);
            jQuery('#minqty-mobile-val-id').html(data[i].formattedMinOrderQty);
            //------------------------------------------------------------------
            if (htmlDataPickup == '') {
                if (isAuslaufartikel == true) {
                    htmlDataPickup += '<li class="info_button_pickup_info">' + Translator.translate('On Request') + '</li>';
                } else {
                    htmlDataPickup += '<li class="info_button_pickup_info">' + Translator.translate('Currently in production') + '</li>';
                }
            }
            //------------------------------------------------------ css classes
            var cssMarkerClass = 'logstics-icon-text';
            if(isBestellartikel && deliveryQtySum <= 0) {
                cssMarkerClass += ' on_request';
            }
            var cssMarkerClass2 = 'formatted-pickup-qty-sum';
            //----------------------- show deliverable + store stock information
            if (item.hideQuantities == false) {
                //------------------------------------------------ popup content
                var popupContent = getProductAvailabilitiesList(item);
                //---------------------------------- class(C)ut(O)ff(T)ime(T)ext
                //-- switch determines if 24h delivery information will be shown
                var classCOTT = 'cut_off_time_text';
                if(deliveryQtySum == 0){
                    classCOTT = 'cut_off_time_text_inactive';
                }
                //--------------------------------------- popup icon + container
                htmlData  = '<div class="logistics-icon-text-row">' +
                                '<span class="logistic-icon"></span>' +
                                '<span class="' + cssMarkerClass + '">' +
                                    nearestDeliveryStock + ' ' +
                                '</span>' +
                            '</div>' +
                            '<div class="' + classCOTT  + ' hide-on-print"></div>' +
                            '<div class="cartInfoHide pickup-icon-text-row">' +
                                '<span class="store-icon-new"></span>' +
                                '<span class="' + cssMarkerClass2 + '">' +
                                    formattedPickupQtySum +
                                '</span>' +
                            '</div>' +
                            '<div class="product_stock_container">' +
                                '<span class="glyphicon glyphicon-info-sign info-icon"' +
                                    ' id="product-stock-icon-' + i + '"' +
                                    ' data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">' +
                                '</span>' +
                                '<div class="popupBox qtyBoxCont dropdown-menu"' +
                                  ' aria-labelledby="product-stock-icon-' + i + '">'+
                                    popupContent +
                                '</div>' +
                            '</div';
                //----------------------------------- add html data into div box
                jQuery('#product_' + i + ' .stock-section .product-store').html(htmlData);
            } else { //----------- if inactive show conditional text information
                if (    typeof data[i]['999'] == 'object'
                     && typeof data[i]['999']['delivery'] == 'object'
                     && typeof data[i]['999']['delivery']['thirdPartyLocation'] == 'string'
                     && data[i]['999']['delivery']['thirdPartyLocation'] > '' ) {
                    jQuery('#product_' + i + ' .stock-section .product-stock').html(Translator.translate("Temporarily unavailable"));
                    jQuery('#product_' + i + ' .stock-section .product-store row_pickup_info').html(Translator.translate("Temporarily unavailable"));
                } else {
                    jQuery('#product_' + i + ' .stock-section .product-stock').html(Translator.translate("Deliverable") + ' : ' + Translator.translate("Currently in production"));
                    jQuery('#product_' + i + ' .stock-section .product-store row_pickup_info').html(Translator.translate("Pickupable") + ' : ' + Translator.translate("Currently in production"));
                }
            }
            jQuery('#product_' + i + ' .stock-section').removeClass('hide');
        });
        //--------------------------------------------------------------
        if (typeof processCutOffTime === "function") {
            processCutOffTime();
        }

    };
    this.getCartGrandTotal = function (data) {
        var result = data;
        jQuery('#product-grand-total').html(result.formatted_amounts.grand_total);
        jQuery('#cartSubTotalHdn').val(result.raw_amounts.grand_total);

        // BOC for Bonus Point update on deletion or quantity update
        if ( result.online_bonus_text != '' ) {
            jQuery('#onlineBonusText').html(result.online_bonus_text);
            jQuery('.onlineBonus').show();
            if ( result.online_bonus_text != '' ) {
                jQuery('.onlineBonusAnchor').show();
            } else {
                jQuery('.onlineBonusAnchor').hide();
            }
        } else {
            jQuery('.onlineBonus').hide();
            jQuery('.onlineBonusAnchor').hide();
        }
    }
    this.getAllPartslists = function (data) {
        if (data.error) {
            partListData = 'error';
        } else {
            partListData = data;
            localStorage.setItem("partListData", JSON.stringify(data));
        }

    };
    this.getQuickAddPopup = function (data) {
        jQuery("body").append(data);
    };
    this.getVisitorInfo = function (data) {
        jQuery("body").append(data);
    };
    this.getMaintenancePageUrl = function (maintenancePageUrl) {
        jQuery.ajax(maintenancePageUrl, {
            'type' : 'GET',
            'success' : function(data) {
                maintenanceAlert(data);
            }
        });
    };
    this.setTransferOfCartAsCSV = function (data) {
        var messageArray = [data.message];
        jQuery('ul.messages').empty();
        jQuery('ul.errors').empty();
        if (data.result.indexOf("SUCCESS") == -1) {
            appendMessageUl(messageArray, 'messages', 'error-msg', 'glyphicon glyphicon-exclamation-sign');
            console.log('appendMessageUl #51');
        } else {
            appendMessageUl(messageArray, 'messages', 'success-msg', 'glyphicon glyphicon-ok');
            console.log('appendMessageUl #52');
        }
    };
    //this.getServerSideOrderList = function (data) {	// Function for Order Listing from Server Directly

    this.ordersAction = function (data) {
        parsedData = data;
        var dashboardDynamicFilterHtmlRes = '';
        var loadedRecordCountDesk = 0;
        var $dashOrderPageNumber = jQuery('#dashOrderPageNumber');
        var pageNumber = $dashOrderPageNumber.val();

        jQuery('ul.messages').empty();
        jQuery('ul.errors').empty();

        var totalRecordCount = parsedData.filterStatusCounts.all;
        jQuery('#dashOrderTotalHdnCnt').val(totalRecordCount);

        var loadedRecordCountDesk = parseInt(dashboardDeskTabMobPageSize)* parseInt(pageNumber);

        if (loadedRecordCountDesk >= totalRecordCount) {
            loadedRecordCountDesk = totalRecordCount;
        }

        jQuery('#loadedRecordCountDesk').val(loadedRecordCountDesk);

        prepareDashboardFilter(parsedData.filterSet);

        if(parseInt(pageNumber) <= 1) {
            jQuery('#loadMoreOrderBodyTabMob').html(parsedData.mobileHtmlBlock);
        } else {
            jQuery('#loadMoreOrderBodyTabMob').append(parsedData.mobileHtmlBlock);
        }
        if(parseInt(pageNumber) <= 1) {
            jQuery('#loadMoreOrderBodyDesktop').html(parsedData.desktopHtmlBlock);
        } else {
            jQuery('#loadMoreOrderBodyDesktop').append(parsedData.desktopHtmlBlock);
        }

        // Add paging count to tfoot
        updatePagingCount(loadedRecordCountDesk, totalRecordCount);
        unsetOverlayLoader();
        jQuery('#ajax_in_progress').val(0);
    };

    this.shipmentAction = function (data) {
        this.ordersAction(data);
    }

    this.invoiceAction = function (data) {
        this.ordersAction(data);
    };

    this.creditmemoAction = function (data) {
        this.ordersAction(data);
    }

    this.detailssearchAction = function (data) {
        this.ordersAction(data);
    }

    this.offersAction = function (data) {
        this.ordersAction(data);
    }

    this.getIsPromotionSKU = function (data) {
        if ( data ) {
            var imgUrl = BASE_URL;
            imgUrl += "skin/frontend/schrack/default/schrackdesign/Public/Images/rwd/promotion/promotion_sticker_rot.svg";

            jQuery('.product-sq').append('<img src="' + imgUrl + '"' + ' class="promotion_sign" />');
        }
    }

    this.validateEmailAddress = function (data) {
        // do nothing, use callback instead
    }
}

function maintenanceAlert(data) {
    data = data.trim();
    // To know if we have to show the box, strip all HTML tags
    var div = document.createElement("div");
    div.innerHTML = data;
    var strippedData = div.textContent || div.innerText || "";
    if (strippedData.trim()) {
        var warningContainer = jQuery('#siteMessageWarning');
        jQuery('<div id="siteMessageWarningContainer">').insertBefore(warningContainer);
        warningContainer.detach().appendTo('#siteMessageWarningContainer');
        warningContainer.html('<div class="messageText">' + data + '</div>');
        warningContainer.show();
        var heightOfRedMessageContainer = warningContainer.outerHeight();
        jQuery('#siteMessageWarningContainer').height(heightOfRedMessageContainer);
        var closeButtonHTML = '<button id="warning-message-close-typo" type="button" class="close" data-dismiss="alert" aria-label="Close" >';
        closeButtonHTML += '<span class="closeButton">x</span>';
        closeButtonHTML += '</button>';
        jQuery(closeButtonHTML).insertAfter(warningContainer);

        jQuery("#warning-message-close-typo").on("click", function () {
            jQuery("#siteMessageWarningContainer").hide();
            localStorage.isAlreadyDisabledByCustomer = 1;
            localStorage.isAlreadyDisabledByCustomerTimestamp = Date.now();
        });

        if (typeof localStorage.isAlreadyDisabledByCustomer !== 'undefined' && localStorage.isAlreadyDisabledByCustomer == 1) {
            // To something useful
            console.log('Header Warning existent, but already read and disabled by User')
            // Reset the disabled warning after one day:
            if (Date.now() > (+localStorage.isAlreadyDisabledByCustomerTimestamp + 86400000)) {
                localStorage.removeItem('isAlreadyDisabledByCustomer');
                localStorage.removeItem('isAlreadyDisabledByCustomerTimestamp');
            } else {
                jQuery("#siteMessageWarningContainer").hide();
            }
        } else {
            console.log('UNREAD Header Warning existent!');
        }

    } else {
        console.log("NO header to show");
        localStorage.removeItem('isAlreadyDisabledByCustomer');
        localStorage.removeItem('isAlreadyDisabledByCustomerTimestamp');
    }
}

function restoreOrderSearchQueryOnBrowserBack () {
    restoreOrderSearchQueryOnBrowserBackForElement('#textsearch');
}

function restoreOrderSearchQueryOnBrowserBackForElement ( jqID ) {
    var searchTerm = localStorage.orderTextSearchTerm;
    // got from https://stackoverflow.com/a/53317159
    if ( typeof searchTerm == 'string' && searchTerm > '' && window.performance ) {
        var navEntries = window.performance.getEntriesByType('navigation');
        if ( navEntries.length > 0 && navEntries[0].type === 'back_forward' ) {
            // As per API lv2, this page is load from back/forward
            jQuery(jqID).val(searchTerm);
        } else if ( window.performance.navigation
            && window.performance.navigation.type == window.performance.navigation.TYPE_BACK_FORWARD ) {
            // As per API lv1, this page is load from back/forward
            jQuery(jqID).val(searchTerm);

        } else {
            // This is normal page load
        }
    }
}

function ajaxCall() {
    var methodNameAction = jQuery('#textsearch').attr('data-func');
    var textSearch = jQuery('#textsearch').val();
    var sortColumn = jQuery('#dashOrderActiveSortColumnName').val();
    var sortOrder = jQuery('#dashOrderActiveSortColumnStatus').val();
    var page = jQuery('#dashOrderPageNumber').val();
    var date_from = jQuery('#from-date').val();
    var date_to = jQuery('#to-date').val();

    localStorage.orderTextSearchTerm = textSearch;

    resetTabletRecords();

    var filter  = {};
    if (textSearch) {
        filter['text'] = textSearch;
    }

    jQuery('.filterdata:checked').each(function() {
        filter[jQuery(this).val()] = 1;
    });

    if(jQuery.trim(date_from).length > 0 && jQuery.trim(date_to).length > 0) {
        filter['date_from'] = date_from;
        filter['date_to'] = date_to;
    }

    var loadedRecordCountDesk = parseInt(jQuery('#loadedRecordCountDesk').val());
    var dashOrderTotalHdnCnt = parseInt(jQuery('#dashOrderTotalHdnCnt').val());
    var qryString = sortColumn + sortOrder + JSON.stringify(filter);
    var oldQryString = localStorage.oldQryString;
    if ( ! isNaN(loadedRecordCountDesk) && ! isNaN(dashOrderTotalHdnCnt) && loadedRecordCountDesk >= dashOrderTotalHdnCnt && oldQryString == qryString ) {
        return;
    } else if ( oldQryString != qryString ) {
        page = 1;
        jQuery('#dashOrderPageNumber').val(page);
    }
    localStorage.oldQryString = qryString;

    dataArray[methodNameAction] =  {
        'data' : {
            'filter': filter,
            'sort' : {
                'field' : sortColumn,
                'ASC' : sortOrder
            },
            'pagination' : {
                'page_size' : dashboardDeskTabMobPageSize,
                'page' : page
            }
        }
    };

    ajaxDispatcherCall();
}

function isMobile() {
    var mobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;
    return mobile.test(navigator.userAgent);
}

function updatePagingCount(tillNowLoadedRecordCount, totalRecordCount) {
    if (totalRecordCount == 0) {
        var noDocumentHtml = '<td colspan="5">' + Translator.translate('No data for table available') + '</td>';
        jQuery('.totalRecordCount').html('0 ' + Translator.translate('to') + ' 0 ' + Translator.translate('from') + ' 0 ' + Translator.translate('entries'));
        jQuery('#loadMoreOrderBodyTabMob').html(noDocumentHtml);
        jQuery('#loadMoreOrderBodyDesktop').html(noDocumentHtml);
    } else {
        jQuery('.totalRecordCount').html('1 ' + Translator.translate('to') + ' ' + tillNowLoadedRecordCount + ' ' + Translator.translate('from') + ' ' + totalRecordCount + ' ' + Translator.translate('entries'));
    }
}

    function prepareDashboardFilter(filterSetRec) {	// Prepare Dashboard Dynamic Status Filter Data in Filter Popup
        var resetDashClassName =  filterParamName = filterUiName =  dashboardDynamicFilterHtml = receiptRecords = '';
        var filterReceipt = ['offer_documents', 'order_documents', 'creditmemo_documents', 'delivery_documents', 'invoice_documents'];

            jQuery.each( filterSetRec, function( k, filterval ) {
                filterParamName = filterval.paramName;
                filterUiName = filterval.uiName;
                resetDashClassName = 'filterdata';
                if(filterval.uiName == 'All') {
                    filterParamName = 'all';
                    receiptRecords += '<li><input class="all-receipt" type="checkbox" data-id="4" id="dashboardFilterCheckbox" name="column-4-' + filterUiName + '" value="' + filterParamName + '"><label for="id-column-4-' + filterUiName + '" name="column-4-' + filterUiName + '">&nbsp;' + filterUiName + '</label></input></li>';
                    dashboardDynamicFilterHtml += '<li><input class="all-filterdata" type="checkbox" data-id="4" id="dashboardFilterCheckbox" name="column-4-' + filterUiName + '" value="' + filterParamName + '"><label for="id-column-4-' + filterUiName + '" name="column-4-' + filterUiName + '">&nbsp;' + filterUiName + '</label></input></li>'
                    return true;
                }

                if (filterReceipt.indexOf(filterParamName) >= 0) {
                    receiptRecords += '<li><input class="'+ resetDashClassName +'" type="checkbox" data-id="4" id="dashboardFilterCheckbox" name="column-4-' + filterUiName + '" value="' + filterParamName + '"><label for="id-column-4-' + filterUiName + '" name="column-4-' + filterUiName + '">&nbsp;' + filterUiName + '&nbsp;(' + filterval.count + ')</label></input></li>';
                } else {
                    dashboardDynamicFilterHtml += '<li><input class="'+ resetDashClassName +'" type="checkbox" data-id="4" id="dashboardFilterCheckbox" name="column-4-' + filterUiName + '" value="' + filterParamName + '"><label for="id-column-4-' + filterUiName + '" name="column-4-' + filterUiName + '">&nbsp;' + filterUiName + '&nbsp;(' + filterval.count + ')</label></input></li>';
                }

            });

            if(jQuery('ul#dynamicStatusRow').children('li').length <= parseInt(0)){
                jQuery('#dynamicStatusRow').append(dashboardDynamicFilterHtml);
                jQuery('#dynamicStatusRow').css("text-transform","capitalize");
            }

            if(jQuery('ul#dynamicReceiptRow').children('li').length <= parseInt(0)){
                jQuery('#dynamicReceiptRow').append(receiptRecords);
                jQuery('#dynamicReceiptRow').css("text-transform","capitalize");
            }
    }

	function resetTabletRecords() {
		//jQuery('#dynamicStatusRow').empty();
        jQuery('#loadedRecordCountDesk').val(dashboardDeskTabMobPageSize);
        jQuery('#loadMoreOrderBodyTabMob').val(dashboardDeskTabMobPageSize);

        if (jQuery('#dashOrderActiveSortColumnName').val() == '') {
            jQuery('#dashOrderActiveSortColumnName').val('orderNumber');
        }

        if (jQuery('#dashOrderActiveSortColumnStatus').val() == '') {
            jQuery('#dashOrderActiveSortColumnStatus').val(0);
        }

        if (jQuery('#ajax_in_progress').val() == 0) {
            jQuery('#dashOrderPageNumber').val(1);
        }
    }

    function resetFilters() {
        jQuery('input[name=time-rangle]').prop('checked', false);
        jQuery('#from-date').val('');
        jQuery('#to-date').val('');
        jQuery('.filterdata').prop('checked', false);
        jQuery('.all-filterdata').prop('checked', false);
        jQuery('#dashOrderPageNumber').val(1);
	}

function isNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : event.keyCode
    if (charCode > 31 && (charCode < 48 || charCode > 57))
        return false;
    return true;
}
function assignCurrCat(evts) {
    if(jQuery(evts).attr('catid') == '') {
        jQuery("#allSrchCat").text(jQuery(evts).attr('value'));
        jQuery("#selSrchHidden").val('');
        searchDefaultText = Translator.translate('Search in:') +' '+ Translator.translate('All Catagories');
        searchBox.value = searchDefaultText;
    } else {
        jQuery("#selSrchHidden").val(jQuery(evts).attr('catid'));
        jQuery("#allSrchCat").text(jQuery(evts).attr('value'));
        var pos = searchDefaultText.lastIndexOf(': ');
        searchDefaultText = searchDefaultText.substring(0,pos) + ': '+jQuery(evts).attr('value');
        searchBox.value = searchDefaultText;
    }

}

function fillCacheMegaMenuResponsive(data) {
    console.log('>>> Filling responsive localstorage from AJAX data (commonJs.js)');
    if (data) {
        localStorage.megamenuContentResponsive = data;
    } else {
        console.log('>>> data desktop --> empty content (commonJs.js)');
    }
}

function documentDownloadAjaxCall(dashAjaxCallUrl, documentType) {
    var request = jQuery.ajax(dashAjaxCallUrl,
            {
                method: 'get',
                success: function (response) {
                    if (dataLayer) {
                        dataLayer.push({
                            'event' : 'userDownloads',
                            'eventCategory' : 'File Download',
                            'eventAction' : 'Document Download',
                            'eventLabel' : documentType
                        });
                    }
                    window.open(dashAjaxCallUrl, '_blank');
                }.bind(this),
                error: function (response) {
                    alert('Can not retrieve document.');
                    return;
                }
            });
    return false;
}

function documentAddtoCartAjaxCall(orderAjaxCartUrl) {
    setOverlayLoader();
    jQuery.ajax({
        url: orderAjaxCartUrl,
        method: 'POST'
    }).done(function (data) {
        var parlistAddData = JSON.parse(data);
        jQuery('ul.messages').empty();
        jQuery('ul.errors').empty();
        if (typeof (parlistAddData.messages) != 'undefined') {
            appendMessageUl(parlistAddData.messages, 'messages', 'success-msg');
            console.log('appendMessageUl #53');
			if (parlistAddData.numberOfDifferentItemsInCart) {
				jQuery('.MyCart').append('<div id="cartNoBxItemCount" class="cartNoBx">' + parlistAddData.numberOfDifferentItemsInCart + '</div>');
            }
        } else {
            appendMessageUl(parlistAddData.errors, 'messages', 'error-msg');
            console.log('appendMessageUl #54');
        }
        unsetOverlayLoader();
        jQuery("html, body").animate({scrollTop: 0}, "slow");
        console.log('ScrollTop #4');
    });
}

/*
 *  TYPO3 Tracking: GTM for Article onclick
 */
jQuery(function () {
    jQuery('.articleLink').bind('click', function () {
        var eventName = 'article-teaser';
        if (jQuery(this).parent().parent().hasClass('breadcrumb'))
            eventName = 'tag-breadcrumb';
        var id = jQuery(this).parent().find('.articleTitle').html();
        if (id == undefined)
            var id = jQuery(this).parent().parent().find('.articleTitle').html();
        id = jQuery.trim(id.replace(/[\t\n]+/g, ' '));
        id.replace('<span></span>', '');
        dataLayer.push(
            {
                'event': eventName,
                'id': id
            });
    });
});

function dashGetProductslistAsSkulistByDocumentAjaxCall (docType, documentId, formkey, modus, featureSrc) {
    var getItemsListAjaxUrl = '';
    var dataEvent = '';
    var featureSource = null;
    if (featureSrc) {
        featureSource = ' ' + featureSrc;
    }

    if (modus == 'partslist') {
        getItemsListAjaxUrl = BASE_URL + 'wishlist/partslist/getProductslistAsSkulistByDocument/type/' + docType + '/documentId/' + documentId;
        dataEvent = 'partlistModification';
    }

    if (modus == 'cart') {
        getItemsListAjaxUrl = BASE_URL + 'checkout/cart/getProductslistAsSkulistByDocument/type/' + docType + '/documentId/' + documentId;
        dataEvent = 'adddocumenttocart';
    }
    jQuery.ajax({
        url: getItemsListAjaxUrl,
        data: {form_key: formkey},
        method: 'POST'
    }).done(function (data) {
        var itemsList = JSON.parse(data);
        var arrayLength = itemsList.length;
        //console.log('Start Logging');
        //console.log(itemsList);
        //console.log(dataEvent);
        //console.log(docType + featureSource);
        //console.log('Stopp Logging');

        if (dataEvent == 'partlistModification') {
            for (var index1 = 0; index1 < arrayLength; index1++) {
                if (dataLayer) {
                    dataLayer.push({
                        'event' : 'partlistModification',
                        'eventAction' : 'Add',
                        'eventLabel' : docType + featureSource,
                        'partlistModificationSource' : 'Document',
                        'productSku' : itemsList[index1]
                    });
                    console.log({'Event (adddocumenttopartlist) #1 tracked IDs ' : itemsList[index1]});
                }
            }
        } else {
            for (var index2 = 0; index2 < arrayLength; index2++) {
                var trackingData = new Object();
                trackingData.trackingEnabled  = itemsList[index2].trackingEnabled;
                trackingData.pagetype         = itemsList[index2].pagetype;
                trackingData.sku              = itemsList[index2].sku;
                trackingData.name             = itemsList[index2].name;
                //trackingData.price            = itemsList[index2].price;
                trackingData.quantity         = itemsList[index2].quantity;
                trackingData.category         = itemsList[index2].category;
                trackingData.currencyCode     = itemsList[index2].currencyCode;

                addToCartTracking(trackingData, 'Document');

                console.log({'Event (adddocumenttocart) #2 tracked IDs ' : itemsList[index2].sku});
                console.log(itemsList[index2]);
            }
        }
    });
}

function dashAddToPartlistAjaxCall(partListID, lsDocType, documentObject, formKey, featureSrc) {
    var hdnDocumentId = jQuery(documentObject).parent().attr('doc-id');
    dashGetProductslistAsSkulistByDocumentAjaxCall(lsDocType, hdnDocumentId, formKey, 'partslist', featureSrc);
    var addItemPartListAjaxUrl = BASE_URL + 'wishlist/partslist/batchAddDocuments/id/' + partListID + '/documents/' + hdnDocumentId + ':' + lsDocType;
    unsetOverlayLoader();
    jQuery.ajax({
        url: addItemPartListAjaxUrl,
        method: 'POST'
    }).done(function (data) {
        var parlistAddData = JSON.parse(data);
        jQuery('ul.messages').empty();
        jQuery('ul.errors').empty();
        if (typeof (parlistAddData.messages) != 'undefined') {
            appendMessageUl(parlistAddData.messages, 'messages_hidden', 'success-msg');
            console.log('appendMessageUl #46');
        } else {
            appendMessageUl(parlistAddData.errors, 'messages_hidden', 'error-msg');
            console.log('appendMessageUl #47');
        }
        unsetOverlayLoader();
        //jQuery("html, body").animate({scrollTop: 0}, "slow");
        //console.log('ScrollTop #5');
    });
}
function ajaxDispatcherCall () {
    ajaxDispatcherCallWithCallback(false);
}
function ajaxDispatcherCallWithCallback ( callbackFunction ) {
    if ( __suppressAjaxDispatcherCalls ) {
        return;
    }
    setOverlayLoader();
    jQuery.ajax(ajaxUrl, {
        'dataType': 'json',
        'type': 'POST',
        'data': dataArray,
        'success': function (data) {
            var parsedData = data;
            unsetOverlayLoader();
            var ajaxDispatcher = new AjaxDispatcher();
            jQuery.each(parsedData, function (key, value) {
                ajaxDispatcher[key](value.result);
            });
            if ( typeof callbackFunction == 'function' ) {
                callbackFunction(data);
            }
        },
        'error': function (data) {
            var parsedData = data;
            //debugger;
        }
    });
}

//================================================= getProductAvailabilitiesList
getProductAvailabilitiesList = function (item) {
//==============================================================================
    var RET = '';
    var nearestDeliveryData = item.nearestDeliveryQty;
    var nearestDeliveryStockListEntries = '';
    var pickupStockListEntries = '';
    var isForcedOrder = item.isForcedOrder;
    /***************************************************************************
     popup list entries has to be concatenated in inverse order
     **************************************************************************/
    //------------------------------ prepare nearest delivery stock list entries
    //-------------------------------------------------------------------- local
    if(typeof nearestDeliveryData['local'] === "object"){
        nearestDeliveryStockListEntries +=
            '<li>' +
                '<span class="nds-time">' +
                    nearestDeliveryData['local']['formattedDeliveryTime'] + ' :' +
                '</span>' +
                '<span class="nds-qty">' +
                    nearestDeliveryData['local']['formattedQty'] +
                '</span>' +
            '</li>';
    }
    //------------------------------------------------------------------ central
    if(typeof nearestDeliveryData['central'] === "object"){
        nearestDeliveryStockListEntries +=
            '<li>' +
                '<span class="nds-time">' +
                    nearestDeliveryData['central']['formattedDeliveryTime'] + ' :' +
                '</span>' +
                '<span class="nds-qty">' +
                    nearestDeliveryData['central']['formattedQty'] +
                '</span>' +
            '</li>';
    }
    //----------------------------------------------------------------- provider
    if(typeof nearestDeliveryData['provider'] === "object"){
        nearestDeliveryStockListEntries +=
            '<li>' +
                '<span class="nds-time">' +
                    nearestDeliveryData['provider']['formattedDeliveryTime'] + ' :' +
                '</span>' +
                '<span class="nds-qty">' +
                    nearestDeliveryData['provider']['formattedQty'] +
                '</span>' +
            '</li>';
    }
    //----------------------------------------------------- all stocks are empty
    if(nearestDeliveryStockListEntries + '.' === '.' ){
        var textLabel = isForcedOrder ? Translator.translate('on request') : Translator.translate('Currently in production');
        var forcedOrderCss = isForcedOrder && item.deliveryQtySum <= 0 ? ' class="on_request"' : '';
        nearestDeliveryStockListEntries = '<li' + forcedOrderCss + '>' + textLabel + '</li>';
    }
    //--------------------------------------------------- aggregate list entries
    jQuery.each(item, function (j, childItem) {
        if (childItem && childItem.pickup && typeof childItem.pickup != 'undefined' ) {
            var FavoriteStoreCSS = 'pickup_store_name';
            var FavoriteStoreIcon = '';
            //------------------------------------------------------------------
            if (childItem.pickup.isDefaultPickup == true && (customerId != '' || customerName != '')) {
                FavoriteStoreCSS += ' schrack_txt_highlight';
                // FavoriteStoreIcon = '<i class="glyphicon glyphicon-heart fav_store"></i>';
            }
            pickupStockListEntries += '<li class="info_button_pickup_info">' +
                '<span class="' + FavoriteStoreCSS + '">' +
                    childItem.pickup.stockName +
                '</span>' +
                FavoriteStoreIcon +
                ' :<span> ' +
                    childItem.formattedQty +
                '</span>' +
            '</li>';
        }
    });
    //--------------------------------------------------------------- build list
    var readyShipmentTxt = Translator.translate('Ready For Shipment');
    var readyPickupTxt = Translator.translate('Ready For Pickup');
    RET = '<ul>' +
            '<li class="hd">' +
                readyShipmentTxt + '<span></span>' +
            '</li>' +
            //------------------------------------------------------------------
            nearestDeliveryStockListEntries +
            //------------------------------------------------------------------
            '<li class="info_button_pickup_info hd">' +
                readyPickupTxt + '<span></span>' +
            '</li>' +
            //------------------------------------------------------------------
            pickupStockListEntries +
            //------------------------------------------------------------------
        '</ul>';
    //------------------------------------------------------------------- RETURN
    return RET;
}; //==================================== getProductAvailabilitiesList ***END***

function getSubCategory(url, id){
    needToUpdateBreadcrumbs = true;
    if(jQuery(window).width() < 767) {
        defaultFilterOpen = false; //only for mobile
    }
    var sign = ( url.indexOf('?') > -1 ) ? '&' : '?';
    urlForBookMark = url + sign + 'catId=' + id;
    if(urlappend != ''){
        urlForBookMark = updateQueryStringParameter(urlForBookMark, 'fq', urlappend);
    }
    history.pushState('data', '', urlForBookMark);
    jQuery('#category_id').val(id);
    checkFilterFromUrl();
    console.log("getSubCategory getRCB !");
    dataArray.getRenderedCategoryBlocks = {'data' : {'query': '', 'start': 0, 'limit': 50, 'accessory':0, 'category': id, 'facets': filterArray, 'general_filters': generalFilterArray}};
    ajaxDispatcherCall();
}

function getAccessory(){
    needToUpdateBreadcrumbs = false;
    console.log("getAccessory getRCB !");
    dataArray.getRenderedCategoryBlocks = {'data' : {'query': jQuery('#search-attr-full').val(), 'start': 0, 'limit': 50, 'accessory':1, 'category': jQuery('#category_id').val(), 'facets': filterArray, 'general_filters': generalFilterArray}};
    ajaxDispatcherCall();
}

function performFilterAjax(ele, showall){
    filter = true;
    needToUpdateBreadcrumbs = false;
    if(showall == 'showall'){
        facet = jQuery(ele).closest('.facet').attr('id');
        jQuery('#'+facet+' input:checkbox:checked').each(function(index){
            jQuery(this).removeAttr('checked');
        });
    }
    urlappend = createQueryFromFilter();
    urlForBookMark = updateQueryStringParameter(window.location.href, 'fq', urlappend);
    history.pushState('data', '', urlForBookMark);
    console.log("performFilterAjax getRCB !");
    dataArray.getRenderedCategoryBlocks = {'data' : {'query': jQuery('#search-attr-full').val(), 'start': 0, 'limit': 50, 'accessory':0, 'category': jQuery('#category_id').val(), 'facets': filterArray, 'general_filters': generalFilterArray}};
    ajaxDispatcherCall();
}

function clearFilter(){
    needToUpdateBreadcrumbs = false;
    jQuery('#solrsearch-container input:checkbox:checked').each(function(index){
        jQuery(this).removeAttr('checked');
    });
    filterArray = {};
    generalFilterArray = {};
    urlappend = '';
    history.pushState('data', '', window.location.pathname);
    console.log("clearFilter getRCB !");
    dataArray.getRenderedCategoryBlocks = {'data' : {'query': '', 'start': 0, 'limit': 50, 'accessory':0, 'category': jQuery('#category_id').val(), 'facets': filterArray, 'general_filters': generalFilterArray}};
    ajaxDispatcherCall();
}

function searchFormWithinArticle() {
    needToUpdateBreadcrumbs = false;
    filter = true;
    if ( jQuery('#search-attr-full').val() === 'Search within article selection' )
        jQuery('#search-attr-full').val('');
    if(jQuery('#search-attr-full').val() == ''){
        jQuery('#search-attr-full').focus();
        //return false;
    }
     //ev.preventDefault();
    checkFilterFromUrl();
    urlForBookMark = updateQueryStringParameter(window.location.href, 'q', jQuery('#search-attr-full').val());
    history.pushState('data', '', urlForBookMark);
    console.log("searchFormWithinArticle getRCB !");
    dataArray.getRenderedCategoryBlocks = {'data' : {'query': jQuery('#search-attr-full').val(), 'start': 0, 'limit': 50, 'accessory':0, 'category': jQuery('#category_id').val(), 'facets': filterArray, 'general_filters': generalFilterArray}};
    ajaxDispatcherCall();
    //jQuery('#search_category_form').submit();
}

function createQueryFromFilter(){
    filterArray = {};
    generalFilterArray = {};
    filters = {};
    topfilters = {};
    jQuery('#solrsearch-container .facet').each(function(){
        tempArray = {};
        tempGeneralArray = {};
        templist = [];
        tempGenerallist = [];
        //filterTabState = {};
        facet = jQuery(this).attr('id');
        if(facet == 'general_filters'){
            jQuery('#'+facet+' input:checkbox:checked').each(function(index){
                    //generalFilterArray[facet] = 1;
                    //topfilters[facet]=1;
                    generalFilterArray[jQuery(this).attr('filtertype')] = 1;
                    topfilters[jQuery(this).attr('filtertype')] = 1;

            });
            //if(!jQuery.isEmptyObject(tempGeneralArray)){
                //topfilters[facet] = tempGeneralArray;
            //}
        }else{
            jQuery('#'+facet+' input:checkbox:checked').each(function(index){

                tempArray[index] = jQuery(this).val();
                var templistentry = {};
                templistentry = jQuery(this).val();
                templist.push(templistentry);
            });
            if(!jQuery.isEmptyObject(tempArray)){
                filterArray[facet] = tempArray;
                filters[facet] = templist;
            }
        }

    });

    //for catalog filter
    jQuery('#solrsearch-container select').each(function(){
        tempArray = {};
        facet = jQuery(this).attr('id');
        if(jQuery(this).val() && jQuery(this).val() != ''){
            tempArray[0] = jQuery(this).val();
            filterArray[facet] = tempArray;
            filters[facet] = tempArray;
        }
    });

    fq.facets = filters;
    fq.general_filters = topfilters;
    return encodeURIComponent(JSON.stringify(fq));
    //document.location.search = '?fq=' + urlappend;
}

function checkFilterFromUrl(url){
    if (!url) url = window.location.href;
    if (url.indexOf('fq') >= 0 && getParameterByName('fq') != null && getParameterByName('fq') != '') {
        var value = decodeURIComponent((new RegExp('[?|&]' + 'fq' + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search) || [null, ''])[1].replace(/\+/g, '%20')) || null;
        var jsonobject = JSON.parse(value);
        filterArray = jsonobject.facets;
        generalFilterArray = jsonobject.general_filters;
    }else{
        filterArray = {};
        generalFilterArray = {};
    }
}

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return sanitizeParam( decodeURIComponent( results[2].replace(/\+/g, " ") ) );
}

function sanitizeParam(paramValue) {
    paramValue = paramValue.replace(/'/gi, "");
    //paramValue = paramValue.replace(/"/gi, "");
    paramValue = paramValue.replace(/\\/g, "");
    paramValue = paramValue.replace("alert(", "");
    paramValue = paramValue.replace(/script/gi, "");
    paramValue = paramValue.replace(/</gi, "");
    paramValue = paramValue.replace(/>/gi, "");
    paramValue = paramValue.replace("(", "");
    paramValue = paramValue.replace(")", "");
    return paramValue;
}

function updateQueryStringParameter(uri, key, value) {
    var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
    var separator = uri.indexOf('?') !== -1 ? "&" : "?";
    if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + value + '$2');
    }
    else {
        return uri + separator + key + "=" + value;
    }
}

// function for generating paging links
function generatePagingLinks(length, pagesize) {
    var links = Math.ceil(length / pagesize);

    jQuery("#paging").children("a").remove(); // Remove existing paging links

    // generate new paging links
    for (var i = 0; i < links; i++)
        jQuery("<a>").attr("href", "#").text(i + 1).appendTo("#paging");
}

function renderAutoSuggest(data, searchUrl, query){
    var html = '';
    html += '<div class="menu-triangle"></div>';
    html += '<div class="row">';
    html += '<div class="col-md-12  col-lg-4 rightSec suggest-categories">';
    var category = '';
    if(data.categories.length > 0){
        category += '<h2>'+Translator.translate('Catagory')+'</h2>';
        category += '<ul id="autosuggestSecond">';
        jQuery.each( data.categories, function( i, val ) {
            category += '<li tabindex="'+i+'">' +
                '<div class="row">' +
                    '<div class="imgBx">' +
                        '<img src="' + val.thumbnail + '" alt="" width="40" height="40">' +
                    '</div>' +
                    '<div class="suggest-text">' +
                        '<a href="'+searchUrl+'?q='+query+'&cat='+val.id+'">'+val.title+'</a>' +
                    '</div>' +
                '</div>' +
                '</li>';
            if(i == 4) return false;
        });
        category += '</ul>';
        html += category;
    }
    html += '</div>';

    html += '<div class="col-md-12 col-lg-4 rightSec suggest-products">';
    var product = '';
    if(data.products.length > 0){
        var thumbnailPath = '';
        product += '<h2>'+Translator.translate('Product Suggestions')+'</h2>';
        product += '<ul id="autosuggestThird">';
        jQuery.each( data.products, function( i, val ) {
            product += '<li tabindex="' + i + '">';
                product += '<div class="row autosuggest_product" data-sku="' + val.skuPlain + '">';
                        product += '<div class="imgBx">';
                            thumbnailPath = val.thumbnail;
                            if (!thumbnailPath.match(/http/gi)) {
                                thumbnailPath = 'https:' + thumbnailPath;
                            }
                            if (thumbnailPath == 'https://image.schrack.com/') {
                                thumbnailPath = BASE_URL + 'media/catalog/product/placeholder/default/Schrack_S_CMYK80x80.jpg';
                            }
                            product += '<img src="' + thumbnailPath + '" alt=""  width="40" height="40">';
                        product += '</div>';
                    product += '<div class="suggest-text">';
                            product += '<a class="autosuggest_product_click_tracking" data-pos="' + (i + 1) + '" data-sku="' + val.skuPlain + '"' + ' href="' + val.url + '?q=' + query + '">' + val.description + '</a><br>';
                            product += '<span class="gray">Art. Nr. ' + val.sku;
                            if (val.mainPackingUnit) {
                                product += ' ' + val.mainPackingUnit;
                            }
                            product += '</span>';
                    product += '</div>';
                product += '</div>';
            product += '</li>';
        });
        product += '</ul>';
        html += product;
    }
    html += '</div>';

    html += '<div class="col-md-12 col-lg-4 leftSec">';
    var suggestion = '';
    if(data.terms.length > 0){
        suggestion += '<h2>'+Translator.translate('Search Suggestions')+'</h2>';
        suggestion += '<ul id="autosuggestFirst">';
        jQuery.each( data.terms, function( i, val ) {
            suggestion += '<li tabindex="'+i+'"><a href="'+searchUrl+'?q='+val+'">'+val+'</a></li>';
            if(i == 4) return false;
        });
        suggestion += '</ul>';
        html += suggestion;
    }
    html += '</div>';

    html += '</div>';

    if(data.terms.products > 0 || data.categories.products > 0 || data.products.length > 0){
        jQuery('#search_autocomplete').html(html);
        jQuery('#search_autocomplete').show(); // css('display', 'block');
        jQuery('#search_autocomplete').css('z-index', '99999');
        jQuery(".grayout").show();
        jQuery(".grayout").css('height',jQuery(document).height());
        //jQuery('body').css('overflow','hidden');
        jQuery('.searchContiner .input-group').css('z-index', '99999');
    } else {
        jQuery('#search_autocomplete').html('');
        jQuery('#search_autocomplete').hide(); // css('display', 'none');
        jQuery(".grayout").hide();
        jQuery('body').css('overflow','auto');
        jQuery('.searchContiner .input-group').css('z-index', 'auto');
    }
    //keyboardNavigationOnAutoSuggest();

    if (data.products.length === 1 || (data.products.length > 0 && data.preferFirst)) {
        localStorage.searchResultIsSingleProduct = data.products[0].url;
    } else {
        localStorage.searchResultIsSingleProduct = '';
    }

    jQuery('.autosuggest_product_click_tracking').on('click', function(){
        var selectedProductInAutosuggest             = jQuery(this).attr('data-sku');
        var selectedPositionFromProductInAutosuggest = jQuery(this).attr('data-pos');

        var trackingData                 = new Object();
        trackingData.trackingEnabled     = globalTRACKING_ENABLED;
        trackingData.pageType            = 'all pages';
        trackingData.affectedSku         = selectedProductInAutosuggest;
        //trackingData.price             = dataProductPrice;
        trackingData.currencyCode        = globalCURRENCY_CODE;
        trackingData.trackingSource      = 'solr auto suggest';
        trackingData.typoUrl             = globalTYPO_URL;
        trackingData.shopCategoryAjaxUrl = globalSHOP_CATEGORY_AJAX_URL;
        trackingData.formKey             = globalFORM_KEY;
        trackingData.crmUserId           = globalCRM_USER_ID;
        trackingData.customerType        = globalCUSTOMER_TYPE;
        trackingData.accountCrmId        = globalACCOUNT_CRM_ID;
        trackingData.position            = selectedPositionFromProductInAutosuggest;

        trackProductClick(trackingData);

    });
}

function trackListAddToCart(eventSource, actionAfter) {
    console.log(eventSource);
    console.log(actionAfter);
    actionAfter
}

/* Start keyboardNavigationOnAutoSuggest */
jQuery(document).ready(function (){
    if(getParameterByName('q') != null && getParameterByName('q') != ''){
        jQuery('#search').val(getParameterByName('q'));
    }
    jQuery(document).click(function(e){
        // Check if click was triggered on or within #
        if( jQuery(e.target).closest("#search_autocomplete").length > 0 ) {

        }else{
            //jQuery('#search_autocomplete').css('display', 'none');
            //jQuery('.grayout').css('display', 'none');
            //jQuery('.searchContiner .input-group').css('z-index', 'auto');
        }
    });
    var ele = '';
    jQuery('#search').keydown(function(e){
        if(e.keyCode == 40)    // down arrow
        {
            e.preventDefault();
            if(jQuery('#autosuggestFirst').length){
                jQuery('#autosuggestFirst li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
            }else if(jQuery('#autosuggestSecond').length){
                jQuery('#autosuggestSecond li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
            }else if(jQuery('#autosuggestThird').length){
                jQuery('#autosuggestThird li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
            }
        }
    });
    jQuery('#search_autocomplete').keydown(function (e){
        if(e.keyCode == 37) // left arrow
        {
            e.preventDefault();
            ele = jQuery('#search_autocomplete').find(".selected");
            if(jQuery('#autosuggestFirst').length){
                ele.removeClass('selected').css('background-color', '');
                jQuery('#autosuggestFirst li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
            }else if(jQuery('#autosuggestSecond').length){
                ele.removeClass('selected').css('background-color', '');
                jQuery('#autosuggestSecond li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
            }
        }
        else if(e.keyCode == 38)    // up arrow
        {
            e.preventDefault();
            ele = jQuery('#search_autocomplete').find(".selected");
            if(ele.is(":first-child")){
                ele.removeClass('selected').css('background-color', '');
                if(ele.parent().attr('id') == 'autosuggestFirst'){
                    if(jQuery('#autosuggestThird').length){
                        jQuery('#autosuggestThird li').last().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else if(jQuery('#autosuggestSecond').length){
                        jQuery('#autosuggestSecond li').last().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else{
                        jQuery('#autosuggestFirst li').last().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }
                }else if(ele.parent().attr('id') == 'autosuggestSecond'){
                    if(jQuery('#autosuggestFirst').length){
                        jQuery('#autosuggestFirst li').last().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else if(jQuery('#autosuggestThird').length){
                        jQuery('#autosuggestThird li').last().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else{
                        jQuery('#autosuggestSecond li').last().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }
                }else{
                    if(jQuery('#autosuggestSecond').length){
                        jQuery('#autosuggestSecond li').last().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else if(jQuery('#autosuggestFirst').length){
                        jQuery('#autosuggestFirst li').last().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else{
                        jQuery('#autosuggestThird li').last().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }
                }
            }else{
                ele.removeClass('selected').css('background-color', '');
                ele.prev().addClass('selected').css('background-color', '#dedede').find('a').focus();
            }
        }
        else if(e.keyCode == 39)    // right arrow
        {
            e.preventDefault();
            ele = jQuery('#search_autocomplete').find(".selected");
            if(jQuery('#autosuggestThird').length){
                ele.removeClass('selected').css('background-color', '');
                jQuery('#autosuggestThird li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
            }
        }else if(e.keyCode == 40)    // down arrow
        {
            e.preventDefault();
            ele = jQuery('#search_autocomplete').find(".selected");
            if(ele.is(":last-child")){
                ele.removeClass('selected').css('background-color', '');
                if(ele.parent().attr('id') == 'autosuggestFirst'){
                    if(jQuery('#autosuggestSecond').length){
                        jQuery('#autosuggestSecond li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else if(jQuery('#autosuggestThird').length){
                        jQuery('#autosuggestThird li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else{
                        jQuery('#autosuggestFirst li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }
                }else if(ele.parent().attr('id') == 'autosuggestSecond'){
                    if(jQuery('#autosuggestThird').length){
                        jQuery('#autosuggestThird li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else if(jQuery('#autosuggestFirst').length){
                        jQuery('#autosuggestFirst li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else{
                        jQuery('#autosuggestSecond li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }
                }else{
                    if(jQuery('#autosuggestFirst').length){
                        jQuery('#autosuggestFirst li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else if(jQuery('#autosuggestSecond').length){
                        jQuery('#autosuggestSecond li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }else{
                        jQuery('#autosuggestThird li').first().addClass('selected').css('background-color', '#dedede').find('a').focus();
                    }
                }
            }else{
                ele.removeClass('selected').css('background-color', '');
                ele.next().addClass('selected').css('background-color', '#dedede').find('a').focus();
            }
            //if(jQuery('#autosuggestThird').find(".selected").is(':nth-child(5)')){
                //jQuery('#search_autocomplete .rightSec').animate({ scrollTop: jQuery('#search_autocomplete .rightSec').height() }, "slow");
            //}
        }
        if(e.keyCode == 13){
            ele.find('a').click();
        }
    });

    jQuery('#save_password_data_button').on('click', function() {
        if (dataLayer) {
            dataLayer.push({
                'event' : 'userSettingChange',
                'eventLabel' : 'Password Change'
            });
        }
    });
});
/* end keyboardNavigationOnAutoSuggest */

function EncryptMailto(emailAddress) {
    var n = 0;
    var r = "";
    var s = "mailto:" + emailAddress;

    for (var i = 0; i < s.length; i++) {
        n = s.charCodeAt(i);
        if (n >= 8364) {
            n = 128;
        }
        r += String.fromCharCode(n + 1);
    }
    return "javascript:linkTo_DecryptMailto('" + r + "');";
}

function DecryptMailto(s) {
    var n = 0;
    var r = "";
    for (var i = 0; i < s.length; i++) {
        n = s.charCodeAt(i);
        if (n >= 8364) {
            n = 128;
        }
        r += String.fromCharCode(n - 1);
    }
    return r;
}

function linkTo_DecryptMailto(s) {
    location.href = DecryptMailto(s);
}

function trackAnalyticsCheckoutStep (trackingevent, eventLabel, step, option, orderedCartItems) {
    dataLayer.push(
        {
            'event': trackingevent,
            'eventCategory' : 'Ecommerce',
            'eventAction' : 'Checkout Step',
            'eventLabel' : eventLabel,
            'eventValue' : 1,
            'ecommerce' : {
                'checkout' : {
                    'actionField': {'step': step, 'option': option},
                    'products' : orderedCartItems
                }
            }
        });
}

function addToCartTracking(data, trackingsource) {
    var trackingEnabled     = data.trackingEnabled;
    var dataPageType        = data.pagetype;
    var dataProductSku      = data.sku;
    var dataProductName     = data.name;
    //var dataProductPrice    = data.price;
    var dataQuantity        = data.quantity;
    var dataProductCategory = data.category;
    var currencyCode        = data.currencyCode;

    var event = 'addToCart';
    if (trackingsource == 'Document' || trackingsource == 'Partlist' || trackingsource == 'Search Result List') {
        event = 'addToCartList';
    }

    if(trackingsource == 'Detail') {
        event = 'addToCartDetail';
        trackingsource = 'Standard'; // Re-Map to standard value
    }

    if(trackingsource == 'Search') {
        event = 'addToCartSearch';
        trackingsource = 'Standard'; // Re-Map to standard value
    }

    if(trackingsource == 'Slider Detail Accessory Product') {
        event = 'addToCartSlider';
        trackingsource = 'Add To Cart Details Page Product Slider Accessories'; // Re-Map to standard value
    }

    if(trackingsource == 'Slider Detail Related Product') {
        event = 'addToCartSlider';
        trackingsource = 'Add To Cart Details Page Product Slider Related Products'; // Re-Map to standard value
    }

    if(trackingsource == 'Slider Latest Purchased Product') {
        event = 'addToCartSlider';
        trackingsource = 'Add To Cart MyAccount Overview Slider Latest Purchased Product'; // Re-Map to standard value
    }

    if(trackingsource == 'TYPO Slider Startpage Last Viewed') {
        event = 'addToCartSlider';
        trackingsource = 'Add To Cart Typo Startpage Slider Last Viewed Products'; // Re-Map to standard value
    }

    if(trackingsource == 'TYPO Slider Startpage Promotions') {
        event = 'addToCartSlider';
        trackingsource = 'Add To Cart Typo Startpage Slider Promotions Products'; // Re-Map to standard value
    }

    if(trackingsource == 'TYPO Slider Content Page') {
        event = 'addToCartSlider';
        trackingsource = 'Add To Cart Typo Content Product Slider'; // Re-Map to standard value
    }

    if (trackingEnabled == 'enabled') {
        if (dataLayer) {
            dataLayer.push({
                'contentType' : trackingsource,
                'event' : event,
                'eventLabel' : dataProductSku,
                'ecommerce' : {
                    'currencyCode' : currencyCode,
                    'add' : {
                        'products' : [{
                            'name' : dataProductName,
                            'id' : dataProductSku,
                            //'price' : dataProductPrice,
                            'quantity' : dataQuantity,
                            'category' : dataProductCategory,
                            'dimension8' : dataPageType,
                            'dimension9' : dataProductCategory,
                            'dimension14' : trackingsource,
                            'dimension15' : dataProductSku
                        }]
                    }
                }
            });
        }
    }
}

function removeFromCartTracking(data) {
    var trackingEnabled     = data.trackingEnabled;
    var dataCartItems       = data.cartItems;
    var currencyCode        = data.currencyCode;

    if (trackingEnabled == 'enabled') {
        if (dataLayer) {
            dataLayer.push({
                'event' : 'removeFromCart',
                'ecommerce' : {
                    'currencyCode' : currencyCode,
                    'remove' : {
                        'products' : dataCartItems
                    }
                }
            });
        }
    }
}

function cartQuantityChange(data) {
    var trackingEnabled     = data.trackingEnabled;
    var dataTrackingAction  = data.trackingAction;
    var dataPageType        = data.pagetype;
    var dataProductSku      = data.sku;
    var dataProductName     = data.name;
    //var dataProductPrice    = data.price;
    var dataQuantity        = data.quantity;
    var dataProductCategory = data.category;
    var currencyCode        = data.currencyCode;

    if (trackingEnabled == 'enabled') {
        if (dataLayer) {
            if (dataTrackingAction == 'increase') {
                dataLayer.push({
                    'contentType' : 'Standard',
                    'event' : 'cartQtyChange',
                    'eventAction' : 'Add To Cart Cart Change',
                    'eventLabel' : dataProductSku,
                    'ecommerce' : {
                        'currencyCode' : currencyCode,
                        'add' : {
                            'products' : [{
                                'name' : dataProductName,
                                'id' : dataProductSku,
                                //'price' : dataProductPrice,
                                'quantity' : dataQuantity,
                                'category' : dataProductCategory,
                                'dimension8' : dataPageType,
                                'dimension9' : dataProductCategory,
                                'dimension14' : 'Standard',
                                'dimension15' : dataProductSku
                            }]
                        }
                    }
                });
            }

            if (dataTrackingAction == 'decrease') {
                dataLayer.push({
                    'contentType' : 'Standard',
                    'event' : 'cartQtyChange',
                    'eventAction' : 'Remove From Cart Cart Change',
                    'eventLabel' : dataProductSku,
                    'ecommerce' : {
                        'currencyCode' : currencyCode,
                        'remove' : {
                            'products' : [{
                                'name' : dataProductName,
                                'id' : dataProductSku,
                                //'price' : dataProductPrice,
                                'quantity' : dataQuantity,
                                'category' : dataProductCategory,
                                'dimension8' : dataPageType,
                                'dimension9' : dataProductCategory,
                                'dimension14' : 'Standard',
                                'dimension15' : dataProductSku
                            }]
                        }
                    }
                });
            }
        }
    }
}

function removeForbiddenPhoneFieldCharacters(elementId) {
    var telCoValue = jQuery('#' + elementId).val();
    var telCoValueLength = telCoValue.length;
    var checkedValue = '';
    var allowedCharacters = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    for (var i = 0; i < telCoValueLength; i++) {
        if (jQuery.inArray(telCoValue[i], allowedCharacters) != -1) {
            checkedValue = checkedValue + telCoValue[i];
        }
    }
    jQuery('#' + elementId).val(checkedValue);

    telCoValue = checkedValue;
    telCoValue = telCoValue.replace(/ /g,'');
    telCoValue = telCoValue.replace(/^0000/, '');
    telCoValue = telCoValue.replace(/^000/, '');
    telCoValue = telCoValue.replace(/^00/, '');
    telCoValue = telCoValue.replace(/^0/, '');
    telCoValueLength = telCoValue.length;

    if (telCoValueLength >= 0 && telCoValueLength <= 3) {
        telCoValue = telCoValue;
    } else if (telCoValueLength > 3 && telCoValueLength <= 6) {
        telCoValue = telCoValue.substring(0, 2) + telCoValue.substring(2, telCoValueLength);
    } else if (telCoValueLength > 6 && telCoValueLength <= 10) {
        telCoValue = telCoValue.substring(0, 2) + telCoValue.substring(2, 6) + telCoValue.substring(6, telCoValueLength) ;
    } else if (telCoValueLength > 10 && telCoValueLength <= 14) {
        telCoValue = telCoValue.substring(0, 2) + telCoValue.substring(2, 6) + telCoValue.substring(6, 10) + telCoValue.substring(10, telCoValueLength) ;
    } else if (telCoValueLength > 14 && telCoValueLength <= 18) {
        telCoValue = telCoValue.substring(0, 2) + telCoValue.substring(2, 6) + telCoValue.substring(6, 10) + telCoValue.substring(10, 14) + telCoValue.substring(14, telCoValueLength) ;
    } else if (telCoValueLength > 18) {
        telCoValue = telCoValue.substring(0, 2) + telCoValue.substring(2, 6) + telCoValue.substring(6, 10) + telCoValue.substring(10, 14) + telCoValue.substring(14, 18) + telCoValue.substring(18, telCoValueLength) ;
    }

    jQuery('#' + elementId).val(telCoValue);
}

function forceFetchMegaMenu() {
    // Reset mega menu, forces new fetch:
    localStorage.removeItem('refreshMegaMenuForceTimeLastChangeDropdownMenu');
    localStorage.removeItem('megamenuContentResponsive');
    localStorage.removeItem('refreshMegaMenuForceTimeLastChange');
    localStorage.removeItem('refreshMegaMenuForceTimeCurrent');
}

function checkMinimumPhoneExistent(phonerequiredwarning) {
    var requiredNumberText = phonerequiredwarning;
    var mode = 'defaultForm';

    if (jQuery('#registerOnlyForm') &&
        (jQuery('#registerOnlyForm').val() == 'raw' || jQuery('#registerOnlyForm').val() == 'validPhone')) {
        mode = 'registerOnlyForm';
    }

    if (mode == 'defaultForm') {
        if (jQuery('#schrack_telephone').val().length > 4 ||
            jQuery('#schrack_fax').val().length > 4 ||
            jQuery('#schrack_mobile_phone').val().length > 4 ) {
            // Everything okay
            jQuery('#requiredWarningPhone').text('');
            jQuery('#single_user_account_edit_button').show();
            jQuery('#schrack_telephone').css("border", "1px solid #ebebeb");
        } else {
            // Some number missing:
            jQuery('#requiredWarningPhone').text(requiredNumberText);
            jQuery('#schrack_telephone').css("border", "2px solid red");
            jQuery('#single_user_account_edit_button').hide();
        }
    }

    if (mode == 'registerOnlyForm' && jQuery('#registerOnlyFormCheckitFlag').val() == 'yes') {
        if (jQuery('#schrack_telephone').val().length > 4 ||
            jQuery('#schrack_fax').val().length > 4 ||
            jQuery('#schrack_mobile_phone').val().length > 4 ) {
            // Everything okay
            jQuery('#requiredWarningPhone').text('');
            jQuery('#schrack_telephone').css("border", "1px solid #ebebeb");
            jQuery('#registerOnlyForm').val('validPhone');
        } else {
            // Some number missing:
            jQuery('#registerOnlyForm').val('raw');
            jQuery('#requiredWarningPhone').text(requiredNumberText);
            jQuery('#schrack_telephone').css("border", "2px solid red");
        }
    }
}

function trackUserDetailDocumentSearch(searchtext) {
    if (dataLayer) {
        dataLayer.push({
            'event' : 'myAccountOverviewDocumentSearch',
            'eventLabel' : searchtext
        });
    }
}

function isIphone5(){
    function iOSVersion(){
        var agent = window.navigator.userAgent,
            start = agent.indexOf( 'OS ' );
        if( (agent.indexOf( 'iPhone' ) > -1) && start > -1)
            return window.Number( agent.substr( start + 3, 3 ).replace( '_', '.' ) );
        else return 0;
    }

    if (iOSVersion() >= 6 && window.devicePixelRatio >= 2 && screen.availHeight == 548) {
        console.log('This Device is an iPhone5');
        return true;
    } else {
        return false;
    }
}

function orderItem(sku, targetList, typoMarker, name, category) {
    var productElement = jQuery('.' + targetList + ' .qty-' + sku);
    if(productElement.val() !== "") {
        var qty = productElement.val();
    } else {
        var qty = 1;
    }

    var productData = jQuery(sku);

    if (typeof typoMarker !== 'undefined' && typoMarker) {
        localStorage.setItem('trackingData_pagetype', 'TYPO');
        localStorage.setItem('trackingData_name', name);
        localStorage.setItem('trackingData_category', category);
        localStorage.setItem('trackingData_featureSrc', typoMarker);
    }

    setOverlayLoader();
    jQuery('ul.messages').empty();
    jQuery('ul.errors').empty();
    jQuery.ajax(ajaxUrl, {
        'dataType' : 'json',
        'type': 'POST',
        'data': {
            'form_key' : formKey,
            'setAddToCart' : {'data' : {'sku' : sku, 'quantity' : qty, 'leaving' : false}}
        },
        'success': function (data) {
            unsetOverlayLoader();
            var parsedData = data;
            var result = parsedData.setAddToCart.result;
            if(result.showPopup == true) {	// Open Inquiry Popup
                jQuery('#quantitywarningpopup').html(result.popupHtml);
                jQuery('#quantitywarningpopupBtn').click();
            } else {
                //jQuery("html, body").animate({ scrollTop: 0 }, "slow");
                //console.log('ScrollTop #64');
                if(result.numberOfDifferentItemsInCart){
                    jQuery('.MyCart').append('<div id="cartNoBxItemCount" class="cartNoBx">'+result.numberOfDifferentItemsInCart+'</div>');
                }
                var newQuantityDetected = false;
                var inputQuantityObject = productElement;
                if (result.data.newQty && result.data.newQty > 0) {
                    newQuantityDetected = true;
                    inputQuantityObject.val(result.data.newQty);
                } else if(inputQuantityObject.val() == "") {
                    inputQuantityObject.val("1");
                }
                var messageArray = result.data.messages;
                if(result.result.indexOf("SUCCESS") == -1) {
                    appendMessageUl(messageArray, 'messages', 'error-msg', 'glyphicon glyphicon-exclamation-sign');
                    console.log('appendMessageUl #48');
                } else {
                    if (newQuantityDetected === false) {
                        // Fire trackingcode:
                        var trackingData = new Object();
                        trackingData.sku             = sku;
                        trackingData.quantity        = qty;
                        trackingData.trackingEnabled = globalTRACKING_ENABLED;
                        trackingData.currencyCode    = globalCURRENCY_CODE;

                        // Fetch values from localStorage:
                        trackingData.featureSrc      = localStorage.getItem('trackingData_featureSrc');
                        trackingData.pagetype        = localStorage.getItem('trackingData_pagetype');
                        trackingData.name            = localStorage.getItem('trackingData_name');
                        //trackingData.price         = -> price should be ignored;
                        trackingData.category        = localStorage.getItem('trackingData_category');

                        addToCartTracking(trackingData, trackingData.featureSrc);
                    }

                    messageArray = messageArray.map(function(x){ return x.replace("setQty(","setQtyToProduct('" + sku + "','" + targetList + "',")});
                    appendMessageUl(messageArray, 'messages_hidden', 'success-msg', 'glyphicon glyphicon-ok');
                    console.log('appendMessageUl #01');
                }
            }
        },
        'error': function (data) {
            var parsedData = data;
            //debugger;
        }
    });
}

function setQtyToProduct(sku, targetList, productId, qty){
    jQuery('.' + targetList + ' .qty-'+sku).val(qty);
    orderItem(sku, targetList);
}

function trackUserDetailDocumentSearch(searchtext) {
    if (dataLayer) {
        dataLayer.push({
            'event' : 'myAccountOverviewDocumentSearch',
            'eventLabel' : searchtext
        });
    }
}

function setEcommerceProductClickTracking(productData) {
    if (productData.trackingEnabled == 'enabled') {
        if (dataLayer) {
            dataLayer.push({
                'crmId' : productData.crmId,
                'crmAccountId' : productData.crmAccountId,
                'customerType' : productData.customertype,
                'ecPageTypeHit' : productData.pagetype,
                'event' : 'productClick',
                'eventLabel' : productData.trackingSource,
                'ecommerce' : {
                    'currencyCode' : productData.currencyCode,
                    'click' : {
                        'products' : [{
                            'name' : productData.name,
                            'id' : productData.sku,
                            //'price' : dataProductPrice,
                            'position' : productData.position,
                            'category' : productData.category,
                            'dimension8' : productData.pagetype,
                            'dimension9' : productData.category
                        }]
                    }
                }
            });
        }
    }
}

function trackProductClick(commonData) {
    var typoUrl             = commonData.typoUrl;
    var shopCategoryAjaxUrl = commonData.shopCategoryAjaxUrl;
    var affectedSku         = commonData.affectedSku;
    var trackingEnabled     = commonData.trackingEnabled;
    var pageType            = commonData.pageType;
    var currencyCode        = commonData.currencyCode;
    var trackingSource      = commonData.trackingSource;
    var formKey             = commonData.formKey;
    var crmId               = commonData.crmUserId;
    var crmAccountId        = commonData.accountCrmId;
    var customerType        = commonData.customerType;
    var position            = commonData.position;

    jQuery.ajax(typoUrl + '?eID=xschrack_suggest&q=' + encodeURIComponent(affectedSku) + '&cat=', {
        'dataType': 'json',
        'type': 'GET',
        'crossDomain':true,
        'success': function (data) {
            var parsedData = data;
            if (!parsedData || parsedData.hasOwnProperty('error')) {
                console.log('SOLR Request not successful');
            } else {
                jQuery.ajax(shopCategoryAjaxUrl, {
                    'dataType' : 'json',
                    'type': 'POST',
                    'data': {
                        'form_key' : formKey,
                        'sku' : affectedSku
                    },
                    'success' : function(categoryFetch) {
                        var parsedCategoryData = categoryFetch;
                        var category = parsedCategoryData.result;

                        var trackingData             = new Object();
                        trackingData.trackingEnabled = trackingEnabled;
                        trackingData.pagetype        = pageType;
                        trackingData.sku             = affectedSku;
                        trackingData.name            = parsedData.products[0].descriptionPlain;
                        //trackingData.price           = dataProductPrice;
                        trackingData.category        = category;
                        trackingData.currencyCode    = currencyCode;
                        trackingData.trackingSource  = trackingSource;
                        trackingData.crmId           = crmId;
                        trackingData.crmAccountId    = crmAccountId;
                        trackingData.customertype    = customerType;
                        trackingData.position        = position;

                        setEcommerceProductClickTracking(trackingData);
                    },
                    'error': function (data) {
                        console.log('AJAX did not work for SKU = ' + affectedSku);
                    }
                });
            }
        },
        'error': function (data) {
            var parsedData = data;
            console.log('Tracking ERROR in commonJs.js -> ' + affectedSku);
            console.log(parsedData);
        }
    });
}

function buildHTMLForPartslistSelection(configObject)  {
    var partListData            = configObject.partListData;
    var productId               = configObject.mageProductEntityId;
    var productSku              = configObject.productSku;
    var destinationElementClass = configObject.destinationElementClass;
    var destinationDropdownId   = configObject.destinationDropdownId;
    var trackingFeatureSource   = configObject.trackingFeatureSource;

    var dropDownList =jQuery('.' + destinationElementClass + ' #' + destinationDropdownId);

    var url = BASE_URL + "customer/account/login";

    if(globalCRM_USER_ID == '' || globalCRM_USER_ID == 0 || typeof globalCRM_USER_ID == "undefined") {
        htmlData  = "<!-- position : commonJs.js #777555333 -->";
        htmlData  = "<li class='add-to-new-partslist'><a href='" + url + "'> ";
        htmlData += Translator.translate('Please login first!') + " </a></li> ";
        dropDownList.html(htmlData);
        dropDownList.addClass("withoutLgn");
        jQuery(".wishListDropdown").addClass("lgtGray");
    }
    else if (typeof partListData !== 'undefined' && partListData != '' && partListData != 'error') {
        htmlData  = "<!-- position : commonJs.js #333444555 -->";
        htmlData += "<li class='add-to-new-partslist' onclick='partslistFE.addItemToNewList(\"New parts list\", new ListRequestManager.Product(" + productId + ", jQuery(\"." + destinationElementClass +" .qty-" + productSku + "\").val(), \"" + productSku + "\"), \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + productSku + "\" ><div class='newPartslistDiv'><span class='glyphicon glyphicon-plus-sign plusIcon' style='float:left;'></span><span class='newPartslistText'>" + Translator.translate('Add to new parts list') + "</span></div></li>";
        jQuery.each(partListData, function (j, item) {
            j = j.replace('\0', '');
            htmlData += "<li class='addToExistingPartslist' onclick='partslistFE.addItemToList(" + j + ", new ListRequestManager.Product(" + productId + ", jQuery(\"." + destinationElementClass +" .qty-" + productSku + "\").val(), \"" + productSku + "\"), false, \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + productSku + "\" title='" + item + "'>" + Translator.translate("Add to") + " " + item + "</li>";
        });

        dropDownList.html(htmlData);
        jQuery('#parlistdropdownbtn-' + productSku).removeClass('lgtGray');
    } else {
        htmlData  = "<!-- position : commonJs.js #111222333 -->";
        htmlData += "<li class='add-to-new-partslist' onclick='partslistFE.addItemToNewList(\"New parts list\", new ListRequestManager.Product(" + productId + ", jQuery(\"." + destinationElementClass +" .qty-" + productSku + "\").val(), \"" + productSku + "\"), \"" + trackingFeatureSource + "\");' data-brand=\"\" data-click=\"\" data-event=\"\" data-id=\"" + productSku + "\"><span class='glyphicon glyphicon-plus-sign plusIcon darkGray'></span> " + Translator.translate('Add to new parts list') + "</li>";
        dropDownList.html(htmlData);
        jQuery('#parlistdropdownbtn-' + productSku).removeClass('lgtGray');
    }
}

function deactivateButton(buttonClass) {
    jQuery('.' + buttonClass).addClass('button_deactivated');
}

function loadSlideInTileQuantities ( sku, formKey ) {
    var requestData = {
        "form_key": formKey,
        "getProductAvailabilities": { "data": { "skus": [sku], "forceRequest": "1" } }
    };
    jQuery.ajax(ajaxUrl, {
        'dataType': 'json',
        'type': 'POST',
        'data': requestData,
        'success': function ( data ) {
            var parsedData = data;
            buildSlideInTileStockHtml(parsedData);
        },
        'error': function ( data ) {
            var parsedData = data;
            setTimeout(function () {
                // unsetOverlayLoader();
            });
            console.log("Error: " + parsedData);
        }
    });
}

function buildSlideInTileStockHtml ( parsedData ) {
    for ( var sku in parsedData['getProductAvailabilities']['result'] ) {
        var productData = parsedData['getProductAvailabilities']['result'][sku];
        var stocks = sortStocks(productData);
        var hideQuantities = productData['hideQuantities'];
        var isStsAvailable = productData['isStsAvailable'];
        var overallSum  = hideQuantities || productData['overallQtySum'] == 0 || isStsAvailable == 0
                        ? '<div>' + Translator.translate('Currently in production') + '</div>'
                        : '<div>' + hardBlank(productData['formattedOverallQtySum']) + '</div>';
        jQuery('#ajaxSpinnerOverlay_' + sku).remove();
        var html    = '<div class="search_result_availability_data">'
                    + '<div class="availability_headline">' + Translator.translate('Availability') + '</div>'
                    + '<div class="qty_underlined">' + Translator.translate('Ready For Shipment') + '</div>';

        var hasStockCount = false;
        if ( ! hideQuantities ) {
            for ( var k in stocks ) {
                var stock = stocks[k];
                var delivery = stock['delivery'];
                if ( typeof delivery !== 'undefined' && stock['qty'] > 0 ) {
                    var qty = hardBlank(stock['formattedQty']);
                    var time = delivery['formattedDeliveryTime'];
                    html += ('<div>' + time + ' <span class="qty_right">' + qty + '</span></div>');
                    hasStockCount = true;
                }
            }
        }
        if ( ! hasStockCount ) {
            html += '<div>' + Translator.translate('Currently in production') + '</div>';
        }

        html += '<div class="qty_underlined">' + Translator.translate('Ready For Pickup') + '</div>';

        hasStockCount = false;
        if ( ! hideQuantities ) {
            for ( var k in stocks ) {
                var stock = stocks[k];
                var pickup = stock['pickup'];
                if ( typeof pickup !== 'undefined' && stock['qty'] > 0 ) {
                    var qty = hardBlank(stock['formattedQty']);
                    var stockName = pickup['stockName'];
                    html += ('<div>' + stockName + ' <span class="qty_right">' + qty + '</span></div>');
                    hasStockCount = true;
                }
            }
        }
        if ( ! hasStockCount ) {
            html += '<div>' + Translator.translate('Currently in production') + '</div>';
        }

        html += '</div>';
        jQuery('#availtab_' + sku).append(html);
    }
}

function hardBlank ( str ) {
    return str.replace(' ','&nbsp;');
}

function sortStocks ( productData ) {
    var res = {};
    for ( var k in productData ) {
        var n = parseInt(k);
        if ( n >= 10 && n < 1000 ) {
            var k2 = n == 80 ? 998 : n;
            var v = productData[k];
            res[k2] = v;
        }
    }
    return res;
}

function handleClickShowStockIcon ( sku, formKey ) {
    jQuery('#availtab_' + sku).show("slide", { direction: "right" }, 500);
    if ( jQuery('#availtab_' + sku).data('loaded') == "0" ) {
        loadSlideInTileQuantities(sku,formKey);
        jQuery('#availtab_' + sku).data('loaded', "1");
    }
}

function handleClickShowStockIconClose ( sku ) {
    jQuery('#availtab_' + sku).hide("slide", { direction: "right" }, 500);
}

function qtyAddToCart ( that ) {
    var sku = jQuery(that).attr('data-sku');
    var lastDefaultMinPurchasedQuantity = jQuery(that).attr('data-salesunitqty');
    var insertedQuantityOfSearchList = jQuery('#qtyaddtocartfield' + sku).val();
    var selectedQuantityOfSearchList = 0;

    if (insertedQuantityOfSearchList > 0) {
        selectedQuantityOfSearchList = insertedQuantityOfSearchList;
    } else {
        selectedQuantityOfSearchList = lastDefaultMinPurchasedQuantity;
        jQuery('#qtyaddtocartfield' + sku).val(lastDefaultMinPurchasedQuantity);
    }

    jQuery('ul.messages').empty();
    jQuery('ul.errors').empty();

    var ajaxgetCategoryUrl = globalSHOP_CATEGORY_AJAX_URL;

    jQuery.ajax(ajaxgetCategoryUrl, {
            'dataType' : 'json',
            'type': 'POST',
            'data': {
                'form_key' : globalFORM_KEY,
            'sku' : sku
            },
        'success': function (categoryFetch) {
            var parsedCategoryData = categoryFetch;
            var category = parsedCategoryData.result;

            jQuery.ajax(ajaxUrl, {
                'dataType' : 'json',
                'type': 'POST',
                'data': {
                    'form_key' : globalFORM_KEY,
                    'setAddToCartFromSlider' : {'data' : {'sliderClass' : 'general_current_addtocart_container', 'sku' : sku, 'quantity' : selectedQuantityOfSearchList, 'drum' : ''}}
                },
                'success': function (data) {
                    unsetOverlayLoader();
                    var parsedData = data;
                    var result = parsedData.setAddToCartFromSlider.result;
                    if(result.showPopup == true) {	// Open Inquiry Popup
                        jQuery('#quantitywarningpopup').html(result.popupHtml);
                        jQuery('#quantitywarningpopupBtn').click();
                    } else {
                        jQuery("html, body").animate({ scrollTop: 0 }, "slow");
                        console.log('ScrollTop #2');
                        if(result.numberOfDifferentItemsInCart){
                            jQuery('.MyCart').append('<div id="cartNoBxItemCount" class="cartNoBx">'+result.numberOfDifferentItemsInCart+'</'+'div'+'>');
                        }
                        var newQuantityDetected = false;
                        if (result.data.newQty && result.data.newQty > 0) {
                            jQuery('#qtyaddtocartfield' + sku).val(result.data.newQty);
                            selectedQuantityOfSearchList = result.data.newQty;
                            newQuantityDetected = true;
                        }

                        var messageArray = result.data.messages;
                        if(result.result.indexOf("SUCCESS") == -1){
                            appendMessageUl(messageArray, 'messages', 'error-msg', 'glyphicon glyphicon-exclamation-sign');
                            console.log('appendMessageUl #49');
                        } else {
                            if (newQuantityDetected == false) {
                                var linkText = jQuery('#textLink_' + sku).text();
                                linkText = linkText.replace('<span class="results-highlight">', '');
                                linkText = linkText.replace('</span>', '');
                                linkText = linkText.replace(/(\r\n|\n|\r)/gm, "");
                                linkText = linkText.trim();
                                var trackingData = new Object();
                                trackingData.trackingEnabled = globalTRACKING_ENABLED;
                                trackingData.pagetype        = 'search results';
                                trackingData.sku             = sku;
                                trackingData.name            = linkText;
                                //trackingData.price           = jQuery('.addToCartLink').attr("data-price");
                                trackingData.category        = category;
                                trackingData.currencyCode    = globalCURRENCY_CODE;
                                trackingData.quantity        = selectedQuantityOfSearchList;

                                // Writing some EE product values to locaStorage:
                                localStorage.setItem('trackingData_pagetype', trackingData.pagetype);
                                localStorage.setItem('trackingData_name', trackingData.name);
                                localStorage.setItem('trackingData_category', trackingData.category );
                                localStorage.setItem('trackingData_featureSrc', 'Search Result Page Product');

                                addToCartTracking(trackingData, 'Search');
                            }
                            appendMessageUl(messageArray, 'messages', 'success-msg', 'glyphicon glyphicon-ok');
                            console.log('appendMessageUl #50');
                        }
                    }
                },
                'error': function (data) {
                    var parsedData = data;
                    //debugger;
                }
            });
        },
        'error': function (data) {
        var parsedData = data;
        //debugger;
        }
    });
}

function vaildateEmail ( email, responseCallback ) {
    dataArray = {};
    dataArray.validateEmailAddress = { 'data' : { 'email_address' : email }};
    ajaxDispatcherCallWithCallback(responseCallback);
}

function testio() {
    console.log('hallo');
}

function suppressAjaxDispatcherCalls () {
    __suppressAjaxDispatcherCalls = true;
}


function validateActAsACustomer (userEmail, systemContactEmail, loggedInAsACustomerSuffix, loggedInAsACustomerSuffixButton) {
    var ajaxUrl = BASE_URL + 'customer/account/validateActAsACustomer';

    jQuery.ajax(ajaxUrl, {
        'dataType' : 'json',
        'type': 'POST',
        'data': {
            'form_key' : globalFORM_KEY,
            'user_email' : userEmail,
            'system_contact_email' : systemContactEmail
        },
        'success': function (response) {
            var parsedData = response;
            if (parsedData.result == 'success') {
                jQuery('#showActAsACustomerEmailField').show();
                var mail = localStorage.actAsACustomerRealEmail;
                jQuery('#showActAsACustomerEmail').html(mail + ' ' + loggedInAsACustomerSuffix + ' ' + loggedInAsACustomerSuffixButton);
            }
        },
        'error': function (data) {
            var parsedData = data;
            //debugger;
        }
    });
}


function deferredAddressValidation(mode, street, postcode, city, country, location) {
    var errTranslation = Translator.translate('Address check input failure recognized');
    var errContinue    = Translator.translate('Proceed Anyway');

    if (localStorage.getItem("counterIgnore") === null) {
        localStorage.setItem('counterIgnore', '');
    }
    if (location == 'billing_2') {
        if (localStorage.getItem("counterIgnore") == 'ignore') {
            console-log('Zweiter Durchlauf -> Jetzt kann gespeichert werden');
            localStorage.setItem('counterIgnore', '');
        } else {
            console-log('Erster Durchlauf -> Nicht Speichern');
            localStorage.setItem('counterIgnore', 'ignore');
            return false;
        }
    }

    var address_validation_service_url = BASE_URL + 'onlinetools/commonTools/addressValidation';
    var adressdata = {
        'street':street,
        'postcode':postcode,
        'city':city,
        'country':country
    };

    jQuery.ajax(address_validation_service_url, {
        'dataType': 'json',
        'type': 'POST',
        'data': {
            'adressdata' : adressdata
        },
        'success': function (responseData) {
            if (responseData != '' && responseData != null) {
                var parsedData = responseData;
                console.log(location);
                if (parsedData.result == 'SUCCESS') {
                    console.log('addressValidation succeeded');
                    if (mode == 'shipping') {
                        shipping.save();
                    }
                    if (mode == 'billing') {
                        billing.save();
                    }
                    if (mode == 'registration') {
                        tryFullRegistration();
                    }
                    if (mode == 'addressmodification') {
                        jQuery('#address-form-validate').submit();
                    }
                } else {
                    console.log('addressValidation failed');
                    var jump = '';
                    if (mode == 'shipping') {
                        jump = ' <span style="cursor: pointer; font-weight: bold" onclick="snackBarContinue(\'shipping\');">' + errContinue + '</span>';
                    }
                    if (mode == 'billing') {
                        jump = ' <span style="cursor: pointer; font-weight: bold" onclick="snackBarContinue(\'billing\');">' + errContinue + '</span>';
                    }
                    if (mode == 'registration') {
                        jump = ' <span style="cursor: pointer; font-weight: bold" onclick="snackBarContinue(\'registration\');">' + errContinue + '</span>';
                    }
                    if (mode == 'addressmodification') {
                        jump = ' <span style="cursor: pointer; font-weight: bold" onclick="snackBarContinue(\'addressmodification\');">' + errContinue + '</span>';
                    }
                    appendMessageUl([errTranslation + jump], 'messages_hidden', 'error-msg');
                }
            }
        },
        'error': function (data) {
            var parsedData = data;
            //debugger;
        }
    });
}

function snackBarContinue(mode) {
    if (mode == 'billing') {
        billing.save();
        jQuery('.smackbar-close').click();
    }
    if (mode == 'shipping') {
        shipping.save();
        jQuery('.smackbar-close').click();
    }
    if (mode == 'registration') {
        tryFullRegistration();
        console.log('continue registration, but address validation failed');
        jQuery('.smackbar-close').click();
    }
    if (mode == 'addressmodification') {
        jQuery('#address-form-validate').submit();
        console.log('continue saving address, but address validation failed');
        jQuery('.smackbar-close').click();
    }
}
