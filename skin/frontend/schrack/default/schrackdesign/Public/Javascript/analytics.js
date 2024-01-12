/*
function trackProductLink(eventSource, itemSelector, listSelector) {
	if (typeof(ga) != "undefined") {
		var productItem = jQuery(eventSource).closest(itemSelector);
		var productList = jQuery(productItem).closest(listSelector);
		var href = jQuery(eventSource).attr("href");
		// Don't try to track anything if info couldn't be loaded completely
		if (!productItem.length || !productList.length || typeof(href) == "undefined") {
			return true;
		}
		ga("ec:addProduct", {
			"id": jQuery(productItem).attr("data-sku"),
			"name": jQuery(productItem).attr("data-name"),
			"category": jQuery(productItem).attr("data-category"),
			"price": jQuery(productItem).attr("data-price"),
			"position": jQuery(productItem).attr("data-position")
		});
		ga('ec:setAction', 'click', {list: jQuery(productList).attr("data-name")});
		ga('send', 'event', 'UX', 'click', 'Product', {
			'hitCallback': function() {
				document.location = href;
			}
		});
		setTimeout(function(){document.location = href;},1000);
		return false;
	} else {
		return true;
	}
}

function trackListAddToCart(eventSource, actionAfter) {
	if (typeof(ga) != "undefined") {
		var productItem = jQuery(eventSource).closest(".product-item");
		var productList = jQuery(productItem).closest(".product-list");
		var qty = jQuery(eventSource).parent().find(".qty").val();
		if (!qty) {
			qty = 1;
		}
		ga("ec:addProduct", {
			"id": jQuery(productItem).attr("data-sku"),
			"name": jQuery(productItem).attr("data-name"),
			"category": jQuery(productItem).attr("data-category"),
			"price": jQuery(productItem).attr("data-price"),
			"position": jQuery(productItem).attr("data-position"),
			"quantity": qty
		});
		ga("ec:setAction", "add", {list: jQuery(productList).attr("data-name")});
		ga("send", "event", "UX", "click", "add to cart", {
			hitCallback: function() {
				actionAfter;
			}
		});
		setTimeout(function(){actionAfter;},1000);
	} else {
		actionAfter;
	}
}

function trackDetailAddToCart(actionAfter) {
	if (typeof(ga) != "undefined") {
		var productItem = jQuery('#detail-product-data');
		var qty = jQuery('#qty').val();
		if (!qty) {
			qty = 1;
		}
		ga("ec:addProduct", {
			"id": jQuery(productItem).attr("data-sku"),
			"name": jQuery(productItem).attr("data-name"),
			"category": jQuery(productItem).attr("data-category"),
			"price": jQuery(productItem).attr("data-price"),
			"quantity": qty
		});
		ga("ec:setAction", "add");
		ga("send", "event", "UX", "click", "add to cart", {
			hitCallback: function() {
				actionAfter;
			}
		});
		setTimeout(function(){actionAfter;},1000);
	} else {
		actionAfter;
	}
}

function trackRemoveFromCart(eventSource, actionAfter) {
	if (typeof(ga) != "undefined") {
		var productItem = jQuery(eventSource).closest(".product-item");
		var productList = jQuery(productItem).closest(".product-list");
		var qty = jQuery(eventSource).parent().find(".qty").val();
		if (!qty) {
			qty = 1;
		}
		ga("ec:addProduct", {
			"id": jQuery(productItem).attr("data-sku"),
			"name": jQuery(productItem).attr("data-name"),
			"category": jQuery(productItem).attr("data-category"),
			"price": jQuery(productItem).attr("data-price"),
			"position": jQuery(productItem).attr("data-position"),
			"quantity": qty
		});
		ga("ec:setAction", "remove", {list: jQuery(productList).attr("data-name")});
		ga("send", "event", "UX", "click", "remove from cart", {
			hitCallback: function() {
				actionAfter;
			}
		});
		setTimeout(function(){actionAfter;},1000);
	} else {
		actionAfter;
	}
}

function getCanonicalUrl() {
	var canonical = location.pathname;
	var links = document.getElementsByTagName("link");
	for (var i = 0; i < links.length; i ++) {
		if (links[i].getAttribute("rel") === "canonical") {
			var reg = /.+?\:\/\/.+?(\/.+?)(?:#|\?|$)/;
			canonical = reg.exec(links[i].getAttribute("href"));
			if (canonical !== null) {
				canonical = canonical[1];
			} else {
				canonical = '/';
			}
		}
	}
	return canonical;
}
*/