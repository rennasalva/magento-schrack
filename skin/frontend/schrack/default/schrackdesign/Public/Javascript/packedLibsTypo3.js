!function(t, e) {
    "use strict";
    "function" == typeof define && define.amd ? define(e) : "object" == typeof exports ? module.exports = e() : t.baguetteBox = e();
}(this, function() {
    "use strict";
    function t(t, n) {
        M.transforms = k(), M.svg = w(), i(), o(t), e(t, n);
    }
    function e(t, e) {
        var n = document.querySelectorAll(t), o = {
            galleries: [],
            nodeList: n
        };
        U[t] = o, [].forEach.call(n, function(t) {
            e && e.filter && (V = e.filter);
            var n = [];
            if (n = "A" === t.tagName ? [ t ] : t.getElementsByTagName("a"), n = [].filter.call(n, function(t) {
                return V.test(t.href);
            }), 0 !== n.length) {
                var i = [];
                [].forEach.call(n, function(t, n) {
                    var o = function(t) {
                        t.preventDefault ? t.preventDefault() : t.returnValue = !1, u(i, e), c(n);
                    }, a = {
                        eventHandler: o,
                        imageElement: t
                    };
                    E(t, "click", o), i.push(a);
                }), o.galleries.push(i);
            }
        });
    }
    function n() {
        for (var t in U) U.hasOwnProperty(t) && o(t);
    }
    function o(t) {
        if (U.hasOwnProperty(t)) {
            var e = U[t].galleries;
            [].forEach.call(e, function(t) {
                [].forEach.call(t, function(t) {
                    B(t.imageElement, "click", t.eventHandler);
                }), R === t && (R = []);
            }), delete U[t];
        }
    }
    function i() {
        return (S = T("baguetteBox-overlay")) ? (P = T("baguetteBox-slider"), F = T("previous-button"), 
        H = T("next-button"), void (L = T("close-button"))) : (S = N("div"), S.setAttribute("role", "dialog"), 
        S.id = "baguetteBox-overlay", document.getElementsByTagName("body")[0].appendChild(S), 
        P = N("div"), P.id = "baguetteBox-slider", S.appendChild(P), F = N("button"), F.setAttribute("type", "button"), 
        F.id = "previous-button", F.setAttribute("aria-label", "Previous"), F.innerHTML = M.svg ? I : "&lt;", 
        S.appendChild(F), H = N("button"), H.setAttribute("type", "button"), H.id = "next-button", 
        H.setAttribute("aria-label", "Next"), H.innerHTML = M.svg ? Y : "&gt;", S.appendChild(H), 
        L = N("button"), L.setAttribute("type", "button"), L.id = "close-button", L.setAttribute("aria-label", "Close"), 
        L.innerHTML = M.svg ? q : "&times;", S.appendChild(L), F.className = H.className = L.className = "baguetteBox-button", 
        void r());
    }
    function a(t) {
        switch (t.keyCode) {
          case 37:
            v();
            break;

          case 39:
            h();
            break;

          case 27:
            p();
        }
    }
    function r() {
        E(S, "click", J), E(F, "click", K), E(H, "click", Q), E(L, "click", Z), E(S, "touchstart", $), 
        E(S, "touchmove", _), E(S, "touchend", tt), E(document, "focus", et, !0);
    }
    function l() {
        B(S, "click", J), B(F, "click", K), B(H, "click", Q), B(L, "click", Z), B(S, "touchstart", $), 
        B(S, "touchmove", _), B(S, "touchend", tt), B(document, "focus", et, !0);
    }
    function u(t, e) {
        if (R !== t) {
            for (R = t, s(e); P.firstChild; ) P.removeChild(P.firstChild);
            W.length = 0;
            for (var n, o = [], i = [], a = 0; a < t.length; a++) n = N("div"), n.className = "full-image", 
            n.id = "baguette-img-" + a, W.push(n), o.push("baguetteBox-figure-" + a), i.push("baguetteBox-figcaption-" + a), 
            P.appendChild(W[a]);
            S.setAttribute("aria-labelledby", o.join(" ")), S.setAttribute("aria-describedby", i.join(" "));
        }
    }
    function s(t) {
        t || (t = {});
        for (var e in X) j[e] = X[e], "undefined" != typeof t[e] && (j[e] = t[e]);
        P.style.transition = P.style.webkitTransition = "fadeIn" === j.animation ? "opacity .4s ease" : "slideIn" === j.animation ? "" : "none", 
        "auto" === j.buttons && ("ontouchstart" in window || 1 === R.length) && (j.buttons = !1), 
        F.style.display = H.style.display = j.buttons ? "" : "none";
        try {
            S.style.backgroundColor = j.overlayBackgroundColor;
        } catch (t) {}
    }
    function c(t) {
        j.noScrollbars && (document.documentElement.style.overflowY = "hidden", document.body.style.overflowY = "scroll"), 
        "block" !== S.style.display && (E(document, "keydown", a), z = t, D = {
            count: 0,
            startX: null,
            startY: null
        }, m(z, function() {
            x(z), C(z);
        }), y(), S.style.display = "block", j.fullScreen && f(), setTimeout(function() {
            S.className = "visible", j.afterShow && j.afterShow();
        }, 50), j.onChange && j.onChange(z, W.length), G = document.activeElement, d());
    }
    function d() {
        j.buttons ? F.focus() : L.focus();
    }
    function f() {
        S.requestFullscreen ? S.requestFullscreen() : S.webkitRequestFullscreen ? S.webkitRequestFullscreen() : S.mozRequestFullScreen && S.mozRequestFullScreen();
    }
    function g() {
        document.exitFullscreen ? document.exitFullscreen() : document.mozCancelFullScreen ? document.mozCancelFullScreen() : document.webkitExitFullscreen && document.webkitExitFullscreen();
    }
    function p() {
        j.noScrollbars && (document.documentElement.style.overflowY = "auto", document.body.style.overflowY = "auto"), 
        "none" !== S.style.display && (B(document, "keydown", a), S.className = "", setTimeout(function() {
            S.style.display = "none", g(), j.afterHide && j.afterHide();
        }, 500), G.focus());
    }
    function m(t, e) {
        var n = W[t];
        if ("undefined" != typeof n) {
            if (n.getElementsByTagName("img")[0]) return void (e && e());
            var o = R[t].imageElement, i = o.getElementsByTagName("img")[0], a = "function" == typeof j.captions ? j.captions.call(R, o) : o.getAttribute("data-caption") || o.title, r = b(o), l = N("figure");
            if (l.id = "baguetteBox-figure-" + t, l.innerHTML = '<div class="baguetteBox-spinner"><div class="baguetteBox-double-bounce1"></div><div class="baguetteBox-double-bounce2"></div></div>', 
            j.captions && a) {
                var u = N("figcaption");
                u.id = "baguetteBox-figcaption-" + t, u.innerHTML = a, l.appendChild(u);
            }
            n.appendChild(l);
            var s = N("img");
            s.onload = function() {
                var n = document.querySelector("#baguette-img-" + t + " .baguetteBox-spinner");
                l.removeChild(n), !j.async && e && e();
            }, s.setAttribute("src", r), s.alt = i ? i.alt || "" : "", j.titleTag && a && (s.title = a), 
            l.appendChild(s), j.async && e && e();
        }
    }
    function b(t) {
        var e = t.href;
        if (t.dataset) {
            var n = [];
            for (var o in t.dataset) "at-" !== o.substring(0, 3) || isNaN(o.substring(3)) || (n[o.replace("at-", "")] = t.dataset[o]);
            for (var i = Object.keys(n).sort(function(t, e) {
                return parseInt(t, 10) < parseInt(e, 10) ? -1 : 1;
            }), a = window.innerWidth * window.devicePixelRatio, r = 0; r < i.length - 1 && i[r] < a; ) r++;
            e = n[i[r]] || e;
        }
        return e;
    }
    function h() {
        var t;
        return z <= W.length - 2 ? (z++, y(), x(z), t = !0) : j.animation && (P.className = "bounce-from-right", 
        setTimeout(function() {
            P.className = "";
        }, 400), t = !1), j.onChange && j.onChange(z, W.length), t;
    }
    function v() {
        var t;
        return z >= 1 ? (z--, y(), C(z), t = !0) : j.animation && (P.className = "bounce-from-left", 
        setTimeout(function() {
            P.className = "";
        }, 400), t = !1), j.onChange && j.onChange(z, W.length), t;
    }
    function y() {
        var t = 100 * -z + "%";
        "fadeIn" === j.animation ? (P.style.opacity = 0, setTimeout(function() {
            M.transforms ? P.style.transform = P.style.webkitTransform = "translate3d(" + t + ",0,0)" : P.style.left = t, 
            P.style.opacity = 1;
        }, 400)) : M.transforms ? P.style.transform = P.style.webkitTransform = "translate3d(" + t + ",0,0)" : P.style.left = t;
    }
    function k() {
        var t = N("div");
        return "undefined" != typeof t.style.perspective || "undefined" != typeof t.style.webkitPerspective;
    }
    function w() {
        var t = N("div");
        return t.innerHTML = "<svg/>", "http://www.w3.org/2000/svg" === (t.firstChild && t.firstChild.namespaceURI);
    }
    function x(t) {
        t - z >= j.preload || m(t + 1, function() {
            x(t + 1);
        });
    }
    function C(t) {
        z - t >= j.preload || m(t - 1, function() {
            C(t - 1);
        });
    }
    function E(t, e, n, o) {
        t.addEventListener ? t.addEventListener(e, n, o) : t.attachEvent("on" + e, n);
    }
    function B(t, e, n, o) {
        t.removeEventListener ? t.removeEventListener(e, n, o) : t.detachEvent("on" + e, n);
    }
    function T(t) {
        return document.getElementById(t);
    }
    function N(t) {
        return document.createElement(t);
    }
    function A() {
        l(), n(), B(document, "keydown", a), document.getElementsByTagName("body")[0].removeChild(document.getElementById("baguetteBox-overlay")), 
        U = {}, R = [], z = 0;
    }
    var S, P, F, H, L, I = '<svg width="44" height="60"><polyline points="30 10 10 30 30 50" stroke="rgba(255,255,255,0.5)" stroke-width="4"stroke-linecap="butt" fill="none" stroke-linejoin="round"/></svg>', Y = '<svg width="44" height="60"><polyline points="14 10 34 30 14 50" stroke="rgba(255,255,255,0.5)" stroke-width="4"stroke-linecap="butt" fill="none" stroke-linejoin="round"/></svg>', q = '<svg width="30" height="30"><g stroke="rgb(160,160,160)" stroke-width="4"><line x1="5" y1="5" x2="25" y2="25"/><line x1="5" y1="25" x2="25" y2="5"/></g></svg>', j = {}, X = {
        captions: !0,
        fullScreen: !1,
        noScrollbars: !1,
        titleTag: !1,
        buttons: "auto",
        async: !1,
        preload: 2,
        animation: "slideIn",
        afterShow: null,
        afterHide: null,
        onChange: null,
        overlayBackgroundColor: "rgba(0,0,0,.8)"
    }, M = {}, R = [], z = 0, D = {}, O = !1, V = /.+\.(gif|jpe?g|png|webp)/i, U = {}, W = [], G = null, J = function(t) {
        t.target.id.indexOf("baguette-img") !== -1 && p();
    }, K = function(t) {
        t.stopPropagation ? t.stopPropagation() : t.cancelBubble = !0, v();
    }, Q = function(t) {
        t.stopPropagation ? t.stopPropagation() : t.cancelBubble = !0, h();
    }, Z = function(t) {
        t.stopPropagation ? t.stopPropagation() : t.cancelBubble = !0, p();
    }, $ = function(t) {
        D.count++, D.count > 1 && (D.multitouch = !0), D.startX = t.changedTouches[0].pageX, 
        D.startY = t.changedTouches[0].pageY;
    }, _ = function(t) {
        if (!O && !D.multitouch) {
            t.preventDefault ? t.preventDefault() : t.returnValue = !1;
            var e = t.touches[0] || t.changedTouches[0];
            e.pageX - D.startX > 40 ? (O = !0, v()) : e.pageX - D.startX < -40 ? (O = !0, h()) : D.startY - e.pageY > 100 && p();
        }
    }, tt = function() {
        D.count--, D.count <= 0 && (D.multitouch = !1), O = !1;
    }, et = function(t) {
        "block" !== S.style.display || S.contains(t.target) || (t.stopPropagation(), d());
    };
    return [].forEach || (Array.prototype.forEach = function(t, e) {
        for (var n = 0; n < this.length; n++) t.call(e, this[n], n, this);
    }), [].filter || (Array.prototype.filter = function(t, e, n, o, i) {
        for (n = this, o = [], i = 0; i < n.length; i++) t.call(e, n[i], i, n) && o.push(n[i]);
        return o;
    }), {
        run: t,
        destroy: A,
        showNext: h,
        showPrevious: v
    };
});

var Plan2net = Plan2net || {};

Plan2net.Tracking = Plan2net.Tracking || {};

Plan2net.Tracking.event = function(e) {
    if (typeof ga != "undefined") {
        var p = {};
        if (e.label) p.eventLabel = e.label;
        if (e.value) p.eventValue = e.value;
        if (e.nonInteraction) p.nonInteraction = e.nonInteraction;
        if (e.page) p.page = e.page;
        ga("send", "event", e.category, e.action, p);
    }
};

Plan2net.Tracking.observeFormFields = function(context) {
    var $ = Plan2net.Tracking.jQuery || window.jQuery;
    $("form", context).each(function() {
        $(this).find(':submit,input[type="image"]').click(function() {
            if (Plan2net.Tracking.focussedFormElement) {
                Plan2net.Tracking.focussedFormElement = $(this);
            }
        });
        $(this).find(":input").not(':submit,input[type="image"]').change(function() {
            Plan2net.Tracking.focussedFormElement = $(this);
        });
    });
};

jQuery(document).ready(function($) {
    Plan2net.Tracking.focussedFormElement = null;
    Plan2net.Tracking.jQuery = $;
    Plan2net.Tracking.observeFormFields(document.body);
    $(window).on("beforeunload", function() {
        if (Plan2net.Tracking.focussedFormElement) {
            var tagName = Plan2net.Tracking.focussedFormElement.prop("tagName");
            var fieldType = Plan2net.Tracking.focussedFormElement.attr("type");
            var fieldId = Plan2net.Tracking.focussedFormElement.attr("id");
            var fieldLabel = fieldId ? $("label[for=" + fieldId + "]").text() : null;
            var fieldName = Plan2net.Tracking.focussedFormElement.attr("title") || fieldLabel || Plan2net.Tracking.focussedFormElement.attr("name") || fieldId || "[field]";
            var form = Plan2net.Tracking.focussedFormElement.prop("form");
            var formName = $(form).attr("name") || "[form]";
            if (tagName == "BUTTON" && !fieldType) {
                fieldType = "submit";
            }
            if (fieldType != "submit" && fieldType != "image") {
                Plan2net.Tracking.event({
                    category: "Form Fields",
                    action: "exit",
                    label: formName + ": " + fieldName,
                    nonInteraction: true
                });
                Plan2net.Tracking.event({
                    category: "Forms",
                    action: "cancel",
                    label: $(form).attr("name"),
                    value: 0
                });
            } else {
                Plan2net.Tracking.event({
                    category: "Forms",
                    action: "submit",
                    label: formName,
                    value: 1
                });
            }
        }
    });
});

jQuery(document).ready(function($) {
    var filetypes = /\.(zip|exe|dmg|pdf|doc[bmx]?|odt|xls[bmx]?|ods|pp[st][mx]?|odp|mp3|txt|rar|wma|mov|avi|wmv|flv|wav)$/i;
    var baseHref = "";
    var removeQueryFields = function(url) {
        var fields = [].slice.call(arguments, 1).join("|"), parts = url.split(new RegExp("[&?](" + fields + ")=[^&]*")), length = parts.length - 1;
        return parts[0] + (length ? "?" + parts[length].slice(1) : "");
    };
    if ($("base").attr("href") != undefined) {
        baseHref = $("base").attr("href");
    }
    $("body").on("click", "a", function(e) {
        var el = $(this);
        var track = true;
        var href = el.attr("href") || "";
        var url = el.prop("href");
        var locationDomain, hrefDomain, linkTarget;
        if (url && (!href.match(/^javascript:/i) || href.match(/^javascript:linkTo_UnCryptMailto/i))) {
            var ev = {
                value: 0,
                nonInteraction: false
            };
            var hrefCleaned = removeQueryFields(href, "no_cache", "cid", "did", "sechash");
            if (href.match(/^javascript:linkTo_UnCryptMailto/i)) {
                ev.category = "email";
                ev.action = "click";
                ev.label = el.html();
            } else if (href.match(/^mailto:/i)) {
                ev.category = "email";
                ev.action = "click";
                ev.label = hrefCleaned.replace(/^mailto:/i, "");
            } else if (href.match(filetypes)) {
                var extension = /[.]/.exec(href) ? /[^.]+$/.exec(href) : undefined;
                ev.category = "download";
                ev.action = "click-" + extension[0];
                ev.label = hrefCleaned.replace(/ /g, "-");
            } else if (href.match(/^https?:/i)) {
                locationDomain = window.location.hostname.split(".").slice(-2).join(".");
                hrefDomain = el.prop("hostname").split(".").slice(-2).join(".");
                if (locationDomain === hrefDomain) {
                    track = false;
                } else {
                    ev.category = "external";
                    ev.action = "click";
                    ev.label = hrefCleaned.replace(/^https?:\/\//i, "");
                    ev.nonInteraction = true;
                }
            } else if (href.match(/^tel:/i)) {
                ev.category = "telephone";
                ev.action = "click";
                ev.label = hrefCleaned.replace(/^tel:/i, "");
            } else {
                track = false;
            }
            if (track) {
                Plan2net.Tracking.event(ev);
                linkTarget = el.attr("target") || "_self";
                var linksToSameFrame = linkTarget.toLowerCase() === "_self";
                var willOpenInBrowser = /https?:\/\//i.test(url);
                if (linksToSameFrame && willOpenInBrowser) {
                    setTimeout(function() {
                        window.location.href = url;
                    }, 400);
                    return false;
                }
            }
        }
    });
});

(function() {
    var b = void 0, f = !0, k = null, l = !1;
    function m() {
        return function() {};
    }
    function p(a) {
        return function() {
            return this[a];
        };
    }
    function q(a) {
        return function() {
            return a;
        };
    }
    var s;
    document.createElement("video");
    document.createElement("audio");
    document.createElement("track");
    function t(a, c, d) {
        if ("string" === typeof a) {
            0 === a.indexOf("#") && (a = a.slice(1));
            if (t.Ca[a]) return t.Ca[a];
            a = t.w(a);
        }
        if (!a || !a.nodeName) throw new TypeError("The element or ID supplied is not valid. (videojs)");
        return a.player || new t.Player(a, c, d);
    }
    var videojs = window.videojs = t;
    t.Vb = "4.10";
    t.Vc = "https:" == document.location.protocol ? "https://" : "http://";
    t.options = {
        techOrder: [ "html5", "flash" ],
        html5: {},
        flash: {},
        width: 300,
        height: 150,
        defaultVolume: 0,
        playbackRates: [],
        inactivityTimeout: 2e3,
        children: {
            mediaLoader: {},
            posterImage: {},
            textTrackDisplay: {},
            loadingSpinner: {},
            bigPlayButton: {},
            controlBar: {},
            errorDisplay: {}
        },
        language: document.getElementsByTagName("html")[0].getAttribute("lang") || navigator.languages && navigator.languages[0] || navigator.ze || navigator.language || "en",
        languages: {},
        notSupportedMessage: "No compatible source was found for this video."
    };
    "GENERATED_CDN_VSN" !== t.Vb && (videojs.options.flash.swf = t.Vc + "vjs.zencdn.net/" + t.Vb + "/video-js.swf");
    t.hd = function(a, c) {
        t.options.languages[a] = t.options.languages[a] !== b ? t.ga.Va(t.options.languages[a], c) : c;
        return t.options.languages;
    };
    t.Ca = {};
    "function" === typeof define && define.amd ? define([], function() {
        return videojs;
    }) : "object" === typeof exports && "object" === typeof module && (module.exports = videojs);
    t.qa = t.CoreObject = m();
    t.qa.extend = function(a) {
        var c, d;
        a = a || {};
        c = a.init || a.i || this.prototype.init || this.prototype.i || m();
        d = function() {
            c.apply(this, arguments);
        };
        d.prototype = t.g.create(this.prototype);
        d.prototype.constructor = d;
        d.extend = t.qa.extend;
        d.create = t.qa.create;
        for (var e in a) a.hasOwnProperty(e) && (d.prototype[e] = a[e]);
        return d;
    };
    t.qa.create = function() {
        var a = t.g.create(this.prototype);
        this.apply(a, arguments);
        return a;
    };
    t.c = function(a, c, d) {
        if (t.g.isArray(c)) return u(t.c, a, c, d);
        var e = t.getData(a);
        e.C || (e.C = {});
        e.C[c] || (e.C[c] = []);
        d.r || (d.r = t.r++);
        e.C[c].push(d);
        e.W || (e.disabled = l, e.W = function(c) {
            if (!e.disabled) {
                c = t.rc(c);
                var d = e.C[c.type];
                if (d) for (var d = d.slice(0), j = 0, n = d.length; j < n && !c.zc(); j++) d[j].call(a, c);
            }
        });
        1 == e.C[c].length && (a.addEventListener ? a.addEventListener(c, e.W, l) : a.attachEvent && a.attachEvent("on" + c, e.W));
    };
    t.j = function(a, c, d) {
        if (t.uc(a)) {
            var e = t.getData(a);
            if (e.C) {
                if (t.g.isArray(c)) return u(t.j, a, c, d);
                if (c) {
                    var g = e.C[c];
                    if (g) {
                        if (d) {
                            if (d.r) for (e = 0; e < g.length; e++) g[e].r === d.r && g.splice(e--, 1);
                        } else e.C[c] = [];
                        t.kc(a, c);
                    }
                } else for (g in e.C) c = g, e.C[c] = [], t.kc(a, c);
            }
        }
    };
    t.kc = function(a, c) {
        var d = t.getData(a);
        0 === d.C[c].length && (delete d.C[c], a.removeEventListener ? a.removeEventListener(c, d.W, l) : a.detachEvent && a.detachEvent("on" + c, d.W));
        t.Ib(d.C) && (delete d.C, delete d.W, delete d.disabled);
        t.Ib(d) && t.Ic(a);
    };
    t.rc = function(a) {
        function c() {
            return f;
        }
        function d() {
            return l;
        }
        if (!a || !a.Jb) {
            var e = a || window.event;
            a = {};
            for (var g in e) "layerX" !== g && ("layerY" !== g && "keyLocation" !== g) && ("returnValue" == g && e.preventDefault || (a[g] = e[g]));
            a.target || (a.target = a.srcElement || document);
            a.relatedTarget = a.fromElement === a.target ? a.toElement : a.fromElement;
            a.preventDefault = function() {
                e.preventDefault && e.preventDefault();
                a.returnValue = l;
                a.Cd = c;
                a.defaultPrevented = f;
            };
            a.Cd = d;
            a.defaultPrevented = l;
            a.stopPropagation = function() {
                e.stopPropagation && e.stopPropagation();
                a.cancelBubble = f;
                a.Jb = c;
            };
            a.Jb = d;
            a.stopImmediatePropagation = function() {
                e.stopImmediatePropagation && e.stopImmediatePropagation();
                a.zc = c;
                a.stopPropagation();
            };
            a.zc = d;
            if (a.clientX != k) {
                g = document.documentElement;
                var h = document.body;
                a.pageX = a.clientX + (g && g.scrollLeft || h && h.scrollLeft || 0) - (g && g.clientLeft || h && h.clientLeft || 0);
                a.pageY = a.clientY + (g && g.scrollTop || h && h.scrollTop || 0) - (g && g.clientTop || h && h.clientTop || 0);
            }
            a.which = a.charCode || a.keyCode;
            a.button != k && (a.button = a.button & 1 ? 0 : a.button & 4 ? 1 : a.button & 2 ? 2 : 0);
        }
        return a;
    };
    t.l = function(a, c) {
        var d = t.uc(a) ? t.getData(a) : {}, e = a.parentNode || a.ownerDocument;
        "string" === typeof c && (c = {
            type: c,
            target: a
        });
        c = t.rc(c);
        d.W && d.W.call(a, c);
        if (e && !c.Jb() && c.bubbles !== l) t.l(e, c); else if (!e && !c.defaultPrevented && (d = t.getData(c.target), 
        c.target[c.type])) {
            d.disabled = f;
            if ("function" === typeof c.target[c.type]) c.target[c.type]();
            d.disabled = l;
        }
        return !c.defaultPrevented;
    };
    t.R = function(a, c, d) {
        function e() {
            t.j(a, c, e);
            d.apply(this, arguments);
        }
        if (t.g.isArray(c)) return u(t.R, a, c, d);
        e.r = d.r = d.r || t.r++;
        t.c(a, c, e);
    };
    function u(a, c, d, e) {
        t.ic.forEach(d, function(d) {
            a(c, d, e);
        });
    }
    var v = Object.prototype.hasOwnProperty;
    t.e = function(a, c) {
        var d;
        c = c || {};
        d = document.createElement(a || "div");
        t.g.X(c, function(a, c) {
            -1 !== a.indexOf("aria-") || "role" == a ? d.setAttribute(a, c) : d[a] = c;
        });
        return d;
    };
    t.ba = function(a) {
        return a.charAt(0).toUpperCase() + a.slice(1);
    };
    t.g = {};
    t.g.create = Object.create || function(a) {
        function c() {}
        c.prototype = a;
        return new c();
    };
    t.g.X = function(a, c, d) {
        for (var e in a) v.call(a, e) && c.call(d || this, e, a[e]);
    };
    t.g.z = function(a, c) {
        if (!c) return a;
        for (var d in c) v.call(c, d) && (a[d] = c[d]);
        return a;
    };
    t.g.qd = function(a, c) {
        var d, e, g;
        a = t.g.copy(a);
        for (d in c) v.call(c, d) && (e = a[d], g = c[d], a[d] = t.g.Ta(e) && t.g.Ta(g) ? t.g.qd(e, g) : c[d]);
        return a;
    };
    t.g.copy = function(a) {
        return t.g.z({}, a);
    };
    t.g.Ta = function(a) {
        return !!a && "object" === typeof a && "[object Object]" === a.toString() && a.constructor === Object;
    };
    t.g.isArray = Array.isArray || function(a) {
        return "[object Array]" === Object.prototype.toString.call(a);
    };
    t.Ed = function(a) {
        return a !== a;
    };
    t.bind = function(a, c, d) {
        function e() {
            return c.apply(a, arguments);
        }
        c.r || (c.r = t.r++);
        e.r = d ? d + "_" + c.r : c.r;
        return e;
    };
    t.va = {};
    t.r = 1;
    t.expando = "vdata" + new Date().getTime();
    t.getData = function(a) {
        var c = a[t.expando];
        c || (c = a[t.expando] = t.r++, t.va[c] = {});
        return t.va[c];
    };
    t.uc = function(a) {
        a = a[t.expando];
        return !(!a || t.Ib(t.va[a]));
    };
    t.Ic = function(a) {
        var c = a[t.expando];
        if (c) {
            delete t.va[c];
            try {
                delete a[t.expando];
            } catch (d) {
                a.removeAttribute ? a.removeAttribute(t.expando) : a[t.expando] = k;
            }
        }
    };
    t.Ib = function(a) {
        for (var c in a) if (a[c] !== k) return l;
        return f;
    };
    t.Sa = function(a, c) {
        return -1 !== (" " + a.className + " ").indexOf(" " + c + " ");
    };
    t.n = function(a, c) {
        t.Sa(a, c) || (a.className = "" === a.className ? c : a.className + " " + c);
    };
    t.p = function(a, c) {
        var d, e;
        if (t.Sa(a, c)) {
            d = a.className.split(" ");
            for (e = d.length - 1; 0 <= e; e--) d[e] === c && d.splice(e, 1);
            a.className = d.join(" ");
        }
    };
    t.A = t.e("video");
    t.O = navigator.userAgent;
    t.bd = /iPhone/i.test(t.O);
    t.ad = /iPad/i.test(t.O);
    t.cd = /iPod/i.test(t.O);
    t.$c = t.bd || t.ad || t.cd;
    var aa = t, x;
    var y = t.O.match(/OS (\d+)_/i);
    x = y && y[1] ? y[1] : b;
    aa.oe = x;
    t.Yc = /Android/i.test(t.O);
    var ba = t, z;
    var A = t.O.match(/Android (\d+)(?:\.(\d+))?(?:\.(\d+))*/i), B, C;
    A ? (B = A[1] && parseFloat(A[1]), C = A[2] && parseFloat(A[2]), z = B && C ? parseFloat(A[1] + "." + A[2]) : B ? B : k) : z = k;
    ba.Ub = z;
    t.dd = t.Yc && /webkit/i.test(t.O) && 2.3 > t.Ub;
    t.Zc = /Firefox/i.test(t.O);
    t.pe = /Chrome/i.test(t.O);
    t.ec = !!("ontouchstart" in window || window.Xc && document instanceof window.Xc);
    t.Wc = "backgroundSize" in t.A.style;
    t.Kc = function(a, c) {
        t.g.X(c, function(c, e) {
            e === k || "undefined" === typeof e || e === l ? a.removeAttribute(c) : a.setAttribute(c, e === f ? "" : e);
        });
    };
    t.Aa = function(a) {
        var c, d, e, g;
        c = {};
        if (a && a.attributes && 0 < a.attributes.length) {
            d = a.attributes;
            for (var h = d.length - 1; 0 <= h; h--) {
                e = d[h].name;
                g = d[h].value;
                if ("boolean" === typeof a[e] || -1 !== ",autoplay,controls,loop,muted,default,".indexOf("," + e + ",")) g = g !== k ? f : l;
                c[e] = g;
            }
        }
        return c;
    };
    t.ve = function(a, c) {
        var d = "";
        document.defaultView && document.defaultView.getComputedStyle ? d = document.defaultView.getComputedStyle(a, "").getPropertyValue(c) : a.currentStyle && (d = a["client" + c.substr(0, 1).toUpperCase() + c.substr(1)] + "px");
        return d;
    };
    t.Hb = function(a, c) {
        c.firstChild ? c.insertBefore(a, c.firstChild) : c.appendChild(a);
    };
    t.Oa = {};
    t.w = function(a) {
        0 === a.indexOf("#") && (a = a.slice(1));
        return document.getElementById(a);
    };
    t.za = function(a, c) {
        c = c || a;
        var d = Math.floor(a % 60), e = Math.floor(a / 60 % 60), g = Math.floor(a / 3600), h = Math.floor(c / 60 % 60), j = Math.floor(c / 3600);
        if (isNaN(a) || Infinity === a) g = e = d = "-";
        g = 0 < g || 0 < j ? g + ":" : "";
        return g + (((g || 10 <= h) && 10 > e ? "0" + e : e) + ":") + (10 > d ? "0" + d : d);
    };
    t.kd = function() {
        document.body.focus();
        document.onselectstart = q(l);
    };
    t.ke = function() {
        document.onselectstart = q(f);
    };
    t.trim = function(a) {
        return (a + "").replace(/^\s+|\s+$/g, "");
    };
    t.round = function(a, c) {
        c || (c = 0);
        return Math.round(a * Math.pow(10, c)) / Math.pow(10, c);
    };
    t.zb = function(a, c) {
        return {
            length: 1,
            start: function() {
                return a;
            },
            end: function() {
                return c;
            }
        };
    };
    t.get = function(a, c, d, e) {
        var g, h, j, n;
        d = d || m();
        "undefined" === typeof XMLHttpRequest && (window.XMLHttpRequest = function() {
            try {
                return new window.ActiveXObject("Msxml2.XMLHTTP.6.0");
            } catch (a) {}
            try {
                return new window.ActiveXObject("Msxml2.XMLHTTP.3.0");
            } catch (c) {}
            try {
                return new window.ActiveXObject("Msxml2.XMLHTTP");
            } catch (d) {}
            throw Error("This browser does not support XMLHttpRequest.");
        });
        h = new XMLHttpRequest();
        j = t.Xd(a);
        n = window.location;
        j.protocol + j.host !== n.protocol + n.host && window.XDomainRequest && !("withCredentials" in h) ? (h = new window.XDomainRequest(), 
        h.onload = function() {
            c(h.responseText);
        }, h.onerror = d, h.onprogress = m(), h.ontimeout = d) : (g = "file:" == j.protocol || "file:" == n.protocol, 
        h.onreadystatechange = function() {
            4 === h.readyState && (200 === h.status || g && 0 === h.status ? c(h.responseText) : d(h.responseText));
        });
        try {
            h.open("GET", a, f), e && (h.withCredentials = f);
        } catch (r) {
            d(r);
            return;
        }
        try {
            h.send();
        } catch (w) {
            d(w);
        }
    };
    t.ae = function(a) {
        try {
            var c = window.localStorage || l;
            c && (c.volume = a);
        } catch (d) {
            22 == d.code || 1014 == d.code ? t.log("LocalStorage Full (VideoJS)", d) : 18 == d.code ? t.log("LocalStorage not allowed (VideoJS)", d) : t.log("LocalStorage Error (VideoJS)", d);
        }
    };
    t.tc = function(a) {
        a.match(/^https?:\/\//) || (a = t.e("div", {
            innerHTML: '<a href="' + a + '">x</a>'
        }).firstChild.href);
        return a;
    };
    t.Xd = function(a) {
        var c, d, e, g;
        g = "protocol hostname port pathname search hash host".split(" ");
        d = t.e("a", {
            href: a
        });
        if (e = "" === d.host && "file:" !== d.protocol) c = t.e("div"), c.innerHTML = '<a href="' + a + '"></a>', 
        d = c.firstChild, c.setAttribute("style", "display:none; position:absolute;"), document.body.appendChild(c);
        a = {};
        for (var h = 0; h < g.length; h++) a[g[h]] = d[g[h]];
        e && document.body.removeChild(c);
        return a;
    };
    function D(a, c) {
        var d, e;
        d = Array.prototype.slice.call(c);
        e = m();
        e = window.console || {
            log: e,
            warn: e,
            error: e
        };
        a ? d.unshift(a.toUpperCase() + ":") : a = "log";
        t.log.history.push(d);
        d.unshift("VIDEOJS:");
        if (e[a].apply) e[a].apply(e, d); else e[a](d.join(" "));
    }
    t.log = function() {
        D(k, arguments);
    };
    t.log.history = [];
    t.log.error = function() {
        D("error", arguments);
    };
    t.log.warn = function() {
        D("warn", arguments);
    };
    t.xd = function(a) {
        var c, d;
        a.getBoundingClientRect && a.parentNode && (c = a.getBoundingClientRect());
        if (!c) return {
            left: 0,
            top: 0
        };
        a = document.documentElement;
        d = document.body;
        return {
            left: t.round(c.left + (window.pageXOffset || d.scrollLeft) - (a.clientLeft || d.clientLeft || 0)),
            top: t.round(c.top + (window.pageYOffset || d.scrollTop) - (a.clientTop || d.clientTop || 0))
        };
    };
    t.ic = {};
    t.ic.forEach = function(a, c, d) {
        if (t.g.isArray(a) && c instanceof Function) for (var e = 0, g = a.length; e < g; ++e) c.call(d || t, a[e], e, a);
        return a;
    };
    t.ga = {};
    t.ga.Va = function(a, c) {
        var d, e, g;
        a = t.g.copy(a);
        for (d in c) c.hasOwnProperty(d) && (e = a[d], g = c[d], a[d] = t.g.Ta(e) && t.g.Ta(g) ? t.ga.Va(e, g) : c[d]);
        return a;
    };
    t.a = t.qa.extend({
        i: function(a, c, d) {
            this.d = a;
            this.m = t.g.copy(this.m);
            c = this.options(c);
            this.L = c.id || c.el && c.el.id;
            this.L || (this.L = (a.id && a.id() || "no_player") + "_component_" + t.r++);
            this.Kd = c.name || k;
            this.b = c.el || this.e();
            this.P = [];
            this.Pa = {};
            this.Qa = {};
            this.wc();
            this.K(d);
            if (c.Jc !== l) {
                var e, g;
                this.k().reportUserActivity && (e = t.bind(this.k(), this.k().reportUserActivity), 
                this.c("touchstart", function() {
                    e();
                    clearInterval(g);
                    g = setInterval(e, 250);
                }), a = function() {
                    e();
                    clearInterval(g);
                }, this.c("touchmove", e), this.c("touchend", a), this.c("touchcancel", a));
            }
        }
    });
    s = t.a.prototype;
    s.dispose = function() {
        this.l({
            type: "dispose",
            bubbles: l
        });
        if (this.P) for (var a = this.P.length - 1; 0 <= a; a--) this.P[a].dispose && this.P[a].dispose();
        this.Qa = this.Pa = this.P = k;
        this.j();
        this.b.parentNode && this.b.parentNode.removeChild(this.b);
        t.Ic(this.b);
        this.b = k;
    };
    s.d = f;
    s.k = p("d");
    s.options = function(a) {
        return a === b ? this.m : this.m = t.ga.Va(this.m, a);
    };
    s.e = function(a, c) {
        return t.e(a, c);
    };
    s.t = function(a) {
        var c = this.d.language(), d = this.d.languages();
        return d && d[c] && d[c][a] ? d[c][a] : a;
    };
    s.w = p("b");
    s.ja = function() {
        return this.v || this.b;
    };
    s.id = p("L");
    s.name = p("Kd");
    s.children = p("P");
    s.zd = function(a) {
        return this.Pa[a];
    };
    s.ka = function(a) {
        return this.Qa[a];
    };
    s.V = function(a, c) {
        var d, e;
        "string" === typeof a ? (e = a, c = c || {}, d = c.componentClass || t.ba(e), c.name = e, 
        d = new window.videojs[d](this.d || this, c)) : d = a;
        this.P.push(d);
        "function" === typeof d.id && (this.Pa[d.id()] = d);
        (e = e || d.name && d.name()) && (this.Qa[e] = d);
        "function" === typeof d.el && d.el() && this.ja().appendChild(d.el());
        return d;
    };
    s.removeChild = function(a) {
        "string" === typeof a && (a = this.ka(a));
        if (a && this.P) {
            for (var c = l, d = this.P.length - 1; 0 <= d; d--) if (this.P[d] === a) {
                c = f;
                this.P.splice(d, 1);
                break;
            }
            c && (this.Pa[a.id] = k, this.Qa[a.name] = k, (c = a.w()) && c.parentNode === this.ja() && this.ja().removeChild(a.w()));
        }
    };
    s.wc = function() {
        var a, c, d, e, g, h;
        a = this;
        c = a.options();
        if (d = c.children) if (h = function(d, e) {
            c[d] !== b && (e = c[d]);
            e !== l && (a[d] = a.V(d, e));
        }, t.g.isArray(d)) for (var j = 0; j < d.length; j++) e = d[j], "string" == typeof e ? (g = e, 
        e = {}) : g = e.name, h(g, e); else t.g.X(d, h);
    };
    s.T = q("");
    s.c = function(a, c, d) {
        var e, g, h;
        "string" === typeof a || t.g.isArray(a) ? t.c(this.b, a, t.bind(this, c)) : (e = t.bind(this, d), 
        h = this, g = function() {
            h.j(a, c, e);
        }, g.r = e.r, this.c("dispose", g), d = function() {
            h.j("dispose", g);
        }, d.r = e.r, a.nodeName ? (t.c(a, c, e), t.c(a, "dispose", d)) : "function" === typeof a.c && (a.c(c, e), 
        a.c("dispose", d)));
        return this;
    };
    s.j = function(a, c, d) {
        !a || "string" === typeof a || t.g.isArray(a) ? t.j(this.b, a, c) : (d = t.bind(this, d), 
        this.j("dispose", d), a.nodeName ? (t.j(a, c, d), t.j(a, "dispose", d)) : (a.j(c, d), 
        a.j("dispose", d)));
        return this;
    };
    s.R = function(a, c, d) {
        var e, g, h;
        "string" === typeof a || t.g.isArray(a) ? t.R(this.b, a, t.bind(this, c)) : (e = t.bind(this, d), 
        g = this, h = function() {
            g.j(a, c, h);
            e.apply(this, arguments);
        }, h.r = e.r, this.c(a, c, h));
        return this;
    };
    s.l = function(a) {
        t.l(this.b, a);
        return this;
    };
    s.K = function(a) {
        a && (this.la ? a.call(this) : (this.ab === b && (this.ab = []), this.ab.push(a)));
        return this;
    };
    s.Ga = function() {
        this.la = f;
        var a = this.ab;
        if (a && 0 < a.length) {
            for (var c = 0, d = a.length; c < d; c++) a[c].call(this);
            this.ab = [];
            this.l("ready");
        }
    };
    s.Sa = function(a) {
        return t.Sa(this.b, a);
    };
    s.n = function(a) {
        t.n(this.b, a);
        return this;
    };
    s.p = function(a) {
        t.p(this.b, a);
        return this;
    };
    s.show = function() {
        this.b.style.display = "block";
        return this;
    };
    s.Y = function() {
        this.b.style.display = "none";
        return this;
    };
    function E(a) {
        a.p("vjs-lock-showing");
    }
    s.disable = function() {
        this.Y();
        this.show = m();
    };
    s.width = function(a, c) {
        return F(this, "width", a, c);
    };
    s.height = function(a, c) {
        return F(this, "height", a, c);
    };
    s.td = function(a, c) {
        return this.width(a, f).height(c);
    };
    function F(a, c, d, e) {
        if (d !== b) {
            if (d === k || t.Ed(d)) d = 0;
            a.b.style[c] = -1 !== ("" + d).indexOf("%") || -1 !== ("" + d).indexOf("px") ? d : "auto" === d ? "" : d + "px";
            e || a.l("resize");
            return a;
        }
        if (!a.b) return 0;
        d = a.b.style[c];
        e = d.indexOf("px");
        return -1 !== e ? parseInt(d.slice(0, e), 10) : parseInt(a.b["offset" + t.ba(c)], 10);
    }
    function G(a) {
        var c, d, e, g, h, j, n, r;
        c = 0;
        d = k;
        a.c("touchstart", function(a) {
            1 === a.touches.length && (d = a.touches[0], c = new Date().getTime(), g = f);
        });
        a.c("touchmove", function(a) {
            1 < a.touches.length ? g = l : d && (j = a.touches[0].pageX - d.pageX, n = a.touches[0].pageY - d.pageY, 
            r = Math.sqrt(j * j + n * n), 22 < r && (g = l));
        });
        h = function() {
            g = l;
        };
        a.c("touchleave", h);
        a.c("touchcancel", h);
        a.c("touchend", function(a) {
            d = k;
            g === f && (e = new Date().getTime() - c, 250 > e && (a.preventDefault(), this.l("tap")));
        });
    }
    t.u = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
            G(this);
            this.c("tap", this.s);
            this.c("click", this.s);
            this.c("focus", this.Ya);
            this.c("blur", this.Xa);
        }
    });
    s = t.u.prototype;
    s.e = function(a, c) {
        var d;
        c = t.g.z({
            className: this.T(),
            role: "button",
            "aria-live": "polite",
            tabIndex: 0
        }, c);
        d = t.a.prototype.e.call(this, a, c);
        c.innerHTML || (this.v = t.e("div", {
            className: "vjs-control-content"
        }), this.xb = t.e("span", {
            className: "vjs-control-text",
            innerHTML: this.t(this.ua) || "Need Text"
        }), this.v.appendChild(this.xb), d.appendChild(this.v));
        return d;
    };
    s.T = function() {
        return "vjs-control " + t.a.prototype.T.call(this);
    };
    s.s = m();
    s.Ya = function() {
        t.c(document, "keydown", t.bind(this, this.Z));
    };
    s.Z = function(a) {
        if (32 == a.which || 13 == a.which) a.preventDefault(), this.s();
    };
    s.Xa = function() {
        t.j(document, "keydown", t.bind(this, this.Z));
    };
    t.S = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
            this.jd = this.ka(this.m.barName);
            this.handle = this.ka(this.m.handleName);
            this.c("mousedown", this.Za);
            this.c("touchstart", this.Za);
            this.c("focus", this.Ya);
            this.c("blur", this.Xa);
            this.c("click", this.s);
            this.c(a, "controlsvisible", this.update);
            this.c(a, this.Ec, this.update);
            this.F = {};
            this.F.move = t.bind(this, this.$a);
            this.F.end = t.bind(this, this.Mb);
        }
    });
    s = t.S.prototype;
    s.dispose = function() {
        t.j(document, "mousemove", this.F.move, l);
        t.j(document, "mouseup", this.F.end, l);
        t.j(document, "touchmove", this.F.move, l);
        t.j(document, "touchend", this.F.end, l);
        t.j(document, "keyup", t.bind(this, this.Z));
        t.a.prototype.dispose.call(this);
    };
    s.e = function(a, c) {
        c = c || {};
        c.className += " vjs-slider";
        c = t.g.z({
            role: "slider",
            "aria-valuenow": 0,
            "aria-valuemin": 0,
            "aria-valuemax": 100,
            tabIndex: 0
        }, c);
        return t.a.prototype.e.call(this, a, c);
    };
    s.Za = function(a) {
        a.preventDefault();
        t.kd();
        this.n("vjs-sliding");
        t.c(document, "mousemove", this.F.move);
        t.c(document, "mouseup", this.F.end);
        t.c(document, "touchmove", this.F.move);
        t.c(document, "touchend", this.F.end);
        this.$a(a);
    };
    s.$a = m();
    s.Mb = function() {
        t.ke();
        this.p("vjs-sliding");
        t.j(document, "mousemove", this.F.move, l);
        t.j(document, "mouseup", this.F.end, l);
        t.j(document, "touchmove", this.F.move, l);
        t.j(document, "touchend", this.F.end, l);
        this.update();
    };
    s.update = function() {
        if (this.b) {
            var a, c = this.Gb(), d = this.handle, e = this.jd;
            isNaN(c) && (c = 0);
            a = c;
            if (d) {
                a = this.b.offsetWidth;
                var g = d.w().offsetWidth;
                a = g ? g / a : 0;
                c *= 1 - a;
                a = c + a / 2;
                d.w().style.left = t.round(100 * c, 2) + "%";
            }
            e && (e.w().style.width = t.round(100 * a, 2) + "%");
        }
    };
    function H(a, c) {
        var d, e, g, h;
        d = a.b;
        e = t.xd(d);
        h = g = d.offsetWidth;
        d = a.handle;
        if (a.options().vertical) return h = e.top, e = c.changedTouches ? c.changedTouches[0].pageY : c.pageY, 
        d && (d = d.w().offsetHeight, h += d / 2, g -= d), Math.max(0, Math.min(1, (h - e + g) / g));
        g = e.left;
        e = c.changedTouches ? c.changedTouches[0].pageX : c.pageX;
        d && (d = d.w().offsetWidth, g += d / 2, h -= d);
        return Math.max(0, Math.min(1, (e - g) / h));
    }
    s.Ya = function() {
        t.c(document, "keyup", t.bind(this, this.Z));
    };
    s.Z = function(a) {
        if (37 == a.which || 40 == a.which) a.preventDefault(), this.Nc(); else if (38 == a.which || 39 == a.which) a.preventDefault(), 
        this.Oc();
    };
    s.Xa = function() {
        t.j(document, "keyup", t.bind(this, this.Z));
    };
    s.s = function(a) {
        a.stopImmediatePropagation();
        a.preventDefault();
    };
    t.$ = t.a.extend();
    t.$.prototype.defaultValue = 0;
    t.$.prototype.e = function(a, c) {
        c = c || {};
        c.className += " vjs-slider-handle";
        c = t.g.z({
            innerHTML: '<span class="vjs-control-text">' + this.defaultValue + "</span>"
        }, c);
        return t.a.prototype.e.call(this, "div", c);
    };
    t.ha = t.a.extend();
    function ca(a, c) {
        a.V(c);
        c.c("click", t.bind(a, function() {
            E(this);
        }));
    }
    t.ha.prototype.e = function() {
        var a = this.options().lc || "ul";
        this.v = t.e(a, {
            className: "vjs-menu-content"
        });
        a = t.a.prototype.e.call(this, "div", {
            append: this.v,
            className: "vjs-menu"
        });
        a.appendChild(this.v);
        t.c(a, "click", function(a) {
            a.preventDefault();
            a.stopImmediatePropagation();
        });
        return a;
    };
    t.J = t.u.extend({
        i: function(a, c) {
            t.u.call(this, a, c);
            this.selected(c.selected);
        }
    });
    t.J.prototype.e = function(a, c) {
        return t.u.prototype.e.call(this, "li", t.g.z({
            className: "vjs-menu-item",
            innerHTML: this.t(this.m.label)
        }, c));
    };
    t.J.prototype.s = function() {
        this.selected(f);
    };
    t.J.prototype.selected = function(a) {
        a ? (this.n("vjs-selected"), this.b.setAttribute("aria-selected", f)) : (this.p("vjs-selected"), 
        this.b.setAttribute("aria-selected", l));
    };
    t.N = t.u.extend({
        i: function(a, c) {
            t.u.call(this, a, c);
            this.Ba = this.xa();
            this.V(this.Ba);
            this.Q && 0 === this.Q.length && this.Y();
            this.c("keyup", this.Z);
            this.b.setAttribute("aria-haspopup", f);
            this.b.setAttribute("role", "button");
        }
    });
    s = t.N.prototype;
    s.ta = l;
    s.xa = function() {
        var a = new t.ha(this.d);
        this.options().title && a.ja().appendChild(t.e("li", {
            className: "vjs-menu-title",
            innerHTML: t.ba(this.options().title),
            he: -1
        }));
        if (this.Q = this.createItems()) for (var c = 0; c < this.Q.length; c++) ca(a, this.Q[c]);
        return a;
    };
    s.wa = m();
    s.T = function() {
        return this.className + " vjs-menu-button " + t.u.prototype.T.call(this);
    };
    s.Ya = m();
    s.Xa = m();
    s.s = function() {
        this.R("mouseout", t.bind(this, function() {
            E(this.Ba);
            this.b.blur();
        }));
        this.ta ? I(this) : J(this);
    };
    s.Z = function(a) {
        a.preventDefault();
        32 == a.which || 13 == a.which ? this.ta ? I(this) : J(this) : 27 == a.which && this.ta && I(this);
    };
    function J(a) {
        a.ta = f;
        a.Ba.n("vjs-lock-showing");
        a.b.setAttribute("aria-pressed", f);
        a.Q && 0 < a.Q.length && a.Q[0].w().focus();
    }
    function I(a) {
        a.ta = l;
        E(a.Ba);
        a.b.setAttribute("aria-pressed", l);
    }
    t.D = function(a) {
        "number" === typeof a ? this.code = a : "string" === typeof a ? this.message = a : "object" === typeof a && t.g.z(this, a);
        this.message || (this.message = t.D.rd[this.code] || "");
    };
    t.D.prototype.code = 0;
    t.D.prototype.message = "";
    t.D.prototype.status = k;
    t.D.Ra = "MEDIA_ERR_CUSTOM MEDIA_ERR_ABORTED MEDIA_ERR_NETWORK MEDIA_ERR_DECODE MEDIA_ERR_SRC_NOT_SUPPORTED MEDIA_ERR_ENCRYPTED".split(" ");
    t.D.rd = {
        1: "You aborted the video playback",
        2: "A network error caused the video download to fail part-way.",
        3: "The video playback was aborted due to a corruption problem or because the video used features your browser did not support.",
        4: "The video could not be loaded, either because the server or network failed or because the format is not supported.",
        5: "The video is encrypted and we do not have the keys to decrypt it."
    };
    for (var K = 0; K < t.D.Ra.length; K++) t.D[t.D.Ra[K]] = K, t.D.prototype[t.D.Ra[K]] = K;
    var L, M, N, O;
    L = [ "requestFullscreen exitFullscreen fullscreenElement fullscreenEnabled fullscreenchange fullscreenerror".split(" "), "webkitRequestFullscreen webkitExitFullscreen webkitFullscreenElement webkitFullscreenEnabled webkitfullscreenchange webkitfullscreenerror".split(" "), "webkitRequestFullScreen webkitCancelFullScreen webkitCurrentFullScreenElement webkitCancelFullScreen webkitfullscreenchange webkitfullscreenerror".split(" "), "mozRequestFullScreen mozCancelFullScreen mozFullScreenElement mozFullScreenEnabled mozfullscreenchange mozfullscreenerror".split(" "), "msRequestFullscreen msExitFullscreen msFullscreenElement msFullscreenEnabled MSFullscreenChange MSFullscreenError".split(" ") ];
    M = L[0];
    for (O = 0; O < L.length; O++) if (L[O][1] in document) {
        N = L[O];
        break;
    }
    if (N) {
        t.Oa.Fb = {};
        for (O = 0; O < N.length; O++) t.Oa.Fb[M[O]] = N[O];
    }
    t.Player = t.a.extend({
        i: function(a, c, d) {
            this.I = a;
            a.id = a.id || "vjs_video_" + t.r++;
            this.ie = a && t.Aa(a);
            c = t.g.z(da(a), c);
            this.Ua = c.language || t.options.language;
            this.Id = c.languages || t.options.languages;
            this.G = {};
            this.Fc = c.poster || "";
            this.yb = !!c.controls;
            a.controls = l;
            c.Jc = l;
            P(this, "audio" === this.I.nodeName.toLowerCase());
            t.a.call(this, this, c, d);
            this.controls() ? this.n("vjs-controls-enabled") : this.n("vjs-controls-disabled");
            P(this) && this.n("vjs-audio");
            t.Ca[this.L] = this;
            c.plugins && t.g.X(c.plugins, function(a, c) {
                this[a](c);
            }, this);
            var e, g, h, j, n, r;
            e = t.bind(this, this.reportUserActivity);
            this.c("mousedown", function() {
                e();
                clearInterval(g);
                g = setInterval(e, 250);
            });
            this.c("mousemove", function(a) {
                if (a.screenX != n || a.screenY != r) n = a.screenX, r = a.screenY, e();
            });
            this.c("mouseup", function() {
                e();
                clearInterval(g);
            });
            this.c("keydown", e);
            this.c("keyup", e);
            h = setInterval(t.bind(this, function() {
                if (this.pa) {
                    this.pa = l;
                    this.userActive(f);
                    clearTimeout(j);
                    var a = this.options().inactivityTimeout;
                    0 < a && (j = setTimeout(t.bind(this, function() {
                        this.pa || this.userActive(l);
                    }), a));
                }
            }), 250);
            this.c("dispose", function() {
                clearInterval(h);
                clearTimeout(j);
            });
        }
    });
    s = t.Player.prototype;
    s.language = function(a) {
        if (a === b) return this.Ua;
        this.Ua = a;
        return this;
    };
    s.languages = p("Id");
    s.m = t.options;
    s.dispose = function() {
        this.l("dispose");
        this.j("dispose");
        t.Ca[this.L] = k;
        this.I && this.I.player && (this.I.player = k);
        this.b && this.b.player && (this.b.player = k);
        this.o && this.o.dispose();
        t.a.prototype.dispose.call(this);
    };
    function da(a) {
        var c, d, e = {
            sources: [],
            tracks: []
        };
        c = t.Aa(a);
        d = c["data-setup"];
        d !== k && t.g.z(c, t.JSON.parse(d || "{}"));
        t.g.z(e, c);
        if (a.hasChildNodes()) {
            var g, h;
            a = a.childNodes;
            g = 0;
            for (h = a.length; g < h; g++) c = a[g], d = c.nodeName.toLowerCase(), "source" === d ? e.sources.push(t.Aa(c)) : "track" === d && e.tracks.push(t.Aa(c));
        }
        return e;
    }
    s.e = function() {
        var a = this.b = t.a.prototype.e.call(this, "div"), c = this.I, d;
        c.removeAttribute("width");
        c.removeAttribute("height");
        if (c.hasChildNodes()) {
            var e, g, h, j, n;
            e = c.childNodes;
            g = e.length;
            for (n = []; g--; ) h = e[g], j = h.nodeName.toLowerCase(), "track" === j && n.push(h);
            for (e = 0; e < n.length; e++) c.removeChild(n[e]);
        }
        d = t.Aa(c);
        t.g.X(d, function(c) {
            "class" == c ? a.className = d[c] : a.setAttribute(c, d[c]);
        });
        c.id += "_html5_api";
        c.className = "vjs-tech";
        c.player = a.player = this;
        this.n("vjs-paused");
        this.width(this.m.width, f);
        this.height(this.m.height, f);
        c.Bd = c.networkState;
        c.parentNode && c.parentNode.insertBefore(a, c);
        t.Hb(c, a);
        this.b = a;
        this.c("loadstart", this.Pd);
        this.c("waiting", this.Vd);
        this.c([ "canplay", "canplaythrough", "playing", "ended" ], this.Ud);
        this.c("seeking", this.Sd);
        this.c("seeked", this.Rd);
        this.c("ended", this.Ld);
        this.c("play", this.Ob);
        this.c("firstplay", this.Nd);
        this.c("pause", this.Nb);
        this.c("progress", this.Qd);
        this.c("durationchange", this.Cc);
        this.c("fullscreenchange", this.Od);
        return a;
    };
    function Q(a, c, d) {
        a.o && (a.la = l, a.o.dispose(), a.o = l);
        "Html5" !== c && a.I && (t.h.Bb(a.I), a.I = k);
        a.eb = c;
        a.la = l;
        var e = t.g.z({
            source: d,
            parentEl: a.b
        }, a.m[c.toLowerCase()]);
        d && (a.nc = d.type, d.src == a.G.src && 0 < a.G.currentTime && (e.startTime = a.G.currentTime), 
        a.G.src = d.src);
        a.o = new window.videojs[c](a, e);
        a.o.K(function() {
            this.d.Ga();
        });
    }
    s.Pd = function() {
        this.error(k);
        this.paused() ? (R(this, l), this.R("play", function() {
            R(this, f);
        })) : this.l("firstplay");
    };
    s.vc = l;
    function R(a, c) {
        c !== b && a.vc !== c && ((a.vc = c) ? (a.n("vjs-has-started"), a.l("firstplay")) : a.p("vjs-has-started"));
    }
    s.Ob = function() {
        this.p("vjs-paused");
        this.n("vjs-playing");
    };
    s.Vd = function() {
        this.n("vjs-waiting");
    };
    s.Ud = function() {
        this.p("vjs-waiting");
    };
    s.Sd = function() {
        this.n("vjs-seeking");
    };
    s.Rd = function() {
        this.p("vjs-seeking");
    };
    s.Nd = function() {
        this.m.starttime && this.currentTime(this.m.starttime);
        this.n("vjs-has-started");
    };
    s.Nb = function() {
        this.p("vjs-playing");
        this.n("vjs-paused");
    };
    s.Qd = function() {
        1 == this.bufferedPercent() && this.l("loadedalldata");
    };
    s.Ld = function() {
        this.m.loop ? (this.currentTime(0), this.play()) : this.paused() || this.pause();
    };
    s.Cc = function() {
        var a = S(this, "duration");
        a && (0 > a && (a = Infinity), this.duration(a), Infinity === a ? this.n("vjs-live") : this.p("vjs-live"));
    };
    s.Od = function() {
        this.isFullscreen() ? this.n("vjs-fullscreen") : this.p("vjs-fullscreen");
    };
    function T(a, c, d) {
        if (a.o && !a.o.la) a.o.K(function() {
            this[c](d);
        }); else try {
            a.o[c](d);
        } catch (e) {
            throw t.log(e), e;
        }
    }
    function S(a, c) {
        if (a.o && a.o.la) try {
            return a.o[c]();
        } catch (d) {
            throw a.o[c] === b ? t.log("Video.js: " + c + " method not defined for " + a.eb + " playback technology.", d) : "TypeError" == d.name ? (t.log("Video.js: " + c + " unavailable on " + a.eb + " playback technology element.", d), 
            a.o.la = l) : t.log(d), d;
        }
    }
    s.play = function() {
        T(this, "play");
        return this;
    };
    s.pause = function() {
        T(this, "pause");
        return this;
    };
    s.paused = function() {
        return S(this, "paused") === l ? l : f;
    };
    s.currentTime = function(a) {
        return a !== b ? (T(this, "setCurrentTime", a), this) : this.G.currentTime = S(this, "currentTime") || 0;
    };
    s.duration = function(a) {
        if (a !== b) return this.G.duration = parseFloat(a), this;
        this.G.duration === b && this.Cc();
        return this.G.duration || 0;
    };
    s.remainingTime = function() {
        return this.duration() - this.currentTime();
    };
    s.buffered = function() {
        var a = S(this, "buffered");
        if (!a || !a.length) a = t.zb(0, 0);
        return a;
    };
    s.bufferedPercent = function() {
        var a = this.duration(), c = this.buffered(), d = 0, e, g;
        if (!a) return 0;
        for (var h = 0; h < c.length; h++) e = c.start(h), g = c.end(h), g > a && (g = a), 
        d += g - e;
        return d / a;
    };
    s.volume = function(a) {
        if (a !== b) return a = Math.max(0, Math.min(1, parseFloat(a))), this.G.volume = a, 
        T(this, "setVolume", a), t.ae(a), this;
        a = parseFloat(S(this, "volume"));
        return isNaN(a) ? 1 : a;
    };
    s.muted = function(a) {
        return a !== b ? (T(this, "setMuted", a), this) : S(this, "muted") || l;
    };
    s.Ea = function() {
        return S(this, "supportsFullScreen") || l;
    };
    s.yc = l;
    s.isFullscreen = function(a) {
        return a !== b ? (this.yc = !!a, this) : this.yc;
    };
    s.isFullScreen = function(a) {
        t.log.warn('player.isFullScreen() has been deprecated, use player.isFullscreen() with a lowercase "s")');
        return this.isFullscreen(a);
    };
    s.requestFullscreen = function() {
        var a = t.Oa.Fb;
        this.isFullscreen(f);
        a ? (t.c(document, a.fullscreenchange, t.bind(this, function(c) {
            this.isFullscreen(document[a.fullscreenElement]);
            this.isFullscreen() === l && t.j(document, a.fullscreenchange, arguments.callee);
            this.l("fullscreenchange");
        })), this.b[a.requestFullscreen]()) : this.o.Ea() ? T(this, "enterFullScreen") : (this.qc(), 
        this.l("fullscreenchange"));
        return this;
    };
    s.requestFullScreen = function() {
        t.log.warn('player.requestFullScreen() has been deprecated, use player.requestFullscreen() with a lowercase "s")');
        return this.requestFullscreen();
    };
    s.exitFullscreen = function() {
        var a = t.Oa.Fb;
        this.isFullscreen(l);
        if (a) document[a.exitFullscreen](); else this.o.Ea() ? T(this, "exitFullScreen") : (this.Cb(), 
        this.l("fullscreenchange"));
        return this;
    };
    s.cancelFullScreen = function() {
        t.log.warn("player.cancelFullScreen() has been deprecated, use player.exitFullscreen()");
        return this.exitFullscreen();
    };
    s.qc = function() {
        this.Dd = f;
        this.ud = document.documentElement.style.overflow;
        t.c(document, "keydown", t.bind(this, this.sc));
        document.documentElement.style.overflow = "hidden";
        t.n(document.body, "vjs-full-window");
        this.l("enterFullWindow");
    };
    s.sc = function(a) {
        27 === a.keyCode && (this.isFullscreen() === f ? this.exitFullscreen() : this.Cb());
    };
    s.Cb = function() {
        this.Dd = l;
        t.j(document, "keydown", this.sc);
        document.documentElement.style.overflow = this.ud;
        t.p(document.body, "vjs-full-window");
        this.l("exitFullWindow");
    };
    s.selectSource = function(a) {
        for (var c = 0, d = this.m.techOrder; c < d.length; c++) {
            var e = t.ba(d[c]), g = window.videojs[e];
            if (g) {
                if (g.isSupported()) for (var h = 0, j = a; h < j.length; h++) {
                    var n = j[h];
                    if (g.canPlaySource(n)) return {
                        source: n,
                        o: e
                    };
                }
            } else t.log.error('The "' + e + '" tech is undefined. Skipped browser support check for that tech.');
        }
        return l;
    };
    s.src = function(a) {
        if (a === b) return S(this, "src");
        t.g.isArray(a) ? U(this, a) : "string" === typeof a ? this.src({
            src: a
        }) : a instanceof Object && (a.type && !window.videojs[this.eb].canPlaySource(a) ? U(this, [ a ]) : (this.G.src = a.src, 
        this.nc = a.type || "", this.K(function() {
            T(this, "src", a.src);
            "auto" == this.m.preload && this.load();
            this.m.autoplay && this.play();
        })));
        return this;
    };
    function U(a, c) {
        var d = a.selectSource(c), e;
        d ? d.o === a.eb ? a.src(d.source) : Q(a, d.o, d.source) : (e = setTimeout(t.bind(a, function() {
            this.error({
                code: 4,
                message: this.t(this.options().notSupportedMessage)
            });
        }), 0), a.Ga(), a.c("dispose", function() {
            clearTimeout(e);
        }));
    }
    s.load = function() {
        T(this, "load");
        return this;
    };
    s.currentSrc = function() {
        return S(this, "currentSrc") || this.G.src || "";
    };
    s.pd = function() {
        return this.nc || "";
    };
    s.Da = function(a) {
        return a !== b ? (T(this, "setPreload", a), this.m.preload = a, this) : S(this, "preload");
    };
    s.autoplay = function(a) {
        return a !== b ? (T(this, "setAutoplay", a), this.m.autoplay = a, this) : S(this, "autoplay");
    };
    s.loop = function(a) {
        return a !== b ? (T(this, "setLoop", a), this.m.loop = a, this) : S(this, "loop");
    };
    s.poster = function(a) {
        if (a === b) return this.Fc;
        a || (a = "");
        this.Fc = a;
        T(this, "setPoster", a);
        this.l("posterchange");
        return this;
    };
    s.controls = function(a) {
        return a !== b ? (a = !!a, this.yb !== a && ((this.yb = a) ? (this.p("vjs-controls-disabled"), 
        this.n("vjs-controls-enabled"), this.l("controlsenabled")) : (this.p("vjs-controls-enabled"), 
        this.n("vjs-controls-disabled"), this.l("controlsdisabled"))), this) : this.yb;
    };
    t.Player.prototype.Tb;
    s = t.Player.prototype;
    s.usingNativeControls = function(a) {
        return a !== b ? (a = !!a, this.Tb !== a && ((this.Tb = a) ? (this.n("vjs-using-native-controls"), 
        this.l("usingnativecontrols")) : (this.p("vjs-using-native-controls"), this.l("usingcustomcontrols"))), 
        this) : this.Tb;
    };
    s.da = k;
    s.error = function(a) {
        if (a === b) return this.da;
        if (a === k) return this.da = a, this.p("vjs-error"), this;
        this.da = a instanceof t.D ? a : new t.D(a);
        this.l("error");
        this.n("vjs-error");
        t.log.error("(CODE:" + this.da.code + " " + t.D.Ra[this.da.code] + ")", this.da.message, this.da);
        return this;
    };
    s.ended = function() {
        return S(this, "ended");
    };
    s.seeking = function() {
        return S(this, "seeking");
    };
    s.pa = f;
    s.reportUserActivity = function() {
        this.pa = f;
    };
    s.Sb = f;
    s.userActive = function(a) {
        return a !== b ? (a = !!a, a !== this.Sb && ((this.Sb = a) ? (this.pa = f, this.p("vjs-user-inactive"), 
        this.n("vjs-user-active"), this.l("useractive")) : (this.pa = l, this.o && this.o.R("mousemove", function(a) {
            a.stopPropagation();
            a.preventDefault();
        }), this.p("vjs-user-active"), this.n("vjs-user-inactive"), this.l("userinactive"))), 
        this) : this.Sb;
    };
    s.playbackRate = function(a) {
        return a !== b ? (T(this, "setPlaybackRate", a), this) : this.o && this.o.featuresPlaybackRate ? S(this, "playbackRate") : 1;
    };
    s.xc = l;
    function P(a, c) {
        return c !== b ? (a.xc = !!c, a) : a.xc;
    }
    t.Ja = t.a.extend();
    t.Ja.prototype.m = {
        we: "play",
        children: {
            playToggle: {},
            currentTimeDisplay: {},
            timeDivider: {},
            durationDisplay: {},
            remainingTimeDisplay: {},
            liveDisplay: {},
            progressControl: {},
            fullscreenToggle: {},
            volumeControl: {},
            muteToggle: {},
            playbackRateMenuButton: {}
        }
    };
    t.Ja.prototype.e = function() {
        return t.e("div", {
            className: "vjs-control-bar"
        });
    };
    t.Yb = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
        }
    });
    t.Yb.prototype.e = function() {
        var a = t.a.prototype.e.call(this, "div", {
            className: "vjs-live-controls vjs-control"
        });
        this.v = t.e("div", {
            className: "vjs-live-display",
            innerHTML: '<span class="vjs-control-text">' + this.t("Stream Type") + "</span>" + this.t("LIVE"),
            "aria-live": "off"
        });
        a.appendChild(this.v);
        return a;
    };
    t.ac = t.u.extend({
        i: function(a, c) {
            t.u.call(this, a, c);
            this.c(a, "play", this.Ob);
            this.c(a, "pause", this.Nb);
        }
    });
    s = t.ac.prototype;
    s.ua = "Play";
    s.T = function() {
        return "vjs-play-control " + t.u.prototype.T.call(this);
    };
    s.s = function() {
        this.d.paused() ? this.d.play() : this.d.pause();
    };
    s.Ob = function() {
        this.p("vjs-paused");
        this.n("vjs-playing");
        this.b.children[0].children[0].innerHTML = this.t("Pause");
    };
    s.Nb = function() {
        this.p("vjs-playing");
        this.n("vjs-paused");
        this.b.children[0].children[0].innerHTML = this.t("Play");
    };
    t.hb = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
            this.c(a, "timeupdate", this.fa);
        }
    });
    t.hb.prototype.e = function() {
        var a = t.a.prototype.e.call(this, "div", {
            className: "vjs-current-time vjs-time-controls vjs-control"
        });
        this.v = t.e("div", {
            className: "vjs-current-time-display",
            innerHTML: '<span class="vjs-control-text">Current Time </span>0:00',
            "aria-live": "off"
        });
        a.appendChild(this.v);
        return a;
    };
    t.hb.prototype.fa = function() {
        var a = this.d.bb ? this.d.G.currentTime : this.d.currentTime();
        this.v.innerHTML = '<span class="vjs-control-text">' + this.t("Current Time") + "</span> " + t.za(a, this.d.duration());
    };
    t.ib = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
            this.c(a, "timeupdate", this.fa);
        }
    });
    t.ib.prototype.e = function() {
        var a = t.a.prototype.e.call(this, "div", {
            className: "vjs-duration vjs-time-controls vjs-control"
        });
        this.v = t.e("div", {
            className: "vjs-duration-display",
            innerHTML: '<span class="vjs-control-text">' + this.t("Duration Time") + "</span> 0:00",
            "aria-live": "off"
        });
        a.appendChild(this.v);
        return a;
    };
    t.ib.prototype.fa = function() {
        var a = this.d.duration();
        a && (this.v.innerHTML = '<span class="vjs-control-text">' + this.t("Duration Time") + "</span> " + t.za(a));
    };
    t.gc = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
        }
    });
    t.gc.prototype.e = function() {
        return t.a.prototype.e.call(this, "div", {
            className: "vjs-time-divider",
            innerHTML: "<div><span>/</span></div>"
        });
    };
    t.pb = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
            this.c(a, "timeupdate", this.fa);
        }
    });
    t.pb.prototype.e = function() {
        var a = t.a.prototype.e.call(this, "div", {
            className: "vjs-remaining-time vjs-time-controls vjs-control"
        });
        this.v = t.e("div", {
            className: "vjs-remaining-time-display",
            innerHTML: '<span class="vjs-control-text">' + this.t("Remaining Time") + "</span> -0:00",
            "aria-live": "off"
        });
        a.appendChild(this.v);
        return a;
    };
    t.pb.prototype.fa = function() {
        this.d.duration() && (this.v.innerHTML = '<span class="vjs-control-text">' + this.t("Remaining Time") + "</span> -" + t.za(this.d.remainingTime()));
    };
    t.Ka = t.u.extend({
        i: function(a, c) {
            t.u.call(this, a, c);
        }
    });
    t.Ka.prototype.ua = "Fullscreen";
    t.Ka.prototype.T = function() {
        return "vjs-fullscreen-control " + t.u.prototype.T.call(this);
    };
    t.Ka.prototype.s = function() {
        this.d.isFullscreen() ? (this.d.exitFullscreen(), this.xb.innerHTML = this.t("Fullscreen")) : (this.d.requestFullscreen(), 
        this.xb.innerHTML = this.t("Non-Fullscreen"));
    };
    t.ob = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
        }
    });
    t.ob.prototype.m = {
        children: {
            seekBar: {}
        }
    };
    t.ob.prototype.e = function() {
        return t.a.prototype.e.call(this, "div", {
            className: "vjs-progress-control vjs-control"
        });
    };
    t.cc = t.S.extend({
        i: function(a, c) {
            t.S.call(this, a, c);
            this.c(a, "timeupdate", this.oa);
            a.K(t.bind(this, this.oa));
        }
    });
    s = t.cc.prototype;
    s.m = {
        children: {
            loadProgressBar: {},
            playProgressBar: {},
            seekHandle: {}
        },
        barName: "playProgressBar",
        handleName: "seekHandle"
    };
    s.Ec = "timeupdate";
    s.e = function() {
        return t.S.prototype.e.call(this, "div", {
            className: "vjs-progress-holder",
            "aria-label": "video progress bar"
        });
    };
    s.oa = function() {
        var a = this.d.bb ? this.d.G.currentTime : this.d.currentTime();
        this.b.setAttribute("aria-valuenow", t.round(100 * this.Gb(), 2));
        this.b.setAttribute("aria-valuetext", t.za(a, this.d.duration()));
    };
    s.Gb = function() {
        return this.d.currentTime() / this.d.duration();
    };
    s.Za = function(a) {
        t.S.prototype.Za.call(this, a);
        this.d.bb = f;
        this.me = !this.d.paused();
        this.d.pause();
    };
    s.$a = function(a) {
        a = H(this, a) * this.d.duration();
        a == this.d.duration() && (a -= .1);
        this.d.currentTime(a);
    };
    s.Mb = function(a) {
        t.S.prototype.Mb.call(this, a);
        this.d.bb = l;
        this.me && this.d.play();
    };
    s.Oc = function() {
        this.d.currentTime(this.d.currentTime() + 5);
    };
    s.Nc = function() {
        this.d.currentTime(this.d.currentTime() - 5);
    };
    t.lb = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
            this.c(a, "progress", this.update);
        }
    });
    t.lb.prototype.e = function() {
        return t.a.prototype.e.call(this, "div", {
            className: "vjs-load-progress",
            innerHTML: '<span class="vjs-control-text"><span>' + this.t("Loaded") + "</span>: 0%</span>"
        });
    };
    t.lb.prototype.update = function() {
        var a, c, d, e, g = this.d.buffered();
        a = this.d.duration();
        var h, j = this.d;
        h = j.buffered();
        j = j.duration();
        h = h.end(h.length - 1);
        h > j && (h = j);
        j = this.b.children;
        this.b.style.width = 100 * (h / a || 0) + "%";
        for (a = 0; a < g.length; a++) c = g.start(a), d = g.end(a), (e = j[a]) || (e = this.b.appendChild(t.e())), 
        e.style.left = 100 * (c / h || 0) + "%", e.style.width = 100 * ((d - c) / h || 0) + "%";
        for (a = j.length; a > g.length; a--) this.b.removeChild(j[a - 1]);
    };
    t.$b = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
        }
    });
    t.$b.prototype.e = function() {
        return t.a.prototype.e.call(this, "div", {
            className: "vjs-play-progress",
            innerHTML: '<span class="vjs-control-text"><span>' + this.t("Progress") + "</span>: 0%</span>"
        });
    };
    t.La = t.$.extend({
        i: function(a, c) {
            t.$.call(this, a, c);
            this.c(a, "timeupdate", this.fa);
        }
    });
    t.La.prototype.defaultValue = "00:00";
    t.La.prototype.e = function() {
        return t.$.prototype.e.call(this, "div", {
            className: "vjs-seek-handle",
            "aria-live": "off"
        });
    };
    t.La.prototype.fa = function() {
        var a = this.d.bb ? this.d.G.currentTime : this.d.currentTime();
        this.b.innerHTML = '<span class="vjs-control-text">' + t.za(a, this.d.duration()) + "</span>";
    };
    t.rb = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
            a.o && a.o.featuresVolumeControl === l && this.n("vjs-hidden");
            this.c(a, "loadstart", function() {
                a.o.featuresVolumeControl === l ? this.n("vjs-hidden") : this.p("vjs-hidden");
            });
        }
    });
    t.rb.prototype.m = {
        children: {
            volumeBar: {}
        }
    };
    t.rb.prototype.e = function() {
        return t.a.prototype.e.call(this, "div", {
            className: "vjs-volume-control vjs-control"
        });
    };
    t.qb = t.S.extend({
        i: function(a, c) {
            t.S.call(this, a, c);
            this.c(a, "volumechange", this.oa);
            a.K(t.bind(this, this.oa));
        }
    });
    s = t.qb.prototype;
    s.oa = function() {
        this.b.setAttribute("aria-valuenow", t.round(100 * this.d.volume(), 2));
        this.b.setAttribute("aria-valuetext", t.round(100 * this.d.volume(), 2) + "%");
    };
    s.m = {
        children: {
            volumeLevel: {},
            volumeHandle: {}
        },
        barName: "volumeLevel",
        handleName: "volumeHandle"
    };
    s.Ec = "volumechange";
    s.e = function() {
        return t.S.prototype.e.call(this, "div", {
            className: "vjs-volume-bar",
            "aria-label": "volume level"
        });
    };
    s.$a = function(a) {
        this.d.muted() && this.d.muted(l);
        this.d.volume(H(this, a));
    };
    s.Gb = function() {
        return this.d.muted() ? 0 : this.d.volume();
    };
    s.Oc = function() {
        this.d.volume(this.d.volume() + .1);
    };
    s.Nc = function() {
        this.d.volume(this.d.volume() - .1);
    };
    t.hc = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
        }
    });
    t.hc.prototype.e = function() {
        return t.a.prototype.e.call(this, "div", {
            className: "vjs-volume-level",
            innerHTML: '<span class="vjs-control-text"></span>'
        });
    };
    t.sb = t.$.extend();
    t.sb.prototype.defaultValue = "00:00";
    t.sb.prototype.e = function() {
        return t.$.prototype.e.call(this, "div", {
            className: "vjs-volume-handle"
        });
    };
    t.ia = t.u.extend({
        i: function(a, c) {
            t.u.call(this, a, c);
            this.c(a, "volumechange", this.update);
            a.o && a.o.featuresVolumeControl === l && this.n("vjs-hidden");
            this.c(a, "loadstart", function() {
                a.o.featuresVolumeControl === l ? this.n("vjs-hidden") : this.p("vjs-hidden");
            });
        }
    });
    t.ia.prototype.e = function() {
        return t.u.prototype.e.call(this, "div", {
            className: "vjs-mute-control vjs-control",
            innerHTML: '<div><span class="vjs-control-text">' + this.t("Mute") + "</span></div>"
        });
    };
    t.ia.prototype.s = function() {
        this.d.muted(this.d.muted() ? l : f);
    };
    t.ia.prototype.update = function() {
        var a = this.d.volume(), c = 3;
        0 === a || this.d.muted() ? c = 0 : .33 > a ? c = 1 : .67 > a && (c = 2);
        this.d.muted() ? this.b.children[0].children[0].innerHTML != this.t("Unmute") && (this.b.children[0].children[0].innerHTML = this.t("Unmute")) : this.b.children[0].children[0].innerHTML != this.t("Mute") && (this.b.children[0].children[0].innerHTML = this.t("Mute"));
        for (a = 0; 4 > a; a++) t.p(this.b, "vjs-vol-" + a);
        t.n(this.b, "vjs-vol-" + c);
    };
    t.sa = t.N.extend({
        i: function(a, c) {
            t.N.call(this, a, c);
            this.c(a, "volumechange", this.update);
            a.o && a.o.featuresVolumeControl === l && this.n("vjs-hidden");
            this.c(a, "loadstart", function() {
                a.o.featuresVolumeControl === l ? this.n("vjs-hidden") : this.p("vjs-hidden");
            });
            this.n("vjs-menu-button");
        }
    });
    t.sa.prototype.xa = function() {
        var a = new t.ha(this.d, {
            lc: "div"
        }), c = new t.qb(this.d, this.m.volumeBar);
        c.c("focus", function() {
            a.n("vjs-lock-showing");
        });
        c.c("blur", function() {
            E(a);
        });
        a.V(c);
        return a;
    };
    t.sa.prototype.s = function() {
        t.ia.prototype.s.call(this);
        t.N.prototype.s.call(this);
    };
    t.sa.prototype.e = function() {
        return t.u.prototype.e.call(this, "div", {
            className: "vjs-volume-menu-button vjs-menu-button vjs-control",
            innerHTML: '<div><span class="vjs-control-text">' + this.t("Mute") + "</span></div>"
        });
    };
    t.sa.prototype.update = t.ia.prototype.update;
    t.bc = t.N.extend({
        i: function(a, c) {
            t.N.call(this, a, c);
            this.Tc();
            this.Sc();
            this.c(a, "loadstart", this.Tc);
            this.c(a, "ratechange", this.Sc);
        }
    });
    s = t.bc.prototype;
    s.e = function() {
        var a = t.a.prototype.e.call(this, "div", {
            className: "vjs-playback-rate vjs-menu-button vjs-control",
            innerHTML: '<div class="vjs-control-content"><span class="vjs-control-text">' + this.t("Playback Rate") + "</span></div>"
        });
        this.Ac = t.e("div", {
            className: "vjs-playback-rate-value",
            innerHTML: 1
        });
        a.appendChild(this.Ac);
        return a;
    };
    s.xa = function() {
        var a = new t.ha(this.k()), c = this.k().options().playbackRates;
        if (c) for (var d = c.length - 1; 0 <= d; d--) a.V(new t.nb(this.k(), {
            rate: c[d] + "x"
        }));
        return a;
    };
    s.oa = function() {
        this.w().setAttribute("aria-valuenow", this.k().playbackRate());
    };
    s.s = function() {
        for (var a = this.k().playbackRate(), c = this.k().options().playbackRates, d = c[0], e = 0; e < c.length; e++) if (c[e] > a) {
            d = c[e];
            break;
        }
        this.k().playbackRate(d);
    };
    function ea(a) {
        return a.k().o && a.k().o.featuresPlaybackRate && a.k().options().playbackRates && 0 < a.k().options().playbackRates.length;
    }
    s.Tc = function() {
        ea(this) ? this.p("vjs-hidden") : this.n("vjs-hidden");
    };
    s.Sc = function() {
        ea(this) && (this.Ac.innerHTML = this.k().playbackRate() + "x");
    };
    t.nb = t.J.extend({
        lc: "button",
        i: function(a, c) {
            var d = this.label = c.rate, e = this.Hc = parseFloat(d, 10);
            c.label = d;
            c.selected = 1 === e;
            t.J.call(this, a, c);
            this.c(a, "ratechange", this.update);
        }
    });
    t.nb.prototype.s = function() {
        t.J.prototype.s.call(this);
        this.k().playbackRate(this.Hc);
    };
    t.nb.prototype.update = function() {
        this.selected(this.k().playbackRate() == this.Hc);
    };
    t.ra = t.u.extend({
        i: function(a, c) {
            t.u.call(this, a, c);
            this.update();
            a.c("posterchange", t.bind(this, this.update));
        }
    });
    t.ra.prototype.dispose = function() {
        this.k().j("posterchange", this.update);
        t.u.prototype.dispose.call(this);
    };
    t.ra.prototype.e = function() {
        var a = t.e("div", {
            className: "vjs-poster",
            tabIndex: -1
        });
        t.Wc || (this.Db = t.e("img"), a.appendChild(this.Db));
        return a;
    };
    t.ra.prototype.update = function() {
        var a = this.k().poster(), c;
        this.Db ? this.Db.src = a : (c = "", a && (c = 'url("' + a + '")'), this.b.style.backgroundImage = c);
        a ? this.b.style.display = "" : this.Y();
    };
    t.ra.prototype.s = function() {
        this.d.play();
    };
    t.Zb = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
        }
    });
    t.Zb.prototype.e = function() {
        return t.a.prototype.e.call(this, "div", {
            className: "vjs-loading-spinner"
        });
    };
    t.fb = t.u.extend();
    t.fb.prototype.e = function() {
        return t.u.prototype.e.call(this, "div", {
            className: "vjs-big-play-button",
            innerHTML: '<span aria-hidden="true"></span>',
            "aria-label": "play video"
        });
    };
    t.fb.prototype.s = function() {
        this.d.play();
    };
    t.jb = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
            this.update();
            this.c(a, "error", this.update);
        }
    });
    t.jb.prototype.e = function() {
        var a = t.a.prototype.e.call(this, "div", {
            className: "vjs-error-display"
        });
        this.v = t.e("div");
        a.appendChild(this.v);
        return a;
    };
    t.jb.prototype.update = function() {
        this.k().error() && (this.v.innerHTML = this.t(this.k().error().message));
    };
    t.q = t.a.extend({
        i: function(a, c, d) {
            c = c || {};
            c.Jc = l;
            t.a.call(this, a, c, d);
            this.featuresProgressEvents || (this.Bc = f, this.Gc = setInterval(t.bind(this, function() {
                var a = this.k().bufferedPercent();
                this.ld != a && this.k().l("progress");
                this.ld = a;
                1 === a && clearInterval(this.Gc);
            }), 500));
            this.featuresTimeupdateEvents || (a = this.d, this.Lb = f, this.c(a, "play", this.Rc), 
            this.c(a, "pause", this.cb), this.R("timeupdate", function() {
                this.featuresTimeupdateEvents = f;
                fa(this);
            }));
            var e;
            e = this.k();
            a = function() {
                if (e.controls() && !e.usingNativeControls()) {
                    var a;
                    this.c("mousedown", this.s);
                    this.c("touchstart", function() {
                        a = this.d.userActive();
                    });
                    this.c("touchmove", function() {
                        a && this.k().reportUserActivity();
                    });
                    this.c("touchend", function(a) {
                        a.preventDefault();
                    });
                    G(this);
                    this.c("tap", this.Td);
                }
            };
            this.K(a);
            this.c(e, "controlsenabled", a);
            this.c(e, "controlsdisabled", this.Zd);
            this.K(function() {
                this.networkState && 0 < this.networkState() && this.k().l("loadstart");
            });
        }
    });
    s = t.q.prototype;
    s.Zd = function() {
        this.j("tap");
        this.j("touchstart");
        this.j("touchmove");
        this.j("touchleave");
        this.j("touchcancel");
        this.j("touchend");
        this.j("click");
        this.j("mousedown");
    };
    s.s = function(a) {
        0 === a.button && this.k().controls() && (this.k().paused() ? this.k().play() : this.k().pause());
    };
    s.Td = function() {
        this.k().userActive(!this.k().userActive());
    };
    function fa(a) {
        a.Lb = l;
        a.cb();
        a.j("play", a.Rc);
        a.j("pause", a.cb);
    }
    s.Rc = function() {
        this.mc && this.cb();
        this.mc = setInterval(t.bind(this, function() {
            this.k().l("timeupdate");
        }), 250);
    };
    s.cb = function() {
        clearInterval(this.mc);
        this.k().l("timeupdate");
    };
    s.dispose = function() {
        this.Bc && (this.Bc = l, clearInterval(this.Gc));
        this.Lb && fa(this);
        t.a.prototype.dispose.call(this);
    };
    s.Qb = function() {
        this.Lb && this.k().l("timeupdate");
    };
    s.Lc = m();
    t.q.prototype.featuresVolumeControl = f;
    t.q.prototype.featuresFullscreenResize = l;
    t.q.prototype.featuresPlaybackRate = l;
    t.q.prototype.featuresProgressEvents = l;
    t.q.prototype.featuresTimeupdateEvents = l;
    t.media = {};
    t.h = t.q.extend({
        i: function(a, c, d) {
            this.featuresVolumeControl = t.h.nd();
            this.featuresPlaybackRate = t.h.md();
            this.movingMediaElementInDOM = !t.$c;
            this.featuresProgressEvents = this.featuresFullscreenResize = f;
            t.q.call(this, a, c, d);
            for (d = t.h.kb.length - 1; 0 <= d; d--) this.c(t.h.kb[d], this.vd);
            if ((c = c.source) && this.b.currentSrc !== c.src || a.I && 3 === a.I.Bd) this.b.src = c.src;
            if (t.ec && a.options().nativeControlsForTouch === f) {
                var e, g, h, j;
                e = this;
                g = this.k();
                c = g.controls();
                e.b.controls = !!c;
                h = function() {
                    e.b.controls = f;
                };
                j = function() {
                    e.b.controls = l;
                };
                g.c("controlsenabled", h);
                g.c("controlsdisabled", j);
                c = function() {
                    g.j("controlsenabled", h);
                    g.j("controlsdisabled", j);
                };
                e.c("dispose", c);
                g.c("usingcustomcontrols", c);
                g.usingNativeControls(f);
            }
            a.K(function() {
                this.I && (this.m.autoplay && this.paused()) && (delete this.I.poster, this.play());
            });
            this.Ga();
        }
    });
    s = t.h.prototype;
    s.dispose = function() {
        t.h.Bb(this.b);
        t.q.prototype.dispose.call(this);
    };
    s.e = function() {
        var a = this.d, c = a.I, d;
        if (!c || this.movingMediaElementInDOM === l) c ? (d = c.cloneNode(l), t.h.Bb(c), 
        c = d, a.I = k) : (c = t.e("video"), t.Kc(c, t.g.z(a.ie || {}, {
            id: a.id() + "_html5_api",
            class: "vjs-tech"
        }))), c.player = a, t.Hb(c, a.w());
        d = [ "autoplay", "preload", "loop", "muted" ];
        for (var e = d.length - 1; 0 <= e; e--) {
            var g = d[e], h = {};
            "undefined" !== typeof a.m[g] && (h[g] = a.m[g]);
            t.Kc(c, h);
        }
        return c;
    };
    s.vd = function(a) {
        "error" == a.type && this.error() ? this.k().error(this.error().code) : (a.bubbles = l, 
        this.k().l(a));
    };
    s.play = function() {
        this.b.play();
    };
    s.pause = function() {
        this.b.pause();
    };
    s.paused = function() {
        return this.b.paused;
    };
    s.currentTime = function() {
        return this.b.currentTime;
    };
    s.Qb = function(a) {
        try {
            this.b.currentTime = a;
        } catch (c) {
            t.log(c, "Video is not ready. (Video.js)");
        }
    };
    s.duration = function() {
        return this.b.duration || 0;
    };
    s.buffered = function() {
        return this.b.buffered;
    };
    s.volume = function() {
        return this.b.volume;
    };
    s.fe = function(a) {
        this.b.volume = a;
    };
    s.muted = function() {
        return this.b.muted;
    };
    s.ce = function(a) {
        this.b.muted = a;
    };
    s.width = function() {
        return this.b.offsetWidth;
    };
    s.height = function() {
        return this.b.offsetHeight;
    };
    s.Ea = function() {
        return "function" == typeof this.b.webkitEnterFullScreen && (/Android/.test(t.O) || !/Chrome|Mac OS X 10.5/.test(t.O)) ? f : l;
    };
    s.pc = function() {
        var a = this.b;
        "webkitDisplayingFullscreen" in a && this.R("webkitbeginfullscreen", function() {
            this.d.isFullscreen(f);
            this.R("webkitendfullscreen", function() {
                this.d.isFullscreen(l);
                this.d.l("fullscreenchange");
            });
            this.d.l("fullscreenchange");
        });
        a.paused && a.networkState <= a.ne ? (this.b.play(), setTimeout(function() {
            a.pause();
            a.webkitEnterFullScreen();
        }, 0)) : a.webkitEnterFullScreen();
    };
    s.wd = function() {
        this.b.webkitExitFullScreen();
    };
    s.src = function(a) {
        if (a === b) return this.b.src;
        this.b.src = a;
    };
    s.load = function() {
        this.b.load();
    };
    s.currentSrc = function() {
        return this.b.currentSrc;
    };
    s.poster = function() {
        return this.b.poster;
    };
    s.Lc = function(a) {
        this.b.poster = a;
    };
    s.Da = function() {
        return this.b.Da;
    };
    s.ee = function(a) {
        this.b.Da = a;
    };
    s.autoplay = function() {
        return this.b.autoplay;
    };
    s.$d = function(a) {
        this.b.autoplay = a;
    };
    s.controls = function() {
        return this.b.controls;
    };
    s.loop = function() {
        return this.b.loop;
    };
    s.be = function(a) {
        this.b.loop = a;
    };
    s.error = function() {
        return this.b.error;
    };
    s.seeking = function() {
        return this.b.seeking;
    };
    s.ended = function() {
        return this.b.ended;
    };
    s.playbackRate = function() {
        return this.b.playbackRate;
    };
    s.de = function(a) {
        this.b.playbackRate = a;
    };
    s.networkState = function() {
        return this.b.networkState;
    };
    t.h.isSupported = function() {
        try {
            t.A.volume = .5;
        } catch (a) {
            return l;
        }
        return !!t.A.canPlayType;
    };
    t.h.vb = function(a) {
        try {
            return !!t.A.canPlayType(a.type);
        } catch (c) {
            return "";
        }
    };
    t.h.nd = function() {
        var a = t.A.volume;
        t.A.volume = a / 2 + .1;
        return a !== t.A.volume;
    };
    t.h.md = function() {
        var a = t.A.playbackRate;
        t.A.playbackRate = a / 2 + .1;
        return a !== t.A.playbackRate;
    };
    var V, ga = /^application\/(?:x-|vnd\.apple\.)mpegurl/i, ha = /^video\/mp4/i;
    t.h.Dc = function() {
        4 <= t.Ub && (V || (V = t.A.constructor.prototype.canPlayType), t.A.constructor.prototype.canPlayType = function(a) {
            return a && ga.test(a) ? "maybe" : V.call(this, a);
        });
        t.dd && (V || (V = t.A.constructor.prototype.canPlayType), t.A.constructor.prototype.canPlayType = function(a) {
            return a && ha.test(a) ? "maybe" : V.call(this, a);
        });
    };
    t.h.le = function() {
        var a = t.A.constructor.prototype.canPlayType;
        t.A.constructor.prototype.canPlayType = V;
        V = k;
        return a;
    };
    t.h.Dc();
    t.h.kb = "loadstart suspend abort error emptied stalled loadedmetadata loadeddata canplay canplaythrough playing waiting seeking seeked ended durationchange timeupdate progress play pause ratechange volumechange".split(" ");
    t.h.Bb = function(a) {
        if (a) {
            a.player = k;
            for (a.parentNode && a.parentNode.removeChild(a); a.hasChildNodes(); ) a.removeChild(a.firstChild);
            a.removeAttribute("src");
            if ("function" === typeof a.load) try {
                a.load();
            } catch (c) {}
        }
    };
    t.f = t.q.extend({
        i: function(a, c, d) {
            t.q.call(this, a, c, d);
            var e = c.source;
            d = c.parentEl;
            var g = this.b = t.e("div", {
                id: a.id() + "_temp_flash"
            }), h = a.id() + "_flash_api", j = a.m, j = t.g.z({
                readyFunction: "videojs.Flash.onReady",
                eventProxyFunction: "videojs.Flash.onEvent",
                errorEventProxyFunction: "videojs.Flash.onError",
                autoplay: j.autoplay,
                preload: j.Da,
                loop: j.loop,
                muted: j.muted
            }, c.flashVars), n = t.g.z({
                wmode: "opaque",
                bgcolor: "#000000"
            }, c.params), h = t.g.z({
                id: h,
                name: h,
                class: "vjs-tech"
            }, c.attributes);
            e && (e.type && t.f.Gd(e.type) ? (e = t.f.Pc(e.src), j.rtmpConnection = encodeURIComponent(e.wb), 
            j.rtmpStream = encodeURIComponent(e.Rb)) : j.src = encodeURIComponent(t.tc(e.src)));
            t.Hb(g, d);
            c.startTime && this.K(function() {
                this.load();
                this.play();
                this.currentTime(c.startTime);
            });
            t.Zc && this.K(function() {
                this.c("mousemove", function() {
                    this.k().l({
                        type: "mousemove",
                        bubbles: l
                    });
                });
            });
            a.c("stageclick", a.reportUserActivity);
            this.b = t.f.oc(c.swf, g, j, n, h);
        }
    });
    t.f.prototype.dispose = function() {
        t.q.prototype.dispose.call(this);
    };
    t.f.prototype.play = function() {
        this.b.vjs_play();
    };
    t.f.prototype.pause = function() {
        this.b.vjs_pause();
    };
    t.f.prototype.src = function(a) {
        if (a === b) return this.currentSrc();
        t.f.Fd(a) ? (a = t.f.Pc(a), this.xe(a.wb), this.ye(a.Rb)) : (a = t.tc(a), this.b.vjs_src(a));
        if (this.d.autoplay()) {
            var c = this;
            setTimeout(function() {
                c.play();
            }, 0);
        }
    };
    t.f.prototype.setCurrentTime = function(a) {
        this.Jd = a;
        this.b.vjs_setProperty("currentTime", a);
        t.q.prototype.Qb.call(this);
    };
    t.f.prototype.currentTime = function() {
        return this.seeking() ? this.Jd || 0 : this.b.vjs_getProperty("currentTime");
    };
    t.f.prototype.currentSrc = function() {
        var a = this.b.vjs_getProperty("currentSrc");
        if (a == k) {
            var c = this.rtmpConnection(), d = this.rtmpStream();
            c && d && (a = t.f.ge(c, d));
        }
        return a;
    };
    t.f.prototype.load = function() {
        this.b.vjs_load();
    };
    t.f.prototype.poster = function() {
        this.b.vjs_getProperty("poster");
    };
    t.f.prototype.setPoster = m();
    t.f.prototype.buffered = function() {
        return t.zb(0, this.b.vjs_getProperty("buffered"));
    };
    t.f.prototype.Ea = q(l);
    t.f.prototype.pc = q(l);
    function ia() {
        var a = W[X], c = a.charAt(0).toUpperCase() + a.slice(1);
        ja["set" + c] = function(c) {
            return this.b.vjs_setProperty(a, c);
        };
    }
    function ka(a) {
        ja[a] = function() {
            return this.b.vjs_getProperty(a);
        };
    }
    var ja = t.f.prototype, W = "rtmpConnection rtmpStream preload defaultPlaybackRate playbackRate autoplay loop mediaGroup controller controls volume muted defaultMuted".split(" "), la = "error networkState readyState seeking initialTime duration startOffsetTime paused played seekable ended videoTracks audioTracks videoWidth videoHeight textTracks".split(" "), X;
    for (X = 0; X < W.length; X++) ka(W[X]), ia();
    for (X = 0; X < la.length; X++) ka(la[X]);
    t.f.isSupported = function() {
        return 10 <= t.f.version()[0];
    };
    t.f.vb = function(a) {
        if (!a.type) return "";
        a = a.type.replace(/;.*/, "").toLowerCase();
        if (a in t.f.yd || a in t.f.Qc) return "maybe";
    };
    t.f.yd = {
        "video/flv": "FLV",
        "video/x-flv": "FLV",
        "video/mp4": "MP4",
        "video/m4v": "MP4"
    };
    t.f.Qc = {
        "rtmp/mp4": "MP4",
        "rtmp/flv": "FLV"
    };
    t.f.onReady = function(a) {
        var c;
        if (c = (a = t.w(a)) && a.parentNode && a.parentNode.player) a.player = c, t.f.checkReady(c.o);
    };
    t.f.checkReady = function(a) {
        a.w() && (a.w().vjs_getProperty ? a.Ga() : setTimeout(function() {
            t.f.checkReady(a);
        }, 50));
    };
    t.f.onEvent = function(a, c) {
        t.w(a).player.l(c);
    };
    t.f.onError = function(a, c) {
        var d = t.w(a).player, e = "FLASH: " + c;
        "srcnotfound" == c ? d.error({
            code: 4,
            message: e
        }) : d.error(e);
    };
    t.f.version = function() {
        var a = "0,0,0";
        try {
            a = new window.ActiveXObject("ShockwaveFlash.ShockwaveFlash").GetVariable("$version").replace(/\D+/g, ",").match(/^,?(.+),?$/)[1];
        } catch (c) {
            try {
                navigator.mimeTypes["application/x-shockwave-flash"].enabledPlugin && (a = (navigator.plugins["Shockwave Flash 2.0"] || navigator.plugins["Shockwave Flash"]).description.replace(/\D+/g, ",").match(/^,?(.+),?$/)[1]);
            } catch (d) {}
        }
        return a.split(",");
    };
    t.f.oc = function(a, c, d, e, g) {
        a = t.f.Ad(a, d, e, g);
        a = t.e("div", {
            innerHTML: a
        }).childNodes[0];
        d = c.parentNode;
        c.parentNode.replaceChild(a, c);
        var h = d.childNodes[0];
        setTimeout(function() {
            h.style.display = "block";
        }, 1e3);
        return a;
    };
    t.f.Ad = function(a, c, d, e) {
        var g = "", h = "", j = "";
        c && t.g.X(c, function(a, c) {
            g += a + "=" + c + "&amp;";
        });
        d = t.g.z({
            movie: a,
            flashvars: g,
            allowScriptAccess: "always",
            allowNetworking: "all"
        }, d);
        t.g.X(d, function(a, c) {
            h += '<param name="' + a + '" value="' + c + '" />';
        });
        e = t.g.z({
            data: a,
            width: "100%",
            height: "100%"
        }, e);
        t.g.X(e, function(a, c) {
            j += a + '="' + c + '" ';
        });
        return '<object type="application/x-shockwave-flash"' + j + ">" + h + "</object>";
    };
    t.f.ge = function(a, c) {
        return a + "&" + c;
    };
    t.f.Pc = function(a) {
        var c = {
            wb: "",
            Rb: ""
        };
        if (!a) return c;
        var d = a.indexOf("&"), e;
        -1 !== d ? e = d + 1 : (d = e = a.lastIndexOf("/") + 1, 0 === d && (d = e = a.length));
        c.wb = a.substring(0, d);
        c.Rb = a.substring(e, a.length);
        return c;
    };
    t.f.Gd = function(a) {
        return a in t.f.Qc;
    };
    t.f.fd = /^rtmp[set]?:\/\//i;
    t.f.Fd = function(a) {
        return t.f.fd.test(a);
    };
    t.ed = t.a.extend({
        i: function(a, c, d) {
            t.a.call(this, a, c, d);
            if (!a.m.sources || 0 === a.m.sources.length) {
                c = 0;
                for (d = a.m.techOrder; c < d.length; c++) {
                    var e = t.ba(d[c]), g = window.videojs[e];
                    if (g && g.isSupported()) {
                        Q(a, e);
                        break;
                    }
                }
            } else a.src(a.m.sources);
        }
    });
    t.Player.prototype.textTracks = function() {
        return this.Fa = this.Fa || [];
    };
    function ma(a, c, d, e, g) {
        var h = a.Fa = a.Fa || [];
        g = g || {};
        g.kind = c;
        g.label = d;
        g.language = e;
        c = t.ba(c || "subtitles");
        var j = new window.videojs[c + "Track"](a, g);
        h.push(j);
        j.Ab() && a.K(function() {
            setTimeout(function() {
                Y(j.k(), j.id());
            }, 0);
        });
    }
    function Y(a, c, d) {
        for (var e = a.Fa, g = 0, h = e.length, j, n; g < h; g++) j = e[g], j.id() === c ? (j.show(), 
        n = j) : d && (j.M() == d && 0 < j.mode()) && j.disable();
        (c = n ? n.M() : d ? d : l) && a.l(c + "trackchange");
    }
    t.B = t.a.extend({
        i: function(a, c) {
            t.a.call(this, a, c);
            this.L = c.id || "vjs_" + c.kind + "_" + c.language + "_" + t.r++;
            this.Mc = c.src;
            this.sd = c["default"] || c.dflt;
            this.je = c.title;
            this.Ua = c.srclang;
            this.Hd = c.label;
            this.ca = [];
            this.tb = [];
            this.ma = this.na = 0;
        }
    });
    s = t.B.prototype;
    s.M = p("H");
    s.src = p("Mc");
    s.Ab = p("sd");
    s.title = p("je");
    s.language = p("Ua");
    s.label = p("Hd");
    s.od = p("ca");
    s.gd = p("tb");
    s.readyState = p("na");
    s.mode = p("ma");
    s.e = function() {
        return t.a.prototype.e.call(this, "div", {
            className: "vjs-" + this.H + " vjs-text-track"
        });
    };
    s.show = function() {
        na(this);
        this.ma = 2;
        t.a.prototype.show.call(this);
    };
    s.Y = function() {
        na(this);
        this.ma = 1;
        t.a.prototype.Y.call(this);
    };
    s.disable = function() {
        2 == this.ma && this.Y();
        this.d.j("timeupdate", t.bind(this, this.update, this.L));
        this.d.j("ended", t.bind(this, this.reset, this.L));
        this.reset();
        this.d.ka("textTrackDisplay").removeChild(this);
        this.ma = 0;
    };
    function na(a) {
        0 === a.na && a.load();
        0 === a.ma && (a.d.c("timeupdate", t.bind(a, a.update, a.L)), a.d.c("ended", t.bind(a, a.reset, a.L)), 
        ("captions" === a.H || "subtitles" === a.H) && a.d.ka("textTrackDisplay").V(a));
    }
    s.load = function() {
        0 === this.na && (this.na = 1, t.get(this.Mc, t.bind(this, this.Wd), t.bind(this, this.Md)));
    };
    s.Md = function(a) {
        this.error = a;
        this.na = 3;
        this.l("error");
    };
    s.Wd = function(a) {
        var c, d;
        a = a.split("\n");
        for (var e = "", g = 1, h = a.length; g < h; g++) if (e = t.trim(a[g])) {
            -1 == e.indexOf("--\x3e") ? (c = e, e = t.trim(a[++g])) : c = this.ca.length;
            c = {
                id: c,
                index: this.ca.length
            };
            d = e.split(/[\t ]+/);
            c.startTime = oa(d[0]);
            c.ya = oa(d[2]);
            for (d = []; a[++g] && (e = t.trim(a[g])); ) d.push(e);
            c.text = d.join("<br/>");
            this.ca.push(c);
        }
        this.na = 2;
        this.l("loaded");
    };
    function oa(a) {
        var c = a.split(":");
        a = 0;
        var d, e, g;
        3 == c.length ? (d = c[0], e = c[1], c = c[2]) : (d = 0, e = c[0], c = c[1]);
        c = c.split(/\s+/);
        c = c.splice(0, 1)[0];
        c = c.split(/\.|,/);
        g = parseFloat(c[1]);
        c = c[0];
        a += 3600 * parseFloat(d);
        a += 60 * parseFloat(e);
        a += parseFloat(c);
        g && (a += g / 1e3);
        return a;
    }
    s.update = function() {
        if (0 < this.ca.length) {
            var a = this.d.options().trackTimeOffset || 0, a = this.d.currentTime() + a;
            if (this.Pb === b || a < this.Pb || this.Wa <= a) {
                var c = this.ca, d = this.d.duration(), e = 0, g = l, h = [], j, n, r, w;
                a >= this.Wa || this.Wa === b ? w = this.Eb !== b ? this.Eb : 0 : (g = f, w = this.Kb !== b ? this.Kb : c.length - 1);
                for (;;) {
                    r = c[w];
                    if (r.ya <= a) e = Math.max(e, r.ya), r.Na && (r.Na = l); else if (a < r.startTime) {
                        if (d = Math.min(d, r.startTime), r.Na && (r.Na = l), !g) break;
                    } else g ? (h.splice(0, 0, r), n === b && (n = w), j = w) : (h.push(r), j === b && (j = w), 
                    n = w), d = Math.min(d, r.ya), e = Math.max(e, r.startTime), r.Na = f;
                    if (g) if (0 === w) break; else w--; else if (w === c.length - 1) break; else w++;
                }
                this.tb = h;
                this.Wa = d;
                this.Pb = e;
                this.Eb = j;
                this.Kb = n;
                j = this.tb;
                n = "";
                a = 0;
                for (c = j.length; a < c; a++) n += '<span class="vjs-tt-cue">' + j[a].text + "</span>";
                this.b.innerHTML = n;
                this.l("cuechange");
            }
        }
    };
    s.reset = function() {
        this.Wa = 0;
        this.Pb = this.d.duration();
        this.Kb = this.Eb = 0;
    };
    t.Wb = t.B.extend();
    t.Wb.prototype.H = "captions";
    t.dc = t.B.extend();
    t.dc.prototype.H = "subtitles";
    t.Xb = t.B.extend();
    t.Xb.prototype.H = "chapters";
    t.fc = t.a.extend({
        i: function(a, c, d) {
            t.a.call(this, a, c, d);
            if (a.m.tracks && 0 < a.m.tracks.length) {
                c = this.d;
                a = a.m.tracks;
                for (var e = 0; e < a.length; e++) d = a[e], ma(c, d.kind, d.label, d.language, d);
            }
        }
    });
    t.fc.prototype.e = function() {
        return t.a.prototype.e.call(this, "div", {
            className: "vjs-text-track-display"
        });
    };
    t.aa = t.J.extend({
        i: function(a, c) {
            var d = this.ea = c.track;
            c.label = d.label();
            c.selected = d.Ab();
            t.J.call(this, a, c);
            this.c(a, d.M() + "trackchange", this.update);
        }
    });
    t.aa.prototype.s = function() {
        t.J.prototype.s.call(this);
        Y(this.d, this.ea.L, this.ea.M());
    };
    t.aa.prototype.update = function() {
        this.selected(2 == this.ea.mode());
    };
    t.mb = t.aa.extend({
        i: function(a, c) {
            c.track = {
                M: function() {
                    return c.kind;
                },
                k: a,
                label: function() {
                    return c.kind + " off";
                },
                Ab: q(l),
                mode: q(l)
            };
            t.aa.call(this, a, c);
            this.selected(f);
        }
    });
    t.mb.prototype.s = function() {
        t.aa.prototype.s.call(this);
        Y(this.d, this.ea.L, this.ea.M());
    };
    t.mb.prototype.update = function() {
        for (var a = this.d.textTracks(), c = 0, d = a.length, e, g = f; c < d; c++) e = a[c], 
        e.M() == this.ea.M() && 2 == e.mode() && (g = l);
        this.selected(g);
    };
    t.U = t.N.extend({
        i: function(a, c) {
            t.N.call(this, a, c);
            1 >= this.Q.length && this.Y();
        }
    });
    t.U.prototype.wa = function() {
        var a = [], c;
        a.push(new t.mb(this.d, {
            kind: this.H
        }));
        for (var d = 0; d < this.d.textTracks().length; d++) c = this.d.textTracks()[d], 
        c.M() === this.H && a.push(new t.aa(this.d, {
            track: c
        }));
        return a;
    };
    t.Ha = t.U.extend({
        i: function(a, c, d) {
            t.U.call(this, a, c, d);
            this.b.setAttribute("aria-label", "Captions Menu");
        }
    });
    t.Ha.prototype.H = "captions";
    t.Ha.prototype.ua = "Captions";
    t.Ha.prototype.className = "vjs-captions-button";
    t.Ma = t.U.extend({
        i: function(a, c, d) {
            t.U.call(this, a, c, d);
            this.b.setAttribute("aria-label", "Subtitles Menu");
        }
    });
    t.Ma.prototype.H = "subtitles";
    t.Ma.prototype.ua = "Subtitles";
    t.Ma.prototype.className = "vjs-subtitles-button";
    t.Ia = t.U.extend({
        i: function(a, c, d) {
            t.U.call(this, a, c, d);
            this.b.setAttribute("aria-label", "Chapters Menu");
        }
    });
    s = t.Ia.prototype;
    s.H = "chapters";
    s.ua = "Chapters";
    s.className = "vjs-chapters-button";
    s.wa = function() {
        for (var a = [], c, d = 0; d < this.d.textTracks().length; d++) c = this.d.textTracks()[d], 
        c.M() === this.H && a.push(new t.aa(this.d, {
            track: c
        }));
        return a;
    };
    s.xa = function() {
        for (var a = this.d.textTracks(), c = 0, d = a.length, e, g, h = this.Q = []; c < d; c++) if (e = a[c], 
        e.M() == this.H) if (0 === e.readyState()) e.load(), e.c("loaded", t.bind(this, this.xa)); else {
            g = e;
            break;
        }
        a = this.Ba;
        a === b && (a = new t.ha(this.d), a.ja().appendChild(t.e("li", {
            className: "vjs-menu-title",
            innerHTML: t.ba(this.H),
            he: -1
        })));
        if (g) {
            e = g.ca;
            for (var j, c = 0, d = e.length; c < d; c++) j = e[c], j = new t.gb(this.d, {
                track: g,
                cue: j
            }), h.push(j), a.V(j);
            this.V(a);
        }
        0 < this.Q.length && this.show();
        return a;
    };
    t.gb = t.J.extend({
        i: function(a, c) {
            var d = this.ea = c.track, e = this.cue = c.cue, g = a.currentTime();
            c.label = e.text;
            c.selected = e.startTime <= g && g < e.ya;
            t.J.call(this, a, c);
            d.c("cuechange", t.bind(this, this.update));
        }
    });
    t.gb.prototype.s = function() {
        t.J.prototype.s.call(this);
        this.d.currentTime(this.cue.startTime);
        this.update(this.cue.startTime);
    };
    t.gb.prototype.update = function() {
        var a = this.cue, c = this.d.currentTime();
        this.selected(a.startTime <= c && c < a.ya);
    };
    t.g.z(t.Ja.prototype.m.children, {
        subtitlesButton: {},
        captionsButton: {},
        chaptersButton: {}
    });
    if ("undefined" !== typeof window.JSON && "function" === typeof window.JSON.parse) t.JSON = window.JSON; else {
        t.JSON = {};
        var Z = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g;
        t.JSON.parse = function(a, c) {
            function d(a, e) {
                var j, n, r = a[e];
                if (r && "object" === typeof r) for (j in r) Object.prototype.hasOwnProperty.call(r, j) && (n = d(r, j), 
                n !== b ? r[j] = n : delete r[j]);
                return c.call(a, e, r);
            }
            var e;
            a = String(a);
            Z.lastIndex = 0;
            Z.test(a) && (a = a.replace(Z, function(a) {
                return "\\u" + ("0000" + a.charCodeAt(0).toString(16)).slice(-4);
            }));
            if (/^[\],:{}\s]*$/.test(a.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, "@").replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, "]").replace(/(?:^|:|,)(?:\s*\[)+/g, ""))) return e = eval("(" + a + ")"), 
            "function" === typeof c ? d({
                "": e
            }, "") : e;
            throw new SyntaxError("JSON.parse(): invalid or malformed JSON data");
        };
    }
    t.jc = function() {
        var a, c, d, e;
        a = document.getElementsByTagName("video");
        c = document.getElementsByTagName("audio");
        var g = [];
        if (a && 0 < a.length) {
            d = 0;
            for (e = a.length; d < e; d++) g.push(a[d]);
        }
        if (c && 0 < c.length) {
            d = 0;
            for (e = c.length; d < e; d++) g.push(c[d]);
        }
        if (g && 0 < g.length) {
            d = 0;
            for (e = g.length; d < e; d++) if ((c = g[d]) && c.getAttribute) c.player === b && (a = c.getAttribute("data-setup"), 
            a !== k && videojs(c)); else {
                t.ub();
                break;
            }
        } else t.Uc || t.ub();
    };
    t.ub = function() {
        setTimeout(t.jc, 1);
    };
    "complete" === document.readyState ? t.Uc = f : t.R(window, "load", function() {
        t.Uc = f;
    });
    t.ub();
    t.Yd = function(a, c) {
        t.Player.prototype[a] = c;
    };
    var pa = this;
    function $(a, c) {
        var d = a.split("."), e = pa;
        !(d[0] in e) && e.execScript && e.execScript("var " + d[0]);
        for (var g; d.length && (g = d.shift()); ) !d.length && c !== b ? e[g] = c : e = e[g] ? e[g] : e[g] = {};
    }
    $("videojs", t);
    $("_V_", t);
    $("videojs.options", t.options);
    $("videojs.players", t.Ca);
    $("videojs.TOUCH_ENABLED", t.ec);
    $("videojs.cache", t.va);
    $("videojs.Component", t.a);
    t.a.prototype.player = t.a.prototype.k;
    t.a.prototype.options = t.a.prototype.options;
    t.a.prototype.init = t.a.prototype.i;
    t.a.prototype.dispose = t.a.prototype.dispose;
    t.a.prototype.createEl = t.a.prototype.e;
    t.a.prototype.contentEl = t.a.prototype.ja;
    t.a.prototype.el = t.a.prototype.w;
    t.a.prototype.addChild = t.a.prototype.V;
    t.a.prototype.getChild = t.a.prototype.ka;
    t.a.prototype.getChildById = t.a.prototype.zd;
    t.a.prototype.children = t.a.prototype.children;
    t.a.prototype.initChildren = t.a.prototype.wc;
    t.a.prototype.removeChild = t.a.prototype.removeChild;
    t.a.prototype.on = t.a.prototype.c;
    t.a.prototype.off = t.a.prototype.j;
    t.a.prototype.one = t.a.prototype.R;
    t.a.prototype.trigger = t.a.prototype.l;
    t.a.prototype.triggerReady = t.a.prototype.Ga;
    t.a.prototype.show = t.a.prototype.show;
    t.a.prototype.hide = t.a.prototype.Y;
    t.a.prototype.width = t.a.prototype.width;
    t.a.prototype.height = t.a.prototype.height;
    t.a.prototype.dimensions = t.a.prototype.td;
    t.a.prototype.ready = t.a.prototype.K;
    t.a.prototype.addClass = t.a.prototype.n;
    t.a.prototype.removeClass = t.a.prototype.p;
    t.a.prototype.buildCSSClass = t.a.prototype.T;
    t.a.prototype.localize = t.a.prototype.t;
    t.Player.prototype.ended = t.Player.prototype.ended;
    t.Player.prototype.enterFullWindow = t.Player.prototype.qc;
    t.Player.prototype.exitFullWindow = t.Player.prototype.Cb;
    t.Player.prototype.preload = t.Player.prototype.Da;
    t.Player.prototype.remainingTime = t.Player.prototype.remainingTime;
    t.Player.prototype.supportsFullScreen = t.Player.prototype.Ea;
    t.Player.prototype.currentType = t.Player.prototype.pd;
    t.Player.prototype.requestFullScreen = t.Player.prototype.requestFullScreen;
    t.Player.prototype.requestFullscreen = t.Player.prototype.requestFullscreen;
    t.Player.prototype.cancelFullScreen = t.Player.prototype.cancelFullScreen;
    t.Player.prototype.exitFullscreen = t.Player.prototype.exitFullscreen;
    t.Player.prototype.isFullScreen = t.Player.prototype.isFullScreen;
    t.Player.prototype.isFullscreen = t.Player.prototype.isFullscreen;
    $("videojs.MediaLoader", t.ed);
    $("videojs.TextTrackDisplay", t.fc);
    $("videojs.ControlBar", t.Ja);
    $("videojs.Button", t.u);
    $("videojs.PlayToggle", t.ac);
    $("videojs.FullscreenToggle", t.Ka);
    $("videojs.BigPlayButton", t.fb);
    $("videojs.LoadingSpinner", t.Zb);
    $("videojs.CurrentTimeDisplay", t.hb);
    $("videojs.DurationDisplay", t.ib);
    $("videojs.TimeDivider", t.gc);
    $("videojs.RemainingTimeDisplay", t.pb);
    $("videojs.LiveDisplay", t.Yb);
    $("videojs.ErrorDisplay", t.jb);
    $("videojs.Slider", t.S);
    $("videojs.ProgressControl", t.ob);
    $("videojs.SeekBar", t.cc);
    $("videojs.LoadProgressBar", t.lb);
    $("videojs.PlayProgressBar", t.$b);
    $("videojs.SeekHandle", t.La);
    $("videojs.VolumeControl", t.rb);
    $("videojs.VolumeBar", t.qb);
    $("videojs.VolumeLevel", t.hc);
    $("videojs.VolumeMenuButton", t.sa);
    $("videojs.VolumeHandle", t.sb);
    $("videojs.MuteToggle", t.ia);
    $("videojs.PosterImage", t.ra);
    $("videojs.Menu", t.ha);
    $("videojs.MenuItem", t.J);
    $("videojs.MenuButton", t.N);
    $("videojs.PlaybackRateMenuButton", t.bc);
    t.N.prototype.createItems = t.N.prototype.wa;
    t.U.prototype.createItems = t.U.prototype.wa;
    t.Ia.prototype.createItems = t.Ia.prototype.wa;
    $("videojs.SubtitlesButton", t.Ma);
    $("videojs.CaptionsButton", t.Ha);
    $("videojs.ChaptersButton", t.Ia);
    $("videojs.MediaTechController", t.q);
    t.q.prototype.featuresVolumeControl = t.q.prototype.ue;
    t.q.prototype.featuresFullscreenResize = t.q.prototype.qe;
    t.q.prototype.featuresPlaybackRate = t.q.prototype.re;
    t.q.prototype.featuresProgressEvents = t.q.prototype.se;
    t.q.prototype.featuresTimeupdateEvents = t.q.prototype.te;
    t.q.prototype.setPoster = t.q.prototype.Lc;
    $("videojs.Html5", t.h);
    t.h.Events = t.h.kb;
    t.h.isSupported = t.h.isSupported;
    t.h.canPlaySource = t.h.vb;
    t.h.patchCanPlayType = t.h.Dc;
    t.h.unpatchCanPlayType = t.h.le;
    t.h.prototype.setCurrentTime = t.h.prototype.Qb;
    t.h.prototype.setVolume = t.h.prototype.fe;
    t.h.prototype.setMuted = t.h.prototype.ce;
    t.h.prototype.setPreload = t.h.prototype.ee;
    t.h.prototype.setAutoplay = t.h.prototype.$d;
    t.h.prototype.setLoop = t.h.prototype.be;
    t.h.prototype.enterFullScreen = t.h.prototype.pc;
    t.h.prototype.exitFullScreen = t.h.prototype.wd;
    t.h.prototype.playbackRate = t.h.prototype.playbackRate;
    t.h.prototype.setPlaybackRate = t.h.prototype.de;
    $("videojs.Flash", t.f);
    t.f.isSupported = t.f.isSupported;
    t.f.canPlaySource = t.f.vb;
    t.f.onReady = t.f.onReady;
    t.f.embed = t.f.oc;
    t.f.version = t.f.version;
    $("videojs.TextTrack", t.B);
    t.B.prototype.label = t.B.prototype.label;
    t.B.prototype.kind = t.B.prototype.M;
    t.B.prototype.mode = t.B.prototype.mode;
    t.B.prototype.cues = t.B.prototype.od;
    t.B.prototype.activeCues = t.B.prototype.gd;
    $("videojs.CaptionsTrack", t.Wb);
    $("videojs.SubtitlesTrack", t.dc);
    $("videojs.ChaptersTrack", t.Xb);
    $("videojs.autoSetup", t.jc);
    $("videojs.plugin", t.Yd);
    $("videojs.createTimeRange", t.zb);
    $("videojs.util", t.ga);
    t.ga.mergeOptions = t.ga.Va;
    t.addLanguage = t.hd;
})();