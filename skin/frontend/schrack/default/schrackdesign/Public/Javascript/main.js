/**
 * main.js
 * Contains custom js that is served with every page.
 */

if('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/serviceworker.js')
        .then(function() { console.log("Service Worker Registered"); });
}

jQuery.noConflict();

jQuery(document).ready(function ($) {
	jQuery("<style type='text/css' id='pseudoClass'></style>").appendTo(jQuery('head'));
	var display = 'block';
	var isTouchDevice = is_touch_device();
	resizeTilted();
	//pageZoom();

	jQuery(".big-slider").show();
	var bigSliderWidth = jQuery('.big-slider').width() - 21;
	if (bigSliderWidth > 0) {
		jQuery('.background-bottom-overview-element').css('width', bigSliderWidth + 'px');
	}

	$(".lightbox").click(function(e){
		e.preventDefault();
		e.stopPropagation();
	});
	baguetteBox.run('.tz-gallery', {
		captions: function(element) {
			return element.getElementsByTagName('img')[0].title;
		}
	});

	//jQuery(document).foundation();
	$('.big-slider').imagesLoaded()
		.done(function (instance) {
			jQuery('.big-slider ul').css({
				'visibility': 'visible',
				'opacity': '1'
			});
			jQuery(".big-slider").removeClass("loading");
		})

	$(window).resize(function () {
		resizeTilted();
	});

	if (!jQuery('.bottom-header').hasClass("no-menu")) {
		//if (loadCachedMegaMenu() == false ) {
			//loadMegaMenuLayer();
		//}
	}

	$('.section-container').each(function () {
		$(this).children('section:first').addClass("active");
	});

	// toggle
	var toggleStatus = 0
	$('.toggleHeader').click(function (event) { // trigger
		event.preventDefault();
		$(this).next('.toggleContent').slideToggle('fast');
	});

	//Hide the tooglebox when page load
	var current, toggleBoxes = $(".toggleItem").hide();
	var isOpened = false;
	//slide up and down when click over heading 2
	$(".gridContainer, .toggleShowOnlyOneItemContainer").children("h2, .toggleItemHeader").click(function () {
		current = $(this).next(".toggleItem");
		if ($(current).is(":visible")) {
			$(this).parent().parent().find('.toggleIsOpened').removeClass('toggleIsOpened');
			isOpened = true;
		} else {
			$(this).parent().parent().find('.toggleIsOpened').removeClass('toggleIsOpened');
			isOpened = false;
			$(this).addClass("toggleIsOpened");

		}
		toggleBoxes.slideUp('fast');

		if (isOpened == false) {
			current.slideDown("fast");
		} else {

		}
	});


	if (isTouchDevice) {
		// disable hover effect - for hover nothing will happen
		jQuery('.fixed-box1').css('right', '-253px');
		var isClicked = false;

		jQuery('.fixed-box1').click(function () {
			if (isClicked == false) {
				jQuery(this).css({
					right: '0'
				});
				isClicked = true;
			} else {
				jQuery(this).css({
					right: '-253px'
				});
				isClicked = false;
			}
		})

	} else {

		jQuery('.fixed-box1').click(function () {


			$(this).mouseleave(function () {
				$(this).removeAttr('style');
			});

			jQuery(this).css({
				right: '0',
				cursor: 'default'
			});
		});
	}

	$('#big-search-field').autocomplete({
		messages: {
			noResults: '',
			results: function () {
			}
		},
		appendTo: '#big-search-field-auto-suggest'
	});

	jQuery('nav li a').on('click', function () {
		if (jQuery(this).parents('.col-md-4.col-sm-4.col-xs-6.col1').size()) {
			if (dataLayer && jQuery(this).text()) {
				dataLayer.push({
					'event' : 'allNavigation',
					'eventAction' : 'Footerlinks Navigation',
					'eventLabel' : jQuery(this).text()
				});
			}
		}
	});


}) /* ready END */;


function resizeTilted() {
	var windowSize = jQuery(window).width();
	var windowSpace = (windowSize - 1180) / 2;
	if (windowSpace < 31) {
		windowSpace = 1;
	}
	var rightTopHeaderbefore = windowSpace - 45;
	var widthTopHeaderafter = windowSpace - 31;

	if (windowSpace < 31) {
		display = 'none';
	} else {
		display = 'block';
	}

	jQuery('#pseudoClass').replaceWith("<style type='text/css' id='pseudoClass'>.top-header:before{display: " + display + "; right:" + rightTopHeaderbefore + "px;} .top-header:after{display: " + display + "; width: " + widthTopHeaderafter + "px;} </style>");
}


function pageZoom() {
	// Don't mess with the zooming if we're in the app and thus the mobile template
	var userAgent = navigator.userAgent;
	if (userAgent.toLowerCase().indexOf("schrack") == -1) {
		var zoomPercent = Math.round((jQuery(window).width() / 1180) * 100) / 100;
		var windowSize = jQuery(window).width();
		if (windowSize < 1180) {
			jQuery('body').css({
				'-moz-transform': 'scale(' + zoomPercent + ')',
				'-webkit-transform': 'scale(' + zoomPercent + ')',
				'-o-transform': 'scale(' + zoomPercent + ')',
				'-ms-transform': 'scale(' + zoomPercent + ')',
				'transform': 'scale(' + zoomPercent + ')',
				'-moz-transform-origin': 'top left',
				'-webkit-transform-origin': 'top left',
				'-o-transform-origin': 'top left',
				'-ms-transform-origin': 'top left',
				'transform-origin': 'top left'
			});
		}
	}
}

function autocomplete() {
	jQuery('#search-field, #big-search-field').autocomplete({
		messages: {
			noResults: '',
			results: function () {
			}
		}
	});

	jQuery("#search-field, #big-search-field").bind("autocompleteopen", function (event, ui) {
		jQuery('.ui-autocomplete li:even').addClass('even');
		jQuery('.ui-autocomplete li:odd').addClass('odd');
	});
}

function is_touch_device() {
	try {
		if (Modernizr.touch || document.createEvent("TouchEvent")) {
			return true;
		}
		return false;
	} catch (e) {
		return false;
	}
}

function addCableDrumField() {
	var countColumns = jQuery(".cable-drum-table tr").length;
	if (countColumns < 10) {
		var currentPosNumber = '0' + countColumns;
	} else {
		var currentPosNumber = countColumns
	}
	if (countColumns < 11) {
		jQuery('.cable-drum-table').append('<tr><td>' + currentPosNumber + '</td><td><label for="cable-drum-nr' + countColumns + '" class=""> </label><input type="text" value="" name="contact[cable-drum-nr' + countColumns + ']" id="cable-drum-nr' + countColumns + '" size="20" class=""></td><td><label for="cable-drum-note' + countColumns + '" class=""> </label><input type="text" value="" name="contact[cable-drum-note' + countColumns + ']" id="cable-drum-note' + countColumns + '" size="20" class=""></td></tr>');
	}
}

function printPage() {
	focus();
	if (window.print) {
		jetztdrucken = confirm('Möchten Sie die Seite jetzt ausdrucken?');
		if (jetztdrucken) window.print();
	}
}

function loadCachedMegaMenu() {
	localStorage.refreshMegaMenuForceTimeCurrent = MEGA_MENU_LATEST_REFRESH_TIMESTAMP;
	console.log('MEGA_MENU_LATEST_REFRESH_TIMESTAMP: ' + MEGA_MENU_LATEST_REFRESH_TIMESTAMP);
	if (!localStorage.refreshMegaMenuForceTimeLastChange || localStorage.refreshMegaMenuForceTimeCurrent > localStorage.refreshMegaMenuForceTimeLastChange) {
		// Remove content from localstorage, to set semaphore to refill with new content:
		localStorage.megamenuContentResponsive = '';

		// ...and set the current forcetime to last change
		localStorage.refreshMegaMenuForceTimeLastChange = localStorage.refreshMegaMenuForceTimeCurrent;
		console.log('>>> Reset localstorage mobile and desktop mega menu');
		return false;
	} else {
		if (localStorage.megamenuContentResponsive || localStorage.megamenuContentResponsive != '') {
			var self = ".bottom-header";

			// TODO Check, if user is logged in:
			// Load WAF-cached mega-menu for non-logged-in users:
			// jQuery(self).prepend(localStorage.megamenuContentCacheParamAppended);
			if (typeof checkout === "undefined" || !checkout) {
				jQuery(self).prepend(localStorage.megamenuContentResponsive);
				console.log('>>> Loaded responsive mega-menu from localstorage');
			} else {
				console.log('>>> Responsive Mega-menu not available in checkout');
			}

			return true;
		} else {
			console.log('>>> Cannot load responsive mega-menu from localstorage --> empty');
			return false;
		}
	}
}
