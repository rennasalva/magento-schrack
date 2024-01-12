jQuery(document).ready(function () {
    var viewportWidth = window.innerWidth; // jQuery(window).width()
    var viewportHeight1 = window.innerHeight; // jQuery(window).height()
    var viewportHeight2 = jQuery(window).height(); // jQuery(window).height()

    console.log('javascript for menu actions ready to process (menu.js)');

    function createMobileBackgroundOverlay() {
        if( jQuery('.mobile_overlay').length == 0 ){
            jQuery('body').append('<div class="mobile_overlay"></div>');
        }
        jQuery('body').css('height', '100px');
        jQuery('.mobile_overlay').show();
    }


    function removeMobileBackgroundOverlay() {
        console.log('removeMobileBackgroundOverlay');
        jQuery('.mobile_overlay').remove();
        console.log('Reset body to normal size #1');
        jQuery('body').css('height', 'auto');
    }


    function menuTracking(trackingCat) {
        if (dataLayer && trackingCat) {
            dataLayer.push({
                'event': 'allNavigation',
                'eventAction' : 'Mega Menu Navigation',
                'eventLabel' : trackingCat
            });
        }
    }

    function mainNavClosing() {
        jQuery('.nav-panel').hide();
        if (viewportWidth <= 992) {
            jQuery('.top-navigation-container').hide();
            removeMobileBackgroundOverlay();
            resetAllSublayers();
            console.log('.main_nav_closing Clicked on mobile device (menu.js)');
        } else {
            console.log('.main_nav_closing Clicked on desktop device (menu.js)');
            jQuery('.backfiller_left').css('background', '#e8f0fa');
            jQuery('.backfiller_right').css('background', '#e8f0fa');
        }
        jQuery('.top_navigation_item.active').removeClass('active');
    }


    // Just wait and observe, until the menu has loaded finally:
    var ajaxCallMenuLoadedFinished = false;
    var counter = 0;
    var maxCount = 20;
    var checkMenuAjaxResponse = setInterval( function() {
        if (jQuery('#navigationEnvelope').length ){
            ajaxCallMenuLoadedFinished = true;
        }

        if (ajaxCallMenuLoadedFinished == true) {
            // After menu-HTML is inside the DOM, we can define events:
            console_info_green('#navigationEnvelope FOUND -> LOADING events (menu.js)');
            setEventsAfterMenuLoadedByAjax();
            clearInterval(checkMenuAjaxResponse);
        } else {
            if (counter < maxCount) {
                console_info_red('#navigationEnvelope NOT FOUND - will try again (menu.js)');
                counter++;
            } else {
                console_info_red('#navigationEnvelope Search FAILED (menu.js)');
                clearInterval(checkMenuAjaxResponse);
            }
        }
    }, 500);

    function console_info_red(message) {
        console.log("%c menu %c " + message, "color: white; background: red;", "");
    }

    function console_info_green(message) {
        console.log("%c menu %c " + message, "color: white; background: #638F4C;", "");
    }

    function resetAllSublayers() {
        if (viewportWidth <= 992) {
            jQuery('.main-categories_panel').css('left', '2000px');
            jQuery('.sub-categories_panel').css('left', '2000px');
            jQuery('.top_navigation_main').removeClass('active');
            jQuery('.top_navigation_item').css('color', '#00589d');
            console.log('Reset body to normal size #2');
            jQuery('body').css('height', 'auto');
            console.log('Reset All Sublayers (menu.js)');
        }
    }

    function setEventsAfterMenuLoadedByAjax() {
        // Define all Events only here for the menu
        // ...
        console.log('Placing related events for menu (menu.js)');

        if (jQuery(window).width() > 992) {
            jQuery('.top_navigation_item_first').on('mouseover', function() {
                jQuery('.backfiller_left').css('background', '#00589d');
            });
            jQuery('.top_navigation_item_first').on('mouseout', function() {
                if (!jQuery('.top_navigation_item_first').hasClass('active')) {
                    jQuery('.backfiller_left').css('background', '#e8f0fa');
                }
            });
            jQuery('.top_navigation_item_alternate').on('click', function() {
                jQuery('.backfiller_left').css('background', '#e8f0fa');
            });
            jQuery('.top_navigation_item_quickadd').on('mouseover', function() {
                jQuery('.backfiller_right').css('background', '#00589d');
                jQuery('.top_navigation_item_quickadd').css('background', '#00589d');
                jQuery('.top_navigation_item_quickadd').removeClass('active');
            });
            jQuery('.top_navigation_item_quickadd').on('mouseout', function() {
                jQuery('.backfiller_right').css('background', '#e8f0fa');
                jQuery('.top_navigation_item_quickadd').css('background', '#e8f0fa');
                jQuery('.top_navigation_item_quickadd').removeClass('active');
            });
            jQuery('.top_navigation_item_quickadd').on('click', function() {
                jQuery('.backfiller_right').css('background', '#e8f0fa');
                jQuery('.top_navigation_item_quickadd').css('background', '#e8f0fa');
                jQuery('.top_navigation_item_quickadd').removeClass('active');
            });
        }

        jQuery('.single_top_nav_special_case').on('click', function(){
            var directurl = jQuery(this).attr('data-directurl');
            window.location = directurl;
        });

        jQuery('.top_navigation_main').on('click', function() {
            var mainCatTargetPanel = jQuery(this).attr('data-target');
            jQuery('.main-categories_panel').hide();
            jQuery('.sub-categories_panel').hide();
            jQuery('div[data-source="' + mainCatTargetPanel + '"]').show();
            if (viewportWidth <= 992) {
                // Animation from right to left side:
                jQuery('div[data-source="' + mainCatTargetPanel + '"]').animate({'left':0}, "slow");
                jQuery('div[data-source="' + mainCatTargetPanel + '"]').animate(
                    {'right':(jQuery('body').innerWidth() - jQuery('div[data-source="' + mainCatTargetPanel + '"]').width())},
                    'slow');
                jQuery('.top_navigation_main').removeClass('active');
                jQuery('.top_navigation_item').css('color', '#00589d');
                //jQuery(this).addClass('active');
                //jQuery(this).children('.top_navigation_item').css('color', 'white');
                //jQuery(this).children('.top_navigation_item').css('background', 'transparent');
                console.log('.top_navigation_main Clicked on mobile device (menu.js)');
            } else {
                jQuery('.top_navigation_item').removeClass('active');
                jQuery(this).children('.top_navigation_item').addClass('active');
                console.log('.top_navigation_main Clicked on desktop device (menu.js)');
                jQuery('.backfiller_right').css('background', '#e8f0fa');
                jQuery('.top_navigation_item_quickadd').removeClass('active');
            }
        });

        jQuery('.main_catInner_shop').on('click', function() {
            var subCatTargetPanel = jQuery(this).attr('data-target-subpanel');
            var manCatSource      = jQuery(this).attr('data-source');
            if (viewportWidth <= 992) {
                // Animation from right to left side:
                jQuery('div[data-source="' + subCatTargetPanel + '"]').show();
                jQuery('div[data-source="' + subCatTargetPanel + '"]').animate({'left':0}, "slow");
                jQuery('div[data-source="' + subCatTargetPanel + '"]').animate(
                    {'right':(jQuery('body').innerWidth() - jQuery('div[data-source="' + subCatTargetPanel + '"]').width())},
                 'slow');
                console.log('.main_catInner_shop Clicked on mobile device (menu.js)');
            } else {
                console.log('.main_catInner_shop Clicked on desktop device (menu.js)');
                jQuery('div[data-source="' + manCatSource + '"]').hide();
                jQuery('div[data-source="' + subCatTargetPanel + '"]').show();
            }
            if (jQuery(this).attr('data-tracking-cat')) {
                var trackingCat = jQuery(this).children('div').children('div').children('div').text();
                menuTracking(trackingCat);
            }
        });

        jQuery('.nav_back_to_top_cats').on('click', function() {
            if (viewportWidth <= 992) {
                jQuery('.main-categories_panel').hide();
                jQuery('.top_navigation_item').removeClass('active');
                jQuery('.main-categories_panel').css('left', '2000px');
                console.log('.nav_back_to_top_cats Clicked on mobile device (menu.js)');
            } else {
                console.log('.nav_back_to_top_cats Clicked on desktop device (menu.js)');
            }
        });

        jQuery('.subNav_back').on('click', function() {
            var targetShowPanel = jQuery(this).attr('data-target-show');
            var targetHidePanel = jQuery(this).attr('data-target-hide');
            jQuery('div[data-source="' + targetHidePanel + '"]').hide();
            jQuery('div[data-source="' + targetShowPanel + '"]').show();
            if (viewportWidth <= 992) {
                jQuery('.sub-categories_panel').css('left', '2000px');
                console.log('.subNav_back Clicked on mobile device (menu.js)');
            } else {
                console.log('.subNav_back Clicked on desktop device (menu.js)');
            }
        });

        jQuery('.main_nav_closing').on('click', function() {
            mainNavClosing();
        });

        jQuery('.sub_nav_closing').on('click', function() {
            jQuery('.nav-panel').hide();
            if (viewportWidth <= 992) {
                jQuery('.top-navigation-container').hide();
                removeMobileBackgroundOverlay();
                resetAllSublayers();
                console.log('.sub_nav_closing Clicked on mobile device (menu.js)');
            } else {
                console.log('.sub_nav_closing Clicked on desktop device (menu.js)');
            }
            jQuery('.top_navigation_item.active').removeClass('active');
        });

        jQuery('.sub_catInner').on('click', function() {
            console.log('.sub_catInner Clicked on mobile device (menu.js)');
            var targetUrl = jQuery(this).attr('data-target-url');
            var trackingCat = jQuery(this).children('div').children('div').children('div').text();
            menuTracking(trackingCat);
            window.location = targetUrl;
        });

        jQuery('.main_catInner_typo').on('click', function() {
            console.log('.main_catInner_typo Clicked on mobile device (menu.js)');
            var targetUrl = jQuery(this).attr('data-target-url');
            window.location = targetUrl;
        });

        jQuery('#hamburgerMenuAlternateButton').on('click', function() {
            console.log('#hamburgerMenuAlternateButton Clicked on mobile device (menu.js)');
            createMobileBackgroundOverlay()
            jQuery('.top-navigation-container').show();
        });

        jQuery('.closeMobileMainLayer').on('click', function() {
            console.log('#closeMobileMainLayer Clicked on mobile device (menu.js)');
            removeMobileBackgroundOverlay();
            jQuery('.top-navigation-container').hide();
            jQuery('.top_navigation_main').removeClass('active');
            jQuery('.top_navigation_item').css('color', '#00589d');
        });
    }

    jQuery(document).on('click', function (event) {
        if (!jQuery(event.target).closest('.nav-panel').length
            && !jQuery(event.target).closest('.top_navigation_main').length
            && !jQuery(event.target).closest('.top_navigation_item').length
            && !jQuery(event.target).closest('.hamburgerMenuAlternateButtonIos').length
            && !jQuery(event.target).closest('.hamburgerMenuAlternateIosTypo').length
            && !jQuery(event.target).closest('.hamburgerMenuAlternateButtonIosTypo').length
            && !jQuery(event.target).closest('#hamburgerMenuAlternateButton').length
            && !jQuery(event.target).closest('#hamburgerMenuAlternateButtonTypo').length
            && !jQuery(event.target).closest('#usercentrics-root').length
            && !jQuery(event.target).closest('#checkoutSteps ').length) {
            console.log(jQuery(window).width());
            if (jQuery(window).width() > 992) {
                console.log('Menu Closing');
                mainNavClosing();
            }
        }
    });
});
