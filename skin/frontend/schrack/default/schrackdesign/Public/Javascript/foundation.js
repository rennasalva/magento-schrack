/*
 * Foundation Responsive Library
 * http://foundation.zurb.com
 * Copyright 2013, ZURB
 * Free to use under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 */
(function (e, t, n, r) {
	"use strict";
	function i(e) {
		var t, n = this;
		this.trackingClick = !1, this.trackingClickStart = 0, this.targetElement = null, this.touchStartX = 0, this.touchStartY = 0, this.lastTouchIdentifier = 0, this.touchBoundary = 10, this.layer = e;
		if (!e || !e.nodeType)throw new TypeError("Layer must be a document node");
		this.onClick = function () {
			return i.prototype.onClick.apply(n, arguments)
		}, this.onMouse = function () {
			return i.prototype.onMouse.apply(n, arguments)
		}, this.onTouchStart = function () {
			return i.prototype.onTouchStart.apply(n, arguments)
		}, this.onTouchMove = function () {
			return i.prototype.onTouchMove.apply(n, arguments)
		}, this.onTouchEnd = function () {
			return i.prototype.onTouchEnd.apply(n, arguments)
		}, this.onTouchCancel = function () {
			return i.prototype.onTouchCancel.apply(n, arguments)
		};
		if (i.notNeeded(e))return;
		this.deviceIsAndroid && (e.addEventListener("mouseover", this.onMouse, !0), e.addEventListener("mousedown", this.onMouse, !0), e.addEventListener("mouseup", this.onMouse, !0)), e.addEventListener("click", this.onClick, !0), e.addEventListener("touchstart", this.onTouchStart, !1), e.addEventListener("touchmove", this.onTouchMove, !1), e.addEventListener("touchend", this.onTouchEnd, !1), e.addEventListener("touchcancel", this.onTouchCancel, !1), Event.prototype.stopImmediatePropagation || (e.removeEventListener = function (t, n, r) {
			var i = Node.prototype.removeEventListener;
			t === "click" ? i.call(e, t, n.hijacked || n, r) : i.call(e, t, n, r)
		}, e.addEventListener = function (t, n, r) {
			var i = Node.prototype.addEventListener;
			t === "click" ? i.call(e, t, n.hijacked || (n.hijacked = function (e) {
				e.propagationStopped || n(e)
			}), r) : i.call(e, t, n, r)
		}), typeof e.onclick == "function" && (t = e.onclick, e.addEventListener("click", function (e) {
			t(e)
		}, !1), e.onclick = null)
	}

	function o(e) {
		if (typeof e == "string" || e instanceof String)e = e.replace(/^[\\/'"]+|(;\s?})+|[\\/'"]+$/g, "");
		return e
	}

	e("head").has(".foundation-mq-small").length === 0 && e("head").append('<meta class="foundation-mq-small">'), e("head").has(".foundation-mq-medium").length === 0 && e("head").append('<meta class="foundation-mq-medium">'), e("head").has(".foundation-mq-large").length === 0 && e("head").append('<meta class="foundation-mq-large">'), e("head").has(".foundation-mq-xlarge").length === 0 && e("head").append('<meta class="foundation-mq-xlarge">'), e("head").has(".foundation-mq-xxlarge").length === 0 && e("head").append('<meta class="foundation-mq-xxlarge">'), i.prototype.deviceIsAndroid = navigator.userAgent.indexOf("Android") > 0, i.prototype.deviceIsIOS = /iP(ad|hone|od)/.test(navigator.userAgent), i.prototype.deviceIsIOS4 = i.prototype.deviceIsIOS && /OS 4_\d(_\d)?/.test(navigator.userAgent), i.prototype.deviceIsIOSWithBadTarget = i.prototype.deviceIsIOS && /OS ([6-9]|\d{2})_\d/.test(navigator.userAgent), i.prototype.needsClick = function (e) {
		switch (e.nodeName.toLowerCase()) {
			case"button":
			case"select":
			case"textarea":
				if (e.disabled)return!0;
				break;
			case"input":
				if (this.deviceIsIOS && e.type === "file" || e.disabled)return!0;
				break;
			case"label":
			case"video":
				return!0
		}
		return/\bneedsclick\b/.test(e.className)
	}, i.prototype.needsFocus = function (e) {
		switch (e.nodeName.toLowerCase()) {
			case"textarea":
			case"select":
				return!0;
			case"input":
				switch (e.type) {
					case"button":
					case"checkbox":
					case"file":
					case"image":
					case"radio":
					case"submit":
						return!1
				}
				return!e.disabled && !e.readOnly;
			default:
				return/\bneedsfocus\b/.test(e.className)
		}
	}, i.prototype.sendClick = function (e, r) {
		var i, s;
		n.activeElement && n.activeElement !== e && n.activeElement.blur(), s = r.changedTouches[0], i = n.createEvent("MouseEvents"), i.initMouseEvent("click", !0, !0, t, 1, s.screenX, s.screenY, s.clientX, s.clientY, !1, !1, !1, !1, 0, null), i.forwardedTouchEvent = !0, e.dispatchEvent(i)
	}, i.prototype.focus = function (e) {
		var t;
		this.deviceIsIOS && e.setSelectionRange ? (t = e.value.length, e.setSelectionRange(t, t)) : e.focus()
	}, i.prototype.updateScrollParent = function (e) {
		var t, n;
		t = e.fastClickScrollParent;
		if (!t || !t.contains(e)) {
			n = e;
			do {
				if (n.scrollHeight > n.offsetHeight) {
					t = n, e.fastClickScrollParent = n;
					break
				}
				n = n.parentElement
			} while (n)
		}
		t && (t.fastClickLastScrollTop = t.scrollTop)
	}, i.prototype.getTargetElementFromEventTarget = function (e) {
		return e.nodeType === Node.TEXT_NODE ? e.parentNode : e
	}, i.prototype.onTouchStart = function (e) {
		var n, r, i;
		if (e.targetTouches.length > 1)return!0;
		n = this.getTargetElementFromEventTarget(e.target), r = e.targetTouches[0];
		if (this.deviceIsIOS) {
			i = t.getSelection();
			if (i.rangeCount && !i.isCollapsed)return!0;
			if (!this.deviceIsIOS4) {
				if (r.identifier === this.lastTouchIdentifier)return e.preventDefault(), !1;
				this.lastTouchIdentifier = r.identifier, this.updateScrollParent(n)
			}
		}
		return this.trackingClick = !0, this.trackingClickStart = e.timeStamp, this.targetElement = n, this.touchStartX = r.pageX, this.touchStartY = r.pageY, e.timeStamp - this.lastClickTime < 200 && e.preventDefault(), !0
	}, i.prototype.touchHasMoved = function (e) {
		var t = e.changedTouches[0], n = this.touchBoundary;
		return Math.abs(t.pageX - this.touchStartX) > n || Math.abs(t.pageY - this.touchStartY) > n ? !0 : !1
	}, i.prototype.onTouchMove = function (e) {
		if (!this.trackingClick)return!0;
		if (this.targetElement !== this.getTargetElementFromEventTarget(e.target) || this.touchHasMoved(e))this.trackingClick = !1, this.targetElement = null;
		return!0
	}, i.prototype.findControl = function (e) {
		return e.control !== r ? e.control : e.htmlFor ? n.getElementById(e.htmlFor) : e.querySelector("button, input:not([type=hidden]), keygen, meter, output, progress, select, textarea")
	}, i.prototype.onTouchEnd = function (e) {
		var r, i, s, o, u, a = this.targetElement;
		if (!this.trackingClick)return!0;
		if (e.timeStamp - this.lastClickTime < 200)return this.cancelNextClick = !0, !0;
		this.lastClickTime = e.timeStamp, i = this.trackingClickStart, this.trackingClick = !1, this.trackingClickStart = 0, this.deviceIsIOSWithBadTarget && (u = e.changedTouches[0], a = n.elementFromPoint(u.pageX - t.pageXOffset, u.pageY - t.pageYOffset) || a, a.fastClickScrollParent = this.targetElement.fastClickScrollParent), s = a.tagName.toLowerCase();
		if (s === "label") {
			r = this.findControl(a);
			if (r) {
				this.focus(a);
				if (this.deviceIsAndroid)return!1;
				a = r
			}
		} else if (this.needsFocus(a)) {
			if (e.timeStamp - i > 100 || this.deviceIsIOS && t.top !== t && s === "input")return this.targetElement = null, !1;
			this.focus(a);
			if (!this.deviceIsIOS4 || s !== "select")this.targetElement = null, e.preventDefault();
			return!1
		}
		if (this.deviceIsIOS && !this.deviceIsIOS4) {
			o = a.fastClickScrollParent;
			if (o && o.fastClickLastScrollTop !== o.scrollTop)return!0
		}
		return this.needsClick(a) || (e.preventDefault(), this.sendClick(a, e)), !1
	}, i.prototype.onTouchCancel = function () {
		this.trackingClick = !1, this.targetElement = null
	}, i.prototype.onMouse = function (e) {
		return this.targetElement ? e.forwardedTouchEvent ? !0 : e.cancelable ? !this.needsClick(this.targetElement) || this.cancelNextClick ? (e.stopImmediatePropagation ? e.stopImmediatePropagation() : e.propagationStopped = !0, e.stopPropagation(), e.preventDefault(), !1) : !0 : !0 : !0
	}, i.prototype.onClick = function (e) {
		var t;
		return this.trackingClick ? (this.targetElement = null, this.trackingClick = !1, !0) : e.target.type === "submit" && e.detail === 0 ? !0 : (t = this.onMouse(e), t || (this.targetElement = null), t)
	}, i.prototype.destroy = function () {
		var e = this.layer;
		this.deviceIsAndroid && (e.removeEventListener("mouseover", this.onMouse, !0), e.removeEventListener("mousedown", this.onMouse, !0), e.removeEventListener("mouseup", this.onMouse, !0)), e.removeEventListener("click", this.onClick, !0), e.removeEventListener("touchstart", this.onTouchStart, !1), e.removeEventListener("touchmove", this.onTouchMove, !1), e.removeEventListener("touchend", this.onTouchEnd, !1), e.removeEventListener("touchcancel", this.onTouchCancel, !1)
	}, i.notNeeded = function (e) {
		var r;
		if (typeof t.ontouchstart == "undefined")return!0;
		if (/Chrome\/[0-9]+/.test(navigator.userAgent)) {
			if (!i.prototype.deviceIsAndroid)return!0;
			r = n.querySelector("meta[name=viewport]");
			if (r && r.content.indexOf("user-scalable=no") !== -1)return!0
		}
		return e.style.msTouchAction === "none" ? !0 : !1
	}, i.attach = function (e) {
		return new i(e)
	}, typeof define != "undefined" && define.amd ? define(function () {
		return i
	}) : typeof module != "undefined" && module.exports ? (module.exports = i.attach, module.exports.FastClick = i) : t.FastClick = i, typeof i != "undefined" && i.attach(n.body);
	var s = function (t, r) {
		return typeof t == "string" ? r ? e(r.querySelectorAll(t)) : e(n.querySelectorAll(t)) : e(t, r)
	};
	t.matchMedia = t.matchMedia || function (e, t) {
		var n, r = e.documentElement, i = r.firstElementChild || r.firstChild, s = e.createElement("body"), o = e.createElement("div");
		return o.id = "mq-test-1", o.style.cssText = "position:absolute;top:-100em", s.style.background = "none", s.appendChild(o), function (e) {
			return o.innerHTML = '&shy;<style media="' + e + '"> #mq-test-1 { width: 42px; }</style>', r.insertBefore(s, i), n = o.offsetWidth === 42, r.removeChild(s), {matches: n, media: e}
		}
	}(n), function (e) {
		function u() {
			n && (s(u), jQuery.fx.tick())
		}

		var n, r = 0, i = ["webkit", "moz"], s = t.requestAnimationFrame, o = t.cancelAnimationFrame;
		for (; r < i.length && !s; r++)s = t[i[r] + "RequestAnimationFrame"], o = o || t[i[r] + "CancelAnimationFrame"] || t[i[r] + "CancelRequestAnimationFrame"];
		s ? (t.requestAnimationFrame = s, t.cancelAnimationFrame = o, jQuery.fx.timer = function (e) {
			e() && jQuery.timers.push(e) && !n && (n = !0, u())
		}, jQuery.fx.stop = function () {
			n = !1
		}) : (t.requestAnimationFrame = function (e, n) {
			var i = (new Date).getTime(), s = Math.max(0, 16 - (i - r)), o = t.setTimeout(function () {
				e(i + s)
			}, s);
			return r = i + s, o
		}, t.cancelAnimationFrame = function (e) {
			clearTimeout(e)
		})
	}(jQuery), t.Foundation = {name: "Foundation", version: "5.0.0", media_queries: {small: s(".foundation-mq-small").css("font-family").replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g, ""), medium: s(".foundation-mq-medium").css("font-family").replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g, ""), large: s(".foundation-mq-large").css("font-family").replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g, ""), xlarge: s(".foundation-mq-xlarge").css("font-family").replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g, ""), xxlarge: s(".foundation-mq-xxlarge").css("font-family").replace(/^[\/\\'"]+|(;\s?})+|[\/\\'"]+$/g, "")}, stylesheet: e("<style></style>").appendTo("head")[0].sheet, init: function (e, t, n, r, i) {
		var o, u = [e, n, r, i], a = [];
		this.rtl = /rtl/i.test(s("html").attr("dir")), this.scope = e || this.scope;
		if (t && typeof t == "string" && !/reflow/i.test(t))this.libs.hasOwnProperty(t) && a.push(this.init_lib(t, u)); else for (var f in this.libs)a.push(this.init_lib(f, t));
		return e
	}, init_lib: function (e, t) {
		return this.libs.hasOwnProperty(e) ? (this.patch(this.libs[e]), t && t.hasOwnProperty(e) ? this.libs[e].init.apply(this.libs[e], [this.scope, t[e]]) : this.libs[e].init.apply(this.libs[e], t)) : function () {
		}
	}, patch: function (e) {
		e.scope = this.scope, e.data_options = this.lib_methods.data_options, e.bindings = this.lib_methods.bindings, e.S = s, e.rtl = this.rtl
	}, inherit: function (e, t) {
		var n = t.split(" ");
		for (var r = n.length - 1; r >= 0; r--)this.lib_methods.hasOwnProperty(n[r]) && (this.libs[e.name][n[r]] = this.lib_methods[n[r]])
	}, random_str: function (e) {
		var t = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz".split("");
		e || (e = Math.floor(Math.random() * t.length));
		var n = "";
		for (var r = 0; r < e; r++)n += t[Math.floor(Math.random() * t.length)];
		return n
	}, libs: {}, lib_methods: {throttle: function (e, t) {
		var n = null;
		return function () {
			var r = this, i = arguments;
			clearTimeout(n), n = setTimeout(function () {
				e.apply(r, i)
			}, t)
		}
	}, data_options: function (t) {
		function a(e) {
			return!isNaN(e - 0) && e !== null && e !== "" && e !== !1 && e !== !0
		}

		function f(t) {
			return typeof t == "string" ? e.trim(t) : t
		}

		var n = {}, r, i, s, o, u = t.data("options");
		if (typeof u == "object")return u;
		s = (u || ":").split(";"), o = s.length;
		for (r = o - 1; r >= 0; r--)i = s[r].split(":"), /true/i.test(i[1]) && (i[1] = !0), /false/i.test(i[1]) && (i[1] = !1), a(i[1]) && (i[1] = parseInt(i[1], 10)), i.length === 2 && i[0].length > 0 && (n[f(i[0])] = f(i[1]));
		return n
	}, delay: function (e, t) {
		return setTimeout(e, t)
	}, empty: function (e) {
		if (e.length && e.length > 0)return!1;
		if (e.length && e.length === 0)return!0;
		for (var t in e)if (hasOwnProperty.call(e, t))return!1;
		return!0
	}, register_media: function (t, n) {
		Foundation.media_queries[t] === r && (e("head").append('<meta class="' + n + '">'), Foundation.media_queries[t] = o(e("." + n).css("font-family")))
	}, addCustomRule: function (e, t) {
		if (t === r)Foundation.stylesheet.insertRule(e, Foundation.stylesheet.cssRules.length); else {
			var n = Foundation.media_queries[t];
			n !== r && Foundation.stylesheet.insertRule("@media " + Foundation.media_queries[t] + "{ " + e + " }")
		}
	}, loaded: function (e, t) {
		function n() {
			t(e[0])
		}

		function r() {
			this.one("load", n);
			if (/MSIE (\d+\.\d+);/.test(navigator.userAgent)) {
				var e = this.attr("src"), t = e.match(/\?/) ? "&" : "?";
				t += "random=" + (new Date).getTime(), this.attr("src", e + t)
			}
		}

		if (!e.attr("src")) {
			n();
			return
		}
		e[0].complete || e[0].readyState === 4 ? n() : r.call(e)
	}, bindings: function (t, n) {
		var r = this, i = !s(this).data(this.name + "-init");
		if (typeof t == "string")return this[t].call(this);
		s(this.scope).is("[data-" + this.name + "]") ? (s(this.scope).data(this.name + "-init", e.extend({}, this.settings, n || t, this.data_options(s(this.scope)))), i && this.events(this.scope)) : s("[data-" + this.name + "]", this.scope).each(function () {
			var i = !s(this).data(r.name + "-init");
			s(this).data(r.name + "-init", e.extend({}, r.settings, n || t, r.data_options(s(this)))), i && r.events(this)
		})
	}}}, e.fn.foundation = function () {
		var e = Array.prototype.slice.call(arguments, 0);
		return this.each(function () {
			return Foundation.init.apply(Foundation, [this].concat(e)), this
		})
	}
})(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	var i = i || !1;
	Foundation.libs.joyride = {name: "joyride", version: "5.0.0", defaults: {expose: !1, modal: !0, tip_location: "bottom", nub_position: "auto", scroll_speed: 1500, scroll_animation: "linear", timer: 0, start_timer_on_click: !0, start_offset: 0, next_button: !0, tip_animation: "fade", pause_after: [], exposed: [], tip_animation_fade_speed: 300, cookie_monster: !1, cookie_name: "joyride", cookie_domain: !1, cookie_expires: 365, tip_container: "body", tip_location_patterns: {top: ["bottom"], bottom: [], left: ["right", "top", "bottom"], right: ["left", "top", "bottom"]}, post_ride_callback: function () {
	}, post_step_callback: function () {
	}, pre_step_callback: function () {
	}, pre_ride_callback: function () {
	}, post_expose_callback: function () {
	}, template: {link: '<a href="#close" class="joyride-close-tip">&times;</a>', timer: '<div class="joyride-timer-indicator-wrap"><span class="joyride-timer-indicator"></span></div>', tip: '<div class="joyride-tip-guide"><span class="joyride-nub"></span></div>', wrapper: '<div class="joyride-content-wrapper"></div>', button: '<a href="#" class="small button joyride-next-tip"></a>', modal: '<div class="joyride-modal-bg"></div>', expose: '<div class="joyride-expose-wrapper"></div>', expose_cover: '<div class="joyride-expose-cover"></div>'}, expose_add_class: ""}, init: function (e, t, n) {
		Foundation.inherit(this, "throttle delay"), this.settings = this.defaults, this.bindings(t, n)
	}, events: function () {
		var n = this;
		e(this.scope).off(".joyride").on("click.fndtn.joyride", ".joyride-next-tip, .joyride-modal-bg", function (e) {
			e.preventDefault(), this.settings.$li.next().length < 1 ? this.end() : this.settings.timer > 0 ? (clearTimeout(this.settings.automate), this.hide(), this.show(), this.startTimer()) : (this.hide(), this.show())
		}.bind(this)).on("click.fndtn.joyride", ".joyride-close-tip", function (e) {
			e.preventDefault(), this.end()
		}.bind(this)), e(t).off(".joyride").on("resize.fndtn.joyride", n.throttle(function () {
			if (e("[data-joyride]").length > 0 && n.settings.$next_tip) {
				if (n.settings.exposed.length > 0) {
					var t = e(n.settings.exposed);
					t.each(function () {
						var t = e(this);
						n.un_expose(t), n.expose(t)
					})
				}
				n.is_phone() ? n.pos_phone() : n.pos_default(!1, !0)
			}
		}, 100))
	}, start: function () {
		var t = this, n = e("[data-joyride]", this.scope), r = ["timer", "scrollSpeed", "startOffset", "tipAnimationFadeSpeed", "cookieExpires"], i = r.length;
		if (!n.length > 0)return;
		this.settings.init || this.events(), this.settings = n.data("joyride-init"), this.settings.$content_el = n, this.settings.$body = e(this.settings.tip_container), this.settings.body_offset = e(this.settings.tip_container).position(), this.settings.$tip_content = this.settings.$content_el.find("> li"), this.settings.paused = !1, this.settings.attempts = 0, typeof e.cookie != "function" && (this.settings.cookie_monster = !1);
		if (!this.settings.cookie_monster || this.settings.cookie_monster && e.cookie(this.settings.cookie_name) === null)this.settings.$tip_content.each(function (n) {
			var s = e(this);
			this.settings = e.extend({}, t.defaults, t.data_options(s));
			for (var o = i - 1; o >= 0; o--)t.settings[r[o]] = parseInt(t.settings[r[o]], 10);
			t.create({$li: s, index: n})
		}), !this.settings.start_timer_on_click && this.settings.timer > 0 ? (this.show("init"), this.startTimer()) : this.show("init")
	}, resume: function () {
		this.set_li(), this.show()
	}, tip_template: function (t) {
		var n, r;
		return t.tip_class = t.tip_class || "", n = e(this.settings.template.tip).addClass(t.tip_class), r = e.trim(e(t.li).html()) + this.button_text(t.button_text) + this.settings.template.link + this.timer_instance(t.index), n.append(e(this.settings.template.wrapper)), n.first().attr("data-index", t.index), e(".joyride-content-wrapper", n).append(r), n[0]
	}, timer_instance: function (t) {
		var n;
		return t === 0 && this.settings.start_timer_on_click && this.settings.timer > 0 || this.settings.timer === 0 ? n = "" : n = e(this.settings.template.timer)[0].outerHTML, n
	}, button_text: function (t) {
		return this.settings.next_button ? (t = e.trim(t) || "Next", t = e(this.settings.template.button).append(t)[0].outerHTML) : t = "", t
	}, create: function (t) {
		var n = t.$li.attr("data-button") || t.$li.attr("data-text"), r = t.$li.attr("class"), i = e(this.tip_template({tip_class: r, index: t.index, button_text: n, li: t.$li}));
		e(this.settings.tip_container).append(i)
	}, show: function (t) {
		var n = null;
		this.settings.$li === r || e.inArray(this.settings.$li.index(), this.settings.pause_after) === -1 ? (this.settings.paused ? this.settings.paused = !1 : this.set_li(t), this.settings.attempts = 0, this.settings.$li.length && this.settings.$target.length > 0 ? (t && (this.settings.pre_ride_callback(this.settings.$li.index(), this.settings.$next_tip), this.settings.modal && this.show_modal()), this.settings.pre_step_callback(this.settings.$li.index(), this.settings.$next_tip), this.settings.modal && this.settings.expose && this.expose(), this.settings.tip_settings = e.extend({}, this.settings, this.data_options(this.settings.$li)), this.settings.timer = parseInt(this.settings.timer, 10), this.settings.tip_settings.tip_location_pattern = this.settings.tip_location_patterns[this.settings.tip_settings.tip_location], /body/i.test(this.settings.$target.selector) || this.scroll_to(), this.is_phone() ? this.pos_phone(!0) : this.pos_default(!0), n = this.settings.$next_tip.find(".joyride-timer-indicator"), /pop/i.test(this.settings.tip_animation) ? (n.width(0), this.settings.timer > 0 ? (this.settings.$next_tip.show(), this.delay(function () {
			n.animate({width: n.parent().width()}, this.settings.timer, "linear")
		}.bind(this), this.settings.tip_animation_fade_speed)) : this.settings.$next_tip.show()) : /fade/i.test(this.settings.tip_animation) && (n.width(0), this.settings.timer > 0 ? (this.settings.$next_tip.fadeIn(this.settings.tip_animation_fade_speed).show(), this.delay(function () {
			n.animate({width: n.parent().width()}, this.settings.timer, "linear")
		}.bind(this), this.settings.tip_animation_fadeSpeed)) : this.settings.$next_tip.fadeIn(this.settings.tip_animation_fade_speed)), this.settings.$current_tip = this.settings.$next_tip) : this.settings.$li && this.settings.$target.length < 1 ? this.show() : this.end()) : this.settings.paused = !0
	}, is_phone: function () {
		return matchMedia(Foundation.media_queries.small).matches && !matchMedia(Foundation.media_queries.medium).matches
	}, hide: function () {
		this.settings.modal && this.settings.expose && this.un_expose(), this.settings.modal || e(".joyride-modal-bg").hide(), this.settings.$current_tip.css("visibility", "hidden"), setTimeout(e.proxy(function () {
			this.hide(), this.css("visibility", "visible")
		}, this.settings.$current_tip), 0), this.settings.post_step_callback(this.settings.$li.index(), this.settings.$current_tip)
	}, set_li: function (e) {
		e ? (this.settings.$li = this.settings.$tip_content.eq(this.settings.start_offset), this.set_next_tip(), this.settings.$current_tip = this.settings.$next_tip) : (this.settings.$li = this.settings.$li.next(), this.set_next_tip()), this.set_target()
	}, set_next_tip: function () {
		this.settings.$next_tip = e(".joyride-tip-guide").eq(this.settings.$li.index()), this.settings.$next_tip.data("closed", "")
	}, set_target: function () {
		var t = this.settings.$li.attr("data-class"), r = this.settings.$li.attr("data-id"), i = function () {
			return r ? e(n.getElementById(r)) : t ? e("." + t).first() : e("body")
		};
		this.settings.$target = i()
	}, scroll_to: function () {
		var n, r;
		n = e(t).height() / 2, r = Math.ceil(this.settings.$target.offset().top - n + this.settings.$next_tip.outerHeight()), r > 0 && e("html, body").animate({scrollTop: r}, this.settings.scroll_speed, "swing")
	}, paused: function () {
		return e.inArray(this.settings.$li.index() + 1, this.settings.pause_after) === -1
	}, restart: function () {
		this.hide(), this.settings.$li = r, this.show("init")
	}, pos_default: function (n, r) {
		var i = Math.ceil(e(t).height() / 2), s = this.settings.$next_tip.offset(), o = this.settings.$next_tip.find(".joyride-nub"), u = Math.ceil(o.outerWidth() / 2), a = Math.ceil(o.outerHeight() / 2), f = n || !1;
		f && (this.settings.$next_tip.css("visibility", "hidden"), this.settings.$next_tip.show()), typeof r == "undefined" && (r = !1);
		if (!/body/i.test(this.settings.$target.selector)) {
			if (this.bottom()) {
				var l = this.settings.$target.offset().left;
				Foundation.rtl && (l = this.settings.$target.offset().width - this.settings.$next_tip.width() + l), this.settings.$next_tip.css({top: this.settings.$target.offset().top + a + this.settings.$target.outerHeight(), left: l}), this.nub_position(o, this.settings.tip_settings.nub_position, "top")
			} else if (this.top()) {
				var l = this.settings.$target.offset().left;
				Foundation.rtl && (l = this.settings.$target.offset().width - this.settings.$next_tip.width() + l), this.settings.$next_tip.css({top: this.settings.$target.offset().top - this.settings.$next_tip.outerHeight() - a, left: l}), this.nub_position(o, this.settings.tip_settings.nub_position, "bottom")
			} else this.right() ? (this.settings.$next_tip.css({top: this.settings.$target.offset().top, left: this.outerWidth(this.settings.$target) + this.settings.$target.offset().left + u}), this.nub_position(o, this.settings.tip_settings.nub_position, "left")) : this.left() && (this.settings.$next_tip.css({top: this.settings.$target.offset().top, left: this.settings.$target.offset().left - this.outerWidth(this.settings.$next_tip) - u}), this.nub_position(o, this.settings.tip_settings.nub_position, "right"));
			!this.visible(this.corners(this.settings.$next_tip)) && this.settings.attempts < this.settings.tip_settings.tip_location_pattern.length && (o.removeClass("bottom").removeClass("top").removeClass("right").removeClass("left"), this.settings.tip_settings.tip_location = this.settings.tip_settings.tip_location_pattern[this.settings.attempts], this.settings.attempts++, this.pos_default())
		} else this.settings.$li.length && this.pos_modal(o);
		f && (this.settings.$next_tip.hide(), this.settings.$next_tip.css("visibility", "visible"))
	}, pos_phone: function (t) {
		var n = this.settings.$next_tip.outerHeight(), r = this.settings.$next_tip.offset(), i = this.settings.$target.outerHeight(), s = e(".joyride-nub", this.settings.$next_tip), o = Math.ceil(s.outerHeight() / 2), u = t || !1;
		s.removeClass("bottom").removeClass("top").removeClass("right").removeClass("left"), u && (this.settings.$next_tip.css("visibility", "hidden"), this.settings.$next_tip.show()), /body/i.test(this.settings.$target.selector) ? this.settings.$li.length && this.pos_modal(s) : this.top() ? (this.settings.$next_tip.offset({top: this.settings.$target.offset().top - n - o}), s.addClass("bottom")) : (this.settings.$next_tip.offset({top: this.settings.$target.offset().top + i + o}), s.addClass("top")), u && (this.settings.$next_tip.hide(), this.settings.$next_tip.css("visibility", "visible"))
	}, pos_modal: function (e) {
		this.center(), e.hide(), this.show_modal()
	}, show_modal: function () {
		if (!this.settings.$next_tip.data("closed")) {
			var t = e(".joyride-modal-bg");
			t.length < 1 && e("body").append(this.settings.template.modal).show(), /pop/i.test(this.settings.tip_animation) ? t.show() : t.fadeIn(this.settings.tip_animation_fade_speed)
		}
	}, expose: function () {
		var n, r, i, s, o, u = "expose-" + Math.floor(Math.random() * 1e4);
		if (arguments.length > 0 && arguments[0]instanceof e)i = arguments[0]; else {
			if (!this.settings.$target || !!/body/i.test(this.settings.$target.selector))return!1;
			i = this.settings.$target
		}
		if (i.length < 1)return t.console && console.error("element not valid", i), !1;
		n = e(this.settings.template.expose), this.settings.$body.append(n), n.css({top: i.offset().top, left: i.offset().left, width: i.outerWidth(!0), height: i.outerHeight(!0)}), r = e(this.settings.template.expose_cover), s = {zIndex: i.css("z-index"), position: i.css("position")}, o = i.attr("class") == null ? "" : i.attr("class"), i.css("z-index", parseInt(n.css("z-index")) + 1), s.position == "static" && i.css("position", "relative"), i.data("expose-css", s), i.data("orig-class", o), i.attr("class", o + " " + this.settings.expose_add_class), r.css({top: i.offset().top, left: i.offset().left, width: i.outerWidth(!0), height: i.outerHeight(!0)}), this.settings.modal && this.show_modal(), this.settings.$body.append(r), n.addClass(u), r.addClass(u), i.data("expose", u), this.settings.post_expose_callback(this.settings.$li.index(), this.settings.$next_tip, i), this.add_exposed(i)
	}, un_expose: function () {
		var n, r, i, s, o, u = !1;
		if (arguments.length > 0 && arguments[0]instanceof e)r = arguments[0]; else {
			if (!this.settings.$target || !!/body/i.test(this.settings.$target.selector))return!1;
			r = this.settings.$target
		}
		if (r.length < 1)return t.console && console.error("element not valid", r), !1;
		n = r.data("expose"), i = e("." + n), arguments.length > 1 && (u = arguments[1]), u === !0 ? e(".joyride-expose-wrapper,.joyride-expose-cover").remove() : i.remove(), s = r.data("expose-css"), s.zIndex == "auto" ? r.css("z-index", "") : r.css("z-index", s.zIndex), s.position != r.css("position") && (s.position == "static" ? r.css("position", "") : r.css("position", s.position)), o = r.data("orig-class"), r.attr("class", o), r.removeData("orig-classes"), r.removeData("expose"), r.removeData("expose-z-index"), this.remove_exposed(r)
	}, add_exposed: function (t) {
		this.settings.exposed = this.settings.exposed || [], t instanceof e || typeof t == "object" ? this.settings.exposed.push(t[0]) : typeof t == "string" && this.settings.exposed.push(t)
	}, remove_exposed: function (t) {
		var n, r;
		t instanceof e ? n = t[0] : typeof t == "string" && (n = t), this.settings.exposed = this.settings.exposed || [], r = this.settings.exposed.length;
		for (var i = 0; i < r; i++)if (this.settings.exposed[i] == n) {
			this.settings.exposed.splice(i, 1);
			return
		}
	}, center: function () {
		var n = e(t);
		return this.settings.$next_tip.css({top: (n.height() - this.settings.$next_tip.outerHeight()) / 2 + n.scrollTop(), left: (n.width() - this.settings.$next_tip.outerWidth()) / 2 + n.scrollLeft()}), !0
	}, bottom: function () {
		return/bottom/i.test(this.settings.tip_settings.tip_location)
	}, top: function () {
		return/top/i.test(this.settings.tip_settings.tip_location)
	}, right: function () {
		return/right/i.test(this.settings.tip_settings.tip_location)
	}, left: function () {
		return/left/i.test(this.settings.tip_settings.tip_location)
	}, corners: function (n) {
		var r = e(t), i = r.height() / 2, s = Math.ceil(this.settings.$target.offset().top - i + this.settings.$next_tip.outerHeight()), o = r.width() + r.scrollLeft(), u = r.height() + s, a = r.height() + r.scrollTop(), f = r.scrollTop();
		return s < f && (s < 0 ? f = 0 : f = s), u > a && (a = u), [n.offset().top < f, o < n.offset().left + n.outerWidth(), a < n.offset().top + n.outerHeight(), r.scrollLeft() > n.offset().left]
	}, visible: function (e) {
		var t = e.length;
		while (t--)if (e[t])return!1;
		return!0
	}, nub_position: function (e, t, n) {
		t === "auto" ? e.addClass(n) : e.addClass(t)
	}, startTimer: function () {
		this.settings.$li.length ? this.settings.automate = setTimeout(function () {
			this.hide(), this.show(), this.startTimer()
		}.bind(this), this.settings.timer) : clearTimeout(this.settings.automate)
	}, end: function () {
		this.settings.cookie_monster && e.cookie(this.settings.cookie_name, "ridden", {expires: this.settings.cookie_expires, domain: this.settings.cookie_domain}), this.settings.timer > 0 && clearTimeout(this.settings.automate), this.settings.modal && this.settings.expose && this.un_expose(), this.settings.$next_tip.data("closed", !0), e(".joyride-modal-bg").hide(), this.settings.$current_tip.hide(), this.settings.post_step_callback(this.settings.$li.index(), this.settings.$current_tip), this.settings.post_ride_callback(this.settings.$li.index(), this.settings.$current_tip), e(".joyride-tip-guide").remove()
	}, off: function () {
		e(this.scope).off(".joyride"), e(t).off(".joyride"), e(".joyride-close-tip, .joyride-next-tip, .joyride-modal-bg").off(".joyride"), e(".joyride-tip-guide, .joyride-modal-bg").remove(), clearTimeout(this.settings.automate), this.settings = {}
	}, reflow: function () {
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.dropdown = {name: "dropdown", version: "5.0.0", settings: {active_class: "open", is_hover: !1, opened: function () {
	}, closed: function () {
	}}, init: function (e, t, n) {
		Foundation.inherit(this, "throttle"), this.bindings(t, n)
	}, events: function (n) {
		var r = this;
		e(this.scope).off(".dropdown").on("click.fndtn.dropdown", "[data-dropdown]",function (t) {
			var n = e(this).data("dropdown-init");
			t.preventDefault(), (!n.is_hover || Modernizr.touch) && r.toggle(e(this))
		}).on("mouseenter.fndtn.dropdown", "[data-dropdown], [data-dropdown-content]",function (t) {
			var n = e(this);
			clearTimeout(r.timeout);
			if (n.data("dropdown"))var i = e("#" + n.data("dropdown")), s = n; else {
				var i = n;
				s = e("[data-dropdown='" + i.attr("id") + "']")
			}
			var o = s.data("dropdown-init");
			o.is_hover && r.open.apply(r, [i, s])
		}).on("mouseleave.fndtn.dropdown", "[data-dropdown], [data-dropdown-content]",function (t) {
			var n = e(this);
			r.timeout = setTimeout(function () {
				if (n.data("dropdown")) {
					var t = n.data("dropdown-init");
					t.is_hover && r.close.call(r, e("#" + n.data("dropdown")))
				} else {
					var i = e('[data-dropdown="' + e(this).attr("id") + '"]'), t = i.data("dropdown-init");
					t.is_hover && r.close.call(r, n)
				}
			}.bind(this), 150)
		}).on("click.fndtn.dropdown",function (t) {
			var n = e(t.target).closest("[data-dropdown-content]");
			if (e(t.target).data("dropdown") || e(t.target).parent().data("dropdown"))return;
			if (!e(t.target).data("revealId") && n.length > 0 && (e(t.target).is("[data-dropdown-content]") || e.contains(n.first()[0], t.target))) {
				t.stopPropagation();
				return
			}
			r.close.call(r, e("[data-dropdown-content]"))
		}).on("opened.fndtn.dropdown", "[data-dropdown-content]", this.settings.opened).on("closed.fndtn.dropdown", "[data-dropdown-content]", this.settings.closed), e(t).off(".dropdown").on("resize.fndtn.dropdown", r.throttle(function () {
			r.resize.call(r)
		}, 50)).trigger("resize")
	}, close: function (t) {
		var n = this;
		t.each(function () {
			e(this).hasClass(n.settings.active_class) && (e(this).css(Foundation.rtl ? "right" : "left", "-99999px").removeClass(n.settings.active_class), e(this).trigger("closed"))
		})
	}, open: function (e, t) {
		this.css(e.addClass(this.settings.active_class), t), e.trigger("opened")
	}, toggle: function (t) {
		var n = e("#" + t.data("dropdown"));
		if (n.length === 0)return;
		this.close.call(this, e("[data-dropdown-content]").not(n)), n.hasClass(this.settings.active_class) ? this.close.call(this, n) : (this.close.call(this, e("[data-dropdown-content]")), this.open.call(this, n, t))
	}, resize: function () {
		var t = e("[data-dropdown-content].open"), n = e("[data-dropdown='" + t.attr("id") + "']");
		t.length && n.length && this.css(t, n)
	}, css: function (n, r) {
		var i = n.offsetParent(), s = r.offset();
		s.top -= i.offset().top, s.left -= i.offset().left;
		if (this.small())n.css({position: "absolute", width: "95%", "max-width": "none", top: s.top + r.outerHeight()}), n.css(Foundation.rtl ? "right" : "left", "2.5%"); else {
			if (!Foundation.rtl && e(t).width() > n.outerWidth() + r.offset().left) {
				var o = s.left;
				n.hasClass("right") && n.removeClass("right")
			} else {
				n.hasClass("right") || n.addClass("right");
				var o = s.left - (n.outerWidth() - r.outerWidth())
			}
			n.attr("style", "").css({position: "absolute", top: s.top + r.outerHeight(), left: o})
		}
		return n
	}, small: function () {
		return matchMedia(Foundation.media_queries.small).matches && !matchMedia(Foundation.media_queries.medium).matches
	}, off: function () {
		e(this.scope).off(".fndtn.dropdown"), e("html, body").off(".fndtn.dropdown"), e(t).off(".fndtn.dropdown"), e("[data-dropdown-content]").off(".fndtn.dropdown"), this.settings.init = !1
	}, reflow: function () {
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.clearing = {name: "clearing", version: "5.0.0", settings: {templates: {viewing: '<a href="#" class="clearing-close">&times;</a><div class="visible-img" style="display: none"><img src="//:0"><p class="clearing-caption"></p><a href="#" class="clearing-main-prev"><span></span></a><a href="#" class="clearing-main-next"><span></span></a></div>'}, close_selectors: ".clearing-close", init: !1, locked: !1}, init: function (t, n, r) {
		var i = this;
		Foundation.inherit(this, "throttle loaded"), this.bindings(n, r), e(this.scope).is("[data-clearing]") ? this.assemble(e("li", this.scope)) : e("[data-clearing]", this.scope).each(function () {
			i.assemble(e("li", this))
		})
	}, events: function (n) {
		var r = this;
		e(this.scope).off(".clearing").on("click.fndtn.clearing", "ul[data-clearing] li",function (t, n, i) {
			var n = n || e(this), i = i || n, s = n.next("li"), o = n.closest("[data-clearing]").data("clearing-init"), u = e(t.target);
			t.preventDefault(), o || (r.init(), o = n.closest("[data-clearing]").data("clearing-init")), i.hasClass("visible") && n[0] === i[0] && s.length > 0 && r.is_open(n) && (i = s, u = e("img", i)), r.open(u, n, i), r.update_paddles(i)
		}).on("click.fndtn.clearing", ".clearing-main-next",function (e) {
			r.nav(e, "next")
		}).on("click.fndtn.clearing", ".clearing-main-prev",function (e) {
			r.nav(e, "prev")
		}).on("click.fndtn.clearing", this
			.settings.close_selectors,function (e) {
			Foundation.libs.clearing.close(e, this)
		}).on("keydown.fndtn.clearing", function (e) {
				r.keydown(e)
			}), e(t).off(".clearing").on("resize.fndtn.clearing", function () {
			r.resize()
		}), this.swipe_events(n)
	}, swipe_events: function (t) {
		var n = this;
		e(this.scope).on("touchstart.fndtn.clearing", ".visible-img",function (t) {
			t.touches || (t = t.originalEvent);
			var n = {start_page_x: t.touches[0].pageX, start_page_y: t.touches[0].pageY, start_time: (new Date).getTime(), delta_x: 0, is_scrolling: r};
			e(this).data("swipe-transition", n), t.stopPropagation()
		}).on("touchmove.fndtn.clearing", ".visible-img",function (t) {
			t.touches || (t = t.originalEvent);
			if (t.touches.length > 1 || t.scale && t.scale !== 1)return;
			var r = e(this).data("swipe-transition");
			typeof r == "undefined" && (r = {}), r.delta_x = t.touches[0].pageX - r.start_page_x, typeof r.is_scrolling == "undefined" && (r.is_scrolling = !!(r.is_scrolling || Math.abs(r.delta_x) < Math.abs(t.touches[0].pageY - r.start_page_y)));
			if (!r.is_scrolling && !r.active) {
				t.preventDefault();
				var i = r.delta_x < 0 ? "next" : "prev";
				r.active = !0, n.nav(t, i)
			}
		}).on("touchend.fndtn.clearing", ".visible-img", function (t) {
			e(this).data("swipe-transition", {}), t.stopPropagation()
		})
	}, assemble: function (t) {
		var n = t.parent();
		if (n.parent().hasClass("carousel"))return;
		n.after('<div id="foundationClearingHolder"></div>');
		var r = e("#foundationClearingHolder"), i = n.data("clearing-init"), s = n.detach(), o = {grid: '<div class="carousel">' + s[0].outerHTML + "</div>", viewing: i.templates.viewing}, u = '<div class="clearing-assembled"><div>' + o.viewing + o.grid + "</div></div>";
		return r.after(u).remove()
	}, open: function (t, n, r) {
		var i = r.closest(".clearing-assembled"), s = e("div", i).first(), o = e(".visible-img", s), u = e("img", o).not(t);
		this.locked() || (u.attr("src", this.load(t)).css("visibility", "hidden"), this.loaded(u, function () {
			u.css("visibility", "visible"), i.addClass("clearing-blackout"), s.addClass("clearing-container"), o.show(), this.fix_height(r).caption(e(".clearing-caption", o), t).center(u).shift(n, r, function () {
				r.siblings().removeClass("visible"), r.addClass("visible")
			})
		}.bind(this)))
	}, close: function (t, n) {
		t.preventDefault();
		var r = function (e) {
			return/blackout/.test(e.selector) ? e : e.closest(".clearing-blackout")
		}(e(n)), i, s;
		return n === t.target && r && (i = e("div", r).first(), s = e(".visible-img", i), this.settings.prev_index = 0, e("ul[data-clearing]", r).attr("style", "").closest(".clearing-blackout").removeClass("clearing-blackout"), i.removeClass("clearing-container"), s.hide()), !1
	}, is_open: function (e) {
		return e.parent().prop("style").length > 0
	}, keydown: function (t) {
		var n = e("ul[data-clearing]", ".clearing-blackout");
		t.which === 39 && this.go(n, "next"), t.which === 37 && this.go(n, "prev"), t.which === 27 && e("a.clearing-close").trigger("click")
	}, nav: function (t, n) {
		var r = e("ul[data-clearing]", ".clearing-blackout");
		t.preventDefault(), this.go(r, n)
	}, resize: function () {
		var t = e("img", ".clearing-blackout .visible-img");
		t.length && this.center(t)
	}, fix_height: function (t) {
		var n = t.parent().children(), r = this;
		return n.each(function () {
			var t = e(this), n = t.find("img");
			t.height() > n.outerHeight() && t.addClass("fix-height")
		}).closest("ul").width(n.length * 100 + "%"), this
	}, update_paddles: function (t) {
		var n = t.closest(".carousel").siblings(".visible-img");
		t.next().length > 0 ? e(".clearing-main-next", n).removeClass("disabled") : e(".clearing-main-next", n).addClass("disabled"), t.prev().length > 0 ? e(".clearing-main-prev", n).removeClass("disabled") : e(".clearing-main-prev", n).addClass("disabled")
	}, center: function (e) {
		return this.rtl ? e.css({marginRight: -(e.outerWidth() / 2), marginTop: -(e.outerHeight() / 2)}) : e.css({marginLeft: -(e.outerWidth() / 2), marginTop: -(e.outerHeight() / 2)}), this
	}, load: function (e) {
		if (e[0].nodeName === "A")var t = e.attr("href"); else var t = e.parent().attr("href");
		return this.preload(e), t ? t : e.attr("src")
	}, preload: function (e) {
		this.img(e.closest("li").next()).img(e.closest("li").prev())
	}, img: function (t) {
		if (t.length) {
			var n = new Image, r = e("a", t);
			r.length ? n.src = r.attr("href") : n.src = e("img", t).attr("src")
		}
		return this
	}, caption: function (e, t) {
		var n = t.data("caption");
		return n ? e.html(n).show() : e.text("").hide(), this
	}, go: function (t, n) {
		var r = e(".visible", t), i = r[n]();
		i.length && e("img", i).trigger("click", [r, i])
	}, shift: function (e, t, n) {
		var r = t.parent(), i = this.settings.prev_index || t.index(), s = this.direction(r, e, t), o = parseInt(r.css("left"), 10), u = t.outerWidth(), a;
		t.index() !== i && !/skip/.test(s) ? /left/.test(s) ? (this.lock(), r.animate({left: o + u}, 300, this.unlock())) : /right/.test(s) && (this.lock(), r.animate({left: o - u}, 300, this.unlock())) : /skip/.test(s) && (a = t.index() - this.settings.up_count, this.lock(), a > 0 ? r.animate({left: -(a * u)}, 300, this.unlock()) : r.animate({left: 0}, 300, this.unlock())), n()
	}, direction: function (t, n, r) {
		var i = e("li", t), s = i.outerWidth() + i.outerWidth() / 4, o = Math.floor(e(".clearing-container").outerWidth() / s) - 1, u = i.index(r), a;
		return this.settings.up_count = o, this.adjacent(this.settings.prev_index, u) ? u > o && u > this.settings.prev_index ? a = "right" : u > o - 1 && u <= this.settings.prev_index ? a = "left" : a = !1 : a = "skip", this.settings.prev_index = u, a
	}, adjacent: function (e, t) {
		for (var n = t + 1; n >= t - 1; n--)if (n === e)return!0;
		return!1
	}, lock: function () {
		this.settings.locked = !0
	}, unlock: function () {
		this.settings.locked = !1
	}, locked: function () {
		return this.settings.locked
	}, off: function () {
		e(this.scope).off(".fndtn.clearing"), e(t).off(".fndtn.clearing")
	}, reflow: function () {
		this.init()
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	var i = function () {
	}, s = function (i, s) {
		if (i.hasClass(s.slides_container_class))return this;
		var f = this, l, c = i, h, p, d, v = 0, m, g, y = !1, b = !1;
		c.children().first().addClass(s.active_slide_class), f.update_slide_number = function (t) {
			s.slide_number && (h.find("span:first").text(parseInt(t) + 1), h.find("span:last").text(c.children().length)), s.bullets && (p.children().removeClass(s.bullets_active_class), e(p.children().get(t)).addClass(s.bullets_active_class))
		}, f.update_active_link = function (t) {
			var n = e('a[data-orbit-link="' + c.children().eq(t).attr("data-orbit-slide") + '"]');
			n.parents("ul").find("[data-orbit-link]").removeClass(s.bullets_active_class), n.addClass(s.bullets_active_class)
		}, f.build_markup = function () {
			c.wrap('<div class="' + s.container_class + '"></div>'), l = c.parent(), c.addClass(s.slides_container_class), s.navigation_arrows && (l.append(e('<a href="#"><span></span></a>').addClass(s.prev_class)), l.append(e('<a href="#"><span></span></a>').addClass(s.next_class))), s.timer && (d = e("<div>").addClass(s.timer_container_class), d.append("<span>"), d.append(e("<div>").addClass(s.timer_progress_class)), d.addClass(s.timer_paused_class), l.append(d)), s.slide_number && (h = e("<div>").addClass(s.slide_number_class), h.append("<span></span> " + s.slide_number_text + " <span></span>"), l.append(h)), s.bullets && (p = e("<ol>").addClass(s.bullets_container_class), l.append(p), p.wrap('<div class="orbit-bullets-container"></div>'), c.children().each(function (t, n) {
				var r = e("<li>").attr("data-orbit-slide", t);
				p.append(r)
			})), s.stack_on_small && l.addClass(s.stack_on_small_class), f.update_slide_number(0), f.update_active_link(0)
		}, f._goto = function (t, n) {
			if (t === v)return!1;
			typeof g == "object" && g.restart();
			var r = c.children(), i = "next";
			y = !0, t < v && (i = "prev"), t >= r.length ? t = 0 : t < 0 && (t = r.length - 1);
			var o = e(r.get(v)), u = e(r.get(t));
			o.css("zIndex", 2), o.removeClass(s.active_slide_class), u.css("zIndex", 4).addClass(s.active_slide_class), c.trigger("before-slide-change.fndtn.orbit"), s.before_slide_change(), f.update_active_link(t);
			var a = function () {
				var e = function () {
					v = t, y = !1, n === !0 && (g = f.create_timer(), g.start()), f.update_slide_number(v), c.trigger("after-slide-change.fndtn.orbit", [
						{slide_number: v, total_slides: r.length}
					]), s.after_slide_change(v, r.length)
				};
				c.height() != u.height() && s.variable_height ? c.animate({height: u.height()}, 250, "linear", e) : e()
			};
			if (r.length === 1)return a(), !1;
			var l = function () {
				i === "next" && m.next(o, u, a), i === "prev" && m.prev(o, u, a)
			};
			u.height() > c.height() && s.variable_height ? c.animate({height: u.height()}, 250, "linear", l) : l()
		}, f.next = function (e) {
			e.stopImmediatePropagation(), e.preventDefault(), f._goto(v + 1)
		}, f.prev = function (e) {
			e.stopImmediatePropagation(), e.preventDefault(), f._goto(v - 1)
		}, f.link_custom = function (t) {
			t.preventDefault();
			var n = e(this).attr("data-orbit-link");
			if (typeof n == "string" && (n = e.trim(n)) != "") {
				var r = l.find("[data-orbit-slide=" + n + "]");
				r.index() != -1 && f._goto(r.index())
			}
		}, f.link_bullet = function (t) {
			var n = e(this).attr("data-orbit-slide");
			typeof n == "string" && (n = e.trim(n)) != "" && f._goto(parseInt(n))
		}, f.timer_callback = function () {
			f._goto(v + 1, !0)
		}, f.compute_dimensions = function () {
			var t = e(c.children().get(v)), n = t.height();
			s.variable_height || c.children().each(function () {
				e(this).height() > n && (n = e(this).height())
			}), c.height(n)
		}, f.create_timer = function () {
			var e = new o(l.find("." + s.timer_container_class), s, f.timer_callback);
			return e
		}, f.stop_timer = function () {
			typeof g == "object" && g.stop()
		}, f.toggle_timer = function () {
			var e = l.find("." + s.timer_container_class);
			e.hasClass(s.timer_paused_class) ? (typeof g == "undefined" && (g = f.create_timer()), g.start()) : typeof g == "object" && g.stop()
		}, f.init = function () {
			f.build_markup(), s.timer && (g = f.create_timer(), g.start()), m = new a(s, c), s.animation === "slide" && (m = new u(s, c)), l.on("click", "." + s.next_class, f.next), l.on("click", "." + s.prev_class, f.prev), l.on("click", "[data-orbit-slide]", f.link_bullet), l.on("click", f.toggle_timer), s.swipe && l.on("touchstart.fndtn.orbit",function (e) {
				e.touches || (e = e.originalEvent);
				var t = {start_page_x: e.touches[0].pageX, start_page_y: e.touches[0].pageY, start_time: (new Date).getTime(), delta_x: 0, is_scrolling: r};
				l.data("swipe-transition", t), e.stopPropagation()
			}).on("touchmove.fndtn.orbit",function (e) {
				e.touches || (e = e.originalEvent);
				if (e.touches.length > 1 || e.scale && e.scale !== 1)return;
				var t = l.data("swipe-transition");
				typeof t == "undefined" && (t = {}), t.delta_x = e.touches[0].pageX - t.start_page_x, typeof t.is_scrolling == "undefined" && (t.is_scrolling = !!(t.is_scrolling || Math.abs(t.delta_x) < Math.abs(e.touches[0].pageY - t.start_page_y)));
				if (!t.is_scrolling && !t.active) {
					e.preventDefault();
					var n = t.delta_x < 0 ? v + 1 : v - 1;
					t.active = !0, f._goto(n)
				}
			}).on("touchend.fndtn.orbit", function (e) {
				l.data("swipe-transition", {}), e.stopPropagation()
			}), l.on("mouseenter.fndtn.orbit",function (e) {
				s.timer && s.pause_on_hover && f.stop_timer()
			}).on("mouseleave.fndtn.orbit", function (e) {
				s.timer && s.resume_on_mouseout && g.start()
			}), e(n).on("click", "[data-orbit-link]", f.link_custom), e(t).on("resize", f.compute_dimensions), e(t).on("load", f.compute_dimensions), e(t).on("load", function () {
				l.prev(".preloader").css("display", "none")
			}), c.trigger("ready.fndtn.orbit")
		}, f.init()
	}, o = function (e, t, n) {
		var r = this, i = t.timer_speed, s = e.find("." + t.timer_progress_class), o, u, a = -1;
		this.update_progress = function (e) {
			var t = s.clone();
			t.attr("style", ""), t.css("width", e + "%"), s.replaceWith(t), s = t
		}, this.restart = function () {
			clearTimeout(u), e.addClass(t.timer_paused_class), a = -1, r.update_progress(0)
		}, this.start = function () {
			if (!e.hasClass(t.timer_paused_class))return!0;
			a = a === -1 ? i : a, e.removeClass(t.timer_paused_class), o = (new Date).getTime(), s.animate({width: "100%"}, a, "linear"), u = setTimeout(function () {
				r.restart(), n()
			}, a), e.trigger("timer-started.fndtn.orbit")
		}, this.stop = function () {
			if (e.hasClass(t.timer_paused_class))return!0;
			clearTimeout(u), e.addClass(t.timer_paused_class);
			var n = (new Date).getTime();
			a -= n - o;
			var s = 100 - a / i * 100;
			r.update_progress(s), e.trigger("timer-stopped.fndtn.orbit")
		}
	}, u = function (t, n) {
		var r = t.animation_speed, i = e("html[dir=rtl]").length === 1, s = i ? "marginRight" : "marginLeft", o = {};
		o[s] = "0%", this.next = function (e, t, n) {
			e.animate({marginLeft: "-100%"}, r), t.animate(o, r, function () {
				e.css(s, "100%"), n()
			})
		}, this.prev = function (e, t, n) {
			e.animate({marginLeft: "100%"}, r), t.css(s, "-100%"), t.animate(o, r, function () {
				e.css(s, "100%"), n()
			})
		}
	}, a = function (t, n) {
		var r = t.animation_speed, i = e("html[dir=rtl]").length === 1, s = i ? "marginRight" : "marginLeft";
		this.next = function (e, t, n) {
			t.css({margin: "0%", opacity: "0.01"}), t.animate({opacity: "1"}, r, "linear", function () {
				e.css("margin", "100%"), n()
			})
		}, this.prev = function (e, t, n) {
			t.css({margin: "0%", opacity: "0.01"}), t.animate({opacity: "1"}, r, "linear", function () {
				e.css("margin", "100%"), n()
			})
		}
	};
	Foundation.libs = Foundation.libs || {}, Foundation.libs.orbit = {name: "orbit", version: "5.0.0", settings: {animation: "slide", timer_speed: 1e4, pause_on_hover: !0, resume_on_mouseout: !1, animation_speed: 500, stack_on_small: !1, navigation_arrows: !0, slide_number: !0, slide_number_text: "of", container_class: "orbit-container", stack_on_small_class: "orbit-stack-on-small", next_class: "orbit-next", prev_class: "orbit-prev", timer_container_class: "orbit-timer", timer_paused_class: "paused", timer_progress_class: "orbit-progress", slides_container_class: "orbit-slides-container", bullets_container_class: "orbit-bullets", bullets_active_class: "active", slide_number_class: "orbit-slide-number", caption_class: "orbit-caption", active_slide_class: "active", orbit_transition_class: "orbit-transitioning", bullets: !0, timer: !0, variable_height: !1, swipe: !0, before_slide_change: i, after_slide_change: i}, init: function (t, n, r) {
		var i = this;
		typeof n == "object" && e.extend(!0, i.settings, n);
		if (e(t).is("[data-orbit]")) {
			var o = e(t), u = i.data_options(o);
			new s(o, e.extend({}, i.settings, u))
		}
		e("[data-orbit]", t).each(function (t, n) {
			var r = e(n), o = i.data_options(r);
			new s(r, e.extend({}, i.settings, o))
		})
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.offcanvas = {name: "offcanvas", version: "5.0.0", settings: {}, init: function (e, t, n) {
		this.events()
	}, events: function () {
		e(this.scope).off(".offcanvas").on("click.fndtn.offcanvas", ".left-off-canvas-toggle",function (t) {
			t.preventDefault(), e(this).closest(".off-canvas-wrap").toggleClass("move-right")
		}).on("click.fndtn.offcanvas", ".exit-off-canvas",function (t) {
			t.preventDefault(), e(".off-canvas-wrap").removeClass("move-right")
		}).on("click.fndtn.offcanvas", ".right-off-canvas-toggle",function (t) {
			t.preventDefault(), e(this).closest(".off-canvas-wrap").toggleClass("move-left")
		}).on("click.fndtn.offcanvas", ".exit-off-canvas", function (t) {
			t.preventDefault(), e(".off-canvas-wrap").removeClass("move-left")
		})
	}, reflow: function () {
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.alert = {name: "alert", version: "5.0.0", settings: {animation: "fadeOut", speed: 300, callback: function () {
	}}, init: function (e, t, n) {
		this.bindings(t, n)
	}, events: function () {
		e(this.scope).off(".alert").on("click.fndtn.alert", "[data-alert] a.close", function (t) {
			var n = e(this).closest("[data-alert]"), r = n.data("alert-init");
			t.preventDefault(), n[r.animation](r.speed, function () {
				e(this).trigger("closed").remove(), r.callback()
			})
		})
	}, reflow: function () {
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.reveal = {name: "reveal", version: "5.0.0", locked: !1, settings: {animation: "fadeAndPop", animation_speed: 250, close_on_background_click: !0, close_on_esc: !0, dismiss_modal_class: "close-reveal-modal", bg_class: "reveal-modal-bg", open: function () {
	}, opened: function () {
	}, close: function () {
	}, closed: function () {
	}, bg: e(".reveal-modal-bg"), css: {open: {opacity: 0, visibility: "visible", display: "block"}, close: {opacity: 1, visibility: "hidden", display: "none"}}}, init: function (e, t, n) {
		Foundation.inherit(this, "delay"), this.bindings(t, n)
	}, events: function (t) {
		var n = this;
		return e("[data-reveal-id]", this.scope).off(".reveal").on("click.fndtn.reveal", function (t) {
			t.preventDefault();
			if (!n.locked) {
				var r = e(this), i = r.data("reveal-ajax");
				n.locked = !0;
				if (typeof i == "undefined")n.open.call(n, r); else {
					var s = i === !0 ? r.attr("href") : i;
					n.open.call(n, r, {url: s})
				}
			}
		}), e(this.scope).off(".reveal").on("click.fndtn.reveal", this.close_targets(), function (t) {
			t.preventDefault();
			if (!n.locked) {
				var r = e("[data-reveal].open").data("reveal-init"), i = e(t.target)[0] === e("." + r.bg_class)[0];
				if (i && !r.close_on_background_click)return;
				n.locked = !0, n.close.call(n, i ? e("[data-reveal].open") : e(this).closest("[data-reveal]"))
			}
		}), e("[data-reveal]", this.scope).length > 0 ? e(this.scope).on("open.fndtn.reveal", this.settings.open).on("opened.fndtn.reveal", this.settings.opened).on("opened.fndtn.reveal", this.open_video).on("close.fndtn.reveal", this.settings.close).on("closed.fndtn.reveal", this.settings.closed).on("closed.fndtn.reveal", this.close_video) : e(this.scope).on("open.fndtn.reveal", "[data-reveal]", this.settings.open).on("opened.fndtn.reveal", "[data-reveal]", this.settings.opened).on("opened.fndtn.reveal", "[data-reveal]", this.open_video).on("close.fndtn.reveal", "[data-reveal]", this.settings.close).on("closed.fndtn.reveal", "[data-reveal]", this.settings.closed).on("closed.fndtn.reveal", "[data-reveal]", this.close_video), e("body").on("keyup.fndtn.reveal", function (t) {
			var n = e("[data-reveal].open"), r = n.data("reveal-init");
			t.which === 27 && r.close_on_esc && n.foundation("reveal", "close")
		}), !0
	}, open: function (t, n) {
		if (t)if (typeof t.selector != "undefined")var r = e("#" + t.data("reveal-id")); else {
			var r = e(this.scope);
			n = t
		} else var r = e(this.scope);
		if (!r.hasClass("open")) {
			var i = e("[data-reveal].open");
			typeof r.data("css-top") == "undefined" && r.data("css-top", parseInt(r.css("top"), 10)).data("offset", this.cache_offset(r)), r.trigger("open"), i.length < 1 && this.toggle_bg();
			if (typeof n == "undefined" || !n.url)this.hide(i, this.settings.css.close), this.show(r, this.settings.css.open); else {
				var s = this, o = typeof n.success != "undefined" ? n.success : null;
				e.extend(n, {success: function (t, n, u) {
					e.isFunction(o) && o(t, n, u), r.html(t), e(r).foundation("section", "reflow"), s.hide(i, s.settings.css.close), s.show(r, s.settings.css.open)
				}}), e.ajax(n)
			}
		}
	}, close: function (t) {
		var t = t && t.length ? t : e(this.scope), n = e("[data-reveal].open");
		n.length > 0 && (this.locked = !0, t.trigger("close"), this.toggle_bg(), this.hide(n, this.settings.css.close))
	}, close_targets: function () {
		var e = "." + this.settings.dismiss_modal_class;
		return this.settings.close_on_background_click ? e + ", ." + this.settings.bg_class : e
	}, toggle_bg: function () {
		e("." + this.settings.bg_class).length === 0 && (this.settings.bg = e("<div />", {"class": this.settings.bg_class}).appendTo("body")), this.settings.bg.filter(":visible").length > 0 ? this.hide(this.settings.bg) : this.show(this.settings.bg)
	}, show: function (n, r) {
		if (r) {
			if (n.parent("body").length === 0) {
				var i = n.wrap('<div style="display: none;" />').parent();
				n.on("closed.fndtn.reveal.wrapped", function () {
					n.detach().appendTo(i), n.unwrap().unbind("closed.fndtn.reveal.wrapped")
				}), n.detach().appendTo("body")
			}
			if (/pop/i.test(this.settings.animation)) {
				r.top = e(t).scrollTop() - n.data("offset") + "px";
				var s = {top: e(t).scrollTop() + n.data("css-top") + "px", opacity: 1};
				return this.delay(function () {
					return n.css(r).animate(s, this.settings.animation_speed, "linear", function () {
						this.locked = !1, n.trigger("opened")
					}.bind(this)).addClass("open")
				}.bind(this), this.settings.animation_speed / 2)
			}
			if (/fade/i.test(this.settings.animation)) {
				var s = {opacity: 1};
				return this.delay(function () {
					return n.css(r).animate(s, this.settings.animation_speed, "linear", function () {
						this.locked = !1, n.trigger("opened")
					}.bind(this)).addClass("open")
				}.bind(this), this.settings.animation_speed / 2)
			}
			return n.css(r).show().css({opacity: 1}).addClass("open").trigger("opened")
		}
		return/fade/i.test(this.settings.animation) ? n.fadeIn(this.settings.animation_speed / 2) : n.show()
	}, hide: function (n, r) {
		if (r) {
			if (/pop/i.test(this.settings.animation)) {
				var i = {top: -e(t).scrollTop() - n.data("offset") + "px", opacity: 0};
				return this.delay(function () {
					return n.animate(i, this.settings.animation_speed, "linear", function () {
						this.locked = !1, n.css(r).trigger("closed")
					}.bind(this)).removeClass("open")
				}.bind(this), this.settings.animation_speed / 2)
			}
			if (/fade/i.test(this.settings.animation)) {
				var i = {opacity: 0};
				return this.delay(function () {
					return n.animate(i, this.settings.animation_speed, "linear", function () {
						this.locked = !1, n.css(r).trigger("closed")
					}.bind(this)).removeClass("open")
				}.bind(this), this.settings.animation_speed / 2)
			}
			return n.hide().css(r).removeClass("open").trigger("closed")
		}
		return/fade/i.test(this.settings.animation) ? n.fadeOut(this.settings.animation_speed / 2) : n.hide()
	}, close_video: function (t) {
		var n = e(this).find(".flex-video"), r = n.find("iframe");
		r.length > 0 && (r.attr("data-src", r[0].src), r.attr("src", "about:blank"), n.hide())
	}, open_video: function (t) {
		var n = e(this).find(".flex-video"), i = n.find("iframe");
		if (i.length > 0) {
			var s = i.attr("data-src");
			if (typeof s == "string")i[0].src = i.attr("data-src"); else {
				var o = i[0].src;
				i[0].src = r, i[0].src = o
			}
			n.show()
		}
	}, cache_offset: function (e) {
		var t = e.show().height() + parseInt(e.css("top"), 10);
		return e.hide(), t
	}, off: function () {
		e(this.scope).off(".fndtn.reveal")
	}, reflow: function () {
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.interchange = {name: "interchange", version: "5.0.0", cache: {}, images_loaded: !1, nodes_loaded: !1, settings: {load_attr: "interchange", named_queries: {"default": Foundation.media_queries.small, small: Foundation.media_queries.small, medium: Foundation.media_queries.medium, large: Foundation.media_queries.large, xlarge: Foundation.media_queries.xlarge, xxlarge: Foundation.media_queries.xxlarge, landscape: "only screen and (orientation: landscape)", portrait: "only screen and (orientation: portrait)", retina: "only screen and (-webkit-min-device-pixel-ratio: 2),only screen and (min--moz-device-pixel-ratio: 2),only screen and (-o-min-device-pixel-ratio: 2/1),only screen and (min-device-pixel-ratio: 2),only screen and (min-resolution: 192dpi),only screen and (min-resolution: 2dppx)"}, directives: {replace: function (t, n, r) {
		if (/IMG/.test(t[0].nodeName)) {
			var i = t[0].src;
			if ((new RegExp(n, "i")).test(i))return;
			return t[0].src = n, r(t[0].src)
		}
		var s = t.data("interchange-last-path");
		if (s == n)return;
		return e.get(n, function (e) {
			t.html(e), t.data("interchange-last-path", n), r()
		})
	}}}, init: function (e, t, n) {
		Foundation.inherit(this, "throttle"), this.data_attr = "data-" + this.settings.load_attr, this.bindings(t, n), this.load("images"), this.load("nodes")
	}, events: function () {
		var n = this;
		return e(t).off(".interchange").on("resize.fndtn.interchange", n.throttle(function () {
			n.resize.call(n)
		}, 50)), this
	}, resize: function () {
		var t = this.cache;
		if (!this.images_loaded || !this.nodes_loaded) {
			setTimeout(e.proxy(this.resize, this), 50);
			return
		}
		for (var n in t)if (t.hasOwnProperty(n)) {
			var r = this.results(n, t[n]);
			r && this.settings.directives[r.scenario[1]](r.el, r.scenario[0], function () {
				if (arguments[0]instanceof Array)var e = arguments[0]; else var e = Array.prototype.slice.call(arguments, 0);
				r.el.trigger(r.scenario[1], e)
			})
		}
	}, results: function (e, t) {
		var n = t.length;
		if (n > 0) {
			var r = this.S('[data-uuid="' + e + '"]');
			for (var i = n - 1; i >= 0; i--) {
				var s, o = t[i][2];
				this.settings.named_queries.hasOwnProperty(o) ? s = matchMedia(this.settings.named_queries[o]) : s = matchMedia(o);
				if (s.matches)return{el: r, scenario: t[i]}
			}
		}
		return!1
	}, load: function (e, t) {
		return(typeof this["cached_" + e] == "undefined" || t) && this["update_" + e](), this["cached_" + e]
	}, update_images: function () {
		var e = this.S("img[" + this.data_attr + "]"), t = e.length, n = 0, r = this.data_attr;
		this.cache = {}, this.cached_images = [], this.images_loaded = t === 0;
		for (var i = t - 1; i >= 0; i--) {
			n++;
			if (e[i]) {
				var s = e[i].getAttribute(r) || "";
				s.length > 0 && this.cached_images.push(e[i])
			}
			n === t && (this.images_loaded = !0, this.enhance("images"))
		}
		return this
	}, update_nodes: function () {
		var e = this.S("[" + this.data_attr + "]:not(img)"), t = e.length, n = 0, r = this.data_attr;
		this.cached_nodes = [], this.nodes_loaded = t === 0;
		for (var i = t - 1; i >= 0; i--) {
			n++;
			var s = e[i].getAttribute(r) || "";
			s.length > 0 && this.cached_nodes.push(e[i]), n === t && (this.nodes_loaded = !0, this.enhance("nodes"))
		}
		return this
	}, enhance: function (n) {
		var r = this["cached_" + n].length;
		for (var i = r - 1; i >= 0; i--)this.object(e(this["cached_" + n][i]));
		return e(t).trigger("resize")
	}, parse_params: function (e, t, n) {
		return[this.trim(e), this.convert_directive(t), this.trim(n)]
	}, convert_directive: function (e) {
		var t = this.trim(e);
		return t.length > 0 ? t : "replace"
	}, object: function (e) {
		var t = this.parse_data_attr(e), n = [], r = t.length;
		if (r > 0)for (var i = r - 1; i >= 0; i--) {
			var s = t[i].split(/\((.*?)(\))$/);
			if (s.length > 1) {
				var o = s[0].split(","), u = this.parse_params(o[0], o[1], s[1]);
				n.push(u)
			}
		}
		return this.store(e, n)
	}, uuid: function (e) {
		function n() {
			return((1 + Math.random()) * 65536 | 0).toString(16).substring(1)
		}

		var t = e || "-";
		return n() + n() + t + n() + t + n() + t + n() + t + n() + n() + n()
	}, store: function (e, t) {
		var n = this.uuid(), r = e.data("uuid");
		return r ? this.cache[r] : (e.attr("data-uuid", n), this.cache[n] = t)
	}, trim: function (t) {
		return typeof t == "string" ? e.trim(t) : t
	}, parse_data_attr: function (e) {
		var t = e.data(this.settings.load_attr).split(/\[(.*?)\]/), n = t.length, r = [];
		for (var i = n - 1; i >= 0; i--)t[i].replace(/[\W\d]+/, "").length > 4 && r.push(t[i]);
		return r
	}, reflow: function () {
		this.load("images", !0), this.load("nodes", !0)
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.magellan = {name: "magellan", version: "5.0.0", settings: {active_class: "active", threshold: 0}, init: function (t, n, r) {
		this.fixed_magellan = e("[data-magellan-expedition]"), this.set_threshold(), this.last_destination = e("[data-magellan-destination]").last(), this.events()
	}, events: function () {
		var n = this;
		e(this.scope).off(".magellan").on("arrival.fndtn.magellan", "[data-magellan-arrival]", function (t) {
			var r = e(this), i = r.closest("[data-magellan-expedition]"), s = i.attr("data-magellan-active-class") || n.settings.active_class;
			r.closest("[data-magellan-expedition]").find("[data-magellan-arrival]").not(r).removeClass(s), r.addClass(s)
		}), this.fixed_magellan.off(".magellan").on("update-position.fndtn.magellan",function () {
			var t = e(this)
		}).trigger("update-position"), e(t).off(".magellan").on("resize.fndtn.magellan", function () {
			this.fixed_magellan.trigger("update-position")
		}.bind(this)).on("scroll.fndtn.magellan", function () {
			var r = e(t).scrollTop();
			n.fixed_magellan.each(function () {
				var t = e(this);
				typeof t.data("magellan-top-offset") == "undefined" && t.data("magellan-top-offset", t.offset().top), typeof t.data("magellan-fixed-position") == "undefined" && t.data("magellan-fixed-position", !1);
				var i = r + n.settings.threshold > t.data("magellan-top-offset"), s = t.attr("data-magellan-top-offset");
				t.data("magellan-fixed-position") != i && (t.data("magellan-fixed-position", i), i ? (t.addClass("fixed"), t.css({position: "fixed", top: 0})) : (t.removeClass("fixed"), t.css({position: "", top: ""})), i && typeof s != "undefined" && s != 0 && t.css({position: "fixed", top: s + "px"}))
			})
		}), this.last_destination.length > 0 && e(t).on("scroll.fndtn.magellan", function (r) {
			var i = e(t).scrollTop(), s = i + e(t).height(), o = Math.ceil(n.last_destination.offset().top);
			e("[data-magellan-destination]").each(function () {
				var t = e(this), r = t.attr("data-magellan-destination"), u = t.offset().top - t.outerHeight(!0) - i;
				u <= n.settings.threshold && e("[data-magellan-arrival='" + r + "']").trigger("arrival"), s >= e(n.scope).height() && o > i && o < s && e("[data-magellan-arrival]").last().trigger("arrival")
			})
		})
	}, set_threshold: function () {
		typeof this.settings.threshold != "number" && (this.settings.threshold = this.fixed_magellan.length > 0 ? this.fixed_magellan.outerHeight(!0) : 0)
	}, off: function () {
		e(this.scope).off(".fndtn.magellan"), e(t).off(".fndtn.magellan")
	}, reflow: function () {
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.accordion = {name: "accordion", version: "5.0.1", settings: {active_class: "active", toggleable: !0}, init: function (e, t, n) {
		this.bindings(t, n)
	}, events: function () {
		e(this.scope).off(".accordion").on("click.fndtn.accordion", "[data-accordion] > dd > a", function (t) {
			var n = e(this).parent(), r = e("#" + this.href.split("#")[1]), i = e("> dd > .content", r.closest("[data-accordion]")), s = n.parent().data("accordion-init"), o = e("> dd > .content." + s.active_class, n.parent());
			t.preventDefault();
			if (o[0] == r[0] && s.toggleable)return r.toggleClass(s.active_class);
			i.removeClass(s.active_class), r.addClass(s.active_class)
		})
	}, off: function () {
	}, reflow: function () {
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.topbar = {name: "topbar", version: "5.0.1", settings: {index: 0, sticky_class: "sticky", custom_back_text: !0, back_text: "Back", is_hover: !0, mobile_show_parent_link: !1, scrolltop: !0}, init: function (t, n, r) {
		Foundation.inherit(this, "addCustomRule register_media throttle");
		var i = this;
		i.register_media("topbar", "foundation-mq-topbar"), this.bindings(n, r), e("[data-topbar]", this.scope).each(function () {
			var t = e(this), n = t.data("topbar-init"), r = e("section", this), s = e("> ul", this).first();
			t.data("index", 0);
			var o = t.parent();
			o.hasClass("fixed") || o.hasClass(n.sticky_class) ? (i.settings.sticky_class = n.sticky_class, i.settings.stick_topbar = t, t.data("height", o.outerHeight()), t.data("stickyoffset", o.offset().top)) : t.data("height", t.outerHeight()), n.assembled || i.assemble(t), n.is_hover ? e(".has-dropdown", t).addClass("not-click") : e(".has-dropdown", t).removeClass("not-click"), i.addCustomRule(".f-topbar-fixed { padding-top: " + t.data("height") + "px }"), o.hasClass("fixed") && e("body").addClass("f-topbar-fixed")
		})
	}, toggle: function (n) {
		var r = this;
		if (n)var i = e(n).closest("[data-topbar]"); else var i = e("[data-topbar]");
		var s = i.data("topbar-init"), o = e("section, .section", i);
		r.breakpoint() && (r.rtl ? (o.css({right: "0%"}), e(">.name", o).css({right: "100%"})) : (o.css({left: "0%"}), e(">.name", o).css({left: "100%"})), e("li.moved", o).removeClass("moved"), i.data("index", 0), i.toggleClass("expanded").css("height", "")), s.scrolltop ? i.hasClass("expanded") ? i.parent().hasClass("fixed") && (s.scrolltop ? (i.parent().removeClass("fixed"), i.addClass("fixed"), e("body").removeClass("f-topbar-fixed"), t.scrollTo(0, 0)) : i.parent().removeClass("expanded")) : i.hasClass("fixed") && (i.parent().addClass("fixed"), i.removeClass("fixed"), e("body").addClass("f-topbar-fixed")) : (i.parent().hasClass(r.settings.sticky_class) && i.parent().addClass("fixed"), i.parent().hasClass("fixed") && (i.hasClass("expanded") ? (i.addClass("fixed"), i.parent().addClass("expanded")) : (i.removeClass("fixed"), i.parent().removeClass("expanded"), r.update_sticky_positioning())))
	}, timer: null, events: function (n) {
		var r = this;
		e(this.scope).off(".topbar").on("click.fndtn.topbar", "[data-topbar] .toggle-topbar",function (e) {
			e.preventDefault(), r.toggle(this)
		}).on("click.fndtn.topbar", "[data-topbar] li.has-dropdown",function (t) {
			var n = e(this), i = e(t.target), s = n.closest("[data-topbar]"), o = s.data("topbar-init");
			if (i.data("revealId")) {
				r.toggle();
				return
			}
			if (r.breakpoint())return;
			if (o.is_hover && !Modernizr.touch)return;
			t.stopImmediatePropagation(), n.hasClass("hover") ? (n.removeClass("hover").find("li").removeClass("hover"), n.parents("li.hover").removeClass("hover")) : (n.addClass("hover"), i[0].nodeName === "A" && i.parent().hasClass("has-dropdown") && t.preventDefault())
		}).on("click.fndtn.topbar", "[data-topbar] .has-dropdown>a", function (t) {
			if (r.breakpoint()) {
				t.preventDefault();
				var n = e(this), i = n.closest("[data-topbar]"), s = i.find("section, .section"), o = n.next(".dropdown").outerHeight(), u = n.closest("li");
				i.data("index", i.data("index") + 1), u.addClass("moved"), r.rtl ? (s.css({right: -(100 * i.data("index")) + "%"}), s.find(">.name").css({right: 100 * i.data("index") + "%"})) : (s.css({left: -(100 * i.data("index")) + "%"}), s.find(">.name").css({left: 100 * i.data("index") + "%"})), i.css("height", n.siblings("ul").outerHeight(!0) + i.data("height"))
			}
		}), e(t).off(".topbar").on("resize.fndtn.topbar", r.throttle(function () {
			r.resize.call(r)
		}, 50)).trigger("resize"), e("body").off(".topbar").on("click.fndtn.topbar touchstart.fndtn.topbar", function (t) {
			var n = e(t.target).closest("li").closest("li.hover");
			if (n.length > 0)return;
			e("[data-topbar] li").removeClass("hover")
		}), e(this.scope).on("click.fndtn.topbar", "[data-topbar] .has-dropdown .back", function (t) {
			t.preventDefault();
			var n = e(this), i = n.closest("[data-topbar]"), s = i.find("section, .section"), o = i.data("topbar-init"), u = n.closest("li.moved"), a = u.parent();
			i.data("index", i.data("index") - 1), r.rtl ? (s.css({right: -(100 * i.data("index")) + "%"}), s.find(">.name").css({right: 100 * i.data("index") + "%"})) : (s.css({left: -(100 * i.data("index")) + "%"}), s.find(">.name").css({left: 100 * i.data("index") + "%"})), i.data("index") === 0 ? i.css("height", "") : i.css("height", a.outerHeight(!0) + i.data("height")), setTimeout(function () {
				u.removeClass("moved")
			}, 300)
		})
	}, resize: function () {
		var t = this;
		e("[data-topbar]").each(function () {
			var r = e(this), i = r.data("topbar-init"), s = r.parent("." + t.settings.sticky_class), o;
			if (!t.breakpoint()) {
				var u = r.hasClass("expanded");
				r.css("height", "").removeClass("expanded").find("li").removeClass("hover"), u && t.toggle(r)
			}
			s.length > 0 && (s.hasClass("fixed") ? (s.removeClass("fixed"), o = s.offset().top, e(n.body).hasClass("f-topbar-fixed") && (o -= r.data("height")), r.data("stickyoffset", o), s.addClass("fixed")) : (o = s.offset().top, r.data("stickyoffset", o)))
		})
	}, breakpoint: function () {
		return!matchMedia(Foundation.media_queries.topbar).matches
	}, assemble: function (t) {
		var n = this, r = t.data("topbar-init"), i = e("section", t), s = e("> ul", t).first();
		i.detach(), e(".has-dropdown>a", i).each(function () {
			var t = e(this), n = t.siblings(".dropdown"), i = t.attr("href");
			if (r.mobile_show_parent_link && i && i.length > 1)var s = e('<li class="title back js-generated"><h5><a href="#"></a></h5></li><li><a class="parent-link js-generated" href="' + i + '">' + t.text() + "</a></li>"); else var s = e('<li class="title back js-generated"><h5><a href="#"></a></h5></li>');
			r.custom_back_text == 1 ? e("h5>a", s).html(r.back_text) : e("h5>a", s).html("&laquo; " + t.html()), n.prepend(s)
		}), i.appendTo(t), this.sticky(), this.assembled(t)
	}, assembled: function (t) {
		t.data("topbar-init", e.extend({}, t.data("topbar-init"), {assembled: !0}))
	}, height: function (t) {
		var n = 0, r = this;
		return e("> li", t).each(function () {
			n += e(this).outerHeight(!0)
		}), n
	}, sticky: function () {
		var n = e(t), r = this;
		e(t).on("scroll", function () {
			r.update_sticky_positioning()
		})
	}, update_sticky_positioning: function () {
		var n = "." + this.settings.sticky_class, r = e(t);
		if (e(n).length > 0) {
			var i = this.settings.sticky_topbar.data("stickyoffset");
			e(n).hasClass("expanded") || (r.scrollTop() > i ? e(n).hasClass("fixed") || (e(n).addClass("fixed"), e("body").addClass("f-topbar-fixed")) : r.scrollTop() <= i && e(n).hasClass("fixed") && (e(n).removeClass("fixed"), e("body").removeClass("f-topbar-fixed")))
		}
	}, off: function () {
		e(this.scope).off(".fndtn.topbar"), e(t).off(".fndtn.topbar")
	}, reflow: function () {
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.tab = {name: "tab", version: "5.0.1", settings: {active_class: "active"}, init: function (e, t, n) {
		this.bindings(t, n)
	}, events: function () {
		e(this.scope).off(".tab").on("click.fndtn.tab", "[data-tab] > dd > a", function (t) {
			t.preventDefault();
			var n = e(this).parent(), r = e("#" + this.href.split("#")[1]), i = n.siblings(), s = n.closest("[data-tab]").data("tab-init");
			n.addClass(s.active_class), i.removeClass(s.active_class), r.siblings().removeClass(s.active_class).end().addClass(s.active_class)
		})
	}, off: function () {
	}, reflow: function () {
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.abide = {name: "abide", version: "5.0.0", settings: {focus_on_invalid: !0, timeout: 1e3, patterns: {alpha: /[a-zA-Z]+/, alpha_numeric: /[a-zA-Z0-9]+/, integer: /-?\d+/, number: /-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?/, password: /(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/, card: /^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6(?:011|5[0-9][0-9])[0-9]{12}|3[47][0-9]{13}|3(?:0[0-5]|[68][0-9])[0-9]{11}|(?:2131|1800|35\d{3})\d{11})$/, cvv: /^([0-9]){3,4}$/, email: /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/, url: /(https?|ftp|file|ssh):\/\/(((([a-zA-Z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-zA-Z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-zA-Z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-zA-Z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-zA-Z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-zA-Z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-zA-Z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-zA-Z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-zA-Z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-zA-Z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-zA-Z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-zA-Z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-zA-Z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?/, domain: /^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}$/, datetime: /([0-2][0-9]{3})\-([0-1][0-9])\-([0-3][0-9])T([0-5][0-9])\:([0-5][0-9])\:([0-5][0-9])(Z|([\-\+]([0-1][0-9])\:00))/, date: /(?:19|20)[0-9]{2}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-9])|(?:(?!02)(?:0[1-9]|1[0-2])-(?:30))|(?:(?:0[13578]|1[02])-31))/, time: /(0[0-9]|1[0-9]|2[0-3])(:[0-5][0-9]){2}/, dateISO: /\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}/, month_day_year: /(0[1-9]|1[012])[- \/.](0[1-9]|[12][0-9]|3[01])[- \/.](19|20)\d\d/, color: /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/}}, timer: null, init: function (e, t, n) {
		this.bindings(t, n)
	}, events: function (t) {
		var n = this, r = e(t).attr("novalidate", "novalidate"), i = r.data("abide-init");
		r.off(".abide").on("submit.fndtn.abide validate.fndtn.abide",function (t) {
			var r = /ajax/i.test(e(this).attr("data-abide"));
			return n.validate(e(this).find("input, textarea, select").get(), t, r)
		}).find("input, textarea, select").off(".abide").on("blur.fndtn.abide change.fndtn.abide",function (e) {
			n.validate([this], e)
		}).on("keydown.fndtn.abide", function (t) {
			var r = e(this).closest("form").data("abide-init");
			clearTimeout(n.timer), n.timer = setTimeout(function () {
				n.validate([this], t)
			}.bind(this), r.timeout)
		})
	}, validate: function (t, n, r) {
		var i = this.parse_patterns(t), s = i.length, o = e(t[0]).closest("form"), u = /submit/.test(n.type);
		for (var a = 0; a < s; a++)if (!i[a] && (u || r))return this.settings.focus_on_invalid && t[a].focus(), o.trigger("invalid"), e(t[a]).closest("form").attr("data-invalid", ""), !1;
		return(u || r) && o.trigger("valid"), o.removeAttr("data-invalid"), r ? !1 : !0
	}, parse_patterns: function (e) {
		var t = e.length, n = [];
		for (var r = t - 1; r >= 0; r--)n.push(this.pattern(e[r]));
		return this.check_validation_and_apply_styles(n)
	}, pattern: function (e) {
		var t = e.getAttribute("type"), n = typeof e.getAttribute("required") == "string";
		if (this.settings.patterns.hasOwnProperty(t))return[e, this.settings.patterns[t], n];
		var r = e.getAttribute("pattern") || "";
		return this.settings.patterns.hasOwnProperty(r) && r.length > 0 ? [e, this.settings.patterns[r], n] : r.length > 0 ? [e, new RegExp(r), n] : (r = /.*/, [e, r, n])
	}, check_validation_and_apply_styles: function (t) {
		var n = t.length, r = [];
		for (var i = n - 1; i >= 0; i--) {
			var s = t[i][0], o = t[i][2], u = s.value, a = s.getAttribute("data-equalto"), f = s.type === "radio", l = o ? s.value.length > 0 : !0;
			f && o ? r.push(this.valid_radio(s, o)) : a && o ? r.push(this.valid_equal(s, o)) : t[i][1].test(u) && l || !o && s.value.length < 1 ? (e(s).removeAttr("data-invalid").parent().removeClass("error"), r.push(!0)) : (e(s).attr("data-invalid", "").parent().addClass("error"), r.push(!1))
		}
		return r
	}, valid_radio: function (t, r) {
		var i = t.getAttribute("name"), s = n.getElementsByName(i), o = s.length, u = !1;
		for (var a = 0; a < o; a++)s[a].checked && (u = !0);
		for (var a = 0; a < o; a++)u ? e(s[a]).removeAttr("data-invalid").parent().removeClass("error") : e(s[a]).attr("data-invalid", "").parent().addClass("error");
		return u
	}, valid_equal: function (t, r) {
		var i = n.getElementById(t.getAttribute("data-equalto")).value, s = t.value, o = i === s;
		return o ? e(t).removeAttr("data-invalid").parent().removeClass("error") : e(t).attr("data-invalid", "").parent().addClass("error"), o
	}}
}(jQuery, this, this.document), function (e, t, n, r) {
	"use strict";
	Foundation.libs.tooltip = {name: "tooltip", version: "5.0.0", settings: {additional_inheritable_classes: [], tooltip_class: ".tooltip", append_to: "body", touch_close_text: "Tap To Close", disable_for_touch: !1, tip_template: function (e, t) {
		return'<span data-selector="' + e + '" class="' + Foundation.libs.tooltip.settings.tooltip_class.substring(1) + '">' + t + '<span class="nub"></span></span>'
	}}, cache: {}, init: function (e, t, n) {
		this.bindings(t, n)
	}, events: function () {
		var t = this;
		Modernizr.touch ? e(this.scope).off(".tooltip").on("click.fndtn.tooltip touchstart.fndtn.tooltip touchend.fndtn.tooltip", "[data-tooltip]",function (n) {
			var r = e.extend({}, t.settings, t.data_options(e(this)));
			r.disable_for_touch || (n.preventDefault(), e(r.tooltip_class).hide(), t.showOrCreateTip(e(this)))
		}).on("click.fndtn.tooltip touchstart.fndtn.tooltip touchend.fndtn.tooltip", this.settings.tooltip_class, function (t) {
			t.preventDefault(), e(this).fadeOut(150)
		}) : e(this.scope).off(".tooltip").on("mouseenter.fndtn.tooltip mouseleave.fndtn.tooltip", "[data-tooltip]", function (n) {
			var r = e(this);
			/enter|over/i.test(n.type) ? t.showOrCreateTip(r) : (n.type === "mouseout" || n.type === "mouseleave") && t.hide(r)
		})
	}, showOrCreateTip: function (e) {
		var t = this.getTip(e);
		return t && t.length > 0 ? this.show(e) : this.create(e)
	}, getTip: function (t) {
		var n = this.selector(t), r = null;
		return n && (r = e('span[data-selector="' + n + '"]' + this.settings.tooltip_class)), typeof r == "object" ? r : !1
	}, selector: function (e) {
		var t = e.attr("id"), n = e.attr("data-tooltip") || e.attr("data-selector");
		return(t && t.length < 1 || !t) && typeof n != "string" && (n = "tooltip" + Math.random().toString(36).substring(7), e.attr("data-selector", n)), t && t.length > 0 ? t : n
	}, create: function (t) {
		var n = e(this.settings.tip_template(this.selector(t), e("<div></div>").html(t.attr("title")).html())), r = this.inheritable_classes(t);
		n.addClass(r).appendTo(this.settings.append_to), Modernizr.touch && n.append('<span class="tap-to-close">' + this.settings.touch_close_text + "</span>"), t.removeAttr("title").attr("title", ""), this.show(t)
	}, reposition: function (t, n, r) {
		var i, s, o, u, a, f;
		n.css("visibility", "hidden").show(), i = t.data("width"), s = n.children(".nub"), o = s.outerHeight(), u = s.outerHeight(), f = function (e, t, n, r, i, s) {
			return e.css({top: t ? t : "auto", bottom: r ? r : "auto", left: i ? i : "auto", right: n ? n : "auto", width: s ? s : "auto"}).end()
		}, f(n, t.offset().top + t.outerHeight() + 10, "auto", "auto", t.offset().left, i);
		if (this.small())f(n, t.offset().top + t.outerHeight() + 10, "auto", "auto", 12.5, e(this.scope).width()), n.addClass("tip-override"), f(s, -o, "auto", "auto", t.offset().left); else {
			var l = t.offset().left;
			Foundation.rtl && (l = t.offset().left + t.offset().width - n.outerWidth()), f(n, t.offset().top + t.outerHeight() + 10, "auto", "auto", l, i), n.removeClass("tip-override"), r && r.indexOf("tip-top") > -1 ? f(n, t.offset().top - n.outerHeight(), "auto", "auto", l, i).removeClass("tip-override") : r && r.indexOf("tip-left") > -1 ? f(n, t.offset().top + t.outerHeight() / 2 - o * 2.5, "auto", "auto", t.offset().left - n.outerWidth() - o, i).removeClass("tip-override") : r && r.indexOf("tip-right") > -1 && f(n, t.offset().top + t.outerHeight() / 2 - o * 2.5, "auto", "auto", t.offset().left + t.outerWidth() + o, i).removeClass("tip-override")
		}
		n.css("visibility", "visible").hide()
	}, small: function () {
		return matchMedia(Foundation.media_queries.small).matches
	}, inheritable_classes: function (t) {
		var n = ["tip-top", "tip-left", "tip-bottom", "tip-right", "noradius"].concat(this.settings.additional_inheritable_classes), r = t.attr("class"), i = r ? e.map(r.split(" "),function (t, r) {
			if (e.inArray(t, n) !== -1)return t
		}).join(" ") : "";
		return e.trim(i)
	}, show: function (e) {
		var t = this.getTip(e);
		this.reposition(e, t, e.attr("class")), t.fadeIn(150)
	}, hide: function (e) {
		var t = this.getTip(e);
		t.fadeOut(150)
	}, reload: function () {
		var t = e(this);
		return t.data("fndtn-tooltips") ? t.foundationTooltips("destroy").foundationTooltips("init") : t.foundationTooltips("init")
	}, off: function () {
		e(this.scope).off(".fndtn.tooltip"), e(this.settings.tooltip_class).each(function (t) {
			e("[data-tooltip]").get(t).attr("title", e(this).text())
		}).remove()
	}, reflow: function () {
	}}
}(jQuery, this, this.document);
