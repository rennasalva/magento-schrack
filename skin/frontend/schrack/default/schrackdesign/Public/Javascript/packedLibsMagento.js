var Validator = Class.create();

Validator.prototype = {
    initialize: function(className, error, test, options) {
        if (typeof test == "function") {
            this.options = $H(options);
            this._test = test;
        } else {
            this.options = $H(test);
            this._test = function() {
                return true;
            };
        }
        this.error = error || "Validation failed.";
        this.className = className;
    },
    test: function(v, elm) {
        return this._test(v, elm) && this.options.all(function(p) {
            return Validator.methods[p.key] ? Validator.methods[p.key](v, elm, p.value) : true;
        });
    }
};

Validator.methods = {
    pattern: function(v, elm, opt) {
        return Validation.get("IsEmpty").test(v) || opt.test(v);
    },
    minLength: function(v, elm, opt) {
        return v.length >= opt;
    },
    maxLength: function(v, elm, opt) {
        return v.length <= opt;
    },
    min: function(v, elm, opt) {
        return v >= parseFloat(opt);
    },
    max: function(v, elm, opt) {
        return v <= parseFloat(opt);
    },
    notOneOf: function(v, elm, opt) {
        return $A(opt).all(function(value) {
            return v != value;
        });
    },
    oneOf: function(v, elm, opt) {
        return $A(opt).any(function(value) {
            return v == value;
        });
    },
    is: function(v, elm, opt) {
        return v == opt;
    },
    isNot: function(v, elm, opt) {
        return v != opt;
    },
    equalToField: function(v, elm, opt) {
        return v == $F(opt);
    },
    notEqualToField: function(v, elm, opt) {
        return v != $F(opt);
    },
    include: function(v, elm, opt) {
        return $A(opt).all(function(value) {
            return Validation.get(value).test(v, elm);
        });
    }
};

var Validation = Class.create();

Validation.defaultOptions = {
    onSubmit: true,
    stopOnFirst: false,
    immediate: false,
    focusOnError: true,
    useTitles: false,
    addClassNameToContainer: false,
    containerClassName: ".input-box",
    onFormValidate: function(result, form) {},
    onElementValidate: function(result, elm) {}
};

Validation.prototype = {
    initialize: function(form, options) {
        this.form = $(form);
        if (!this.form) {
            return;
        }
        this.options = Object.extend({
            onSubmit: Validation.defaultOptions.onSubmit,
            stopOnFirst: Validation.defaultOptions.stopOnFirst,
            immediate: Validation.defaultOptions.immediate,
            focusOnError: Validation.defaultOptions.focusOnError,
            useTitles: Validation.defaultOptions.useTitles,
            onFormValidate: Validation.defaultOptions.onFormValidate,
            onElementValidate: Validation.defaultOptions.onElementValidate
        }, options || {});
        if (this.options.onSubmit) Event.observe(this.form, "submit", this.onSubmit.bind(this), false);
        if (this.options.immediate) {
            Form.getElements(this.form).each(function(input) {
                if (input.tagName.toLowerCase() == "select") {
                    Event.observe(input, "blur", this.onChange.bindAsEventListener(this));
                }
                if (input.type.toLowerCase() == "radio" || input.type.toLowerCase() == "checkbox") {
                    Event.observe(input, "click", this.onChange.bindAsEventListener(this));
                } else {
                    Event.observe(input, "change", this.onChange.bindAsEventListener(this));
                }
            }, this);
        }
    },
    onChange: function(ev) {
        Validation.isOnChange = true;
        Validation.validate(Event.element(ev), {
            useTitle: this.options.useTitles,
            onElementValidate: this.options.onElementValidate
        });
        Validation.isOnChange = false;
    },
    onSubmit: function(ev) {
        if (!this.validate()) Event.stop(ev);
    },
    validate: function() {
        console.log("validation.js -> validate()");
        var result = false;
        var useTitles = this.options.useTitles;
        var callback = this.options.onElementValidate;
        try {
            if (this.options.stopOnFirst) {
                result = Form.getElements(this.form).all(function(elm) {
                    if (elm.hasClassName("local-validation") && !this.isElementInForm(elm, this.form)) {
                        return true;
                    }
                    return Validation.validate(elm, {
                        useTitle: useTitles,
                        onElementValidate: callback
                    });
                }, this);
            } else {
                result = Form.getElements(this.form).collect(function(elm) {
                    if (elm.hasClassName("local-validation") && !this.isElementInForm(elm, this.form)) {
                        return true;
                    }
                    return Validation.validate(elm, {
                        useTitle: useTitles,
                        onElementValidate: callback
                    });
                }, this).all();
            }
        } catch (e) {
            console.log("validation.js -> validate() : error #1");
        }
        if (!result && this.options.focusOnError) {
            try {
                Form.getElements(this.form).findAll(function(elm) {
                    return $(elm).hasClassName("validation-failed");
                }).first().focus();
            } catch (e) {
                console.log("validation.js -> validate() : error #2");
            }
        }
        this.options.onFormValidate(result, this.form);
        return result;
    },
    reset: function() {
        Form.getElements(this.form).each(Validation.reset);
    },
    isElementInForm: function(elm, form) {
        var domForm = elm.up("form");
        if (domForm == form) {
            return true;
        }
        return false;
    }
};

Object.extend(Validation, {
    validate: function(elm, options) {
        options = Object.extend({
            useTitle: false,
            onElementValidate: function(result, elm) {}
        }, options || {});
        elm = $(elm);
        var cn = $w(elm.className);
        return result = cn.all(function(value) {
            var test = Validation.test(value, elm, options.useTitle);
            options.onElementValidate(test, elm);
            return test;
        });
    },
    insertAdvice: function(elm, advice) {
        var container = $(elm).up(".field-row");
        if (container) {
            Element.insert(container, {
                after: advice
            });
        } else if (elm.up("td.value")) {
            elm.up("td.value").insert({
                bottom: advice
            });
        } else if (elm.advaiceContainer && $(elm.advaiceContainer)) {
            $(elm.advaiceContainer).update(advice);
        } else {
            switch (elm.type.toLowerCase()) {
              case "checkbox":
              case "radio":
                var p = elm.parentNode;
                if (p) {
                    Element.insert(p, {
                        bottom: advice
                    });
                } else {
                    Element.insert(elm, {
                        after: advice
                    });
                }
                break;

              default:
                Element.insert(elm, {
                    after: advice
                });
            }
        }
    },
    showAdvice: function(elm, advice, adviceName) {
        if (!elm.advices) {
            elm.advices = new Hash();
        } else {
            elm.advices.each(function(pair) {
                if (!advice || pair.value.id != advice.id) {
                    this.hideAdvice(elm, pair.value);
                }
            }.bind(this));
        }
        elm.advices.set(adviceName, advice);
        if (typeof Effect == "undefined") {
            advice.style.display = "block";
        } else {
            if (!advice._adviceAbsolutize) {
                new Effect.Appear(advice, {
                    duration: 1
                });
            } else {
                Position.absolutize(advice);
                advice.show();
                advice.setStyle({
                    top: advice._adviceTop,
                    left: advice._adviceLeft,
                    width: advice._adviceWidth,
                    "z-index": 1e3
                });
                advice.addClassName("advice-absolute");
            }
        }
    },
    hideAdvice: function(elm, advice) {
        if (advice != null) {
            new Effect.Fade(advice, {
                duration: 1,
                afterFinishInternal: function() {
                    advice.hide();
                }
            });
        }
    },
    updateCallback: function(elm, status) {
        if (typeof elm.callbackFunction != "undefined") {
            eval(elm.callbackFunction + "('" + elm.id + "','" + status + "')");
        }
    },
    ajaxError: function(elm, errorMsg) {
        var name = "validate-ajax";
        var advice = Validation.getAdvice(name, elm);
        if (advice == null) {
            advice = this.createAdvice(name, elm, false, errorMsg);
        }
        this.showAdvice(elm, advice, "validate-ajax");
        this.updateCallback(elm, "failed");
        elm.addClassName("validation-failed");
        elm.addClassName("validate-ajax");
        if (Validation.defaultOptions.addClassNameToContainer && Validation.defaultOptions.containerClassName != "") {
            var container = elm.up(Validation.defaultOptions.containerClassName);
            if (container && this.allowContainerClassName(elm)) {
                container.removeClassName("validation-passed");
                container.addClassName("validation-error");
            }
        }
    },
    allowContainerClassName: function(elm) {
        if (elm.type == "radio" || elm.type == "checkbox") {
            return elm.hasClassName("change-container-classname");
        }
        return true;
    },
    test: function(name, elm, useTitle) {
        var v = Validation.get(name);
        var prop = "__advice" + name.camelize();
        try {
            if (Validation.isVisible(elm) && !v.test($F(elm), elm)) {
                var advice = Validation.getAdvice(name, elm);
                if (advice == null) {
                    advice = this.createAdvice(name, elm, useTitle);
                }
                this.showAdvice(elm, advice, name);
                this.updateCallback(elm, "failed");
                elm[prop] = 1;
                if (!elm.advaiceContainer) {
                    elm.removeClassName("validation-passed");
                    elm.addClassName("validation-failed");
                }
                if (Validation.defaultOptions.addClassNameToContainer && Validation.defaultOptions.containerClassName != "") {
                    var container = elm.up(Validation.defaultOptions.containerClassName);
                    if (container && this.allowContainerClassName(elm)) {
                        container.removeClassName("validation-passed");
                        container.addClassName("validation-error");
                    }
                }
                return false;
            } else {
                var advice = Validation.getAdvice(name, elm);
                this.hideAdvice(elm, advice);
                this.updateCallback(elm, "passed");
                elm[prop] = "";
                elm.removeClassName("validation-failed");
                elm.addClassName("validation-passed");
                if (Validation.defaultOptions.addClassNameToContainer && Validation.defaultOptions.containerClassName != "") {
                    var container = elm.up(Validation.defaultOptions.containerClassName);
                    if (container && !container.down(".validation-failed") && this.allowContainerClassName(elm)) {
                        if (!Validation.get("IsEmpty").test(elm.value) || !this.isVisible(elm)) {
                            container.addClassName("validation-passed");
                        } else {
                            container.removeClassName("validation-passed");
                        }
                        container.removeClassName("validation-error");
                    }
                }
                return true;
            }
        } catch (e) {
            throw e;
        }
    },
    isVisible: function(elm) {
        while (elm.tagName != "BODY") {
            if (!$(elm).visible()) return false;
            elm = elm.parentNode;
        }
        return true;
    },
    getAdvice: function(name, elm) {
        return $("advice-" + name + "-" + Validation.getElmID(elm)) || $("advice-" + Validation.getElmID(elm));
    },
    createAdvice: function(name, elm, useTitle, customError) {
        var v = Validation.get(name);
        var errorMsg = useTitle ? elm && elm.title ? elm.title : v.error : v.error;
        if (customError) {
            errorMsg = customError;
        }
        try {
            if (Translator) {
                errorMsg = Translator.translate(errorMsg);
            }
        } catch (e) {}
        advice = '<div class="validation-advice" id="advice-' + name + "-" + Validation.getElmID(elm) + '" style="display:none">' + errorMsg + "</div>";
        Validation.insertAdvice(elm, advice);
        advice = Validation.getAdvice(name, elm);
        if ($(elm).hasClassName("absolute-advice")) {
            var dimensions = $(elm).getDimensions();
            var originalPosition = Position.cumulativeOffset(elm);
            advice._adviceTop = originalPosition[1] + dimensions.height + "px";
            advice._adviceLeft = originalPosition[0] + "px";
            advice._adviceWidth = dimensions.width + "px";
            advice._adviceAbsolutize = true;
        }
        return advice;
    },
    getElmID: function(elm) {
        return elm.id ? elm.id : elm.name;
    },
    reset: function(elm) {
        elm = $(elm);
        var cn = $w(elm.className);
        cn.each(function(value) {
            var prop = "__advice" + value.camelize();
            if (elm[prop]) {
                var advice = Validation.getAdvice(value, elm);
                if (advice) {
                    advice.hide();
                }
                elm[prop] = "";
            }
            elm.removeClassName("validation-failed");
            elm.removeClassName("validation-passed");
            if (Validation.defaultOptions.addClassNameToContainer && Validation.defaultOptions.containerClassName != "") {
                var container = elm.up(Validation.defaultOptions.containerClassName);
                if (container) {
                    container.removeClassName("validation-passed");
                    container.removeClassName("validation-error");
                }
            }
        });
    },
    add: function(className, error, test, options) {
        var nv = {};
        nv[className] = new Validator(className, error, test, options);
        Object.extend(Validation.methods, nv);
    },
    addAllThese: function(validators) {
        var nv = {};
        $A(validators).each(function(value) {
            nv[value[0]] = new Validator(value[0], value[1], value[2], value.length > 3 ? value[3] : {});
        });
        Object.extend(Validation.methods, nv);
    },
    get: function(name) {
        return Validation.methods[name] ? Validation.methods[name] : Validation.methods["_LikeNoIDIEverSaw_"];
    },
    methods: {
        _LikeNoIDIEverSaw_: new Validator("_LikeNoIDIEverSaw_", "", {})
    }
});

Validation.add("IsEmpty", "", function(v) {
    return v == "" || v == null || v.length == 0 || /^\s+$/.test(v);
});

Validation.addAllThese([ [ "validate-no-html-tags", "HTML tags are not allowed", function(v) {
    return !/<(\/)?\w+/.test(v);
} ], [ "validate-select", "Please select an option.", function(v) {
    return v != "none" && v != null && v.length != 0;
} ], [ "required-entry", "This is a required field.", function(v) {
    return !Validation.get("IsEmpty").test(v);
} ], [ "validate-number", "Please enter a valid number in this field.", function(v) {
    return Validation.get("IsEmpty").test(v) || !isNaN(parseNumber(v)) && /^\s*-?\d*(\.\d*)?\s*$/.test(v);
} ], [ "validate-number-range", "The value is not within the specified range.", function(v, elm) {
    if (Validation.get("IsEmpty").test(v)) {
        return true;
    }
    var numValue = parseNumber(v);
    if (isNaN(numValue)) {
        return false;
    }
    var reRange = /^number-range-(-?[\d.,]+)?-(-?[\d.,]+)?$/, result = true;
    $w(elm.className).each(function(name) {
        var m = reRange.exec(name);
        if (m) {
            result = result && (m[1] == null || m[1] == "" || numValue >= parseNumber(m[1])) && (m[2] == null || m[2] == "" || numValue <= parseNumber(m[2]));
        }
    });
    return result;
} ], [ "validate-digits", "Please use numbers only in this field. Please avoid spaces or other characters such as dots or commas.", function(v) {
    return Validation.get("IsEmpty").test(v) || !/[^\d]/.test(v);
} ], [ "validate-digits-range", "The value is not within the specified range.", function(v, elm) {
    if (Validation.get("IsEmpty").test(v)) {
        return true;
    }
    var numValue = parseNumber(v);
    if (isNaN(numValue)) {
        return false;
    }
    var reRange = /^digits-range-(-?\d+)?-(-?\d+)?$/, result = true;
    $w(elm.className).each(function(name) {
        var m = reRange.exec(name);
        if (m) {
            result = result && (m[1] == null || m[1] == "" || numValue >= parseNumber(m[1])) && (m[2] == null || m[2] == "" || numValue <= parseNumber(m[2]));
        }
    });
    return result;
} ], [ "validate-alpha", "Please use letters only (a-z or A-Z) in this field.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^[a-zA-Z]+$/.test(v);
} ], [ "validate-code", "Please use only letters (a-z), numbers (0-9) or underscore(_) in this field, first character should be a letter.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^[a-z]+[a-z0-9_]+$/.test(v);
} ], [ "validate-code-event", 'Please do not use "event" for an attribute code.', function(v) {
    return Validation.get("IsEmpty").test(v) || !/^(event)$/.test(v);
} ], [ "validate-alphanum", "Please use only letters (a-z or A-Z) or numbers (0-9) only in this field. No spaces or other characters are allowed.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^[a-zA-Z0-9]+$/.test(v);
} ], [ "validate-alphanum-with-spaces", "Please use only letters (a-z or A-Z), numbers (0-9) or spaces only in this field.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^[a-zA-Z0-9 ]+$/.test(v);
} ], [ "validate-street", "Please use only letters (a-z or A-Z) or numbers (0-9) or spaces and # only in this field.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^[ \w]{3,}([A-Za-z]\.)?([ \w]*\#\d+)?(\r\n| )[ \w]{3,}/.test(v);
} ], [ "validate-phoneStrict", "Please enter a valid phone number. For example (123) 456-7890 or 123-456-7890.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^(\()?\d{3}(\))?(-|\s)?\d{3}(-|\s)\d{4}$/.test(v);
} ], [ "validate-phoneLax", "Please enter a valid phone number. For example (123) 456-7890 or 123-456-7890.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^((\d[-. ]?)?((\(\d{3}\))|\d{3}))?[-. ]?\d{3}[-. ]?\d{4}$/.test(v);
} ], [ "validate-fax", "Please enter a valid fax number. For example (123) 456-7890 or 123-456-7890.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^(\()?\d{3}(\))?(-|\s)?\d{3}(-|\s)\d{4}$/.test(v);
} ], [ "validate-date", "Please enter a valid date.", function(v) {
    var test = new Date(v);
    return Validation.get("IsEmpty").test(v) || !isNaN(test);
} ], [ "validate-date-range", "The From Date value should be less than or equal to the To Date value.", function(v, elm) {
    var m = /\bdate-range-(\w+)-(\w+)\b/.exec(elm.className);
    if (!m || m[2] == "to" || Validation.get("IsEmpty").test(v)) {
        return true;
    }
    var currentYear = new Date().getFullYear() + "";
    var normalizedTime = function(v) {
        v = v.split(/[.\/]/);
        if (v[2] && v[2].length < 4) {
            v[2] = currentYear.substr(0, v[2].length) + v[2];
        }
        return new Date(v.join("/")).getTime();
    };
    var dependentElements = Element.select(elm.form, ".validate-date-range.date-range-" + m[1] + "-to");
    return !dependentElements.length || Validation.get("IsEmpty").test(dependentElements[0].value) || normalizedTime(v) <= normalizedTime(dependentElements[0].value);
} ], [ "validate-email", "Please enter a valid email address. For example johndoe@domain.com.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9,!\#\$%&'\*\+\/=\?\^_`\{\|\}~-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*@([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z0-9-]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*\.(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]){2,})$/i.test(v);
} ], [ "validate-emailSender", "Please use only visible characters and spaces.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^[\S ]+$/.test(v);
} ], [ "validate-password", "Please enter 6 or more characters. Leading or trailing spaces will be ignored.", function(v, elm) {
    var pass = v.strip();
    var reMin = new RegExp(/^min-pass-length-[0-9]+$/);
    var minLength = 6;
    $w(elm.className).each(function(name, index) {
        if (name.match(reMin)) {
            minLength = name.split("-")[3];
        }
    });
    return !(v.length > 0 && v.length < minLength) && v.length == pass.length;
} ], [ "validate-admin-password", "Please enter 7 or more characters. Password should contain both numeric and alphabetic characters.", function(v, elm) {
    var pass = v.strip();
    if (0 == pass.length) {
        return true;
    }
    if (!/[a-z]/i.test(v) || !/[0-9]/.test(v)) {
        return false;
    }
    var reMin = new RegExp(/^min-admin-pass-length-[0-9]+$/);
    var minLength = 6;
    $w(elm.className).each(function(name, index) {
        if (name.match(reMin)) {
            minLength = name.split("-")[4];
        }
    });
    return !(pass.length < minLength);
} ], [ "validate-cpassword", "Please make sure your passwords match.", function(v) {
    var conf = $("confirmation") ? $("confirmation") : $$(".validate-cpassword")[0];
    var pass = false;
    if ($("password")) {
        pass = $("password");
    }
    var passwordElements = $$(".validate-password");
    for (var i = 0; i < passwordElements.size(); i++) {
        var passwordElement = passwordElements[i];
        if (passwordElement.up("form").id == conf.up("form").id) {
            pass = passwordElement;
        }
    }
    if ($$(".validate-admin-password").size()) {
        pass = $$(".validate-admin-password")[0];
    }
    return pass.value == conf.value;
} ], [ "validate-both-passwords", "Please make sure your passwords match.", function(v, input) {
    var dependentInput = $(input.form[input.name == "password" ? "confirmation" : "password"]), isEqualValues = input.value == dependentInput.value;
    if (isEqualValues && dependentInput.hasClassName("validation-failed")) {
        Validation.test(this.className, dependentInput);
    }
    return dependentInput.value == "" || isEqualValues;
} ], [ "validate-url", "Please enter a valid URL. Protocol is required (http://, https:// or ftp://)", function(v) {
    v = (v || "").replace(/^\s+/, "").replace(/\s+$/, "");
    return Validation.get("IsEmpty").test(v) || /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(v);
} ], [ "validate-clean-url", "Please enter a valid URL. For example http://www.example.com or www.example.com", function(v) {
    return Validation.get("IsEmpty").test(v) || /^(http|https|ftp):\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+.(com|org|net|dk|at|us|tv|info|uk|co.uk|biz|se)$)(:(\d+))?\/?/i.test(v) || /^(www)((\.[A-Z0-9][A-Z0-9_-]*)+.(com|org|net|dk|at|us|tv|info|uk|co.uk|biz|se)$)(:(\d+))?\/?/i.test(v);
} ], [ "validate-identifier", 'Please enter a valid URL Key. For example "example-page", "example-page.html" or "anotherlevel/example-page".', function(v) {
    return Validation.get("IsEmpty").test(v) || /^[a-z0-9][a-z0-9_\/-]+(\.[a-z0-9_-]+)?$/.test(v);
} ], [ "validate-xml-identifier", "Please enter a valid XML-identifier. For example something_1, block5, id-4.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^[A-Z][A-Z0-9_\/-]*$/i.test(v);
} ], [ "validate-ssn", "Please enter a valid social security number. For example 123-45-6789.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^\d{3}-?\d{2}-?\d{4}$/.test(v);
} ], [ "validate-zip", "Please enter a valid zip code. For example 90602 or 90602-1234.", function(v) {
    return Validation.get("IsEmpty").test(v) || /(^\d{5}$)|(^\d{5}-\d{4}$)/.test(v);
} ], [ "validate-zip-international", "Please enter a valid zip code.", function(v) {
    return true;
} ], [ "validate-date-au", "Please use this date format: dd/mm/yyyy. For example 17/03/2006 for the 17th of March, 2006.", function(v) {
    if (Validation.get("IsEmpty").test(v)) return true;
    var regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
    if (!regex.test(v)) return false;
    var d = new Date(v.replace(regex, "$2/$1/$3"));
    return parseInt(RegExp.$2, 10) == 1 + d.getMonth() && parseInt(RegExp.$1, 10) == d.getDate() && parseInt(RegExp.$3, 10) == d.getFullYear();
} ], [ "validate-currency-dollar", "Please enter a valid $ amount. For example $100.00.", function(v) {
    return Validation.get("IsEmpty").test(v) || /^\$?\-?([1-9]{1}[0-9]{0,2}(\,[0-9]{3})*(\.[0-9]{0,2})?|[1-9]{1}\d*(\.[0-9]{0,2})?|0(\.[0-9]{0,2})?|(\.[0-9]{1,2})?)$/.test(v);
} ], [ "validate-one-required", "Please select one of the above options.", function(v, elm) {
    var p = elm.parentNode;
    var options = p.getElementsByTagName("INPUT");
    return $A(options).any(function(elm) {
        return $F(elm);
    });
} ], [ "validate-one-required-by-name", "Please select one of the options.", function(v, elm) {
    var inputs = $$('input[name="' + elm.name.replace(/([\\"])/g, "\\$1") + '"]');
    var error = 1;
    for (var i = 0; i < inputs.length; i++) {
        if ((inputs[i].type == "checkbox" || inputs[i].type == "radio") && inputs[i].checked == true) {
            error = 0;
        }
        if (Validation.isOnChange && (inputs[i].type == "checkbox" || inputs[i].type == "radio")) {
            Validation.reset(inputs[i]);
        }
    }
    if (error == 0) {
        return true;
    } else {
        return false;
    }
} ], [ "validate-not-negative-number", "Please enter a number 0 or greater in this field.", function(v) {
    if (Validation.get("IsEmpty").test(v)) {
        return true;
    }
    v = parseNumber(v);
    return !isNaN(v) && v >= 0;
} ], [ "validate-zero-or-greater", "Please enter a number 0 or greater in this field.", function(v) {
    return Validation.get("validate-not-negative-number").test(v);
} ], [ "validate-greater-than-zero", "Please enter a number greater than 0 in this field.", function(v) {
    if (Validation.get("IsEmpty").test(v)) {
        return true;
    }
    v = parseNumber(v);
    return !isNaN(v) && v > 0;
} ], [ "validate-special-price", "The Special Price is active only when lower than the Actual Price.", function(v) {
    var priceInput = $("price");
    var priceType = $("price_type");
    var priceValue = parseFloat(v);
    if (!priceInput || Validation.get("IsEmpty").test(v) || !Validation.get("validate-number").test(v)) {
        return true;
    }
    if (priceType) {
        return priceType && priceValue <= 99.99;
    }
    return priceValue < parseFloat($F(priceInput));
} ], [ "validate-state", "Please select State/Province.", function(v) {
    return v != 0 || v == "";
} ], [ "validate-new-password", "Please enter 6 or more characters. Leading or trailing spaces will be ignored.", function(v, elm) {
    if (!Validation.get("validate-password").test(v, elm)) return false;
    if (Validation.get("IsEmpty").test(v) && v != "") return false;
    return true;
} ], [ "validate-cc-number", "Please enter a valid credit card number.", function(v, elm) {
    var ccTypeContainer = $(elm.id.substr(0, elm.id.indexOf("_cc_number")) + "_cc_type");
    if (ccTypeContainer && typeof Validation.creditCartTypes.get(ccTypeContainer.value) != "undefined" && Validation.creditCartTypes.get(ccTypeContainer.value)[2] == false) {
        if (!Validation.get("IsEmpty").test(v) && Validation.get("validate-digits").test(v)) {
            return true;
        } else {
            return false;
        }
    }
    return validateCreditCard(v);
} ], [ "validate-cc-type", "Credit card number does not match credit card type.", function(v, elm) {
    elm.value = removeDelimiters(elm.value);
    v = removeDelimiters(v);
    var ccTypeContainer = $(elm.id.substr(0, elm.id.indexOf("_cc_number")) + "_cc_type");
    if (!ccTypeContainer) {
        return true;
    }
    var ccType = ccTypeContainer.value;
    if (typeof Validation.creditCartTypes.get(ccType) == "undefined") {
        return false;
    }
    if (Validation.creditCartTypes.get(ccType)[0] == false) {
        return true;
    }
    var validationFailure = false;
    Validation.creditCartTypes.each(function(pair) {
        if (pair.key == ccType) {
            if (pair.value[0] && !v.match(pair.value[0])) {
                validationFailure = true;
            }
            throw $break;
        }
    });
    if (validationFailure) {
        return false;
    }
    if (ccTypeContainer.hasClassName("validation-failed") && Validation.isOnChange) {
        Validation.validate(ccTypeContainer);
    }
    return true;
} ], [ "validate-cc-type-select", "Card type does not match credit card number.", function(v, elm) {
    var ccNumberContainer = $(elm.id.substr(0, elm.id.indexOf("_cc_type")) + "_cc_number");
    if (Validation.isOnChange && Validation.get("IsEmpty").test(ccNumberContainer.value)) {
        return true;
    }
    if (Validation.get("validate-cc-type").test(ccNumberContainer.value, ccNumberContainer)) {
        Validation.validate(ccNumberContainer);
    }
    return Validation.get("validate-cc-type").test(ccNumberContainer.value, ccNumberContainer);
} ], [ "validate-cc-exp", "Incorrect credit card expiration date.", function(v, elm) {
    var ccExpMonth = v;
    var ccExpYear = $(elm.id.substr(0, elm.id.indexOf("_expiration")) + "_expiration_yr").value;
    var currentTime = new Date();
    var currentMonth = currentTime.getMonth() + 1;
    var currentYear = currentTime.getFullYear();
    if (ccExpMonth < currentMonth && ccExpYear == currentYear) {
        return false;
    }
    return true;
} ], [ "validate-cc-cvn", "Please enter a valid credit card verification number.", function(v, elm) {
    var ccTypeContainer = $(elm.id.substr(0, elm.id.indexOf("_cc_cid")) + "_cc_type");
    if (!ccTypeContainer) {
        return true;
    }
    var ccType = ccTypeContainer.value;
    if (typeof Validation.creditCartTypes.get(ccType) == "undefined") {
        return false;
    }
    var re = Validation.creditCartTypes.get(ccType)[1];
    if (v.match(re)) {
        return true;
    }
    return false;
} ], [ "validate-ajax", "", function(v, elm) {
    return true;
} ], [ "validate-data", "Please use only letters (a-z or A-Z), numbers (0-9) or underscore(_) in this field, first character should be a letter.", function(v) {
    if (v != "" && v) {
        return /^[A-Za-z]+[A-Za-z0-9_]+$/.test(v);
    }
    return true;
} ], [ "validate-css-length", "Please input a valid CSS-length. For example 100px or 77pt or 20em or .5ex or 50%.", function(v) {
    if (v != "" && v) {
        return /^[0-9\.]+(px|pt|em|ex|%)?$/.test(v) && !/\..*\./.test(v) && !/\.$/.test(v);
    }
    return true;
} ], [ "validate-length", "Text length does not satisfy specified text range.", function(v, elm) {
    var reMax = new RegExp(/^maximum-length-[0-9]+$/);
    var reMin = new RegExp(/^minimum-length-[0-9]+$/);
    var result = true;
    $w(elm.className).each(function(name, index) {
        if (name.match(reMax) && result) {
            var length = name.split("-")[2];
            result = v.length <= length;
        }
        if (name.match(reMin) && result && !Validation.get("IsEmpty").test(v)) {
            var length = name.split("-")[2];
            result = v.length >= length;
        }
    });
    return result;
} ], [ "validate-percents", "Please enter a number lower than 100.", {
    max: 100
} ], [ "required-file", "Please select a file", function(v, elm) {
    var result = !Validation.get("IsEmpty").test(v);
    if (result === false) {
        ovId = elm.id + "_value";
        if ($(ovId)) {
            result = !Validation.get("IsEmpty").test($(ovId).value);
        }
    }
    return result;
} ], [ "validate-cc-ukss", "Please enter issue number or start date for switch/solo card type.", function(v, elm) {
    var endposition;
    if (elm.id.match(/(.)+_cc_issue$/)) {
        endposition = elm.id.indexOf("_cc_issue");
    } else if (elm.id.match(/(.)+_start_month$/)) {
        endposition = elm.id.indexOf("_start_month");
    } else {
        endposition = elm.id.indexOf("_start_year");
    }
    var prefix = elm.id.substr(0, endposition);
    var ccTypeContainer = $(prefix + "_cc_type");
    if (!ccTypeContainer) {
        return true;
    }
    var ccType = ccTypeContainer.value;
    if ([ "SS", "SM", "SO" ].indexOf(ccType) == -1) {
        return true;
    }
    $(prefix + "_cc_issue").advaiceContainer = $(prefix + "_start_month").advaiceContainer = $(prefix + "_start_year").advaiceContainer = $(prefix + "_cc_type_ss_div").down("ul li.adv-container");
    var ccIssue = $(prefix + "_cc_issue").value;
    var ccSMonth = $(prefix + "_start_month").value;
    var ccSYear = $(prefix + "_start_year").value;
    var ccStartDatePresent = ccSMonth && ccSYear ? true : false;
    if (!ccStartDatePresent && !ccIssue) {
        return false;
    }
    return true;
} ] ]);

function removeDelimiters(v) {
    v = v.replace(/\s/g, "");
    v = v.replace(/\-/g, "");
    return v;
}

function parseNumber(v) {
    if (typeof v != "string") {
        return parseFloat(v);
    }
    var isDot = v.indexOf(".");
    var isComa = v.indexOf(",");
    if (isDot != -1 && isComa != -1) {
        if (isComa > isDot) {
            v = v.replace(".", "").replace(",", ".");
        } else {
            v = v.replace(",", "");
        }
    } else if (isComa != -1) {
        v = v.replace(",", ".");
    }
    return parseFloat(v);
}

Validation.creditCartTypes = $H({
    SO: [ new RegExp("^(6334[5-9]([0-9]{11}|[0-9]{13,14}))|(6767([0-9]{12}|[0-9]{14,15}))$"), new RegExp("^([0-9]{3}|[0-9]{4})?$"), true ],
    VI: [ new RegExp("^4[0-9]{12}([0-9]{3})?$"), new RegExp("^[0-9]{3}$"), true ],
    MC: [ new RegExp("^(5[1-5][0-9]{14}|2(22[1-9][0-9]{12}|2[3-9][0-9]{13}|[3-6][0-9]{14}|7[0-1][0-9]{13}|720[0-9]{12}))$"), new RegExp("^[0-9]{3}$"), true ],
    AE: [ new RegExp("^3[47][0-9]{13}$"), new RegExp("^[0-9]{4}$"), true ],
    DI: [ new RegExp("^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})|36[0-9]{12}|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}|5[0-9]{14}))$"), new RegExp("^[0-9]{3}$"), true ],
    JCB: [ new RegExp("^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})|36[0-9]{12}|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}|5[0-9]{14}))$"), new RegExp("^[0-9]{3,4}$"), true ],
    DICL: [ new RegExp("^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})|36[0-9]{12}|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}|5[0-9]{14}))$"), new RegExp("^[0-9]{3}$"), true ],
    SM: [ new RegExp("(^(5[0678])[0-9]{11,18}$)|(^(6[^05])[0-9]{11,18}$)|(^(601)[^1][0-9]{9,16}$)|(^(6011)[0-9]{9,11}$)|(^(6011)[0-9]{13,16}$)|(^(65)[0-9]{11,13}$)|(^(65)[0-9]{15,18}$)|(^(49030)[2-9]([0-9]{10}$|[0-9]{12,13}$))|(^(49033)[5-9]([0-9]{10}$|[0-9]{12,13}$))|(^(49110)[1-2]([0-9]{10}$|[0-9]{12,13}$))|(^(49117)[4-9]([0-9]{10}$|[0-9]{12,13}$))|(^(49118)[0-2]([0-9]{10}$|[0-9]{12,13}$))|(^(4936)([0-9]{12}$|[0-9]{14,15}$))"), new RegExp("^([0-9]{3}|[0-9]{4})?$"), true ],
    OT: [ false, new RegExp("^([0-9]{3}|[0-9]{4})?$"), false ]
});

var Product = Product || {};

Product.OptionsPrice = Class.create();

Product.OptionsPrice.prototype = {
    initialize: function(config) {
        this.productId = config.productId;
        this.priceFormat = config.priceFormat;
        this.includeTax = config.includeTax;
        this.defaultTax = config.defaultTax;
        this.currentTax = config.currentTax;
        this.productPrice = config.productPrice;
        this.showIncludeTax = config.showIncludeTax;
        this.showBothPrices = config.showBothPrices;
        this.productOldPrice = config.productOldPrice;
        this.priceInclTax = config.priceInclTax;
        this.priceExclTax = config.priceExclTax;
        this.skipCalculate = config.skipCalculate;
        this.duplicateIdSuffix = config.idSuffix;
        this.specialTaxPrice = config.specialTaxPrice;
        this.tierPrices = config.tierPrices;
        this.tierPricesInclTax = config.tierPricesInclTax;
        this.oldPlusDisposition = config.oldPlusDisposition;
        this.plusDisposition = config.plusDisposition;
        this.plusDispositionTax = config.plusDispositionTax;
        this.oldMinusDisposition = config.oldMinusDisposition;
        this.minusDisposition = config.minusDisposition;
        this.exclDisposition = config.exclDisposition;
        this.optionPrices = {};
        this.customPrices = {};
        this.containers = {};
        this.displayZeroPrice = true;
        this.initPrices();
    },
    setDuplicateIdSuffix: function(idSuffix) {
        this.duplicateIdSuffix = idSuffix;
    },
    initPrices: function() {
        this.containers[0] = "product-price-" + this.productId;
        this.containers[1] = "bundle-price-" + this.productId;
        this.containers[2] = "price-including-tax-" + this.productId;
        this.containers[3] = "price-excluding-tax-" + this.productId;
        this.containers[4] = "old-price-" + this.productId;
    },
    changePrice: function(key, price) {
        this.optionPrices[key] = price;
    },
    addCustomPrices: function(key, price) {
        this.customPrices[key] = price;
    },
    getOptionPrices: function() {
        var price = 0;
        var nonTaxable = 0;
        var oldPrice = 0;
        var priceInclTax = 0;
        var currentTax = this.currentTax;
        $H(this.optionPrices).each(function(pair) {
            if ("undefined" != typeof pair.value.price && "undefined" != typeof pair.value.oldPrice) {
                price += parseFloat(pair.value.price);
                oldPrice += parseFloat(pair.value.oldPrice);
            } else if (pair.key == "nontaxable") {
                nonTaxable = pair.value;
            } else if (pair.key == "priceInclTax") {
                priceInclTax += pair.value;
            } else if (pair.key == "optionsPriceInclTax") {
                priceInclTax += pair.value * (100 + currentTax) / 100;
            } else {
                price += parseFloat(pair.value);
                oldPrice += parseFloat(pair.value);
            }
        });
        return [ price, nonTaxable, oldPrice, priceInclTax ];
    },
    reload: function() {
        var price;
        var formattedPrice;
        var optionPrices = this.getOptionPrices();
        var nonTaxable = optionPrices[1];
        var optionOldPrice = optionPrices[2];
        var priceInclTax = optionPrices[3];
        optionPrices = optionPrices[0];
        $H(this.containers).each(function(pair) {
            var _productPrice;
            var _plusDisposition;
            var _minusDisposition;
            var _priceInclTax;
            var excl;
            var incl;
            var tax;
            if ($(pair.value) == null) {
                pair.value = "product-price-weee-" + this.productId;
            }
            if ($(pair.value)) {
                if (pair.value == "old-price-" + this.productId && this.productOldPrice != this.productPrice) {
                    _productPrice = this.productOldPrice;
                    _plusDisposition = this.oldPlusDisposition;
                    _minusDisposition = this.oldMinusDisposition;
                } else {
                    _productPrice = this.productPrice;
                    _plusDisposition = this.plusDisposition;
                    _minusDisposition = this.minusDisposition;
                }
                _priceInclTax = priceInclTax;
                if (pair.value == "old-price-" + this.productId && optionOldPrice !== undefined) {
                    price = optionOldPrice + parseFloat(_productPrice);
                } else if (this.specialTaxPrice == "true" && this.priceInclTax !== undefined && this.priceExclTax !== undefined) {
                    price = optionPrices + parseFloat(this.priceExclTax);
                    _priceInclTax += this.priceInclTax;
                } else {
                    price = optionPrices + parseFloat(_productPrice);
                    _priceInclTax += parseFloat(_productPrice) * (100 + this.currentTax) / 100;
                }
                if (this.specialTaxPrice == "true") {
                    excl = price;
                    incl = _priceInclTax;
                } else if (this.includeTax == "true") {
                    tax = price / (100 + this.defaultTax) * this.defaultTax;
                    excl = price - tax;
                    incl = excl * (1 + this.currentTax / 100);
                } else {
                    tax = price * (this.currentTax / 100);
                    excl = price;
                    incl = excl + tax;
                }
                var subPrice = 0;
                var subPriceincludeTax = 0;
                Object.values(this.customPrices).each(function(el) {
                    if (el.excludeTax && el.includeTax) {
                        subPrice += parseFloat(el.excludeTax);
                        subPriceincludeTax += parseFloat(el.includeTax);
                    } else {
                        subPrice += parseFloat(el.price);
                        subPriceincludeTax += parseFloat(el.price);
                    }
                });
                excl += subPrice;
                incl += subPriceincludeTax;
                if (typeof this.exclDisposition == "undefined") {
                    excl += parseFloat(_plusDisposition);
                }
                incl += parseFloat(_plusDisposition) + parseFloat(this.plusDispositionTax);
                excl -= parseFloat(_minusDisposition);
                incl -= parseFloat(_minusDisposition);
                excl += parseFloat(nonTaxable);
                incl += parseFloat(nonTaxable);
                if (pair.value == "price-including-tax-" + this.productId) {
                    price = incl;
                } else if (pair.value == "price-excluding-tax-" + this.productId) {
                    price = excl;
                } else if (pair.value == "old-price-" + this.productId) {
                    if (this.showIncludeTax || this.showBothPrices) {
                        price = incl;
                    } else {
                        price = excl;
                    }
                } else {
                    if (this.showIncludeTax) {
                        price = incl;
                    } else {
                        price = excl;
                    }
                }
                if (price < 0) price = 0;
                if (price > 0 || this.displayZeroPrice) {
                    formattedPrice = this.formatPrice(price);
                } else {
                    formattedPrice = "";
                }
                if ($(pair.value).select(".price")[0]) {
                    $(pair.value).select(".price")[0].innerHTML = formattedPrice;
                    if ($(pair.value + this.duplicateIdSuffix) && $(pair.value + this.duplicateIdSuffix).select(".price")[0]) {
                        $(pair.value + this.duplicateIdSuffix).select(".price")[0].innerHTML = formattedPrice;
                    }
                } else {
                    $(pair.value).innerHTML = formattedPrice;
                    if ($(pair.value + this.duplicateIdSuffix)) {
                        $(pair.value + this.duplicateIdSuffix).innerHTML = formattedPrice;
                    }
                }
            }
        }.bind(this));
        if (typeof skipTierPricePercentUpdate === "undefined" && typeof this.tierPrices !== "undefined") {
            for (var i = 0; i < this.tierPrices.length; i++) {
                $$(".benefit").each(function(el) {
                    var parsePrice = function(html) {
                        var format = this.priceFormat;
                        var decimalSymbol = format.decimalSymbol === undefined ? "," : format.decimalSymbol;
                        var regexStr = "[^0-9-" + decimalSymbol + "]";
                        html = html.replace(new RegExp(regexStr, "g"), "");
                        html = html.replace(decimalSymbol, ".");
                        return parseFloat(html);
                    }.bind(this);
                    var updateTierPriceInfo = function(priceEl, tierPriceDiff, tierPriceEl, benefitEl) {
                        if (typeof tierPriceEl === "undefined") {
                            return;
                        }
                        var price = parsePrice(priceEl.innerHTML);
                        var tierPrice = price + tierPriceDiff;
                        tierPriceEl.innerHTML = this.formatPrice(tierPrice);
                        var $percent = Selector.findChildElements(benefitEl, [ ".percent.tier-" + i ]);
                        $percent.each(function(el) {
                            el.innerHTML = Math.ceil(100 - 100 / price * tierPrice);
                        });
                    }.bind(this);
                    var tierPriceElArray = $$(".tier-price.tier-" + i + " .price");
                    if (this.showBothPrices) {
                        var containerExclTax = $(this.containers[3]);
                        var tierPriceExclTaxDiff = this.tierPrices[i];
                        var tierPriceExclTaxEl = tierPriceElArray[0];
                        updateTierPriceInfo(containerExclTax, tierPriceExclTaxDiff, tierPriceExclTaxEl, el);
                        var containerInclTax = $(this.containers[2]);
                        var tierPriceInclTaxDiff = this.tierPricesInclTax[i];
                        var tierPriceInclTaxEl = tierPriceElArray[1];
                        updateTierPriceInfo(containerInclTax, tierPriceInclTaxDiff, tierPriceInclTaxEl, el);
                    } else if (this.showIncludeTax) {
                        var container = $(this.containers[0]);
                        var tierPriceInclTaxDiff = this.tierPricesInclTax[i];
                        var tierPriceInclTaxEl = tierPriceElArray[0];
                        updateTierPriceInfo(container, tierPriceInclTaxDiff, tierPriceInclTaxEl, el);
                    } else {
                        var container = $(this.containers[0]);
                        var tierPriceExclTaxDiff = this.tierPrices[i];
                        var tierPriceExclTaxEl = tierPriceElArray[0];
                        updateTierPriceInfo(container, tierPriceExclTaxDiff, tierPriceExclTaxEl, el);
                    }
                }, this);
            }
        }
    },
    formatPrice: function(price) {
        return formatCurrency(price, this.priceFormat);
    }
};

var Builder = {
    NODEMAP: {
        AREA: "map",
        CAPTION: "table",
        COL: "table",
        COLGROUP: "table",
        LEGEND: "fieldset",
        OPTGROUP: "select",
        OPTION: "select",
        PARAM: "object",
        TBODY: "table",
        TD: "table",
        TFOOT: "table",
        TH: "table",
        THEAD: "table",
        TR: "table"
    },
    node: function(elementName) {
        elementName = elementName.toUpperCase();
        var parentTag = this.NODEMAP[elementName] || "div";
        var parentElement = document.createElement(parentTag);
        try {
            parentElement.innerHTML = "<" + elementName + "></" + elementName + ">";
        } catch (e) {}
        var element = parentElement.firstChild || null;
        if (element && element.tagName.toUpperCase() != elementName) element = element.getElementsByTagName(elementName)[0];
        if (!element) element = document.createElement(elementName);
        if (!element) return;
        if (arguments[1]) if (this._isStringOrNumber(arguments[1]) || arguments[1] instanceof Array || arguments[1].tagName) {
            this._children(element, arguments[1]);
        } else {
            var attrs = this._attributes(arguments[1]);
            if (attrs.length) {
                try {
                    parentElement.innerHTML = "<" + elementName + " " + attrs + "></" + elementName + ">";
                } catch (e) {}
                element = parentElement.firstChild || null;
                if (!element) {
                    element = document.createElement(elementName);
                    for (attr in arguments[1]) element[attr == "class" ? "className" : attr] = arguments[1][attr];
                }
                if (element.tagName.toUpperCase() != elementName) element = parentElement.getElementsByTagName(elementName)[0];
            }
        }
        if (arguments[2]) this._children(element, arguments[2]);
        return $(element);
    },
    _text: function(text) {
        return document.createTextNode(text);
    },
    ATTR_MAP: {
        className: "class",
        htmlFor: "for"
    },
    _attributes: function(attributes) {
        var attrs = [];
        for (attribute in attributes) attrs.push((attribute in this.ATTR_MAP ? this.ATTR_MAP[attribute] : attribute) + '="' + attributes[attribute].toString().escapeHTML().gsub(/"/, "&quot;") + '"');
        return attrs.join(" ");
    },
    _children: function(element, children) {
        if (children.tagName) {
            element.appendChild(children);
            return;
        }
        if (typeof children == "object") {
            children.flatten().each(function(e) {
                if (typeof e == "object") element.appendChild(e); else if (Builder._isStringOrNumber(e)) element.appendChild(Builder._text(e));
            });
        } else if (Builder._isStringOrNumber(children)) element.appendChild(Builder._text(children));
    },
    _isStringOrNumber: function(param) {
        return typeof param == "string" || typeof param == "number";
    },
    build: function(html) {
        var element = this.node("div");
        $(element).update(html.strip());
        return element.down();
    },
    dump: function(scope) {
        if (typeof scope != "object" && typeof scope != "function") scope = window;
        var tags = ("A ABBR ACRONYM ADDRESS APPLET AREA B BASE BASEFONT BDO BIG BLOCKQUOTE BODY " + "BR BUTTON CAPTION CENTER CITE CODE COL COLGROUP DD DEL DFN DIR DIV DL DT EM FIELDSET " + "FONT FORM FRAME FRAMESET H1 H2 H3 H4 H5 H6 HEAD HR HTML I IFRAME IMG INPUT INS ISINDEX " + "KBD LABEL LEGEND LI LINK MAP MENU META NOFRAMES NOSCRIPT OBJECT OL OPTGROUP OPTION P " + "PARAM PRE Q S SAMP SCRIPT SELECT SMALL SPAN STRIKE STRONG STYLE SUB SUP TABLE TBODY TD " + "TEXTAREA TFOOT TH THEAD TITLE TR TT U UL VAR").split(/\s+/);
        tags.each(function(tag) {
            scope[tag] = function() {
                return Builder.node.apply(Builder, [ tag ].concat($A(arguments)));
            };
        });
    }
};

String.prototype.parseColor = function() {
    var color = "#";
    if (this.slice(0, 4) == "rgb(") {
        var cols = this.slice(4, this.length - 1).split(",");
        var i = 0;
        do {
            color += parseInt(cols[i]).toColorPart();
        } while (++i < 3);
    } else {
        if (this.slice(0, 1) == "#") {
            if (this.length == 4) for (var i = 1; i < 4; i++) color += (this.charAt(i) + this.charAt(i)).toLowerCase();
            if (this.length == 7) color = this.toLowerCase();
        }
    }
    return color.length == 7 ? color : arguments[0] || this;
};

Element.collectTextNodes = function(element) {
    return $A($(element).childNodes).collect(function(node) {
        return node.nodeType == 3 ? node.nodeValue : node.hasChildNodes() ? Element.collectTextNodes(node) : "";
    }).flatten().join("");
};

Element.collectTextNodesIgnoreClass = function(element, className) {
    return $A($(element).childNodes).collect(function(node) {
        return node.nodeType == 3 ? node.nodeValue : node.hasChildNodes() && !Element.hasClassName(node, className) ? Element.collectTextNodesIgnoreClass(node, className) : "";
    }).flatten().join("");
};

Element.setContentZoom = function(element, percent) {
    element = $(element);
    element.setStyle({
        fontSize: percent / 100 + "em"
    });
    if (Prototype.Browser.WebKit) window.scrollBy(0, 0);
    return element;
};

Element.getInlineOpacity = function(element) {
    return $(element).style.opacity || "";
};

Element.forceRerendering = function(element) {
    try {
        element = $(element);
        var n = document.createTextNode(" ");
        element.appendChild(n);
        element.removeChild(n);
    } catch (e) {}
};

var Effect = {
    _elementDoesNotExistError: {
        name: "ElementDoesNotExistError",
        message: "The specified DOM element does not exist, but is required for this effect to operate"
    },
    Transitions: {
        linear: Prototype.K,
        sinoidal: function(pos) {
            return -Math.cos(pos * Math.PI) / 2 + .5;
        },
        reverse: function(pos) {
            return 1 - pos;
        },
        flicker: function(pos) {
            var pos = -Math.cos(pos * Math.PI) / 4 + .75 + Math.random() / 4;
            return pos > 1 ? 1 : pos;
        },
        wobble: function(pos) {
            return -Math.cos(pos * Math.PI * (9 * pos)) / 2 + .5;
        },
        pulse: function(pos, pulses) {
            return -Math.cos(pos * ((pulses || 5) - .5) * 2 * Math.PI) / 2 + .5;
        },
        spring: function(pos) {
            return 1 - Math.cos(pos * 4.5 * Math.PI) * Math.exp(-pos * 6);
        },
        none: function(pos) {
            return 0;
        },
        full: function(pos) {
            return 1;
        }
    },
    DefaultOptions: {
        duration: 1,
        fps: 100,
        sync: false,
        from: 0,
        to: 1,
        delay: 0,
        queue: "parallel"
    },
    tagifyText: function(element) {
        var tagifyStyle = "position:relative";
        if (Prototype.Browser.IE) tagifyStyle += ";zoom:1";
        element = $(element);
        $A(element.childNodes).each(function(child) {
            if (child.nodeType == 3) {
                child.nodeValue.toArray().each(function(character) {
                    element.insertBefore(new Element("span", {
                        style: tagifyStyle
                    }).update(character == " " ? String.fromCharCode(160) : character), child);
                });
                Element.remove(child);
            }
        });
    },
    multiple: function(element, effect) {
        var elements;
        if ((typeof element == "object" || Object.isFunction(element)) && element.length) elements = element; else elements = $(element).childNodes;
        var options = Object.extend({
            speed: .1,
            delay: 0
        }, arguments[2] || {});
        var masterDelay = options.delay;
        $A(elements).each(function(element, index) {
            new effect(element, Object.extend(options, {
                delay: index * options.speed + masterDelay
            }));
        });
    },
    PAIRS: {
        slide: [ "SlideDown", "SlideUp" ],
        blind: [ "BlindDown", "BlindUp" ],
        appear: [ "Appear", "Fade" ]
    },
    toggle: function(element, effect) {
        element = $(element);
        effect = (effect || "appear").toLowerCase();
        var options = Object.extend({
            queue: {
                position: "end",
                scope: element.id || "global",
                limit: 1
            }
        }, arguments[2] || {});
        Effect[element.visible() ? Effect.PAIRS[effect][1] : Effect.PAIRS[effect][0]](element, options);
    }
};

Effect.DefaultOptions.transition = Effect.Transitions.sinoidal;

Effect.ScopedQueue = Class.create(Enumerable, {
    initialize: function() {
        this.effects = [];
        this.interval = null;
    },
    _each: function(iterator) {
        this.effects._each(iterator);
    },
    add: function(effect) {
        var timestamp = new Date().getTime();
        var position = Object.isString(effect.options.queue) ? effect.options.queue : effect.options.queue.position;
        switch (position) {
          case "front":
            this.effects.findAll(function(e) {
                return e.state == "idle";
            }).each(function(e) {
                e.startOn += effect.finishOn;
                e.finishOn += effect.finishOn;
            });
            break;

          case "with-last":
            timestamp = this.effects.pluck("startOn").max() || timestamp;
            break;

          case "end":
            timestamp = this.effects.pluck("finishOn").max() || timestamp;
            break;
        }
        effect.startOn += timestamp;
        effect.finishOn += timestamp;
        if (!effect.options.queue.limit || this.effects.length < effect.options.queue.limit) this.effects.push(effect);
        if (!this.interval) this.interval = setInterval(this.loop.bind(this), 15);
    },
    remove: function(effect) {
        this.effects = this.effects.reject(function(e) {
            return e == effect;
        });
        if (this.effects.length == 0) {
            clearInterval(this.interval);
            this.interval = null;
        }
    },
    loop: function() {
        var timePos = new Date().getTime();
        for (var i = 0, len = this.effects.length; i < len; i++) this.effects[i] && this.effects[i].loop(timePos);
    }
});

Effect.Queues = {
    instances: $H(),
    get: function(queueName) {
        if (!Object.isString(queueName)) return queueName;
        return this.instances.get(queueName) || this.instances.set(queueName, new Effect.ScopedQueue());
    }
};

Effect.Queue = Effect.Queues.get("global");

Effect.Base = Class.create({
    position: null,
    start: function(options) {
        function codeForEvent(options, eventName) {
            return (options[eventName + "Internal"] ? "this.options." + eventName + "Internal(this);" : "") + (options[eventName] ? "this.options." + eventName + "(this);" : "");
        }
        if (options && options.transition === false) options.transition = Effect.Transitions.linear;
        this.options = Object.extend(Object.extend({}, Effect.DefaultOptions), options || {});
        this.currentFrame = 0;
        this.state = "idle";
        this.startOn = this.options.delay * 1e3;
        this.finishOn = this.startOn + this.options.duration * 1e3;
        this.fromToDelta = this.options.to - this.options.from;
        this.totalTime = this.finishOn - this.startOn;
        this.totalFrames = this.options.fps * this.options.duration;
        this.render = function() {
            function dispatch(effect, eventName) {
                if (effect.options[eventName + "Internal"]) effect.options[eventName + "Internal"](effect);
                if (effect.options[eventName]) effect.options[eventName](effect);
            }
            return function(pos) {
                if (this.state === "idle") {
                    this.state = "running";
                    dispatch(this, "beforeSetup");
                    if (this.setup) this.setup();
                    dispatch(this, "afterSetup");
                }
                if (this.state === "running") {
                    pos = this.options.transition(pos) * this.fromToDelta + this.options.from;
                    this.position = pos;
                    dispatch(this, "beforeUpdate");
                    if (this.update) this.update(pos);
                    dispatch(this, "afterUpdate");
                }
            };
        }();
        this.event("beforeStart");
        if (!this.options.sync) Effect.Queues.get(Object.isString(this.options.queue) ? "global" : this.options.queue.scope).add(this);
    },
    loop: function(timePos) {
        if (timePos >= this.startOn) {
            if (timePos >= this.finishOn) {
                this.render(1);
                this.cancel();
                this.event("beforeFinish");
                if (this.finish) this.finish();
                this.event("afterFinish");
                return;
            }
            var pos = (timePos - this.startOn) / this.totalTime, frame = (pos * this.totalFrames).round();
            if (frame > this.currentFrame) {
                this.render(pos);
                this.currentFrame = frame;
            }
        }
    },
    cancel: function() {
        if (!this.options.sync) Effect.Queues.get(Object.isString(this.options.queue) ? "global" : this.options.queue.scope).remove(this);
        this.state = "finished";
    },
    event: function(eventName) {
        if (this.options[eventName + "Internal"]) this.options[eventName + "Internal"](this);
        if (this.options[eventName]) this.options[eventName](this);
    },
    inspect: function() {
        var data = $H();
        for (property in this) if (!Object.isFunction(this[property])) data.set(property, this[property]);
        return "#<Effect:" + data.inspect() + ",options:" + $H(this.options).inspect() + ">";
    }
});

Effect.Parallel = Class.create(Effect.Base, {
    initialize: function(effects) {
        this.effects = effects || [];
        this.start(arguments[1]);
    },
    update: function(position) {
        this.effects.invoke("render", position);
    },
    finish: function(position) {
        this.effects.each(function(effect) {
            effect.render(1);
            effect.cancel();
            effect.event("beforeFinish");
            if (effect.finish) effect.finish(position);
            effect.event("afterFinish");
        });
    }
});

Effect.Tween = Class.create(Effect.Base, {
    initialize: function(object, from, to) {
        object = Object.isString(object) ? $(object) : object;
        var args = $A(arguments), method = args.last(), options = args.length == 5 ? args[3] : null;
        this.method = Object.isFunction(method) ? method.bind(object) : Object.isFunction(object[method]) ? object[method].bind(object) : function(value) {
            object[method] = value;
        };
        this.start(Object.extend({
            from: from,
            to: to
        }, options || {}));
    },
    update: function(position) {
        this.method(position);
    }
});

Effect.Event = Class.create(Effect.Base, {
    initialize: function() {
        this.start(Object.extend({
            duration: 0
        }, arguments[0] || {}));
    },
    update: Prototype.emptyFunction
});

Effect.Opacity = Class.create(Effect.Base, {
    initialize: function(element) {
        this.element = $(element);
        if (!this.element) throw Effect._elementDoesNotExistError;
        if (Prototype.Browser.IE && !this.element.currentStyle.hasLayout) this.element.setStyle({
            zoom: 1
        });
        var options = Object.extend({
            from: this.element.getOpacity() || 0,
            to: 1
        }, arguments[1] || {});
        this.start(options);
    },
    update: function(position) {
        this.element.setOpacity(position);
    }
});

Effect.Move = Class.create(Effect.Base, {
    initialize: function(element) {
        this.element = $(element);
        if (!this.element) throw Effect._elementDoesNotExistError;
        var options = Object.extend({
            x: 0,
            y: 0,
            mode: "relative"
        }, arguments[1] || {});
        this.start(options);
    },
    setup: function() {
        this.element.makePositioned();
        this.originalLeft = parseFloat(this.element.getStyle("left") || "0");
        this.originalTop = parseFloat(this.element.getStyle("top") || "0");
        if (this.options.mode == "absolute") {
            this.options.x = this.options.x - this.originalLeft;
            this.options.y = this.options.y - this.originalTop;
        }
    },
    update: function(position) {
        this.element.setStyle({
            left: (this.options.x * position + this.originalLeft).round() + "px",
            top: (this.options.y * position + this.originalTop).round() + "px"
        });
    }
});

Effect.MoveBy = function(element, toTop, toLeft) {
    return new Effect.Move(element, Object.extend({
        x: toLeft,
        y: toTop
    }, arguments[3] || {}));
};

Effect.Scale = Class.create(Effect.Base, {
    initialize: function(element, percent) {
        this.element = $(element);
        if (!this.element) throw Effect._elementDoesNotExistError;
        var options = Object.extend({
            scaleX: true,
            scaleY: true,
            scaleContent: true,
            scaleFromCenter: false,
            scaleMode: "box",
            scaleFrom: 100,
            scaleTo: percent
        }, arguments[2] || {});
        this.start(options);
    },
    setup: function() {
        this.restoreAfterFinish = this.options.restoreAfterFinish || false;
        this.elementPositioning = this.element.getStyle("position");
        this.originalStyle = {};
        [ "top", "left", "width", "height", "fontSize" ].each(function(k) {
            this.originalStyle[k] = this.element.style[k];
        }.bind(this));
        this.originalTop = this.element.offsetTop;
        this.originalLeft = this.element.offsetLeft;
        var fontSize = this.element.getStyle("font-size") || "100%";
        [ "em", "px", "%", "pt" ].each(function(fontSizeType) {
            if (fontSize.indexOf(fontSizeType) > 0) {
                this.fontSize = parseFloat(fontSize);
                this.fontSizeType = fontSizeType;
            }
        }.bind(this));
        this.factor = (this.options.scaleTo - this.options.scaleFrom) / 100;
        this.dims = null;
        if (this.options.scaleMode == "box") this.dims = [ this.element.offsetHeight, this.element.offsetWidth ];
        if (/^content/.test(this.options.scaleMode)) this.dims = [ this.element.scrollHeight, this.element.scrollWidth ];
        if (!this.dims) this.dims = [ this.options.scaleMode.originalHeight, this.options.scaleMode.originalWidth ];
    },
    update: function(position) {
        var currentScale = this.options.scaleFrom / 100 + this.factor * position;
        if (this.options.scaleContent && this.fontSize) this.element.setStyle({
            fontSize: this.fontSize * currentScale + this.fontSizeType
        });
        this.setDimensions(this.dims[0] * currentScale, this.dims[1] * currentScale);
    },
    finish: function(position) {
        if (this.restoreAfterFinish) this.element.setStyle(this.originalStyle);
    },
    setDimensions: function(height, width) {
        var d = {};
        if (this.options.scaleX) d.width = width.round() + "px";
        if (this.options.scaleY) d.height = height.round() + "px";
        if (this.options.scaleFromCenter) {
            var topd = (height - this.dims[0]) / 2;
            var leftd = (width - this.dims[1]) / 2;
            if (this.elementPositioning == "absolute") {
                if (this.options.scaleY) d.top = this.originalTop - topd + "px";
                if (this.options.scaleX) d.left = this.originalLeft - leftd + "px";
            } else {
                if (this.options.scaleY) d.top = -topd + "px";
                if (this.options.scaleX) d.left = -leftd + "px";
            }
        }
        this.element.setStyle(d);
    }
});

Effect.Highlight = Class.create(Effect.Base, {
    initialize: function(element) {
        this.element = $(element);
        if (!this.element) throw Effect._elementDoesNotExistError;
        var options = Object.extend({
            startcolor: "#ffff99"
        }, arguments[1] || {});
        this.start(options);
    },
    setup: function() {
        if (this.element.getStyle("display") == "none") {
            this.cancel();
            return;
        }
        this.oldStyle = {};
        if (!this.options.keepBackgroundImage) {
            this.oldStyle.backgroundImage = this.element.getStyle("background-image");
            this.element.setStyle({
                backgroundImage: "none"
            });
        }
        if (!this.options.endcolor) this.options.endcolor = this.element.getStyle("background-color").parseColor("#ffffff");
        if (!this.options.restorecolor) this.options.restorecolor = this.element.getStyle("background-color");
        this._base = $R(0, 2).map(function(i) {
            return parseInt(this.options.startcolor.slice(i * 2 + 1, i * 2 + 3), 16);
        }.bind(this));
        this._delta = $R(0, 2).map(function(i) {
            return parseInt(this.options.endcolor.slice(i * 2 + 1, i * 2 + 3), 16) - this._base[i];
        }.bind(this));
    },
    update: function(position) {
        this.element.setStyle({
            backgroundColor: $R(0, 2).inject("#", function(m, v, i) {
                return m + (this._base[i] + this._delta[i] * position).round().toColorPart();
            }.bind(this))
        });
    },
    finish: function() {
        this.element.setStyle(Object.extend(this.oldStyle, {
            backgroundColor: this.options.restorecolor
        }));
    }
});

Effect.ScrollTo = function(element) {
    var options = arguments[1] || {}, scrollOffsets = document.viewport.getScrollOffsets(), elementOffsets = $(element).cumulativeOffset();
    if (options.offset) elementOffsets[1] += options.offset;
    return new Effect.Tween(null, scrollOffsets.top, elementOffsets[1], options, function(p) {
        scrollTo(scrollOffsets.left, p.round());
    });
};

Effect.Fade = function(element) {
    element = $(element);
    var oldOpacity = element.getInlineOpacity();
    var options = Object.extend({
        from: element.getOpacity() || 1,
        to: 0,
        afterFinishInternal: function(effect) {
            if (effect.options.to != 0) return;
            effect.element.hide().setStyle({
                opacity: oldOpacity
            });
        }
    }, arguments[1] || {});
    return new Effect.Opacity(element, options);
};

Effect.Appear = function(element) {
    element = $(element);
    var options = Object.extend({
        from: element.getStyle("display") == "none" ? 0 : element.getOpacity() || 0,
        to: 1,
        afterFinishInternal: function(effect) {
            effect.element.forceRerendering();
        },
        beforeSetup: function(effect) {
            effect.element.setOpacity(effect.options.from).show();
        }
    }, arguments[1] || {});
    return new Effect.Opacity(element, options);
};

Effect.Puff = function(element) {
    element = $(element);
    var oldStyle = {
        opacity: element.getInlineOpacity(),
        position: element.getStyle("position"),
        top: element.style.top,
        left: element.style.left,
        width: element.style.width,
        height: element.style.height
    };
    return new Effect.Parallel([ new Effect.Scale(element, 200, {
        sync: true,
        scaleFromCenter: true,
        scaleContent: true,
        restoreAfterFinish: true
    }), new Effect.Opacity(element, {
        sync: true,
        to: 0
    }) ], Object.extend({
        duration: 1,
        beforeSetupInternal: function(effect) {
            Position.absolutize(effect.effects[0].element);
        },
        afterFinishInternal: function(effect) {
            effect.effects[0].element.hide().setStyle(oldStyle);
        }
    }, arguments[1] || {}));
};

Effect.BlindUp = function(element) {
    element = $(element);
    element.makeClipping();
    return new Effect.Scale(element, 0, Object.extend({
        scaleContent: false,
        scaleX: false,
        restoreAfterFinish: true,
        afterFinishInternal: function(effect) {
            effect.element.hide().undoClipping();
        }
    }, arguments[1] || {}));
};

Effect.BlindDown = function(element) {
    element = $(element);
    var elementDimensions = element.getDimensions();
    return new Effect.Scale(element, 100, Object.extend({
        scaleContent: false,
        scaleX: false,
        scaleFrom: 0,
        scaleMode: {
            originalHeight: elementDimensions.height,
            originalWidth: elementDimensions.width
        },
        restoreAfterFinish: true,
        afterSetup: function(effect) {
            effect.element.makeClipping().setStyle({
                height: "0px"
            }).show();
        },
        afterFinishInternal: function(effect) {
            effect.element.undoClipping();
        }
    }, arguments[1] || {}));
};

Effect.SwitchOff = function(element) {
    element = $(element);
    var oldOpacity = element.getInlineOpacity();
    return new Effect.Appear(element, Object.extend({
        duration: .4,
        from: 0,
        transition: Effect.Transitions.flicker,
        afterFinishInternal: function(effect) {
            new Effect.Scale(effect.element, 1, {
                duration: .3,
                scaleFromCenter: true,
                scaleX: false,
                scaleContent: false,
                restoreAfterFinish: true,
                beforeSetup: function(effect) {
                    effect.element.makePositioned().makeClipping();
                },
                afterFinishInternal: function(effect) {
                    effect.element.hide().undoClipping().undoPositioned().setStyle({
                        opacity: oldOpacity
                    });
                }
            });
        }
    }, arguments[1] || {}));
};

Effect.DropOut = function(element) {
    element = $(element);
    var oldStyle = {
        top: element.getStyle("top"),
        left: element.getStyle("left"),
        opacity: element.getInlineOpacity()
    };
    return new Effect.Parallel([ new Effect.Move(element, {
        x: 0,
        y: 100,
        sync: true
    }), new Effect.Opacity(element, {
        sync: true,
        to: 0
    }) ], Object.extend({
        duration: .5,
        beforeSetup: function(effect) {
            effect.effects[0].element.makePositioned();
        },
        afterFinishInternal: function(effect) {
            effect.effects[0].element.hide().undoPositioned().setStyle(oldStyle);
        }
    }, arguments[1] || {}));
};

Effect.Shake = function(element) {
    element = $(element);
    var options = Object.extend({
        distance: 20,
        duration: .5
    }, arguments[1] || {});
    var distance = parseFloat(options.distance);
    var split = parseFloat(options.duration) / 10;
    var oldStyle = {
        top: element.getStyle("top"),
        left: element.getStyle("left")
    };
    return new Effect.Move(element, {
        x: distance,
        y: 0,
        duration: split,
        afterFinishInternal: function(effect) {
            new Effect.Move(effect.element, {
                x: -distance * 2,
                y: 0,
                duration: split * 2,
                afterFinishInternal: function(effect) {
                    new Effect.Move(effect.element, {
                        x: distance * 2,
                        y: 0,
                        duration: split * 2,
                        afterFinishInternal: function(effect) {
                            new Effect.Move(effect.element, {
                                x: -distance * 2,
                                y: 0,
                                duration: split * 2,
                                afterFinishInternal: function(effect) {
                                    new Effect.Move(effect.element, {
                                        x: distance * 2,
                                        y: 0,
                                        duration: split * 2,
                                        afterFinishInternal: function(effect) {
                                            new Effect.Move(effect.element, {
                                                x: -distance,
                                                y: 0,
                                                duration: split,
                                                afterFinishInternal: function(effect) {
                                                    effect.element.undoPositioned().setStyle(oldStyle);
                                                }
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    });
                }
            });
        }
    });
};

Effect.SlideDown = function(element) {
    element = $(element).cleanWhitespace();
    var oldInnerBottom = element.down().getStyle("bottom");
    var elementDimensions = element.getDimensions();
    return new Effect.Scale(element, 100, Object.extend({
        scaleContent: false,
        scaleX: false,
        scaleFrom: window.opera ? 0 : 1,
        scaleMode: {
            originalHeight: elementDimensions.height,
            originalWidth: elementDimensions.width
        },
        restoreAfterFinish: true,
        afterSetup: function(effect) {
            effect.element.makePositioned();
            effect.element.down().makePositioned();
            if (window.opera) effect.element.setStyle({
                top: ""
            });
            effect.element.makeClipping().setStyle({
                height: "0px"
            }).show();
        },
        afterUpdateInternal: function(effect) {
            effect.element.down().setStyle({
                bottom: effect.dims[0] - effect.element.clientHeight + "px"
            });
        },
        afterFinishInternal: function(effect) {
            effect.element.undoClipping().undoPositioned();
            effect.element.down().undoPositioned().setStyle({
                bottom: oldInnerBottom
            });
        }
    }, arguments[1] || {}));
};

Effect.SlideUp = function(element) {
    element = $(element).cleanWhitespace();
    var oldInnerBottom = element.down().getStyle("bottom");
    var elementDimensions = element.getDimensions();
    return new Effect.Scale(element, window.opera ? 0 : 1, Object.extend({
        scaleContent: false,
        scaleX: false,
        scaleMode: "box",
        scaleFrom: 100,
        scaleMode: {
            originalHeight: elementDimensions.height,
            originalWidth: elementDimensions.width
        },
        restoreAfterFinish: true,
        afterSetup: function(effect) {
            effect.element.makePositioned();
            effect.element.down().makePositioned();
            if (window.opera) effect.element.setStyle({
                top: ""
            });
            effect.element.makeClipping().show();
        },
        afterUpdateInternal: function(effect) {
            effect.element.down().setStyle({
                bottom: effect.dims[0] - effect.element.clientHeight + "px"
            });
        },
        afterFinishInternal: function(effect) {
            effect.element.hide().undoClipping().undoPositioned();
            effect.element.down().undoPositioned().setStyle({
                bottom: oldInnerBottom
            });
        }
    }, arguments[1] || {}));
};

Effect.Squish = function(element) {
    return new Effect.Scale(element, window.opera ? 1 : 0, {
        restoreAfterFinish: true,
        beforeSetup: function(effect) {
            effect.element.makeClipping();
        },
        afterFinishInternal: function(effect) {
            effect.element.hide().undoClipping();
        }
    });
};

Effect.Grow = function(element) {
    element = $(element);
    var options = Object.extend({
        direction: "center",
        moveTransition: Effect.Transitions.sinoidal,
        scaleTransition: Effect.Transitions.sinoidal,
        opacityTransition: Effect.Transitions.full
    }, arguments[1] || {});
    var oldStyle = {
        top: element.style.top,
        left: element.style.left,
        height: element.style.height,
        width: element.style.width,
        opacity: element.getInlineOpacity()
    };
    var dims = element.getDimensions();
    var initialMoveX, initialMoveY;
    var moveX, moveY;
    switch (options.direction) {
      case "top-left":
        initialMoveX = initialMoveY = moveX = moveY = 0;
        break;

      case "top-right":
        initialMoveX = dims.width;
        initialMoveY = moveY = 0;
        moveX = -dims.width;
        break;

      case "bottom-left":
        initialMoveX = moveX = 0;
        initialMoveY = dims.height;
        moveY = -dims.height;
        break;

      case "bottom-right":
        initialMoveX = dims.width;
        initialMoveY = dims.height;
        moveX = -dims.width;
        moveY = -dims.height;
        break;

      case "center":
        initialMoveX = dims.width / 2;
        initialMoveY = dims.height / 2;
        moveX = -dims.width / 2;
        moveY = -dims.height / 2;
        break;
    }
    return new Effect.Move(element, {
        x: initialMoveX,
        y: initialMoveY,
        duration: .01,
        beforeSetup: function(effect) {
            effect.element.hide().makeClipping().makePositioned();
        },
        afterFinishInternal: function(effect) {
            new Effect.Parallel([ new Effect.Opacity(effect.element, {
                sync: true,
                to: 1,
                from: 0,
                transition: options.opacityTransition
            }), new Effect.Move(effect.element, {
                x: moveX,
                y: moveY,
                sync: true,
                transition: options.moveTransition
            }), new Effect.Scale(effect.element, 100, {
                scaleMode: {
                    originalHeight: dims.height,
                    originalWidth: dims.width
                },
                sync: true,
                scaleFrom: window.opera ? 1 : 0,
                transition: options.scaleTransition,
                restoreAfterFinish: true
            }) ], Object.extend({
                beforeSetup: function(effect) {
                    effect.effects[0].element.setStyle({
                        height: "0px"
                    }).show();
                },
                afterFinishInternal: function(effect) {
                    effect.effects[0].element.undoClipping().undoPositioned().setStyle(oldStyle);
                }
            }, options));
        }
    });
};

Effect.Shrink = function(element) {
    element = $(element);
    var options = Object.extend({
        direction: "center",
        moveTransition: Effect.Transitions.sinoidal,
        scaleTransition: Effect.Transitions.sinoidal,
        opacityTransition: Effect.Transitions.none
    }, arguments[1] || {});
    var oldStyle = {
        top: element.style.top,
        left: element.style.left,
        height: element.style.height,
        width: element.style.width,
        opacity: element.getInlineOpacity()
    };
    var dims = element.getDimensions();
    var moveX, moveY;
    switch (options.direction) {
      case "top-left":
        moveX = moveY = 0;
        break;

      case "top-right":
        moveX = dims.width;
        moveY = 0;
        break;

      case "bottom-left":
        moveX = 0;
        moveY = dims.height;
        break;

      case "bottom-right":
        moveX = dims.width;
        moveY = dims.height;
        break;

      case "center":
        moveX = dims.width / 2;
        moveY = dims.height / 2;
        break;
    }
    return new Effect.Parallel([ new Effect.Opacity(element, {
        sync: true,
        to: 0,
        from: 1,
        transition: options.opacityTransition
    }), new Effect.Scale(element, window.opera ? 1 : 0, {
        sync: true,
        transition: options.scaleTransition,
        restoreAfterFinish: true
    }), new Effect.Move(element, {
        x: moveX,
        y: moveY,
        sync: true,
        transition: options.moveTransition
    }) ], Object.extend({
        beforeStartInternal: function(effect) {
            effect.effects[0].element.makePositioned().makeClipping();
        },
        afterFinishInternal: function(effect) {
            effect.effects[0].element.hide().undoClipping().undoPositioned().setStyle(oldStyle);
        }
    }, options));
};

Effect.Pulsate = function(element) {
    element = $(element);
    var options = arguments[1] || {}, oldOpacity = element.getInlineOpacity(), transition = options.transition || Effect.Transitions.linear, reverser = function(pos) {
        return 1 - transition(-Math.cos(pos * (options.pulses || 5) * 2 * Math.PI) / 2 + .5);
    };
    return new Effect.Opacity(element, Object.extend(Object.extend({
        duration: 2,
        from: 0,
        afterFinishInternal: function(effect) {
            effect.element.setStyle({
                opacity: oldOpacity
            });
        }
    }, options), {
        transition: reverser
    }));
};

Effect.Fold = function(element) {
    element = $(element);
    var oldStyle = {
        top: element.style.top,
        left: element.style.left,
        width: element.style.width,
        height: element.style.height
    };
    element.makeClipping();
    return new Effect.Scale(element, 5, Object.extend({
        scaleContent: false,
        scaleX: false,
        afterFinishInternal: function(effect) {
            new Effect.Scale(element, 1, {
                scaleContent: false,
                scaleY: false,
                afterFinishInternal: function(effect) {
                    effect.element.hide().undoClipping().setStyle(oldStyle);
                }
            });
        }
    }, arguments[1] || {}));
};

Effect.Morph = Class.create(Effect.Base, {
    initialize: function(element) {
        this.element = $(element);
        if (!this.element) throw Effect._elementDoesNotExistError;
        var options = Object.extend({
            style: {}
        }, arguments[1] || {});
        if (!Object.isString(options.style)) this.style = $H(options.style); else {
            if (options.style.include(":")) this.style = options.style.parseStyle(); else {
                this.element.addClassName(options.style);
                this.style = $H(this.element.getStyles());
                this.element.removeClassName(options.style);
                var css = this.element.getStyles();
                this.style = this.style.reject(function(style) {
                    return style.value == css[style.key];
                });
                options.afterFinishInternal = function(effect) {
                    effect.element.addClassName(effect.options.style);
                    effect.transforms.each(function(transform) {
                        effect.element.style[transform.style] = "";
                    });
                };
            }
        }
        this.start(options);
    },
    setup: function() {
        function parseColor(color) {
            if (!color || [ "rgba(0, 0, 0, 0)", "transparent" ].include(color)) color = "#ffffff";
            color = color.parseColor();
            return $R(0, 2).map(function(i) {
                return parseInt(color.slice(i * 2 + 1, i * 2 + 3), 16);
            });
        }
        this.transforms = this.style.map(function(pair) {
            var property = pair[0], value = pair[1], unit = null;
            if (value.parseColor("#zzzzzz") != "#zzzzzz") {
                value = value.parseColor();
                unit = "color";
            } else if (property == "opacity") {
                value = parseFloat(value);
                if (Prototype.Browser.IE && !this.element.currentStyle.hasLayout) this.element.setStyle({
                    zoom: 1
                });
            } else if (Element.CSS_LENGTH.test(value)) {
                var components = value.match(/^([\+\-]?[0-9\.]+)(.*)$/);
                value = parseFloat(components[1]);
                unit = components.length == 3 ? components[2] : null;
            }
            var originalValue = this.element.getStyle(property);
            return {
                style: property.camelize(),
                originalValue: unit == "color" ? parseColor(originalValue) : parseFloat(originalValue || 0),
                targetValue: unit == "color" ? parseColor(value) : value,
                unit: unit
            };
        }.bind(this)).reject(function(transform) {
            return transform.originalValue == transform.targetValue || transform.unit != "color" && (isNaN(transform.originalValue) || isNaN(transform.targetValue));
        });
    },
    update: function(position) {
        var style = {}, transform, i = this.transforms.length;
        while (i--) style[(transform = this.transforms[i]).style] = transform.unit == "color" ? "#" + Math.round(transform.originalValue[0] + (transform.targetValue[0] - transform.originalValue[0]) * position).toColorPart() + Math.round(transform.originalValue[1] + (transform.targetValue[1] - transform.originalValue[1]) * position).toColorPart() + Math.round(transform.originalValue[2] + (transform.targetValue[2] - transform.originalValue[2]) * position).toColorPart() : (transform.originalValue + (transform.targetValue - transform.originalValue) * position).toFixed(3) + (transform.unit === null ? "" : transform.unit);
        this.element.setStyle(style, true);
    }
});

Effect.Transform = Class.create({
    initialize: function(tracks) {
        this.tracks = [];
        this.options = arguments[1] || {};
        this.addTracks(tracks);
    },
    addTracks: function(tracks) {
        tracks.each(function(track) {
            track = $H(track);
            var data = track.values().first();
            this.tracks.push($H({
                ids: track.keys().first(),
                effect: Effect.Morph,
                options: {
                    style: data
                }
            }));
        }.bind(this));
        return this;
    },
    play: function() {
        return new Effect.Parallel(this.tracks.map(function(track) {
            var ids = track.get("ids"), effect = track.get("effect"), options = track.get("options");
            var elements = [ $(ids) || $$(ids) ].flatten();
            return elements.map(function(e) {
                return new effect(e, Object.extend({
                    sync: true
                }, options));
            });
        }).flatten(), this.options);
    }
});

Element.CSS_PROPERTIES = $w("backgroundColor backgroundPosition borderBottomColor borderBottomStyle " + "borderBottomWidth borderLeftColor borderLeftStyle borderLeftWidth " + "borderRightColor borderRightStyle borderRightWidth borderSpacing " + "borderTopColor borderTopStyle borderTopWidth bottom clip color " + "fontSize fontWeight height left letterSpacing lineHeight " + "marginBottom marginLeft marginRight marginTop markerOffset maxHeight " + "maxWidth minHeight minWidth opacity outlineColor outlineOffset " + "outlineWidth paddingBottom paddingLeft paddingRight paddingTop " + "right textIndent top width wordSpacing zIndex");

Element.CSS_LENGTH = /^(([\+\-]?[0-9\.]+)(em|ex|px|in|cm|mm|pt|pc|\%))|0$/;

String.__parseStyleElement = document.createElement("div");

String.prototype.parseStyle = function() {
    var style, styleRules = $H();
    if (Prototype.Browser.WebKit) style = new Element("div", {
        style: this
    }).style; else {
        String.__parseStyleElement.innerHTML = '<div style="' + this + '"></div>';
        style = String.__parseStyleElement.childNodes[0].style;
    }
    Element.CSS_PROPERTIES.each(function(property) {
        if (style[property]) styleRules.set(property, style[property]);
    });
    if (Prototype.Browser.IE && this.include("opacity")) styleRules.set("opacity", this.match(/opacity:\s*((?:0|1)?(?:\.\d*)?)/)[1]);
    return styleRules;
};

if (document.defaultView && document.defaultView.getComputedStyle) {
    Element.getStyles = function(element) {
        var css = document.defaultView.getComputedStyle($(element), null);
        return Element.CSS_PROPERTIES.inject({}, function(styles, property) {
            styles[property] = css[property];
            return styles;
        });
    };
} else {
    Element.getStyles = function(element) {
        element = $(element);
        var css = element.currentStyle, styles;
        styles = Element.CSS_PROPERTIES.inject({}, function(results, property) {
            results[property] = css[property];
            return results;
        });
        if (!styles.opacity) styles.opacity = element.getOpacity();
        return styles;
    };
}

Effect.Methods = {
    morph: function(element, style) {
        element = $(element);
        new Effect.Morph(element, Object.extend({
            style: style
        }, arguments[2] || {}));
        return element;
    },
    visualEffect: function(element, effect, options) {
        element = $(element);
        var s = effect.dasherize().camelize(), klass = s.charAt(0).toUpperCase() + s.substring(1);
        new Effect[klass](element, options);
        return element;
    },
    highlight: function(element, options) {
        element = $(element);
        new Effect.Highlight(element, options);
        return element;
    }
};

$w("fade appear grow shrink fold blindUp blindDown slideUp slideDown " + "pulsate shake puff squish switchOff dropOut").each(function(effect) {
    Effect.Methods[effect] = function(element, options) {
        element = $(element);
        Effect[effect.charAt(0).toUpperCase() + effect.substring(1)](element, options);
        return element;
    };
});

$w("getInlineOpacity forceRerendering setContentZoom collectTextNodes collectTextNodesIgnoreClass getStyles").each(function(f) {
    Effect.Methods[f] = Element[f];
});

Element.addMethods(Effect.Methods);

if (Object.isUndefined(Effect)) throw "dragdrop.js requires including script.aculo.us' effects.js library";

var Droppables = {
    drops: [],
    remove: function(element) {
        this.drops = this.drops.reject(function(d) {
            return d.element == $(element);
        });
    },
    add: function(element) {
        element = $(element);
        var options = Object.extend({
            greedy: true,
            hoverclass: null,
            tree: false
        }, arguments[1] || {});
        if (options.containment) {
            options._containers = [];
            var containment = options.containment;
            if (Object.isArray(containment)) {
                containment.each(function(c) {
                    options._containers.push($(c));
                });
            } else {
                options._containers.push($(containment));
            }
        }
        if (options.accept) options.accept = [ options.accept ].flatten();
        Element.makePositioned(element);
        options.element = element;
        this.drops.push(options);
    },
    findDeepestChild: function(drops) {
        deepest = drops[0];
        for (i = 1; i < drops.length; ++i) if (Element.isParent(drops[i].element, deepest.element)) deepest = drops[i];
        return deepest;
    },
    isContained: function(element, drop) {
        var containmentNode;
        if (drop.tree) {
            containmentNode = element.treeNode;
        } else {
            containmentNode = element.parentNode;
        }
        return drop._containers.detect(function(c) {
            return containmentNode == c;
        });
    },
    isAffected: function(point, element, drop) {
        return drop.element != element && (!drop._containers || this.isContained(element, drop)) && (!drop.accept || Element.classNames(element).detect(function(v) {
            return drop.accept.include(v);
        })) && Position.within(drop.element, point[0], point[1]);
    },
    deactivate: function(drop) {
        if (drop.hoverclass) Element.removeClassName(drop.element, drop.hoverclass);
        this.last_active = null;
    },
    activate: function(drop) {
        if (drop.hoverclass) Element.addClassName(drop.element, drop.hoverclass);
        this.last_active = drop;
    },
    show: function(point, element) {
        if (!this.drops.length) return;
        var drop, affected = [];
        this.drops.each(function(drop) {
            if (Droppables.isAffected(point, element, drop)) affected.push(drop);
        });
        if (affected.length > 0) drop = Droppables.findDeepestChild(affected);
        if (this.last_active && this.last_active != drop) this.deactivate(this.last_active);
        if (drop) {
            Position.within(drop.element, point[0], point[1]);
            if (drop.onHover) drop.onHover(element, drop.element, Position.overlap(drop.overlap, drop.element));
            if (drop != this.last_active) Droppables.activate(drop);
        }
    },
    fire: function(event, element) {
        if (!this.last_active) return;
        Position.prepare();
        if (this.isAffected([ Event.pointerX(event), Event.pointerY(event) ], element, this.last_active)) if (this.last_active.onDrop) {
            this.last_active.onDrop(element, this.last_active.element, event);
            return true;
        }
    },
    reset: function() {
        if (this.last_active) this.deactivate(this.last_active);
    }
};

var Draggables = {
    drags: [],
    observers: [],
    register: function(draggable) {
        if (this.drags.length == 0) {
            this.eventMouseUp = this.endDrag.bindAsEventListener(this);
            this.eventMouseMove = this.updateDrag.bindAsEventListener(this);
            this.eventKeypress = this.keyPress.bindAsEventListener(this);
            Event.observe(document, "mouseup", this.eventMouseUp);
            Event.observe(document, "mousemove", this.eventMouseMove);
            Event.observe(document, "keypress", this.eventKeypress);
        }
        this.drags.push(draggable);
    },
    unregister: function(draggable) {
        this.drags = this.drags.reject(function(d) {
            return d == draggable;
        });
        if (this.drags.length == 0) {
            Event.stopObserving(document, "mouseup", this.eventMouseUp);
            Event.stopObserving(document, "mousemove", this.eventMouseMove);
            Event.stopObserving(document, "keypress", this.eventKeypress);
        }
    },
    activate: function(draggable) {
        if (draggable.options.delay) {
            this._timeout = setTimeout(function() {
                Draggables._timeout = null;
                window.focus();
                Draggables.activeDraggable = draggable;
            }.bind(this), draggable.options.delay);
        } else {
            window.focus();
            this.activeDraggable = draggable;
        }
    },
    deactivate: function() {
        this.activeDraggable = null;
    },
    updateDrag: function(event) {
        if (!this.activeDraggable) return;
        var pointer = [ Event.pointerX(event), Event.pointerY(event) ];
        if (this._lastPointer && this._lastPointer.inspect() == pointer.inspect()) return;
        this._lastPointer = pointer;
        this.activeDraggable.updateDrag(event, pointer);
    },
    endDrag: function(event) {
        if (this._timeout) {
            clearTimeout(this._timeout);
            this._timeout = null;
        }
        if (!this.activeDraggable) return;
        this._lastPointer = null;
        this.activeDraggable.endDrag(event);
        this.activeDraggable = null;
    },
    keyPress: function(event) {
        if (this.activeDraggable) this.activeDraggable.keyPress(event);
    },
    addObserver: function(observer) {
        this.observers.push(observer);
        this._cacheObserverCallbacks();
    },
    removeObserver: function(element) {
        this.observers = this.observers.reject(function(o) {
            return o.element == element;
        });
        this._cacheObserverCallbacks();
    },
    notify: function(eventName, draggable, event) {
        if (this[eventName + "Count"] > 0) this.observers.each(function(o) {
            if (o[eventName]) o[eventName](eventName, draggable, event);
        });
        if (draggable.options[eventName]) draggable.options[eventName](draggable, event);
    },
    _cacheObserverCallbacks: function() {
        [ "onStart", "onEnd", "onDrag" ].each(function(eventName) {
            Draggables[eventName + "Count"] = Draggables.observers.select(function(o) {
                return o[eventName];
            }).length;
        });
    }
};

var Draggable = Class.create({
    initialize: function(element) {
        var defaults = {
            handle: false,
            reverteffect: function(element, top_offset, left_offset) {
                var dur = Math.sqrt(Math.abs(top_offset ^ 2) + Math.abs(left_offset ^ 2)) * .02;
                new Effect.Move(element, {
                    x: -left_offset,
                    y: -top_offset,
                    duration: dur,
                    queue: {
                        scope: "_draggable",
                        position: "end"
                    }
                });
            },
            endeffect: function(element) {
                var toOpacity = Object.isNumber(element._opacity) ? element._opacity : 1;
                new Effect.Opacity(element, {
                    duration: .2,
                    from: .7,
                    to: toOpacity,
                    queue: {
                        scope: "_draggable",
                        position: "end"
                    },
                    afterFinish: function() {
                        Draggable._dragging[element] = false;
                    }
                });
            },
            zindex: 1e3,
            revert: false,
            quiet: false,
            scroll: false,
            scrollSensitivity: 20,
            scrollSpeed: 15,
            snap: false,
            delay: 0
        };
        if (!arguments[1] || Object.isUndefined(arguments[1].endeffect)) Object.extend(defaults, {
            starteffect: function(element) {
                element._opacity = Element.getOpacity(element);
                Draggable._dragging[element] = true;
                new Effect.Opacity(element, {
                    duration: .2,
                    from: element._opacity,
                    to: .7
                });
            }
        });
        var options = Object.extend(defaults, arguments[1] || {});
        this.element = $(element);
        if (options.handle && Object.isString(options.handle)) this.handle = this.element.down("." + options.handle, 0);
        if (!this.handle) this.handle = $(options.handle);
        if (!this.handle) this.handle = this.element;
        if (options.scroll && !options.scroll.scrollTo && !options.scroll.outerHTML) {
            options.scroll = $(options.scroll);
            this._isScrollChild = Element.childOf(this.element, options.scroll);
        }
        Element.makePositioned(this.element);
        this.options = options;
        this.dragging = false;
        this.eventMouseDown = this.initDrag.bindAsEventListener(this);
        Event.observe(this.handle, "mousedown", this.eventMouseDown);
        Draggables.register(this);
    },
    destroy: function() {
        Event.stopObserving(this.handle, "mousedown", this.eventMouseDown);
        Draggables.unregister(this);
    },
    currentDelta: function() {
        return [ parseInt(Element.getStyle(this.element, "left") || "0"), parseInt(Element.getStyle(this.element, "top") || "0") ];
    },
    initDrag: function(event) {
        if (!Object.isUndefined(Draggable._dragging[this.element]) && Draggable._dragging[this.element]) return;
        if (Event.isLeftClick(event)) {
            var src = Event.element(event);
            if ((tag_name = src.tagName.toUpperCase()) && (tag_name == "INPUT" || tag_name == "SELECT" || tag_name == "OPTION" || tag_name == "BUTTON" || tag_name == "TEXTAREA")) return;
            var pointer = [ Event.pointerX(event), Event.pointerY(event) ];
            var pos = this.element.cumulativeOffset();
            this.offset = [ 0, 1 ].map(function(i) {
                return pointer[i] - pos[i];
            });
            Draggables.activate(this);
            Event.stop(event);
        }
    },
    startDrag: function(event) {
        this.dragging = true;
        if (!this.delta) this.delta = this.currentDelta();
        if (this.options.zindex) {
            this.originalZ = parseInt(Element.getStyle(this.element, "z-index") || 0);
            this.element.style.zIndex = this.options.zindex;
        }
        if (this.options.ghosting) {
            this._clone = this.element.cloneNode(true);
            this._originallyAbsolute = this.element.getStyle("position") == "absolute";
            if (!this._originallyAbsolute) Position.absolutize(this.element);
            this.element.parentNode.insertBefore(this._clone, this.element);
        }
        if (this.options.scroll) {
            if (this.options.scroll == window) {
                var where = this._getWindowScroll(this.options.scroll);
                this.originalScrollLeft = where.left;
                this.originalScrollTop = where.top;
            } else {
                this.originalScrollLeft = this.options.scroll.scrollLeft;
                this.originalScrollTop = this.options.scroll.scrollTop;
            }
        }
        Draggables.notify("onStart", this, event);
        if (this.options.starteffect) this.options.starteffect(this.element);
    },
    updateDrag: function(event, pointer) {
        if (!this.dragging) this.startDrag(event);
        if (!this.options.quiet) {
            Position.prepare();
            Droppables.show(pointer, this.element);
        }
        Draggables.notify("onDrag", this, event);
        this.draw(pointer);
        if (this.options.change) this.options.change(this);
        if (this.options.scroll) {
            this.stopScrolling();
            var p;
            if (this.options.scroll == window) {
                with (this._getWindowScroll(this.options.scroll)) {
                    p = [ left, top, left + width, top + height ];
                }
            } else {
                p = Position.page(this.options.scroll).toArray();
                p[0] += this.options.scroll.scrollLeft + Position.deltaX;
                p[1] += this.options.scroll.scrollTop + Position.deltaY;
                p.push(p[0] + this.options.scroll.offsetWidth);
                p.push(p[1] + this.options.scroll.offsetHeight);
            }
            var speed = [ 0, 0 ];
            if (pointer[0] < p[0] + this.options.scrollSensitivity) speed[0] = pointer[0] - (p[0] + this.options.scrollSensitivity);
            if (pointer[1] < p[1] + this.options.scrollSensitivity) speed[1] = pointer[1] - (p[1] + this.options.scrollSensitivity);
            if (pointer[0] > p[2] - this.options.scrollSensitivity) speed[0] = pointer[0] - (p[2] - this.options.scrollSensitivity);
            if (pointer[1] > p[3] - this.options.scrollSensitivity) speed[1] = pointer[1] - (p[3] - this.options.scrollSensitivity);
            this.startScrolling(speed);
        }
        if (Prototype.Browser.WebKit) window.scrollBy(0, 0);
        Event.stop(event);
    },
    finishDrag: function(event, success) {
        this.dragging = false;
        if (this.options.quiet) {
            Position.prepare();
            var pointer = [ Event.pointerX(event), Event.pointerY(event) ];
            Droppables.show(pointer, this.element);
        }
        if (this.options.ghosting) {
            if (!this._originallyAbsolute) Position.relativize(this.element);
            delete this._originallyAbsolute;
            Element.remove(this._clone);
            this._clone = null;
        }
        var dropped = false;
        if (success) {
            dropped = Droppables.fire(event, this.element);
            if (!dropped) dropped = false;
        }
        if (dropped && this.options.onDropped) this.options.onDropped(this.element);
        Draggables.notify("onEnd", this, event);
        var revert = this.options.revert;
        if (revert && Object.isFunction(revert)) revert = revert(this.element);
        var d = this.currentDelta();
        if (revert && this.options.reverteffect) {
            if (dropped == 0 || revert != "failure") this.options.reverteffect(this.element, d[1] - this.delta[1], d[0] - this.delta[0]);
        } else {
            this.delta = d;
        }
        if (this.options.zindex) this.element.style.zIndex = this.originalZ;
        if (this.options.endeffect) this.options.endeffect(this.element);
        Draggables.deactivate(this);
        Droppables.reset();
    },
    keyPress: function(event) {
        if (event.keyCode != Event.KEY_ESC) return;
        this.finishDrag(event, false);
        Event.stop(event);
    },
    endDrag: function(event) {
        if (!this.dragging) return;
        this.stopScrolling();
        this.finishDrag(event, true);
        Event.stop(event);
    },
    draw: function(point) {
        var pos = this.element.cumulativeOffset();
        if (this.options.ghosting) {
            var r = Position.realOffset(this.element);
            pos[0] += r[0] - Position.deltaX;
            pos[1] += r[1] - Position.deltaY;
        }
        var d = this.currentDelta();
        pos[0] -= d[0];
        pos[1] -= d[1];
        if (this.options.scroll && (this.options.scroll != window && this._isScrollChild)) {
            pos[0] -= this.options.scroll.scrollLeft - this.originalScrollLeft;
            pos[1] -= this.options.scroll.scrollTop - this.originalScrollTop;
        }
        var p = [ 0, 1 ].map(function(i) {
            return point[i] - pos[i] - this.offset[i];
        }.bind(this));
        if (this.options.snap) {
            if (Object.isFunction(this.options.snap)) {
                p = this.options.snap(p[0], p[1], this);
            } else {
                if (Object.isArray(this.options.snap)) {
                    p = p.map(function(v, i) {
                        return (v / this.options.snap[i]).round() * this.options.snap[i];
                    }.bind(this));
                } else {
                    p = p.map(function(v) {
                        return (v / this.options.snap).round() * this.options.snap;
                    }.bind(this));
                }
            }
        }
        var style = this.element.style;
        if (!this.options.constraint || this.options.constraint == "horizontal") style.left = p[0] + "px";
        if (!this.options.constraint || this.options.constraint == "vertical") style.top = p[1] + "px";
        if (style.visibility == "hidden") style.visibility = "";
    },
    stopScrolling: function() {
        if (this.scrollInterval) {
            clearInterval(this.scrollInterval);
            this.scrollInterval = null;
            Draggables._lastScrollPointer = null;
        }
    },
    startScrolling: function(speed) {
        if (!(speed[0] || speed[1])) return;
        this.scrollSpeed = [ speed[0] * this.options.scrollSpeed, speed[1] * this.options.scrollSpeed ];
        this.lastScrolled = new Date();
        this.scrollInterval = setInterval(this.scroll.bind(this), 10);
    },
    scroll: function() {
        var current = new Date();
        var delta = current - this.lastScrolled;
        this.lastScrolled = current;
        if (this.options.scroll == window) {
            with (this._getWindowScroll(this.options.scroll)) {
                if (this.scrollSpeed[0] || this.scrollSpeed[1]) {
                    var d = delta / 1e3;
                    this.options.scroll.scrollTo(left + d * this.scrollSpeed[0], top + d * this.scrollSpeed[1]);
                }
            }
        } else {
            this.options.scroll.scrollLeft += this.scrollSpeed[0] * delta / 1e3;
            this.options.scroll.scrollTop += this.scrollSpeed[1] * delta / 1e3;
        }
        Position.prepare();
        Droppables.show(Draggables._lastPointer, this.element);
        Draggables.notify("onDrag", this);
        if (this._isScrollChild) {
            Draggables._lastScrollPointer = Draggables._lastScrollPointer || $A(Draggables._lastPointer);
            Draggables._lastScrollPointer[0] += this.scrollSpeed[0] * delta / 1e3;
            Draggables._lastScrollPointer[1] += this.scrollSpeed[1] * delta / 1e3;
            if (Draggables._lastScrollPointer[0] < 0) Draggables._lastScrollPointer[0] = 0;
            if (Draggables._lastScrollPointer[1] < 0) Draggables._lastScrollPointer[1] = 0;
            this.draw(Draggables._lastScrollPointer);
        }
        if (this.options.change) this.options.change(this);
    },
    _getWindowScroll: function(w) {
        var T, L, W, H;
        with (w.document) {
            if (w.document.documentElement && documentElement.scrollTop) {
                T = documentElement.scrollTop;
                L = documentElement.scrollLeft;
            } else if (w.document.body) {
                T = body.scrollTop;
                L = body.scrollLeft;
            }
            if (w.innerWidth) {
                W = w.innerWidth;
                H = w.innerHeight;
            } else if (w.document.documentElement && documentElement.clientWidth) {
                W = documentElement.clientWidth;
                H = documentElement.clientHeight;
            } else {
                W = body.offsetWidth;
                H = body.offsetHeight;
            }
        }
        return {
            top: T,
            left: L,
            width: W,
            height: H
        };
    }
});

Draggable._dragging = {};

var SortableObserver = Class.create({
    initialize: function(element, observer) {
        this.element = $(element);
        this.observer = observer;
        this.lastValue = Sortable.serialize(this.element);
    },
    onStart: function() {
        this.lastValue = Sortable.serialize(this.element);
    },
    onEnd: function() {
        Sortable.unmark();
        if (this.lastValue != Sortable.serialize(this.element)) this.observer(this.element);
    }
});

var Sortable = {
    SERIALIZE_RULE: /^[^_\-](?:[A-Za-z0-9\-\_]*)[_](.*)$/,
    sortables: {},
    _findRootElement: function(element) {
        while (element.tagName.toUpperCase() != "BODY") {
            if (element.id && Sortable.sortables[element.id]) return element;
            element = element.parentNode;
        }
    },
    options: function(element) {
        element = Sortable._findRootElement($(element));
        if (!element) return;
        return Sortable.sortables[element.id];
    },
    destroy: function(element) {
        element = $(element);
        var s = Sortable.sortables[element.id];
        if (s) {
            Draggables.removeObserver(s.element);
            s.droppables.each(function(d) {
                Droppables.remove(d);
            });
            s.draggables.invoke("destroy");
            delete Sortable.sortables[s.element.id];
        }
    },
    create: function(element) {
        element = $(element);
        var options = Object.extend({
            element: element,
            tag: "li",
            dropOnEmpty: false,
            tree: false,
            treeTag: "ul",
            overlap: "vertical",
            constraint: "vertical",
            containment: element,
            handle: false,
            only: false,
            delay: 0,
            hoverclass: null,
            ghosting: false,
            quiet: false,
            scroll: false,
            scrollSensitivity: 20,
            scrollSpeed: 15,
            format: this.SERIALIZE_RULE,
            elements: false,
            handles: false,
            onChange: Prototype.emptyFunction,
            onUpdate: Prototype.emptyFunction
        }, arguments[1] || {});
        this.destroy(element);
        var options_for_draggable = {
            revert: true,
            quiet: options.quiet,
            scroll: options.scroll,
            scrollSpeed: options.scrollSpeed,
            scrollSensitivity: options.scrollSensitivity,
            delay: options.delay,
            ghosting: options.ghosting,
            constraint: options.constraint,
            handle: options.handle
        };
        if (options.starteffect) options_for_draggable.starteffect = options.starteffect;
        if (options.reverteffect) options_for_draggable.reverteffect = options.reverteffect; else if (options.ghosting) options_for_draggable.reverteffect = function(element) {
            element.style.top = 0;
            element.style.left = 0;
        };
        if (options.endeffect) options_for_draggable.endeffect = options.endeffect;
        if (options.zindex) options_for_draggable.zindex = options.zindex;
        var options_for_droppable = {
            overlap: options.overlap,
            containment: options.containment,
            tree: options.tree,
            hoverclass: options.hoverclass,
            onHover: Sortable.onHover
        };
        var options_for_tree = {
            onHover: Sortable.onEmptyHover,
            overlap: options.overlap,
            containment: options.containment,
            hoverclass: options.hoverclass
        };
        Element.cleanWhitespace(element);
        options.draggables = [];
        options.droppables = [];
        if (options.dropOnEmpty || options.tree) {
            Droppables.add(element, options_for_tree);
            options.droppables.push(element);
        }
        (options.elements || this.findElements(element, options) || []).each(function(e, i) {
            var handle = options.handles ? $(options.handles[i]) : options.handle ? $(e).select("." + options.handle)[0] : e;
            options.draggables.push(new Draggable(e, Object.extend(options_for_draggable, {
                handle: handle
            })));
            Droppables.add(e, options_for_droppable);
            if (options.tree) e.treeNode = element;
            options.droppables.push(e);
        });
        if (options.tree) {
            (Sortable.findTreeElements(element, options) || []).each(function(e) {
                Droppables.add(e, options_for_tree);
                e.treeNode = element;
                options.droppables.push(e);
            });
        }
        this.sortables[element.identify()] = options;
        Draggables.addObserver(new SortableObserver(element, options.onUpdate));
    },
    findElements: function(element, options) {
        return Element.findChildren(element, options.only, options.tree ? true : false, options.tag);
    },
    findTreeElements: function(element, options) {
        return Element.findChildren(element, options.only, options.tree ? true : false, options.treeTag);
    },
    onHover: function(element, dropon, overlap) {
        if (Element.isParent(dropon, element)) return;
        if (overlap > .33 && overlap < .66 && Sortable.options(dropon).tree) {
            return;
        } else if (overlap > .5) {
            Sortable.mark(dropon, "before");
            if (dropon.previousSibling != element) {
                var oldParentNode = element.parentNode;
                element.style.visibility = "hidden";
                dropon.parentNode.insertBefore(element, dropon);
                if (dropon.parentNode != oldParentNode) Sortable.options(oldParentNode).onChange(element);
                Sortable.options(dropon.parentNode).onChange(element);
            }
        } else {
            Sortable.mark(dropon, "after");
            var nextElement = dropon.nextSibling || null;
            if (nextElement != element) {
                var oldParentNode = element.parentNode;
                element.style.visibility = "hidden";
                dropon.parentNode.insertBefore(element, nextElement);
                if (dropon.parentNode != oldParentNode) Sortable.options(oldParentNode).onChange(element);
                Sortable.options(dropon.parentNode).onChange(element);
            }
        }
    },
    onEmptyHover: function(element, dropon, overlap) {
        var oldParentNode = element.parentNode;
        var droponOptions = Sortable.options(dropon);
        if (!Element.isParent(dropon, element)) {
            var index;
            var children = Sortable.findElements(dropon, {
                tag: droponOptions.tag,
                only: droponOptions.only
            });
            var child = null;
            if (children) {
                var offset = Element.offsetSize(dropon, droponOptions.overlap) * (1 - overlap);
                for (index = 0; index < children.length; index += 1) {
                    if (offset - Element.offsetSize(children[index], droponOptions.overlap) >= 0) {
                        offset -= Element.offsetSize(children[index], droponOptions.overlap);
                    } else if (offset - Element.offsetSize(children[index], droponOptions.overlap) / 2 >= 0) {
                        child = index + 1 < children.length ? children[index + 1] : null;
                        break;
                    } else {
                        child = children[index];
                        break;
                    }
                }
            }
            dropon.insertBefore(element, child);
            Sortable.options(oldParentNode).onChange(element);
            droponOptions.onChange(element);
        }
    },
    unmark: function() {
        if (Sortable._marker) Sortable._marker.hide();
    },
    mark: function(dropon, position) {
        var sortable = Sortable.options(dropon.parentNode);
        if (sortable && !sortable.ghosting) return;
        if (!Sortable._marker) {
            Sortable._marker = ($("dropmarker") || Element.extend(document.createElement("DIV"))).hide().addClassName("dropmarker").setStyle({
                position: "absolute"
            });
            document.getElementsByTagName("body").item(0).appendChild(Sortable._marker);
        }
        var offsets = dropon.cumulativeOffset();
        Sortable._marker.setStyle({
            left: offsets[0] + "px",
            top: offsets[1] + "px"
        });
        if (position == "after") if (sortable.overlap == "horizontal") Sortable._marker.setStyle({
            left: offsets[0] + dropon.clientWidth + "px"
        }); else Sortable._marker.setStyle({
            top: offsets[1] + dropon.clientHeight + "px"
        });
        Sortable._marker.show();
    },
    _tree: function(element, options, parent) {
        var children = Sortable.findElements(element, options) || [];
        for (var i = 0; i < children.length; ++i) {
            var match = children[i].id.match(options.format);
            if (!match) continue;
            var child = {
                id: encodeURIComponent(match ? match[1] : null),
                element: element,
                parent: parent,
                children: [],
                position: parent.children.length,
                container: $(children[i]).down(options.treeTag)
            };
            if (child.container) this._tree(child.container, options, child);
            parent.children.push(child);
        }
        return parent;
    },
    tree: function(element) {
        element = $(element);
        var sortableOptions = this.options(element);
        var options = Object.extend({
            tag: sortableOptions.tag,
            treeTag: sortableOptions.treeTag,
            only: sortableOptions.only,
            name: element.id,
            format: sortableOptions.format
        }, arguments[1] || {});
        var root = {
            id: null,
            parent: null,
            children: [],
            container: element,
            position: 0
        };
        return Sortable._tree(element, options, root);
    },
    _constructIndex: function(node) {
        var index = "";
        do {
            if (node.id) index = "[" + node.position + "]" + index;
        } while ((node = node.parent) != null);
        return index;
    },
    sequence: function(element) {
        element = $(element);
        var options = Object.extend(this.options(element), arguments[1] || {});
        return $(this.findElements(element, options) || []).map(function(item) {
            return item.id.match(options.format) ? item.id.match(options.format)[1] : "";
        });
    },
    setSequence: function(element, new_sequence) {
        element = $(element);
        var options = Object.extend(this.options(element), arguments[2] || {});
        var nodeMap = {};
        this.findElements(element, options).each(function(n) {
            if (n.id.match(options.format)) nodeMap[n.id.match(options.format)[1]] = [ n, n.parentNode ];
            n.parentNode.removeChild(n);
        });
        new_sequence.each(function(ident) {
            var n = nodeMap[ident];
            if (n) {
                n[1].appendChild(n[0]);
                delete nodeMap[ident];
            }
        });
    },
    serialize: function(element) {
        element = $(element);
        var options = Object.extend(Sortable.options(element), arguments[1] || {});
        var name = encodeURIComponent(arguments[1] && arguments[1].name ? arguments[1].name : element.id);
        if (options.tree) {
            return Sortable.tree(element, arguments[1]).children.map(function(item) {
                return [ name + Sortable._constructIndex(item) + "[id]=" + encodeURIComponent(item.id) ].concat(item.children.map(arguments.callee));
            }).flatten().join("&");
        } else {
            return Sortable.sequence(element, arguments[1]).map(function(item) {
                return name + "[]=" + encodeURIComponent(item);
            }).join("&");
        }
    }
};

Element.isParent = function(child, element) {
    if (!child.parentNode || child == element) return false;
    if (child.parentNode == element) return true;
    return Element.isParent(child.parentNode, element);
};

Element.findChildren = function(element, only, recursive, tagName) {
    if (!element.hasChildNodes()) return null;
    tagName = tagName.toUpperCase();
    if (only) only = [ only ].flatten();
    var elements = [];
    $A(element.childNodes).each(function(e) {
        if (e.tagName && e.tagName.toUpperCase() == tagName && (!only || Element.classNames(e).detect(function(v) {
            return only.include(v);
        }))) elements.push(e);
        if (recursive) {
            var grandchildren = Element.findChildren(e, only, recursive, tagName);
            if (grandchildren) elements.push(grandchildren);
        }
    });
    return elements.length > 0 ? elements.flatten() : [];
};

Element.offsetSize = function(element, type) {
    return element["offset" + (type == "vertical" || type == "height" ? "Height" : "Width")];
};

if (typeof Effect == "undefined") throw "controls.js requires including script.aculo.us' effects.js library";

var Autocompleter = {};

Autocompleter.Base = Class.create({
    baseInitialize: function(element, update, options) {
        element = $(element);
        this.element = element;
        this.update = $(update);
        this.hasFocus = false;
        this.changed = false;
        this.active = false;
        this.index = 0;
        this.entryCount = 0;
        this.oldElementValue = this.element.value;
        if (this.setOptions) this.setOptions(options); else this.options = options || {};
        this.options.paramName = this.options.paramName || this.element.name;
        this.options.tokens = this.options.tokens || [];
        this.options.frequency = this.options.frequency || .4;
        this.options.minChars = this.options.minChars || 1;
        this.options.onShow = this.options.onShow || function(element, update) {
            if (!update.style.position || update.style.position == "absolute") {
                update.style.position = "absolute";
                Position.clone(element, update, {
                    setHeight: false,
                    offsetTop: element.offsetHeight
                });
            }
            Effect.Appear(update, {
                duration: .15
            });
        };
        this.options.onHide = this.options.onHide || function(element, update) {
            new Effect.Fade(update, {
                duration: .15
            });
        };
        if (typeof this.options.tokens == "string") this.options.tokens = new Array(this.options.tokens);
        if (!this.options.tokens.include("\n")) this.options.tokens.push("\n");
        this.observer = null;
        this.element.setAttribute("autocomplete", "off");
        Element.hide(this.update);
        Event.observe(this.element, "blur", this.onBlur.bindAsEventListener(this));
        Event.observe(this.element, "keydown", this.onKeyPress.bindAsEventListener(this));
    },
    show: function() {
        if (Element.getStyle(this.update, "display") == "none") this.options.onShow(this.element, this.update);
        if (!this.iefix && Prototype.Browser.IE && Element.getStyle(this.update, "position") == "absolute") {
            new Insertion.After(this.update, '<iframe id="' + this.update.id + '_iefix" ' + 'style="display:none;position:absolute;filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0);" ' + 'src="javascript:false;" frameborder="0" scrolling="no"></iframe>');
            this.iefix = $(this.update.id + "_iefix");
        }
        if (this.iefix) setTimeout(this.fixIEOverlapping.bind(this), 50);
    },
    fixIEOverlapping: function() {
        Position.clone(this.update, this.iefix, {
            setTop: !this.update.style.height
        });
        this.iefix.style.zIndex = 1;
        this.update.style.zIndex = 2;
        Element.show(this.iefix);
    },
    hide: function() {
        this.stopIndicator();
        if (Element.getStyle(this.update, "display") != "none") this.options.onHide(this.element, this.update);
        if (this.iefix) Element.hide(this.iefix);
    },
    startIndicator: function() {
        if (this.options.indicator) Element.show(this.options.indicator);
    },
    stopIndicator: function() {
        if (this.options.indicator) Element.hide(this.options.indicator);
    },
    onKeyPress: function(event) {
        if (this.active) switch (event.keyCode) {
          case Event.KEY_TAB:
          case Event.KEY_RETURN:
            this.selectEntry();
            Event.stop(event);

          case Event.KEY_ESC:
            this.hide();
            this.active = false;
            Event.stop(event);
            return;

          case Event.KEY_LEFT:
          case Event.KEY_RIGHT:
            return;

          case Event.KEY_UP:
            this.markPrevious();
            this.render();
            Event.stop(event);
            return;

          case Event.KEY_DOWN:
            this.markNext();
            this.render();
            Event.stop(event);
            return;
        } else if (event.keyCode == Event.KEY_TAB || event.keyCode == Event.KEY_RETURN || Prototype.Browser.WebKit > 0 && event.keyCode == 0) return;
        this.changed = true;
        this.hasFocus = true;
        if (this.observer) clearTimeout(this.observer);
        this.observer = setTimeout(this.onObserverEvent.bind(this), this.options.frequency * 1e3);
    },
    activate: function() {
        this.changed = false;
        this.hasFocus = true;
        this.getUpdatedChoices();
    },
    onHover: function(event) {
        var element = Event.findElement(event, "LI");
        if (this.index != element.autocompleteIndex) {
            this.index = element.autocompleteIndex;
            this.render();
        }
        Event.stop(event);
    },
    onClick: function(event) {
        var element = Event.findElement(event, "LI");
        this.index = element.autocompleteIndex;
        this.selectEntry();
        this.hide();
    },
    onBlur: function(event) {
        setTimeout(this.hide.bind(this), 250);
        this.hasFocus = false;
        this.active = false;
    },
    render: function() {
        if (this.entryCount > 0) {
            for (var i = 0; i < this.entryCount; i++) this.index == i ? Element.addClassName(this.getEntry(i), "selected") : Element.removeClassName(this.getEntry(i), "selected");
            if (this.hasFocus) {
                this.show();
                this.active = true;
            }
        } else {
            this.active = false;
            this.hide();
        }
    },
    markPrevious: function() {
        if (this.index > 0) this.index--; else this.index = this.entryCount - 1;
    },
    markNext: function() {
        if (this.index < this.entryCount - 1) this.index++; else this.index = 0;
        this.getEntry(this.index).scrollIntoView(false);
    },
    getEntry: function(index) {
        return this.update.firstChild.childNodes[index];
    },
    getCurrentEntry: function() {
        return this.getEntry(this.index);
    },
    selectEntry: function() {
        this.active = false;
        this.updateElement(this.getCurrentEntry());
    },
    updateElement: function(selectedElement) {
        if (this.options.updateElement) {
            this.options.updateElement(selectedElement);
            return;
        }
        var value = "";
        if (this.options.select) {
            var nodes = $(selectedElement).select("." + this.options.select) || [];
            if (nodes.length > 0) value = Element.collectTextNodes(nodes[0], this.options.select);
        } else value = Element.collectTextNodesIgnoreClass(selectedElement, "informal");
        var bounds = this.getTokenBounds();
        if (bounds[0] != -1) {
            var newValue = this.element.value.substr(0, bounds[0]);
            var whitespace = this.element.value.substr(bounds[0]).match(/^\s+/);
            if (whitespace) newValue += whitespace[0];
            this.element.value = newValue + value + this.element.value.substr(bounds[1]);
        } else {
            this.element.value = value;
        }
        this.oldElementValue = this.element.value;
        this.element.focus();
        if (this.options.afterUpdateElement) this.options.afterUpdateElement(this.element, selectedElement);
    },
    updateChoices: function(choices) {
        if (!this.changed && this.hasFocus) {
            this.update.innerHTML = choices;
            Element.cleanWhitespace(this.update);
            Element.cleanWhitespace(this.update.down());
            if (this.update.firstChild && this.update.down().childNodes) {
                this.entryCount = this.update.down().childNodes.length;
                for (var i = 0; i < this.entryCount; i++) {
                    var entry = this.getEntry(i);
                    entry.autocompleteIndex = i;
                    this.addObservers(entry);
                }
            } else {
                this.entryCount = 0;
            }
            this.stopIndicator();
            this.index = 0;
            if (this.entryCount == 1 && this.options.autoSelect) {
                this.selectEntry();
                this.hide();
            } else {
                this.render();
            }
        }
    },
    addObservers: function(element) {
        Event.observe(element, "mouseover", this.onHover.bindAsEventListener(this));
        Event.observe(element, "click", this.onClick.bindAsEventListener(this));
    },
    onObserverEvent: function() {
        this.changed = false;
        this.tokenBounds = null;
        if (this.getToken().length >= this.options.minChars) {
            this.getUpdatedChoices();
        } else {
            this.active = false;
            this.hide();
        }
        this.oldElementValue = this.element.value;
    },
    getToken: function() {
        var bounds = this.getTokenBounds();
        return this.element.value.substring(bounds[0], bounds[1]).strip();
    },
    getTokenBounds: function() {
        if (null != this.tokenBounds) return this.tokenBounds;
        var value = this.element.value;
        if (value.strip().empty()) return [ -1, 0 ];
        var diff = arguments.callee.getFirstDifferencePos(value, this.oldElementValue);
        var offset = diff == this.oldElementValue.length ? 1 : 0;
        var prevTokenPos = -1, nextTokenPos = value.length;
        var tp;
        for (var index = 0, l = this.options.tokens.length; index < l; ++index) {
            tp = value.lastIndexOf(this.options.tokens[index], diff + offset - 1);
            if (tp > prevTokenPos) prevTokenPos = tp;
            tp = value.indexOf(this.options.tokens[index], diff + offset);
            if (-1 != tp && tp < nextTokenPos) nextTokenPos = tp;
        }
        return this.tokenBounds = [ prevTokenPos + 1, nextTokenPos ];
    }
});

Autocompleter.Base.prototype.getTokenBounds.getFirstDifferencePos = function(newS, oldS) {
    var boundary = Math.min(newS.length, oldS.length);
    for (var index = 0; index < boundary; ++index) if (newS[index] != oldS[index]) return index;
    return boundary;
};

Ajax.Autocompleter = Class.create(Autocompleter.Base, {
    initialize: function(element, update, url, options) {
        this.baseInitialize(element, update, options);
        this.options.asynchronous = true;
        this.options.onComplete = this.onComplete.bind(this);
        this.options.defaultParams = this.options.parameters || null;
        this.url = url;
    },
    getUpdatedChoices: function() {
        this.startIndicator();
        var entry = encodeURIComponent(this.options.paramName) + "=" + encodeURIComponent(this.getToken());
        this.options.parameters = this.options.callback ? this.options.callback(this.element, entry) : entry;
        if (this.options.defaultParams) this.options.parameters += "&" + this.options.defaultParams;
        new Ajax.Request(this.url, this.options);
    },
    onComplete: function(request) {
        this.updateChoices(request.responseText);
    }
});

Autocompleter.Local = Class.create(Autocompleter.Base, {
    initialize: function(element, update, array, options) {
        this.baseInitialize(element, update, options);
        this.options.array = array;
    },
    getUpdatedChoices: function() {
        this.updateChoices(this.options.selector(this));
    },
    setOptions: function(options) {
        this.options = Object.extend({
            choices: 10,
            partialSearch: true,
            partialChars: 2,
            ignoreCase: true,
            fullSearch: false,
            selector: function(instance) {
                var ret = [];
                var partial = [];
                var entry = instance.getToken();
                var count = 0;
                for (var i = 0; i < instance.options.array.length && ret.length < instance.options.choices; i++) {
                    var elem = instance.options.array[i];
                    var foundPos = instance.options.ignoreCase ? elem.toLowerCase().indexOf(entry.toLowerCase()) : elem.indexOf(entry);
                    while (foundPos != -1) {
                        if (foundPos == 0 && elem.length != entry.length) {
                            ret.push("<li><strong>" + elem.substr(0, entry.length) + "</strong>" + elem.substr(entry.length) + "</li>");
                            break;
                        } else if (entry.length >= instance.options.partialChars && instance.options.partialSearch && foundPos != -1) {
                            if (instance.options.fullSearch || /\s/.test(elem.substr(foundPos - 1, 1))) {
                                partial.push("<li>" + elem.substr(0, foundPos) + "<strong>" + elem.substr(foundPos, entry.length) + "</strong>" + elem.substr(foundPos + entry.length) + "</li>");
                                break;
                            }
                        }
                        foundPos = instance.options.ignoreCase ? elem.toLowerCase().indexOf(entry.toLowerCase(), foundPos + 1) : elem.indexOf(entry, foundPos + 1);
                    }
                }
                if (partial.length) ret = ret.concat(partial.slice(0, instance.options.choices - ret.length));
                return "<ul>" + ret.join("") + "</ul>";
            }
        }, options || {});
    }
});

Field.scrollFreeActivate = function(field) {
    setTimeout(function() {
        Field.activate(field);
    }, 1);
};

Ajax.InPlaceEditor = Class.create({
    initialize: function(element, url, options) {
        this.url = url;
        this.element = element = $(element);
        this.prepareOptions();
        this._controls = {};
        arguments.callee.dealWithDeprecatedOptions(options);
        Object.extend(this.options, options || {});
        if (!this.options.formId && this.element.id) {
            this.options.formId = this.element.id + "-inplaceeditor";
            if ($(this.options.formId)) this.options.formId = "";
        }
        if (this.options.externalControl) this.options.externalControl = $(this.options.externalControl);
        if (!this.options.externalControl) this.options.externalControlOnly = false;
        this._originalBackground = this.element.getStyle("background-color") || "transparent";
        this.element.title = this.options.clickToEditText;
        this._boundCancelHandler = this.handleFormCancellation.bind(this);
        this._boundComplete = (this.options.onComplete || Prototype.emptyFunction).bind(this);
        this._boundFailureHandler = this.handleAJAXFailure.bind(this);
        this._boundSubmitHandler = this.handleFormSubmission.bind(this);
        this._boundWrapperHandler = this.wrapUp.bind(this);
        this.registerListeners();
    },
    checkForEscapeOrReturn: function(e) {
        if (!this._editing || e.ctrlKey || e.altKey || e.shiftKey) return;
        if (Event.KEY_ESC == e.keyCode) this.handleFormCancellation(e); else if (Event.KEY_RETURN == e.keyCode) this.handleFormSubmission(e);
    },
    createControl: function(mode, handler, extraClasses) {
        var control = this.options[mode + "Control"];
        var text = this.options[mode + "Text"];
        if ("button" == control) {
            var btn = document.createElement("input");
            btn.type = "submit";
            btn.value = text;
            btn.className = "editor_" + mode + "_button";
            if ("cancel" == mode) btn.onclick = this._boundCancelHandler;
            this._form.appendChild(btn);
            this._controls[mode] = btn;
        } else if ("link" == control) {
            var link = document.createElement("a");
            link.href = "#";
            link.appendChild(document.createTextNode(text));
            link.onclick = "cancel" == mode ? this._boundCancelHandler : this._boundSubmitHandler;
            link.className = "editor_" + mode + "_link";
            if (extraClasses) link.className += " " + extraClasses;
            this._form.appendChild(link);
            this._controls[mode] = link;
        }
    },
    createEditField: function() {
        var text = this.options.loadTextURL ? this.options.loadingText : this.getText();
        var fld;
        if (1 >= this.options.rows && !/\r|\n/.test(this.getText())) {
            fld = document.createElement("input");
            fld.type = "text";
            var size = this.options.size || this.options.cols || 0;
            if (0 < size) fld.size = size;
        } else {
            fld = document.createElement("textarea");
            fld.rows = 1 >= this.options.rows ? this.options.autoRows : this.options.rows;
            fld.cols = this.options.cols || 40;
        }
        fld.name = this.options.paramName;
        fld.value = text;
        fld.className = "editor_field";
        if (this.options.submitOnBlur) fld.onblur = this._boundSubmitHandler;
        this._controls.editor = fld;
        if (this.options.loadTextURL) this.loadExternalText();
        this._form.appendChild(this._controls.editor);
    },
    createForm: function() {
        var ipe = this;
        function addText(mode, condition) {
            var text = ipe.options["text" + mode + "Controls"];
            if (!text || condition === false) return;
            ipe._form.appendChild(document.createTextNode(text));
        }
        this._form = $(document.createElement("form"));
        this._form.id = this.options.formId;
        this._form.addClassName(this.options.formClassName);
        this._form.onsubmit = this._boundSubmitHandler;
        this.createEditField();
        if ("textarea" == this._controls.editor.tagName.toLowerCase()) this._form.appendChild(document.createElement("br"));
        if (this.options.onFormCustomization) this.options.onFormCustomization(this, this._form);
        addText("Before", this.options.okControl || this.options.cancelControl);
        this.createControl("ok", this._boundSubmitHandler);
        addText("Between", this.options.okControl && this.options.cancelControl);
        this.createControl("cancel", this._boundCancelHandler, "editor_cancel");
        addText("After", this.options.okControl || this.options.cancelControl);
    },
    destroy: function() {
        if (this._oldInnerHTML) this.element.innerHTML = this._oldInnerHTML;
        this.leaveEditMode();
        this.unregisterListeners();
    },
    enterEditMode: function(e) {
        if (this._saving || this._editing) return;
        this._editing = true;
        this.triggerCallback("onEnterEditMode");
        if (this.options.externalControl) this.options.externalControl.hide();
        this.element.hide();
        this.createForm();
        this.element.parentNode.insertBefore(this._form, this.element);
        if (!this.options.loadTextURL) this.postProcessEditField();
        if (e) Event.stop(e);
    },
    enterHover: function(e) {
        if (this.options.hoverClassName) this.element.addClassName(this.options.hoverClassName);
        if (this._saving) return;
        this.triggerCallback("onEnterHover");
    },
    getText: function() {
        return this.element.innerHTML.unescapeHTML();
    },
    handleAJAXFailure: function(transport) {
        this.triggerCallback("onFailure", transport);
        if (this._oldInnerHTML) {
            this.element.innerHTML = this._oldInnerHTML;
            this._oldInnerHTML = null;
        }
    },
    handleFormCancellation: function(e) {
        this.wrapUp();
        if (e) Event.stop(e);
    },
    handleFormSubmission: function(e) {
        var form = this._form;
        var value = $F(this._controls.editor);
        this.prepareSubmission();
        var params = this.options.callback(form, value) || "";
        if (Object.isString(params)) params = params.toQueryParams();
        params.editorId = this.element.id;
        if (this.options.htmlResponse) {
            var options = Object.extend({
                evalScripts: true
            }, this.options.ajaxOptions);
            Object.extend(options, {
                parameters: params,
                onComplete: this._boundWrapperHandler,
                onFailure: this._boundFailureHandler
            });
            new Ajax.Updater({
                success: this.element
            }, this.url, options);
        } else {
            var options = Object.extend({
                method: "get"
            }, this.options.ajaxOptions);
            Object.extend(options, {
                parameters: params,
                onComplete: this._boundWrapperHandler,
                onFailure: this._boundFailureHandler
            });
            new Ajax.Request(this.url, options);
        }
        if (e) Event.stop(e);
    },
    leaveEditMode: function() {
        this.element.removeClassName(this.options.savingClassName);
        this.removeForm();
        this.leaveHover();
        this.element.style.backgroundColor = this._originalBackground;
        this.element.show();
        if (this.options.externalControl) this.options.externalControl.show();
        this._saving = false;
        this._editing = false;
        this._oldInnerHTML = null;
        this.triggerCallback("onLeaveEditMode");
    },
    leaveHover: function(e) {
        if (this.options.hoverClassName) this.element.removeClassName(this.options.hoverClassName);
        if (this._saving) return;
        this.triggerCallback("onLeaveHover");
    },
    loadExternalText: function() {
        this._form.addClassName(this.options.loadingClassName);
        this._controls.editor.disabled = true;
        var options = Object.extend({
            method: "get"
        }, this.options.ajaxOptions);
        Object.extend(options, {
            parameters: "editorId=" + encodeURIComponent(this.element.id),
            onComplete: Prototype.emptyFunction,
            onSuccess: function(transport) {
                this._form.removeClassName(this.options.loadingClassName);
                var text = transport.responseText;
                if (this.options.stripLoadedTextTags) text = text.stripTags();
                this._controls.editor.value = text;
                this._controls.editor.disabled = false;
                this.postProcessEditField();
            }.bind(this),
            onFailure: this._boundFailureHandler
        });
        new Ajax.Request(this.options.loadTextURL, options);
    },
    postProcessEditField: function() {
        var fpc = this.options.fieldPostCreation;
        if (fpc) $(this._controls.editor)["focus" == fpc ? "focus" : "activate"]();
    },
    prepareOptions: function() {
        this.options = Object.clone(Ajax.InPlaceEditor.DefaultOptions);
        Object.extend(this.options, Ajax.InPlaceEditor.DefaultCallbacks);
        [ this._extraDefaultOptions ].flatten().compact().each(function(defs) {
            Object.extend(this.options, defs);
        }.bind(this));
    },
    prepareSubmission: function() {
        this._saving = true;
        this.removeForm();
        this.leaveHover();
        this.showSaving();
    },
    registerListeners: function() {
        this._listeners = {};
        var listener;
        $H(Ajax.InPlaceEditor.Listeners).each(function(pair) {
            listener = this[pair.value].bind(this);
            this._listeners[pair.key] = listener;
            if (!this.options.externalControlOnly) this.element.observe(pair.key, listener);
            if (this.options.externalControl) this.options.externalControl.observe(pair.key, listener);
        }.bind(this));
    },
    removeForm: function() {
        if (!this._form) return;
        this._form.remove();
        this._form = null;
        this._controls = {};
    },
    showSaving: function() {
        this._oldInnerHTML = this.element.innerHTML;
        this.element.innerHTML = this.options.savingText;
        this.element.addClassName(this.options.savingClassName);
        this.element.style.backgroundColor = this._originalBackground;
        this.element.show();
    },
    triggerCallback: function(cbName, arg) {
        if ("function" == typeof this.options[cbName]) {
            this.options[cbName](this, arg);
        }
    },
    unregisterListeners: function() {
        $H(this._listeners).each(function(pair) {
            if (!this.options.externalControlOnly) this.element.stopObserving(pair.key, pair.value);
            if (this.options.externalControl) this.options.externalControl.stopObserving(pair.key, pair.value);
        }.bind(this));
    },
    wrapUp: function(transport) {
        this.leaveEditMode();
        this._boundComplete(transport, this.element);
    }
});

Object.extend(Ajax.InPlaceEditor.prototype, {
    dispose: Ajax.InPlaceEditor.prototype.destroy
});

Ajax.InPlaceCollectionEditor = Class.create(Ajax.InPlaceEditor, {
    initialize: function($super, element, url, options) {
        this._extraDefaultOptions = Ajax.InPlaceCollectionEditor.DefaultOptions;
        $super(element, url, options);
    },
    createEditField: function() {
        var list = document.createElement("select");
        list.name = this.options.paramName;
        list.size = 1;
        this._controls.editor = list;
        this._collection = this.options.collection || [];
        if (this.options.loadCollectionURL) this.loadCollection(); else this.checkForExternalText();
        this._form.appendChild(this._controls.editor);
    },
    loadCollection: function() {
        this._form.addClassName(this.options.loadingClassName);
        this.showLoadingText(this.options.loadingCollectionText);
        var options = Object.extend({
            method: "get"
        }, this.options.ajaxOptions);
        Object.extend(options, {
            parameters: "editorId=" + encodeURIComponent(this.element.id),
            onComplete: Prototype.emptyFunction,
            onSuccess: function(transport) {
                var js = transport.responseText.strip();
                if (!/^\[.*\]$/.test(js)) throw "Server returned an invalid collection representation.";
                this._collection = eval(js);
                this.checkForExternalText();
            }.bind(this),
            onFailure: this.onFailure
        });
        new Ajax.Request(this.options.loadCollectionURL, options);
    },
    showLoadingText: function(text) {
        this._controls.editor.disabled = true;
        var tempOption = this._controls.editor.firstChild;
        if (!tempOption) {
            tempOption = document.createElement("option");
            tempOption.value = "";
            this._controls.editor.appendChild(tempOption);
            tempOption.selected = true;
        }
        tempOption.update((text || "").stripScripts().stripTags());
    },
    checkForExternalText: function() {
        this._text = this.getText();
        if (this.options.loadTextURL) this.loadExternalText(); else this.buildOptionList();
    },
    loadExternalText: function() {
        this.showLoadingText(this.options.loadingText);
        var options = Object.extend({
            method: "get"
        }, this.options.ajaxOptions);
        Object.extend(options, {
            parameters: "editorId=" + encodeURIComponent(this.element.id),
            onComplete: Prototype.emptyFunction,
            onSuccess: function(transport) {
                this._text = transport.responseText.strip();
                this.buildOptionList();
            }.bind(this),
            onFailure: this.onFailure
        });
        new Ajax.Request(this.options.loadTextURL, options);
    },
    buildOptionList: function() {
        this._form.removeClassName(this.options.loadingClassName);
        this._collection = this._collection.map(function(entry) {
            return 2 === entry.length ? entry : [ entry, entry ].flatten();
        });
        var marker = "value" in this.options ? this.options.value : this._text;
        var textFound = this._collection.any(function(entry) {
            return entry[0] == marker;
        }.bind(this));
        this._controls.editor.update("");
        var option;
        this._collection.each(function(entry, index) {
            option = document.createElement("option");
            option.value = entry[0];
            option.selected = textFound ? entry[0] == marker : 0 == index;
            option.appendChild(document.createTextNode(entry[1]));
            this._controls.editor.appendChild(option);
        }.bind(this));
        this._controls.editor.disabled = false;
        Field.scrollFreeActivate(this._controls.editor);
    }
});

Ajax.InPlaceEditor.prototype.initialize.dealWithDeprecatedOptions = function(options) {
    if (!options) return;
    function fallback(name, expr) {
        if (name in options || expr === undefined) return;
        options[name] = expr;
    }
    fallback("cancelControl", options.cancelLink ? "link" : options.cancelButton ? "button" : options.cancelLink == options.cancelButton == false ? false : undefined);
    fallback("okControl", options.okLink ? "link" : options.okButton ? "button" : options.okLink == options.okButton == false ? false : undefined);
    fallback("highlightColor", options.highlightcolor);
    fallback("highlightEndColor", options.highlightendcolor);
};

Object.extend(Ajax.InPlaceEditor, {
    DefaultOptions: {
        ajaxOptions: {},
        autoRows: 3,
        cancelControl: "link",
        cancelText: "cancel",
        clickToEditText: "Click to edit",
        externalControl: null,
        externalControlOnly: false,
        fieldPostCreation: "activate",
        formClassName: "inplaceeditor-form",
        formId: null,
        highlightColor: "#ffff99",
        highlightEndColor: "#ffffff",
        hoverClassName: "",
        htmlResponse: true,
        loadingClassName: "inplaceeditor-loading",
        loadingText: "Loading...",
        okControl: "button",
        okText: "ok",
        paramName: "value",
        rows: 1,
        savingClassName: "inplaceeditor-saving",
        savingText: "Saving...",
        size: 0,
        stripLoadedTextTags: false,
        submitOnBlur: false,
        textAfterControls: "",
        textBeforeControls: "",
        textBetweenControls: ""
    },
    DefaultCallbacks: {
        callback: function(form) {
            return Form.serialize(form);
        },
        onComplete: function(transport, element) {
            new Effect.Highlight(element, {
                startcolor: this.options.highlightColor,
                keepBackgroundImage: true
            });
        },
        onEnterEditMode: null,
        onEnterHover: function(ipe) {
            ipe.element.style.backgroundColor = ipe.options.highlightColor;
            if (ipe._effect) ipe._effect.cancel();
        },
        onFailure: function(transport, ipe) {
            alert("Error communication with the server: " + transport.responseText.stripTags());
        },
        onFormCustomization: null,
        onLeaveEditMode: null,
        onLeaveHover: function(ipe) {
            ipe._effect = new Effect.Highlight(ipe.element, {
                startcolor: ipe.options.highlightColor,
                endcolor: ipe.options.highlightEndColor,
                restorecolor: ipe._originalBackground,
                keepBackgroundImage: true
            });
        }
    },
    Listeners: {
        click: "enterEditMode",
        keydown: "checkForEscapeOrReturn",
        mouseover: "enterHover",
        mouseout: "leaveHover"
    }
});

Ajax.InPlaceCollectionEditor.DefaultOptions = {
    loadingCollectionText: "Loading options..."
};

Form.Element.DelayedObserver = Class.create({
    initialize: function(element, delay, callback) {
        this.delay = delay || .5;
        this.element = $(element);
        this.callback = callback;
        this.timer = null;
        this.lastValue = $F(this.element);
        Event.observe(this.element, "keyup", this.delayedListener.bindAsEventListener(this));
    },
    delayedListener: function(event) {
        if (this.lastValue == $F(this.element)) return;
        if (this.timer) clearTimeout(this.timer);
        this.timer = setTimeout(this.onTimerEvent.bind(this), this.delay * 1e3);
        this.lastValue = $F(this.element);
    },
    onTimerEvent: function() {
        this.timer = null;
        this.callback(this.element, $F(this.element));
    }
});

if (!Control) var Control = {};

Control.Slider = Class.create({
    initialize: function(handle, track, options) {
        var slider = this;
        if (Object.isArray(handle)) {
            this.handles = handle.collect(function(e) {
                return $(e);
            });
        } else {
            this.handles = [ $(handle) ];
        }
        this.track = $(track);
        this.options = options || {};
        this.axis = this.options.axis || "horizontal";
        this.increment = this.options.increment || 1;
        this.step = parseInt(this.options.step || "1");
        this.range = this.options.range || $R(0, 1);
        this.value = 0;
        this.values = this.handles.map(function() {
            return 0;
        });
        this.spans = this.options.spans ? this.options.spans.map(function(s) {
            return $(s);
        }) : false;
        this.options.startSpan = $(this.options.startSpan || null);
        this.options.endSpan = $(this.options.endSpan || null);
        this.restricted = this.options.restricted || false;
        this.maximum = this.options.maximum || this.range.end;
        this.minimum = this.options.minimum || this.range.start;
        this.alignX = parseInt(this.options.alignX || "0");
        this.alignY = parseInt(this.options.alignY || "0");
        this.trackLength = this.maximumOffset() - this.minimumOffset();
        this.handleLength = this.isVertical() ? this.handles[0].offsetHeight != 0 ? this.handles[0].offsetHeight : this.handles[0].style.height.replace(/px$/, "") : this.handles[0].offsetWidth != 0 ? this.handles[0].offsetWidth : this.handles[0].style.width.replace(/px$/, "");
        this.active = false;
        this.dragging = false;
        this.disabled = false;
        if (this.options.disabled) this.setDisabled();
        this.allowedValues = this.options.values ? this.options.values.sortBy(Prototype.K) : false;
        if (this.allowedValues) {
            this.minimum = this.allowedValues.min();
            this.maximum = this.allowedValues.max();
        }
        this.eventMouseDown = this.startDrag.bindAsEventListener(this);
        this.eventMouseUp = this.endDrag.bindAsEventListener(this);
        this.eventMouseMove = this.update.bindAsEventListener(this);
        this.handles.each(function(h, i) {
            i = slider.handles.length - 1 - i;
            slider.setValue(parseFloat((Object.isArray(slider.options.sliderValue) ? slider.options.sliderValue[i] : slider.options.sliderValue) || slider.range.start), i);
            h.makePositioned().observe("mousedown", slider.eventMouseDown);
        });
        this.track.observe("mousedown", this.eventMouseDown);
        document.observe("mouseup", this.eventMouseUp);
        $(this.track.parentNode.parentNode).observe("mousemove", this.eventMouseMove);
        this.initialized = true;
    },
    dispose: function() {
        var slider = this;
        Event.stopObserving(this.track, "mousedown", this.eventMouseDown);
        Event.stopObserving(document, "mouseup", this.eventMouseUp);
        Event.stopObserving(this.track.parentNode.parentNode, "mousemove", this.eventMouseMove);
        this.handles.each(function(h) {
            Event.stopObserving(h, "mousedown", slider.eventMouseDown);
        });
    },
    setDisabled: function() {
        this.disabled = true;
        this.track.parentNode.className = this.track.parentNode.className + " disabled";
    },
    setEnabled: function() {
        this.disabled = false;
    },
    getNearestValue: function(value) {
        if (this.allowedValues) {
            if (value >= this.allowedValues.max()) return this.allowedValues.max();
            if (value <= this.allowedValues.min()) return this.allowedValues.min();
            var offset = Math.abs(this.allowedValues[0] - value);
            var newValue = this.allowedValues[0];
            this.allowedValues.each(function(v) {
                var currentOffset = Math.abs(v - value);
                if (currentOffset <= offset) {
                    newValue = v;
                    offset = currentOffset;
                }
            });
            return newValue;
        }
        if (value > this.range.end) return this.range.end;
        if (value < this.range.start) return this.range.start;
        return value;
    },
    setValue: function(sliderValue, handleIdx) {
        if (!this.active) {
            this.activeHandleIdx = handleIdx || 0;
            this.activeHandle = this.handles[this.activeHandleIdx];
            this.updateStyles();
        }
        handleIdx = handleIdx || this.activeHandleIdx || 0;
        if (this.initialized && this.restricted) {
            if (handleIdx > 0 && sliderValue < this.values[handleIdx - 1]) sliderValue = this.values[handleIdx - 1];
            if (handleIdx < this.handles.length - 1 && sliderValue > this.values[handleIdx + 1]) sliderValue = this.values[handleIdx + 1];
        }
        sliderValue = this.getNearestValue(sliderValue);
        this.values[handleIdx] = sliderValue;
        this.value = this.values[0];
        this.handles[handleIdx].style[this.isVertical() ? "top" : "left"] = this.translateToPx(sliderValue);
        this.drawSpans();
        if (!this.dragging || !this.event) this.updateFinished();
    },
    setValueBy: function(delta, handleIdx) {
        this.setValue(this.values[handleIdx || this.activeHandleIdx || 0] + delta, handleIdx || this.activeHandleIdx || 0);
    },
    translateToPx: function(value) {
        return Math.round((this.trackLength - this.handleLength) / (this.range.end - this.range.start) * (value - this.range.start)) + "px";
    },
    translateToValue: function(offset) {
        return offset / (this.trackLength - this.handleLength) * (this.range.end - this.range.start) + this.range.start;
    },
    getRange: function(range) {
        var v = this.values.sortBy(Prototype.K);
        range = range || 0;
        return $R(v[range], v[range + 1]);
    },
    minimumOffset: function() {
        return this.isVertical() ? this.alignY : this.alignX;
    },
    maximumOffset: function() {
        return this.isVertical() ? (this.track.offsetHeight != 0 ? this.track.offsetHeight : this.track.style.height.replace(/px$/, "")) - this.alignY : (this.track.offsetWidth != 0 ? this.track.offsetWidth : this.track.style.width.replace(/px$/, "")) - this.alignX;
    },
    isVertical: function() {
        return this.axis == "vertical";
    },
    drawSpans: function() {
        var slider = this;
        if (this.spans) $R(0, this.spans.length - 1).each(function(r) {
            slider.setSpan(slider.spans[r], slider.getRange(r));
        });
        if (this.options.startSpan) this.setSpan(this.options.startSpan, $R(0, this.values.length > 1 ? this.getRange(0).min() : this.value));
        if (this.options.endSpan) this.setSpan(this.options.endSpan, $R(this.values.length > 1 ? this.getRange(this.spans.length - 1).max() : this.value, this.maximum));
    },
    setSpan: function(span, range) {
        if (this.isVertical()) {
            span.style.top = this.translateToPx(range.start);
            span.style.height = this.translateToPx(range.end - range.start + this.range.start);
        } else {
            span.style.left = this.translateToPx(range.start);
            span.style.width = this.translateToPx(range.end - range.start + this.range.start);
        }
    },
    updateStyles: function() {
        this.handles.each(function(h) {
            Element.removeClassName(h, "selected");
        });
        Element.addClassName(this.activeHandle, "selected");
    },
    startDrag: function(event) {
        if (Event.isLeftClick(event)) {
            if (!this.disabled) {
                this.active = true;
                var handle = Event.element(event);
                var pointer = [ Event.pointerX(event), Event.pointerY(event) ];
                var track = handle;
                if (track == this.track) {
                    var offsets = Position.cumulativeOffset(this.track);
                    this.event = event;
                    this.setValue(this.translateToValue((this.isVertical() ? pointer[1] - offsets[1] : pointer[0] - offsets[0]) - this.handleLength / 2));
                    var offsets = Position.cumulativeOffset(this.activeHandle);
                    this.offsetX = pointer[0] - offsets[0];
                    this.offsetY = pointer[1] - offsets[1];
                } else {
                    while (this.handles.indexOf(handle) == -1 && handle.parentNode) handle = handle.parentNode;
                    if (this.handles.indexOf(handle) != -1) {
                        this.activeHandle = handle;
                        this.activeHandleIdx = this.handles.indexOf(this.activeHandle);
                        this.updateStyles();
                        var offsets = Position.cumulativeOffset(this.activeHandle);
                        this.offsetX = pointer[0] - offsets[0];
                        this.offsetY = pointer[1] - offsets[1];
                    }
                }
            }
            Event.stop(event);
        }
    },
    update: function(event) {
        if (this.active) {
            if (!this.dragging) this.dragging = true;
            this.draw(event);
            if (Prototype.Browser.WebKit) window.scrollBy(0, 0);
            Event.stop(event);
        }
    },
    draw: function(event) {
        var pointer = [ Event.pointerX(event), Event.pointerY(event) ];
        var offsets = Position.cumulativeOffset(this.track);
        pointer[0] -= this.offsetX + offsets[0];
        pointer[1] -= this.offsetY + offsets[1];
        this.event = event;
        this.setValue(this.translateToValue(this.isVertical() ? pointer[1] : pointer[0]));
        if (this.initialized && this.options.onSlide) this.options.onSlide(this.values.length > 1 ? this.values : this.value, this);
    },
    endDrag: function(event) {
        if (this.active && this.dragging) {
            this.finishDrag(event, true);
            Event.stop(event);
        }
        this.active = false;
        this.dragging = false;
    },
    finishDrag: function(event, success) {
        this.active = false;
        this.dragging = false;
        this.updateFinished();
    },
    updateFinished: function() {
        if (this.initialized && this.options.onChange) this.options.onChange(this.values.length > 1 ? this.values : this.value, this);
        this.event = null;
    }
});

function popWin(url, win, para) {
    var win = window.open(url, win, para);
    win.focus();
}

function setLocation(url) {
    window.location.href = encodeURI(url);
}

function setPLocation(url, setFocus) {
    if (setFocus) {
        window.opener.focus();
    }
    window.opener.location.href = encodeURI(url);
}

function setLanguageCode(code, fromCode) {
    var href = window.location.href;
    var after = "", dash;
    if (dash = href.match(/\#(.*)$/)) {
        href = href.replace(/\#(.*)$/, "");
        after = dash[0];
    }
    if (href.match(/[?]/)) {
        var re = /([?&]store=)[a-z0-9_]*/;
        if (href.match(re)) {
            href = href.replace(re, "$1" + code);
        } else {
            href += "&store=" + code;
        }
        var re = /([?&]from_store=)[a-z0-9_]*/;
        if (href.match(re)) {
            href = href.replace(re, "");
        }
    } else {
        href += "?store=" + code;
    }
    if (typeof fromCode != "undefined") {
        href += "&from_store=" + fromCode;
    }
    href += after;
    setLocation(href);
}

function decorateGeneric(elements, decorateParams) {
    var allSupportedParams = [ "odd", "even", "first", "last" ];
    var _decorateParams = {};
    var total = elements.length;
    if (total) {
        if (typeof decorateParams == "undefined") {
            decorateParams = allSupportedParams;
        }
        if (!decorateParams.length) {
            return;
        }
        for (var k in allSupportedParams) {
            _decorateParams[allSupportedParams[k]] = false;
        }
        for (var k in decorateParams) {
            _decorateParams[decorateParams[k]] = true;
        }
        if (_decorateParams.first) {
            Element.addClassName(elements[0], "first");
        }
        if (_decorateParams.last) {
            Element.addClassName(elements[total - 1], "last");
        }
        for (var i = 0; i < total; i++) {
            if ((i + 1) % 2 == 0) {
                if (_decorateParams.even) {
                    Element.addClassName(elements[i], "even");
                }
            } else {
                if (_decorateParams.odd) {
                    Element.addClassName(elements[i], "odd");
                }
            }
        }
    }
}

function decorateTable(table, options) {
    var table = $(table);
    if (table) {
        var _options = {
            tbody: false,
            "tbody tr": [ "odd", "even", "first", "last" ],
            "thead tr": [ "first", "last" ],
            "tfoot tr": [ "first", "last" ],
            "tr td": [ "last" ]
        };
        if (typeof options != "undefined") {
            for (var k in options) {
                _options[k] = options[k];
            }
        }
        if (_options["tbody"]) {
            decorateGeneric(table.select("tbody"), _options["tbody"]);
        }
        if (_options["tbody tr"]) {
            decorateGeneric(table.select("tbody tr"), _options["tbody tr"]);
        }
        if (_options["thead tr"]) {
            decorateGeneric(table.select("thead tr"), _options["thead tr"]);
        }
        if (_options["tfoot tr"]) {
            decorateGeneric(table.select("tfoot tr"), _options["tfoot tr"]);
        }
        if (_options["tr td"]) {
            var allRows = table.select("tr");
            if (allRows.length) {
                for (var i = 0; i < allRows.length; i++) {
                    decorateGeneric(allRows[i].getElementsByTagName("TD"), _options["tr td"]);
                }
            }
        }
    }
}

function decorateList(list, nonRecursive) {
    if ($(list)) {
        if (typeof nonRecursive == "undefined") {
            var items = $(list).select("li");
        } else {
            var items = $(list).childElements();
        }
        decorateGeneric(items, [ "odd", "even", "last" ]);
    }
}

function decorateDataList(list) {
    list = $(list);
    if (list) {
        decorateGeneric(list.select("dt"), [ "odd", "even", "last" ]);
        decorateGeneric(list.select("dd"), [ "odd", "even", "last" ]);
    }
}

function parseSidUrl(baseUrl, urlExt) {
    var sidPos = baseUrl.indexOf("/?SID=");
    var sid = "";
    urlExt = urlExt != undefined ? urlExt : "";
    if (sidPos > -1) {
        sid = "?" + baseUrl.substring(sidPos + 2);
        baseUrl = baseUrl.substring(0, sidPos + 1);
    }
    return baseUrl + urlExt + sid;
}

function formatCurrency(price, format, showPlus) {
    var precision = isNaN(format.precision = Math.abs(format.precision)) ? 2 : format.precision;
    var requiredPrecision = isNaN(format.requiredPrecision = Math.abs(format.requiredPrecision)) ? 2 : format.requiredPrecision;
    precision = requiredPrecision;
    var integerRequired = isNaN(format.integerRequired = Math.abs(format.integerRequired)) ? 1 : format.integerRequired;
    var decimalSymbol = format.decimalSymbol == undefined ? "," : format.decimalSymbol;
    var groupSymbol = format.groupSymbol == undefined ? "." : format.groupSymbol;
    var groupLength = format.groupLength == undefined ? 3 : format.groupLength;
    var s = "";
    if (showPlus == undefined || showPlus == true) {
        s = price < 0 ? "-" : showPlus ? "+" : "";
    } else if (showPlus == false) {
        s = "";
    }
    var i = parseInt(price = Math.abs(+price || 0).toFixed(precision)) + "";
    var pad = i.length < integerRequired ? integerRequired - i.length : 0;
    while (pad) {
        i = "0" + i;
        pad--;
    }
    j = (j = i.length) > groupLength ? j % groupLength : 0;
    re = new RegExp("(\\d{" + groupLength + "})(?=\\d)", "g");
    var r = (j ? i.substr(0, j) + groupSymbol : "") + i.substr(j).replace(re, "$1" + groupSymbol) + (precision ? decimalSymbol + Math.abs(price - i).toFixed(precision).replace(/-/, 0).slice(2) : "");
    var pattern = "";
    if (format.pattern.indexOf("{sign}") == -1) {
        pattern = s + format.pattern;
    } else {
        pattern = format.pattern.replace("{sign}", s);
    }
    return pattern.replace("%s", r).replace(/^\s\s*/, "").replace(/\s\s*$/, "");
}

function expandDetails(el, childClass) {
    if (Element.hasClassName(el, "show-details")) {
        $$(childClass).each(function(item) {
            item.hide();
        });
        Element.removeClassName(el, "show-details");
    } else {
        $$(childClass).each(function(item) {
            item.show();
        });
        Element.addClassName(el, "show-details");
    }
}

var isIE = navigator.appVersion.match(/MSIE/) == "MSIE";

if (!window.Varien) var Varien = new Object();

Varien.showLoading = function() {
    var loader = $("loading-process");
    loader && loader.show();
};

Varien.hideLoading = function() {
    var loader = $("loading-process");
    loader && loader.hide();
};

Varien.GlobalHandlers = {
    onCreate: function() {
        Varien.showLoading();
    },
    onComplete: function() {
        if (Ajax.activeRequestCount == 0) {
            Varien.hideLoading();
        }
    }
};

Ajax.Responders.register(Varien.GlobalHandlers);

Varien.searchForm = Class.create();

Varien.searchForm.prototype = {
    initialize: function(form, field, emptyText) {
        this.form = $(form);
        this.field = $(field);
        this.emptyText = emptyText;
        Event.observe(this.form, "submit", this.submit.bind(this));
        Event.observe(this.field, "focus", this.focus.bind(this));
        Event.observe(this.field, "blur", this.blur.bind(this));
        this.blur();
    },
    submit: function(event) {
        if (this.field.value == this.emptyText || this.field.value == "") {
            Event.stop(event);
            return false;
        }
        return true;
    },
    focus: function(event) {
        if (this.field.value == this.emptyText) {
            this.field.value = "";
        }
    },
    blur: function(event) {
        if (this.field.value == "") {
            this.field.value = this.emptyText;
        }
    },
    initAutocomplete: function(url, destinationElement) {
        new Ajax.Autocompleter(this.field, destinationElement, url, {
            paramName: this.field.name,
            method: "get",
            minChars: 2,
            updateElement: this._selectAutocompleteItem.bind(this),
            onShow: function(element, update) {
                if (!update.style.position || update.style.position == "absolute") {
                    update.style.position = "absolute";
                    Position.clone(element, update, {
                        setHeight: false,
                        offsetTop: element.offsetHeight
                    });
                }
                Effect.Appear(update, {
                    duration: 0
                });
            }
        });
    },
    _selectAutocompleteItem: function(element) {
        if (element.title) {
            this.field.value = element.title;
        }
        this.form.submit();
    }
};

Varien.Tabs = Class.create();

Varien.Tabs.prototype = {
    initialize: function(selector) {
        var self = this;
        $$(selector + " a").each(this.initTab.bind(this));
    },
    initTab: function(el) {
        el.href = "javascript:void(0)";
        if ($(el.parentNode).hasClassName("active")) {
            this.showContent(el);
        }
        el.observe("click", this.showContent.bind(this, el));
    },
    showContent: function(a) {
        var li = $(a.parentNode), ul = $(li.parentNode);
        ul.getElementsBySelector("li", "ol").each(function(el) {
            var contents = $(el.id + "_contents");
            if (el == li) {
                el.addClassName("active");
                contents.show();
            } else {
                el.removeClassName("active");
                contents.hide();
            }
        });
    }
};

Varien.DateElement = Class.create();

Varien.DateElement.prototype = {
    initialize: function(type, content, required, format) {
        if (type == "id") {
            this.day = $(content + "day");
            this.month = $(content + "month");
            this.year = $(content + "year");
            this.full = $(content + "full");
            this.advice = $(content + "date-advice");
        } else if (type == "container") {
            this.day = content.day;
            this.month = content.month;
            this.year = content.year;
            this.full = content.full;
            this.advice = content.advice;
        } else {
            return;
        }
        this.required = required;
        this.format = format;
        this.day.addClassName("validate-custom");
        this.day.validate = this.validate.bind(this);
        this.month.addClassName("validate-custom");
        this.month.validate = this.validate.bind(this);
        this.year.addClassName("validate-custom");
        this.year.validate = this.validate.bind(this);
        this.setDateRange(false, false);
        this.year.setAttribute("autocomplete", "off");
        this.advice.hide();
        var date = new Date();
        this.curyear = date.getFullYear();
    },
    validate: function() {
        var error = false, day = parseInt(this.day.value, 10) || 0, month = parseInt(this.month.value, 10) || 0, year = parseInt(this.year.value, 10) || 0;
        if (this.day.value.strip().empty() && this.month.value.strip().empty() && this.year.value.strip().empty()) {
            if (this.required) {
                error = "This date is a required value.";
            } else {
                this.full.value = "";
            }
        } else if (!day || !month || !year) {
            error = "Please enter a valid full date";
        } else {
            var date = new Date(), countDaysInMonth = 0, errorType = null;
            date.setYear(year);
            date.setMonth(month - 1);
            date.setDate(32);
            countDaysInMonth = 32 - date.getDate();
            if (!countDaysInMonth || countDaysInMonth > 31) countDaysInMonth = 31;
            if (year < 1900) error = this.errorTextModifier(this.validateDataErrorText);
            if (day < 1 || day > countDaysInMonth) {
                errorType = "day";
                error = "Please enter a valid day (1-%d).";
            } else if (month < 1 || month > 12) {
                errorType = "month";
                error = "Please enter a valid month (1-12).";
            } else {
                if (day % 10 == day) this.day.value = "0" + day;
                if (month % 10 == month) this.month.value = "0" + month;
                this.full.value = this.format.replace(/%[mb]/i, this.month.value).replace(/%[de]/i, this.day.value).replace(/%y/i, this.year.value);
                var testFull = this.month.value + "/" + this.day.value + "/" + this.year.value;
                var test = new Date(testFull);
                if (isNaN(test)) {
                    error = "Please enter a valid date.";
                } else {
                    this.setFullDate(test);
                }
            }
            var valueError = false;
            if (!error && !this.validateData()) {
                errorType = this.validateDataErrorType;
                valueError = this.validateDataErrorText;
                error = valueError;
            }
        }
        if (error !== false) {
            try {
                error = Translator.translate(error);
            } catch (e) {}
            if (!valueError) {
                this.advice.innerHTML = error.replace("%d", countDaysInMonth);
            } else {
                this.advice.innerHTML = this.errorTextModifier(error);
            }
            this.advice.show();
            return false;
        }
        this.day.removeClassName("validation-failed");
        this.month.removeClassName("validation-failed");
        this.year.removeClassName("validation-failed");
        this.advice.hide();
        return true;
    },
    validateData: function() {
        var year = this.fullDate.getFullYear();
        return year >= 1900 && year <= this.curyear;
    },
    validateDataErrorType: "year",
    validateDataErrorText: "Please enter a valid year (1900-%d).",
    errorTextModifier: function(text) {
        text = Translator.translate(text);
        return text.replace("%d", this.curyear);
    },
    setDateRange: function(minDate, maxDate) {
        this.minDate = minDate;
        this.maxDate = maxDate;
    },
    setFullDate: function(date) {
        this.fullDate = date;
    }
};

Varien.DOB = Class.create();

Varien.DOB.prototype = {
    initialize: function(selector, required, format) {
        var el = $$(selector)[0];
        var container = {};
        container.day = Element.select(el, ".dob-day input")[0];
        container.month = Element.select(el, ".dob-month input")[0];
        container.year = Element.select(el, ".dob-year input")[0];
        container.full = Element.select(el, ".dob-full input")[0];
        container.advice = Element.select(el, ".validation-advice")[0];
        new Varien.DateElement("container", container, required, format);
    }
};

Varien.dateRangeDate = Class.create();

Varien.dateRangeDate.prototype = Object.extend(new Varien.DateElement(), {
    validateData: function() {
        var validate = true;
        if (this.minDate || this.maxValue) {
            if (this.minDate) {
                this.minDate = new Date(this.minDate);
                this.minDate.setHours(0);
                if (isNaN(this.minDate)) {
                    this.minDate = new Date("1/1/1900");
                }
                validate = validate && this.fullDate >= this.minDate;
            }
            if (this.maxDate) {
                this.maxDate = new Date(this.maxDate);
                this.minDate.setHours(0);
                if (isNaN(this.maxDate)) {
                    this.maxDate = new Date();
                }
                validate = validate && this.fullDate <= this.maxDate;
            }
            if (this.maxDate && this.minDate) {
                this.validateDataErrorText = "Please enter a valid date between %s and %s";
            } else if (this.maxDate) {
                this.validateDataErrorText = "Please enter a valid date less than or equal to %s";
            } else if (this.minDate) {
                this.validateDataErrorText = "Please enter a valid date equal to or greater than %s";
            } else {
                this.validateDataErrorText = "";
            }
        }
        return validate;
    },
    validateDataErrorText: "Date should be between %s and %s",
    errorTextModifier: function(text) {
        if (this.minDate) {
            text = text.sub("%s", this.dateFormat(this.minDate));
        }
        if (this.maxDate) {
            text = text.sub("%s", this.dateFormat(this.maxDate));
        }
        return text;
    },
    dateFormat: function(date) {
        return date.getMonth() + 1 + "/" + date.getDate() + "/" + date.getFullYear();
    }
});

Varien.FileElement = Class.create();

Varien.FileElement.prototype = {
    initialize: function(id) {
        this.fileElement = $(id);
        this.hiddenElement = $(id + "_value");
        this.fileElement.observe("change", this.selectFile.bind(this));
    },
    selectFile: function(event) {
        this.hiddenElement.value = this.fileElement.getValue();
    }
};

Validation.addAllThese([ [ "validate-custom", " ", function(v, elm) {
    return elm.validate();
} ] ]);

function truncateOptions() {
    $$(".truncated").each(function(element) {
        Event.observe(element, "mouseover", function() {
            if (element.down("div.truncated_full_value")) {
                element.down("div.truncated_full_value").addClassName("show");
            }
        });
        Event.observe(element, "mouseout", function() {
            if (element.down("div.truncated_full_value")) {
                element.down("div.truncated_full_value").removeClassName("show");
            }
        });
    });
}

Event.observe(window, "load", function() {
    truncateOptions();
});

Element.addMethods({
    getInnerText: function(element) {
        element = $(element);
        if (element.innerText && !Prototype.Browser.Opera) {
            return element.innerText;
        }
        return element.innerHTML.stripScripts().unescapeHTML().replace(/[\n\r\s]+/g, " ").strip();
    }
});

function fireEvent(element, event) {
    if (document.createEvent) {
        var evt = document.createEvent("HTMLEvents");
        evt.initEvent(event, true, true);
        return element.dispatchEvent(evt);
    } else {
        var evt = document.createEventObject();
        return element.fireEvent("on" + event, evt);
    }
}

function modulo(dividend, divisor) {
    var epsilon = divisor / 1e4;
    var remainder = dividend % divisor;
    if (Math.abs(remainder - divisor) < epsilon || Math.abs(remainder) < epsilon) {
        remainder = 0;
    }
    return remainder;
}

if (typeof Range != "undefined" && !Range.prototype.createContextualFragment) {
    Range.prototype.createContextualFragment = function(html) {
        var frag = document.createDocumentFragment(), div = document.createElement("div");
        frag.appendChild(div);
        div.outerHTML = html;
        return frag;
    };
}

Varien.formCreator = Class.create();

Varien.formCreator.prototype = {
    initialize: function(url, parametersArray, method) {
        this.url = url;
        this.parametersArray = JSON.parse(parametersArray);
        this.method = method;
        this.form = "";
        this.createForm();
        this.setFormData();
    },
    createForm: function() {
        this.form = new Element("form", {
            method: this.method,
            action: this.url
        });
    },
    setFormData: function() {
        for (var key in this.parametersArray) {
            Element.insert(this.form, new Element("input", {
                name: key,
                value: this.parametersArray[key],
                type: "hidden"
            }));
        }
    }
};

function customFormSubmit(url, parametersArray, method) {
    var createdForm = new Varien.formCreator(url, parametersArray, method);
    Element.insert($$("body")[0], createdForm.form);
    createdForm.form.submit();
}

function customFormSubmitToParent(url, parametersArray, method) {
    new Ajax.Request(url, {
        method: method,
        parameters: JSON.parse(parametersArray),
        onSuccess: function(response) {
            var node = document.createElement("div");
            node.innerHTML = response.responseText;
            var responseMessage = node.getElementsByClassName("messages")[0];
            var pageTitle = window.document.body.getElementsByClassName("page-title")[0];
            pageTitle.insertAdjacentHTML("afterend", responseMessage.outerHTML);
            window.opener.focus();
            window.opener.location.href = response.transport.responseURL;
        }
    });
}

function buttonDisabler() {
    var buttons = document.querySelectorAll("button.save");
    buttons.forEach(function(button) {
        button.disabled = true;
    });
}

VarienForm = Class.create();

VarienForm.prototype = {
    initialize: function(formId, firstFieldFocus) {
        this.form = $(formId);
        if (!this.form) {
            return;
        }
        this.cache = $A();
        this.currLoader = false;
        this.currDataIndex = false;
        this.validator = new Validation(this.form);
        this.elementFocus = this.elementOnFocus.bindAsEventListener(this);
        this.elementBlur = this.elementOnBlur.bindAsEventListener(this);
        this.childLoader = this.onChangeChildLoad.bindAsEventListener(this);
        this.highlightClass = "highlight";
        this.extraChildParams = "";
        this.firstFieldFocus = firstFieldFocus || false;
        this.bindElements();
        if (this.firstFieldFocus) {
            try {
                Form.Element.focus(Form.findFirstElement(this.form));
            } catch (e) {}
        }
    },
    submit: function(url) {
        if (this.validator && this.validator.validate()) {
            this.form.submit();
        }
        return false;
    },
    bindElements: function() {
        var elements = Form.getElements(this.form);
        for (var row in elements) {
            if (elements[row].id) {
                Event.observe(elements[row], "focus", this.elementFocus);
                Event.observe(elements[row], "blur", this.elementBlur);
            }
        }
    },
    elementOnFocus: function(event) {
        var element = Event.findElement(event, "fieldset");
        if (element) {
            Element.addClassName(element, this.highlightClass);
        }
    },
    elementOnBlur: function(event) {
        var element = Event.findElement(event, "fieldset");
        if (element) {
            Element.removeClassName(element, this.highlightClass);
        }
    },
    setElementsRelation: function(parent, child, dataUrl, first) {
        if (parent = $(parent)) {
            if (!this.cache[parent.id]) {
                this.cache[parent.id] = $A();
                this.cache[parent.id]["child"] = child;
                this.cache[parent.id]["dataUrl"] = dataUrl;
                this.cache[parent.id]["data"] = $A();
                this.cache[parent.id]["first"] = first || false;
            }
            Event.observe(parent, "change", this.childLoader);
        }
    },
    onChangeChildLoad: function(event) {
        element = Event.element(event);
        this.elementChildLoad(element);
    },
    elementChildLoad: function(element, callback) {
        this.callback = callback || false;
        if (element.value) {
            this.currLoader = element.id;
            this.currDataIndex = element.value;
            if (this.cache[element.id]["data"][element.value]) {
                this.setDataToChild(this.cache[element.id]["data"][element.value]);
            } else {
                new Ajax.Request(this.cache[this.currLoader]["dataUrl"], {
                    method: "post",
                    parameters: {
                        parent: element.value
                    },
                    onComplete: this.reloadChildren.bind(this)
                });
            }
        }
    },
    reloadChildren: function(transport) {
        var data = transport.responseJSON || transport.responseText.evalJSON(true) || {};
        this.cache[this.currLoader]["data"][this.currDataIndex] = data;
        this.setDataToChild(data);
    },
    setDataToChild: function(data) {
        if (data.length) {
            var child = $(this.cache[this.currLoader]["child"]);
            if (child) {
                var html = '<select name="' + child.name + '" id="' + child.id + '" class="' + child.className + '" title="' + child.title + '" ' + this.extraChildParams + ">";
                if (this.cache[this.currLoader]["first"]) {
                    html += '<option value="">' + this.cache[this.currLoader]["first"] + "</option>";
                }
                for (var i in data) {
                    if (data[i].value) {
                        html += '<option value="' + data[i].value + '"';
                        if (child.value && (child.value == data[i].value || child.value == data[i].label)) {
                            html += " selected";
                        }
                        html += ">" + data[i].label + "</option>";
                    }
                }
                html += "</select>";
                Element.insert(child, {
                    before: html
                });
                Element.remove(child);
            }
        } else {
            var child = $(this.cache[this.currLoader]["child"]);
            if (child) {
                var html = '<input type="text" name="' + child.name + '" id="' + child.id + '" class="' + child.className + '" title="' + child.title + '" ' + this.extraChildParams + ">";
                Element.insert(child, {
                    before: html
                });
                Element.remove(child);
            }
        }
        this.bindElements();
        if (this.callback) {
            this.callback();
        }
    }
};

RegionUpdater = Class.create();

RegionUpdater.prototype = {
    initialize: function(countryEl, regionTextEl, regionSelectEl, regions, disableAction, zipEl) {
        this.countryEl = $(countryEl);
        this.regionTextEl = $(regionTextEl);
        this.regionSelectEl = $(regionSelectEl);
        this.zipEl = $(zipEl);
        this.config = regions["config"];
        delete regions.config;
        this.regions = regions;
        this.disableAction = typeof disableAction == "undefined" ? "hide" : disableAction;
        this.zipOptions = typeof zipOptions == "undefined" ? false : zipOptions;
        if (this.regionSelectEl.options.length <= 1) {
            this.update();
        }
        Event.observe(this.countryEl, "change", this.update.bind(this));
    },
    _checkRegionRequired: function() {
        var label, wildCard;
        var elements = [ this.regionTextEl, this.regionSelectEl ];
        var that = this;
        if (typeof this.config == "undefined") {
            return;
        }
        var regionRequired = this.config.regions_required.indexOf(this.countryEl.value) >= 0;
        elements.each(function(currentElement) {
            Validation.reset(currentElement);
            label = $$('label[for="' + currentElement.id + '"]')[0];
            if (label) {
                wildCard = label.down("em") || label.down("span.required");
                if (!that.config.show_all_regions) {
                    if (regionRequired) {
                        label.up().show();
                    } else {
                        label.up().hide();
                    }
                }
            }
            if (label && wildCard) {
                if (!regionRequired) {
                    wildCard.hide();
                    if (label.hasClassName("required")) {
                        label.removeClassName("required");
                    }
                } else if (regionRequired) {
                    wildCard.show();
                    if (!label.hasClassName("required")) {
                        label.addClassName("required");
                    }
                }
            }
            if (!regionRequired) {
                if (currentElement.hasClassName("required-entry")) {
                    currentElement.removeClassName("required-entry");
                }
                if ("select" == currentElement.tagName.toLowerCase() && currentElement.hasClassName("validate-select")) {
                    currentElement.removeClassName("validate-select");
                }
            } else {
                if (!currentElement.hasClassName("required-entry")) {
                    currentElement.addClassName("required-entry");
                }
                if ("select" == currentElement.tagName.toLowerCase() && !currentElement.hasClassName("validate-select")) {
                    currentElement.addClassName("validate-select");
                }
            }
        });
    },
    update: function() {
        if (this.regions[this.countryEl.value]) {
            var i, option, region, def;
            def = this.regionSelectEl.getAttribute("defaultValue");
            if (this.regionTextEl) {
                if (!def) {
                    def = this.regionTextEl.value.toLowerCase();
                }
                this.regionTextEl.value = "";
            }
            this.regionSelectEl.options.length = 1;
            for (regionId in this.regions[this.countryEl.value]) {
                region = this.regions[this.countryEl.value][regionId];
                option = document.createElement("OPTION");
                option.value = regionId;
                option.text = region.name.stripTags();
                option.title = region.name;
                if (this.regionSelectEl.options.add) {
                    this.regionSelectEl.options.add(option);
                } else {
                    this.regionSelectEl.appendChild(option);
                }
                if (regionId == def || region.name && region.name.toLowerCase() == def || region.name && region.code.toLowerCase() == def) {
                    this.regionSelectEl.value = regionId;
                }
            }
            this.sortSelect();
            if (this.disableAction == "hide") {
                if (this.regionTextEl) {
                    this.regionTextEl.style.display = "none";
                }
                this.regionSelectEl.style.display = "";
            } else if (this.disableAction == "disable") {
                if (this.regionTextEl) {
                    this.regionTextEl.disabled = true;
                }
                this.regionSelectEl.disabled = false;
            }
            this.setMarkDisplay(this.regionSelectEl, true);
        } else {
            this.regionSelectEl.options.length = 1;
            this.sortSelect();
            if (this.disableAction == "hide") {
                if (this.regionTextEl) {
                    this.regionTextEl.style.display = "";
                }
                this.regionSelectEl.style.display = "none";
                Validation.reset(this.regionSelectEl);
            } else if (this.disableAction == "disable") {
                if (this.regionTextEl) {
                    this.regionTextEl.disabled = false;
                }
                this.regionSelectEl.disabled = true;
            } else if (this.disableAction == "nullify") {
                this.regionSelectEl.options.length = 1;
                this.regionSelectEl.value = "";
                this.regionSelectEl.selectedIndex = 0;
                this.lastCountryId = "";
            }
            this.setMarkDisplay(this.regionSelectEl, false);
        }
        this._checkRegionRequired();
        var zipUpdater = new ZipUpdater(this.countryEl.value, this.zipEl);
        zipUpdater.update();
    },
    setMarkDisplay: function(elem, display) {
        elem = $(elem);
        var labelElement = elem.up(0).down("label > span.required") || elem.up(1).down("label > span.required") || elem.up(0).down("label.required > em") || elem.up(1).down("label.required > em");
        if (labelElement) {
            inputElement = labelElement.up().next("input");
            if (display) {
                labelElement.show();
                if (inputElement) {
                    inputElement.addClassName("required-entry");
                }
            } else {
                labelElement.hide();
                if (inputElement) {
                    inputElement.removeClassName("required-entry");
                }
            }
        }
    },
    sortSelect: function() {
        var elem = this.regionSelectEl;
        var tmpArray = new Array();
        var currentVal = $(elem).value;
        for (var i = 0; i < $(elem).options.length; i++) {
            if (i == 0) {
                continue;
            }
            tmpArray[i - 1] = new Array();
            tmpArray[i - 1][0] = $(elem).options[i].text;
            tmpArray[i - 1][1] = $(elem).options[i].value;
        }
        tmpArray.sort();
        for (var i = 1; i <= tmpArray.length; i++) {
            var op = new Option(tmpArray[i - 1][0], tmpArray[i - 1][1]);
            $(elem).options[i] = op;
        }
        $(elem).value = currentVal;
        return;
    }
};

ZipUpdater = Class.create();

ZipUpdater.prototype = {
    initialize: function(country, zipElement) {
        this.country = country;
        this.zipElement = $(zipElement);
    },
    update: function() {
        if (typeof optionalZipCountries == "undefined") {
            return false;
        }
        if (this.zipElement != undefined) {
            Validation.reset(this.zipElement);
            this._setPostcodeOptional();
        } else {
            Event.observe(window, "load", this._setPostcodeOptional.bind(this));
        }
    },
    _setPostcodeOptional: function() {
        this.zipElement = $(this.zipElement);
        if (this.zipElement == undefined) {
            return false;
        }
        var label = $$('label[for="' + this.zipElement.id + '"]')[0];
        if (label != undefined) {
            var wildCard = label.down("em") || label.down("span.required");
        }
        if (optionalZipCountries.indexOf(this.country) != -1) {
            while (this.zipElement.hasClassName("required-entry")) {
                this.zipElement.removeClassName("required-entry");
            }
            if (wildCard != undefined) {
                wildCard.hide();
            }
        } else {
            this.zipElement.addClassName("required-entry");
            if (wildCard != undefined) {
                wildCard.show();
            }
        }
    }
};

if (typeof Product == "undefined") {
    var Product = {};
}

Product.Zoom = Class.create();

Product.Zoom.prototype = {
    initialize: function(imageEl, trackEl, handleEl, zoomInEl, zoomOutEl, hintEl) {
        this.containerEl = $(imageEl).parentNode;
        this.imageEl = $(imageEl);
        this.handleEl = $(handleEl);
        this.trackEl = $(trackEl);
        this.hintEl = $(hintEl);
        this.containerDim = Element.getDimensions(this.containerEl);
        this.imageDim = Element.getDimensions(this.imageEl);
        this.imageDim.ratio = this.imageDim.width / this.imageDim.height;
        this.floorZoom = 1;
        if (this.imageDim.width > this.imageDim.height) {
            this.ceilingZoom = this.imageDim.width / this.containerDim.width;
        } else {
            this.ceilingZoom = this.imageDim.height / this.containerDim.height;
        }
        if (this.imageDim.width <= this.containerDim.width && this.imageDim.height <= this.containerDim.height) {
            this.trackEl.up().hide();
            this.hintEl.hide();
            this.containerEl.removeClassName("product-image-zoom");
            return;
        }
        this.imageX = 0;
        this.imageY = 0;
        this.imageZoom = 1;
        this.sliderSpeed = 0;
        this.sliderAccel = 0;
        this.zoomBtnPressed = false;
        this.showFull = false;
        this.selects = document.getElementsByTagName("select");
        this.draggable = new Draggable(imageEl, {
            starteffect: false,
            reverteffect: false,
            endeffect: false,
            snap: this.contain.bind(this)
        });
        this.slider = new Control.Slider(handleEl, trackEl, {
            axis: "horizontal",
            minimum: 0,
            maximum: Element.getDimensions(this.trackEl).width,
            alignX: 0,
            increment: 1,
            sliderValue: 0,
            onSlide: this.scale.bind(this),
            onChange: this.scale.bind(this)
        });
        this.scale(0);
        Event.observe(this.imageEl, "dblclick", this.toggleFull.bind(this));
        Event.observe($(zoomInEl), "mousedown", this.startZoomIn.bind(this));
        Event.observe($(zoomInEl), "mouseup", this.stopZooming.bind(this));
        Event.observe($(zoomInEl), "mouseout", this.stopZooming.bind(this));
        Event.observe($(zoomOutEl), "mousedown", this.startZoomOut.bind(this));
        Event.observe($(zoomOutEl), "mouseup", this.stopZooming.bind(this));
        Event.observe($(zoomOutEl), "mouseout", this.stopZooming.bind(this));
    },
    toggleFull: function() {
        this.showFull = !this.showFull;
        if (typeof document.body.style.maxHeight == "undefined") {
            for (i = 0; i < this.selects.length; i++) {
                this.selects[i].style.visibility = this.showFull ? "hidden" : "visible";
            }
        }
        val_scale = !this.showFull ? this.slider.value : 1;
        this.scale(val_scale);
        this.trackEl.style.visibility = this.showFull ? "hidden" : "visible";
        this.containerEl.style.overflow = this.showFull ? "visible" : "hidden";
        this.containerEl.style.zIndex = this.showFull ? "1000" : "9";
        return this;
    },
    scale: function(v) {
        var centerX = (this.containerDim.width * (1 - this.imageZoom) / 2 - this.imageX) / this.imageZoom;
        var centerY = (this.containerDim.height * (1 - this.imageZoom) / 2 - this.imageY) / this.imageZoom;
        var overSize = this.imageDim.width > this.containerDim.width || this.imageDim.height > this.containerDim.height;
        this.imageZoom = this.floorZoom + v * (this.ceilingZoom - this.floorZoom);
        if (overSize) {
            if (this.imageDim.width > this.imageDim.height) {
                this.imageEl.style.width = this.imageZoom * this.containerDim.width + "px";
            } else {
                this.imageEl.style.height = this.imageZoom * this.containerDim.height + "px";
            }
            if (this.containerDim.ratio) {
                if (this.imageDim.width > this.imageDim.height) {
                    this.imageEl.style.height = this.imageZoom * this.containerDim.width * this.containerDim.ratio + "px";
                } else {
                    this.imageEl.style.width = this.imageZoom * this.containerDim.height * this.containerDim.ratio + "px";
                }
            }
        } else {
            this.slider.setDisabled();
        }
        this.imageX = this.containerDim.width * (1 - this.imageZoom) / 2 - centerX * this.imageZoom;
        this.imageY = this.containerDim.height * (1 - this.imageZoom) / 2 - centerY * this.imageZoom;
        this.contain(this.imageX, this.imageY, this.draggable);
        return true;
    },
    startZoomIn: function() {
        if (!this.slider.disabled) {
            this.zoomBtnPressed = true;
            this.sliderAccel = .002;
            this.periodicalZoom();
            this.zoomer = new PeriodicalExecuter(this.periodicalZoom.bind(this), .05);
        }
        return this;
    },
    startZoomOut: function() {
        if (!this.slider.disabled) {
            this.zoomBtnPressed = true;
            this.sliderAccel = -.002;
            this.periodicalZoom();
            this.zoomer = new PeriodicalExecuter(this.periodicalZoom.bind(this), .05);
        }
        return this;
    },
    stopZooming: function() {
        if (!this.zoomer || this.sliderSpeed == 0) {
            return;
        }
        this.zoomBtnPressed = false;
        this.sliderAccel = 0;
    },
    periodicalZoom: function() {
        if (!this.zoomer) {
            return this;
        }
        if (this.zoomBtnPressed) {
            this.sliderSpeed += this.sliderAccel;
        } else {
            this.sliderSpeed /= 1.5;
            if (Math.abs(this.sliderSpeed) < .001) {
                this.sliderSpeed = 0;
                this.zoomer.stop();
                this.zoomer = null;
            }
        }
        this.slider.value += this.sliderSpeed;
        this.slider.setValue(this.slider.value);
        this.scale(this.slider.value);
        return this;
    },
    contain: function(x, y, draggable) {
        var dim = Element.getDimensions(draggable.element);
        var xMin = 0, xMax = this.containerDim.width - dim.width;
        var yMin = 0, yMax = this.containerDim.height - dim.height;
        x = x > xMin ? xMin : x;
        x = x < xMax ? xMax : x;
        y = y > yMin ? yMin : y;
        y = y < yMax ? yMax : y;
        if (this.containerDim.width > dim.width) {
            x = this.containerDim.width / 2 - dim.width / 2;
        }
        if (this.containerDim.height > dim.height) {
            y = this.containerDim.height / 2 - dim.height / 2;
        }
        this.imageX = x;
        this.imageY = y;
        this.imageEl.style.left = this.imageX + "px";
        this.imageEl.style.top = this.imageY + "px";
        return [ x, y ];
    }
};

Product.Config = Class.create();

Product.Config.prototype = {
    initialize: function(config) {
        this.config = config;
        this.taxConfig = this.config.taxConfig;
        this.settings = $$(".super-attribute-select");
        this.state = new Hash();
        this.priceTemplate = new Template(this.config.template);
        this.prices = config.prices;
        this.settings.each(function(element) {
            Event.observe(element, "change", this.configure.bind(this));
        }.bind(this));
        this.settings.each(function(element) {
            var attributeId = element.id.replace(/[a-z]*/, "");
            if (attributeId && this.config.attributes[attributeId]) {
                element.config = this.config.attributes[attributeId];
                element.attributeId = attributeId;
                this.state[attributeId] = false;
            }
        }.bind(this));
        var childSettings = [];
        for (var i = this.settings.length - 1; i >= 0; i--) {
            var prevSetting = this.settings[i - 1] ? this.settings[i - 1] : false;
            var nextSetting = this.settings[i + 1] ? this.settings[i + 1] : false;
            if (i == 0) {
                this.fillSelect(this.settings[i]);
            } else {
                this.settings[i].disabled = true;
            }
            $(this.settings[i]).childSettings = childSettings.clone();
            $(this.settings[i]).prevSetting = prevSetting;
            $(this.settings[i]).nextSetting = nextSetting;
            childSettings.push(this.settings[i]);
        }
        if (config.defaultValues) {
            this.values = config.defaultValues;
        }
        var separatorIndex = window.location.href.indexOf("#");
        if (separatorIndex != -1) {
            var paramsStr = window.location.href.substr(separatorIndex + 1);
            var urlValues = paramsStr.toQueryParams();
            if (!this.values) {
                this.values = {};
            }
            for (var i in urlValues) {
                this.values[i] = urlValues[i];
            }
        }
        this.configureForValues();
        document.observe("dom:loaded", this.configureForValues.bind(this));
    },
    configureForValues: function() {
        if (this.values) {
            this.settings.each(function(element) {
                var attributeId = element.attributeId;
                element.value = typeof this.values[attributeId] == "undefined" ? "" : this.values[attributeId];
                this.configureElement(element);
            }.bind(this));
        }
    },
    configure: function(event) {
        var element = Event.element(event);
        this.configureElement(element);
    },
    configureElement: function(element) {
        this.reloadOptionLabels(element);
        if (element.value) {
            this.state[element.config.id] = element.value;
            if (element.nextSetting) {
                element.nextSetting.disabled = false;
                this.fillSelect(element.nextSetting);
                this.resetChildren(element.nextSetting);
            }
        } else {
            this.resetChildren(element);
        }
        this.reloadPrice();
    },
    reloadOptionLabels: function(element) {
        var selectedPrice;
        if (element.options[element.selectedIndex].config) {
            selectedPrice = parseFloat(element.options[element.selectedIndex].config.price);
        } else {
            selectedPrice = 0;
        }
        for (var i = 0; i < element.options.length; i++) {
            if (element.options[i].config) {
                element.options[i].text = this.getOptionLabel(element.options[i].config, element.options[i].config.price - selectedPrice);
            }
        }
    },
    resetChildren: function(element) {
        if (element.childSettings) {
            for (var i = 0; i < element.childSettings.length; i++) {
                element.childSettings[i].selectedIndex = 0;
                element.childSettings[i].disabled = true;
                if (element.config) {
                    this.state[element.config.id] = false;
                }
            }
        }
    },
    fillSelect: function(element) {
        var attributeId = element.id.replace(/[a-z]*/, "");
        var options = this.getAttributeOptions(attributeId);
        this.clearSelect(element);
        element.options[0] = new Option("", "");
        element.options[0].innerHTML = this.config.chooseText;
        var prevConfig = false;
        if (element.prevSetting) {
            prevConfig = element.prevSetting.options[element.prevSetting.selectedIndex];
        }
        if (options) {
            var index = 1;
            for (var i = 0; i < options.length; i++) {
                var allowedProducts = [];
                if (prevConfig) {
                    for (var j = 0; j < options[i].products.length; j++) {
                        if (prevConfig.config.allowedProducts && prevConfig.config.allowedProducts.indexOf(options[i].products[j]) > -1) {
                            allowedProducts.push(options[i].products[j]);
                        }
                    }
                } else {
                    allowedProducts = options[i].products.clone();
                }
                if (allowedProducts.size() > 0) {
                    options[i].allowedProducts = allowedProducts;
                    element.options[index] = new Option(this.getOptionLabel(options[i], options[i].price), options[i].id);
                    element.options[index].config = options[i];
                    index++;
                }
            }
        }
    },
    getOptionLabel: function(option, price) {
        var price = parseFloat(price);
        if (this.taxConfig.includeTax) {
            var tax = price / (100 + this.taxConfig.defaultTax) * this.taxConfig.defaultTax;
            var excl = price - tax;
            var incl = excl * (1 + this.taxConfig.currentTax / 100);
        } else {
            var tax = price * (this.taxConfig.currentTax / 100);
            var excl = price;
            var incl = excl + tax;
        }
        if (this.taxConfig.showIncludeTax || this.taxConfig.showBothPrices) {
            price = incl;
        } else {
            price = excl;
        }
        var str = option.label;
        if (price) {
            if (this.taxConfig.showBothPrices) {
                str += " " + this.formatPrice(excl, true) + " (" + this.formatPrice(price, true) + " " + this.taxConfig.inclTaxTitle + ")";
            } else {
                str += " " + this.formatPrice(price, true);
            }
        }
        return str;
    },
    formatPrice: function(price, showSign) {
        var str = "";
        price = parseFloat(price);
        if (showSign) {
            if (price < 0) {
                str += "-";
                price = -price;
            } else {
                str += "+";
            }
        }
        var roundedPrice = (Math.round(price * 100) / 100).toString();
        if (this.prices && this.prices[roundedPrice]) {
            str += this.prices[roundedPrice];
        } else {
            str += this.priceTemplate.evaluate({
                price: price.toFixed(2)
            });
        }
        return str;
    },
    clearSelect: function(element) {
        for (var i = element.options.length - 1; i >= 0; i--) {
            element.remove(i);
        }
    },
    getAttributeOptions: function(attributeId) {
        if (this.config.attributes[attributeId]) {
            return this.config.attributes[attributeId].options;
        }
    },
    reloadPrice: function() {
        var price = 0;
        var oldPrice = 0;
        for (var i = this.settings.length - 1; i >= 0; i--) {
            var selected = this.settings[i].options[this.settings[i].selectedIndex];
            if (selected.config) {
                price += parseFloat(selected.config.price);
                oldPrice += parseFloat(selected.config.oldPrice);
            }
        }
        optionsPrice.changePrice("config", {
            price: price,
            oldPrice: oldPrice
        });
        optionsPrice.reload();
        return price;
    },
    reloadOldPrice: function() {
        if ($("old-price-" + this.config.productId)) {
            var price = parseFloat(this.config.oldPrice);
            for (var i = this.settings.length - 1; i >= 0; i--) {
                var selected = this.settings[i].options[this.settings[i].selectedIndex];
                if (selected.config) {
                    var parsedOldPrice = parseFloat(selected.config.oldPrice);
                    price += isNaN(parsedOldPrice) ? 0 : parsedOldPrice;
                }
            }
            if (price < 0) price = 0;
            price = this.formatPrice(price);
            if ($("old-price-" + this.config.productId)) {
                $("old-price-" + this.config.productId).innerHTML = price;
            }
        }
    }
};

Product.Super = {};

Product.Super.Configurable = Class.create();

Product.Super.Configurable.prototype = {
    initialize: function(container, observeCss, updateUrl, updatePriceUrl, priceContainerId) {
        this.container = $(container);
        this.observeCss = observeCss;
        this.updateUrl = updateUrl;
        this.updatePriceUrl = updatePriceUrl;
        this.priceContainerId = priceContainerId;
        this.registerObservers();
    },
    registerObservers: function() {
        var elements = this.container.getElementsByClassName(this.observeCss);
        elements.each(function(element) {
            Event.observe(element, "change", this.update.bindAsEventListener(this));
        }.bind(this));
        return this;
    },
    update: function(event) {
        var elements = this.container.getElementsByClassName(this.observeCss);
        var parameters = Form.serializeElements(elements, true);
        new Ajax.Updater(this.container, this.updateUrl + "?ajax=1", {
            parameters: parameters,
            onComplete: this.registerObservers.bind(this)
        });
        var priceContainer = $(this.priceContainerId);
        if (priceContainer) {
            new Ajax.Updater(priceContainer, this.updatePriceUrl + "?ajax=1", {
                parameters: parameters
            });
        }
    }
};

if (typeof Product == "undefined") {
    var Product = {};
}

Product.Config = Class.create();

Product.Config.prototype = {
    initialize: function(config) {
        this.config = config;
        this.taxConfig = this.config.taxConfig;
        if (config.containerId) {
            this.settings = $$("#" + config.containerId + " " + ".super-attribute-select");
        } else {
            this.settings = $$(".super-attribute-select");
        }
        this.state = new Hash();
        this.priceTemplate = new Template(this.config.template);
        this.prices = config.prices;
        if (config.defaultValues) {
            this.values = config.defaultValues;
        }
        var separatorIndex = window.location.href.indexOf("#");
        if (separatorIndex != -1) {
            var paramsStr = window.location.href.substr(separatorIndex + 1);
            var urlValues = paramsStr.toQueryParams();
            if (!this.values) {
                this.values = {};
            }
            for (var i in urlValues) {
                this.values[i] = urlValues[i];
            }
        }
        if (config.inputsInitialized) {
            this.values = {};
            this.settings.each(function(element) {
                if (element.value) {
                    var attributeId = element.id.replace(/[a-z]*/, "");
                    this.values[attributeId] = element.value;
                }
            }.bind(this));
        }
        this.settings.each(function(element) {
            Event.observe(element, "change", this.configure.bind(this));
        }.bind(this));
        this.settings.each(function(element) {
            var attributeId = element.id.replace(/[a-z]*/, "");
            if (attributeId && this.config.attributes[attributeId]) {
                element.config = this.config.attributes[attributeId];
                element.attributeId = attributeId;
                this.state[attributeId] = false;
            }
        }.bind(this));
        var childSettings = [];
        for (var i = this.settings.length - 1; i >= 0; i--) {
            var prevSetting = this.settings[i - 1] ? this.settings[i - 1] : false;
            var nextSetting = this.settings[i + 1] ? this.settings[i + 1] : false;
            if (i == 0) {
                this.fillSelect(this.settings[i]);
            } else {
                this.settings[i].disabled = true;
            }
            $(this.settings[i]).childSettings = childSettings.clone();
            $(this.settings[i]).prevSetting = prevSetting;
            $(this.settings[i]).nextSetting = nextSetting;
            childSettings.push(this.settings[i]);
        }
        this.configureForValues();
        document.observe("dom:loaded", this.configureForValues.bind(this));
    },
    configureForValues: function() {
        if (this.values) {
            this.settings.each(function(element) {
                var attributeId = element.attributeId;
                element.value = typeof this.values[attributeId] == "undefined" ? "" : this.values[attributeId];
                this.configureElement(element);
            }.bind(this));
        }
    },
    configure: function(event) {
        var element = Event.element(event);
        this.configureElement(element);
    },
    configureElement: function(element) {
        this.reloadOptionLabels(element);
        if (element.value) {
            this.state[element.config.id] = element.value;
            if (element.nextSetting) {
                element.nextSetting.disabled = false;
                this.fillSelect(element.nextSetting);
                this.resetChildren(element.nextSetting);
            }
        } else {
            this.resetChildren(element);
        }
        this.reloadPrice();
    },
    reloadOptionLabels: function(element) {
        var selectedPrice;
        if (element.options[element.selectedIndex].config && !this.config.stablePrices) {
            selectedPrice = parseFloat(element.options[element.selectedIndex].config.price);
        } else {
            selectedPrice = 0;
        }
        for (var i = 0; i < element.options.length; i++) {
            if (element.options[i].config) {
                element.options[i].text = this.getOptionLabel(element.options[i].config, element.options[i].config.price - selectedPrice);
            }
        }
    },
    resetChildren: function(element) {
        if (element.childSettings) {
            for (var i = 0; i < element.childSettings.length; i++) {
                element.childSettings[i].selectedIndex = 0;
                element.childSettings[i].disabled = true;
                if (element.config) {
                    this.state[element.config.id] = false;
                }
            }
        }
    },
    fillSelect: function(element) {
        var attributeId = element.id.replace(/[a-z]*/, "");
        var options = this.getAttributeOptions(attributeId);
        this.clearSelect(element);
        element.options[0] = new Option("", "");
        element.options[0].innerHTML = this.config.chooseText;
        var prevConfig = false;
        if (element.prevSetting) {
            prevConfig = element.prevSetting.options[element.prevSetting.selectedIndex];
        }
        if (options) {
            var index = 1;
            for (var i = 0; i < options.length; i++) {
                var allowedProducts = [];
                if (prevConfig) {
                    for (var j = 0; j < options[i].products.length; j++) {
                        if (prevConfig.config.allowedProducts && prevConfig.config.allowedProducts.indexOf(options[i].products[j]) > -1) {
                            allowedProducts.push(options[i].products[j]);
                        }
                    }
                } else {
                    allowedProducts = options[i].products.clone();
                }
                if (allowedProducts.size() > 0) {
                    options[i].allowedProducts = allowedProducts;
                    element.options[index] = new Option(this.getOptionLabel(options[i], options[i].price), options[i].id);
                    if (typeof options[i].price != "undefined") {
                        element.options[index].setAttribute("price", options[i].price);
                    }
                    element.options[index].config = options[i];
                    index++;
                }
            }
        }
    },
    getOptionLabel: function(option, price) {
        var price = parseFloat(price);
        if (this.taxConfig.includeTax) {
            var tax = price / (100 + this.taxConfig.defaultTax) * this.taxConfig.defaultTax;
            var excl = price - tax;
            var incl = excl * (1 + this.taxConfig.currentTax / 100);
        } else {
            var tax = price * (this.taxConfig.currentTax / 100);
            var excl = price;
            var incl = excl + tax;
        }
        if (this.taxConfig.showIncludeTax || this.taxConfig.showBothPrices) {
            price = incl;
        } else {
            price = excl;
        }
        var str = option.label;
        if (price) {
            if (this.taxConfig.showBothPrices) {
                str += " " + this.formatPrice(excl, true) + " (" + this.formatPrice(price, true) + " " + this.taxConfig.inclTaxTitle + ")";
            } else {
                str += " " + this.formatPrice(price, true);
            }
        }
        return str;
    },
    formatPrice: function(price, showSign) {
        var str = "";
        price = parseFloat(price);
        if (showSign) {
            if (price < 0) {
                str += "-";
                price = -price;
            } else {
                str += "+";
            }
        }
        var roundedPrice = (Math.round(price * 100) / 100).toString();
        if (this.prices && this.prices[roundedPrice]) {
            str += this.prices[roundedPrice];
        } else {
            str += this.priceTemplate.evaluate({
                price: price.toFixed(2)
            });
        }
        return str;
    },
    clearSelect: function(element) {
        for (var i = element.options.length - 1; i >= 0; i--) {
            element.remove(i);
        }
    },
    getAttributeOptions: function(attributeId) {
        if (this.config.attributes[attributeId]) {
            return this.config.attributes[attributeId].options;
        }
    },
    reloadPrice: function() {
        if (this.config.disablePriceReload) {
            return;
        }
        var price = 0;
        var oldPrice = 0;
        for (var i = this.settings.length - 1; i >= 0; i--) {
            var selected = this.settings[i].options[this.settings[i].selectedIndex];
            if (selected.config) {
                price += parseFloat(selected.config.price);
                oldPrice += parseFloat(selected.config.oldPrice);
            }
        }
        optionsPrice.changePrice("config", {
            price: price,
            oldPrice: oldPrice
        });
        optionsPrice.reload();
        return price;
    },
    reloadOldPrice: function() {
        if (this.config.disablePriceReload) {
            return;
        }
        if ($("old-price-" + this.config.productId)) {
            var price = parseFloat(this.config.oldPrice);
            for (var i = this.settings.length - 1; i >= 0; i--) {
                var selected = this.settings[i].options[this.settings[i].selectedIndex];
                if (selected.config) {
                    price += parseFloat(selected.config.price);
                }
            }
            if (price < 0) price = 0;
            price = this.formatPrice(price);
            if ($("old-price-" + this.config.productId)) {
                $("old-price-" + this.config.productId).innerHTML = price;
            }
        }
    }
};

Calendar = function(firstDayOfWeek, dateStr, onSelected, onClose) {
    this.activeDiv = null;
    this.currentDateEl = null;
    this.getDateStatus = null;
    this.getDateToolTip = null;
    this.getDateText = null;
    this.timeout = null;
    this.onSelected = onSelected || null;
    this.onClose = onClose || null;
    this.dragging = false;
    this.hidden = false;
    this.minYear = 1970;
    this.maxYear = 2050;
    this.dateFormat = Calendar._TT["DEF_DATE_FORMAT"];
    this.ttDateFormat = Calendar._TT["TT_DATE_FORMAT"];
    this.isPopup = true;
    this.weekNumbers = true;
    this.firstDayOfWeek = typeof firstDayOfWeek == "number" ? firstDayOfWeek : Calendar._FD;
    this.showsOtherMonths = false;
    this.dateStr = dateStr;
    this.ar_days = null;
    this.showsTime = false;
    this.time24 = true;
    this.yearStep = 2;
    this.hiliteToday = true;
    this.multiple = null;
    this.table = null;
    this.element = null;
    this.tbody = null;
    this.firstdayname = null;
    this.monthsCombo = null;
    this.yearsCombo = null;
    this.hilitedMonth = null;
    this.activeMonth = null;
    this.hilitedYear = null;
    this.activeYear = null;
    this.dateClicked = false;
    if (typeof Calendar._SDN == "undefined") {
        if (typeof Calendar._SDN_len == "undefined") Calendar._SDN_len = 3;
        var ar = new Array();
        for (var i = 8; i > 0; ) {
            ar[--i] = Calendar._DN[i].substr(0, Calendar._SDN_len);
        }
        Calendar._SDN = ar;
        if (typeof Calendar._SMN_len == "undefined") Calendar._SMN_len = 3;
        ar = new Array();
        for (var i = 12; i > 0; ) {
            ar[--i] = Calendar._MN[i].substr(0, Calendar._SMN_len);
        }
        Calendar._SMN = ar;
    }
};

Calendar._C = null;

Calendar.is_ie = /msie/i.test(navigator.userAgent) && !/opera/i.test(navigator.userAgent);

Calendar.is_ie5 = Calendar.is_ie && /msie 5\.0/i.test(navigator.userAgent);

Calendar.is_opera = /opera/i.test(navigator.userAgent);

Calendar.is_khtml = /Konqueror|Safari|KHTML/i.test(navigator.userAgent);

Calendar.is_gecko = navigator.userAgent.match(/gecko/i);

Calendar.getStyle = function(element, style) {
    if (element.currentStyle) {
        var y = element.currentStyle[style];
    } else if (window.getComputedStyle) {
        var y = document.defaultView.getComputedStyle(element, null).getPropertyValue(style);
    }
    return y;
};

Calendar.getAbsolutePos = function(element) {
    var res = new Object();
    res.x = 0;
    res.y = 0;
    do {
        res.x += element.offsetLeft || 0;
        res.y += element.offsetTop || 0;
        element = element.offsetParent;
        if (element) {
            if (element.tagName.toUpperCase() == "BODY") break;
            var p = Calendar.getStyle(element, "position");
            if (p !== "static" && p !== "relative") break;
        }
    } while (element);
    return res;
    if (element !== null) {
        res.x = element.offsetLeft;
        res.y = element.offsetTop;
        var offsetParent = element.offsetParent;
        var parentNode = element.parentNode;
        while (offsetParent !== null) {
            res.x += offsetParent.offsetLeft;
            res.y += offsetParent.offsetTop;
            if (offsetParent != document.body && offsetParent != document.documentElement) {
                res.x -= offsetParent.scrollLeft;
                res.y -= offsetParent.scrollTop;
            }
            if (Calendar.is_gecko) {
                while (offsetParent != parentNode && parentNode !== null) {
                    res.x -= parentNode.scrollLeft;
                    res.y -= parentNode.scrollTop;
                    parentNode = parentNode.parentNode;
                }
            }
            parentNode = offsetParent.parentNode;
            offsetParent = offsetParent.offsetParent;
        }
    }
    return res;
};

Calendar.isRelated = function(el, evt) {
    var related = evt.relatedTarget;
    if (!related) {
        var type = evt.type;
        if (type == "mouseover") {
            related = evt.fromElement;
        } else if (type == "mouseout") {
            related = evt.toElement;
        }
    }
    while (related) {
        if (related == el) {
            return true;
        }
        related = related.parentNode;
    }
    return false;
};

Calendar.removeClass = function(el, className) {
    if (!(el && el.className)) {
        return;
    }
    var cls = el.className.split(" ");
    var ar = new Array();
    for (var i = cls.length; i > 0; ) {
        if (cls[--i] != className) {
            ar[ar.length] = cls[i];
        }
    }
    el.className = ar.join(" ");
};

Calendar.addClass = function(el, className) {
    Calendar.removeClass(el, className);
    el.className += " " + className;
};

Calendar.getElement = function(ev) {
    var f = Calendar.is_ie ? window.event.srcElement : ev.currentTarget;
    while (f.nodeType != 1 || /^div$/i.test(f.tagName)) f = f.parentNode;
    return f;
};

Calendar.getTargetElement = function(ev) {
    var f = Calendar.is_ie ? window.event.srcElement : ev.target;
    while (f.nodeType != 1) f = f.parentNode;
    return f;
};

Calendar.stopEvent = function(ev) {
    ev || (ev = window.event);
    if (Calendar.is_ie) {
        ev.cancelBubble = true;
        ev.returnValue = false;
    } else {
        ev.preventDefault();
        ev.stopPropagation();
    }
    return false;
};

Calendar.addEvent = function(el, evname, func) {
    if (el.attachEvent) {
        el.attachEvent("on" + evname, func);
    } else if (el.addEventListener) {
        el.addEventListener(evname, func, true);
    } else {
        el["on" + evname] = func;
    }
};

Calendar.removeEvent = function(el, evname, func) {
    if (el.detachEvent) {
        el.detachEvent("on" + evname, func);
    } else if (el.removeEventListener) {
        el.removeEventListener(evname, func, true);
    } else {
        el["on" + evname] = null;
    }
};

Calendar.createElement = function(type, parent) {
    var el = null;
    if (document.createElementNS) {
        el = document.createElementNS("http://www.w3.org/1999/xhtml", type);
    } else {
        el = document.createElement(type);
    }
    if (typeof parent != "undefined") {
        parent.appendChild(el);
    }
    return el;
};

Calendar._add_evs = function(el) {
    with (Calendar) {
        addEvent(el, "mouseover", dayMouseOver);
        addEvent(el, "mousedown", dayMouseDown);
        addEvent(el, "mouseout", dayMouseOut);
        if (is_ie) {
            addEvent(el, "dblclick", dayMouseDblClick);
            el.setAttribute("unselectable", true);
        }
    }
};

Calendar.findMonth = function(el) {
    if (typeof el.month != "undefined") {
        return el;
    } else if (typeof el.parentNode.month != "undefined") {
        return el.parentNode;
    }
    return null;
};

Calendar.findYear = function(el) {
    if (typeof el.year != "undefined") {
        return el;
    } else if (typeof el.parentNode.year != "undefined") {
        return el.parentNode;
    }
    return null;
};

Calendar.showMonthsCombo = function() {
    var cal = Calendar._C;
    if (!cal) {
        return false;
    }
    var cal = cal;
    var cd = cal.activeDiv;
    var mc = cal.monthsCombo;
    if (cal.hilitedMonth) {
        Calendar.removeClass(cal.hilitedMonth, "hilite");
    }
    if (cal.activeMonth) {
        Calendar.removeClass(cal.activeMonth, "active");
    }
    var mon = cal.monthsCombo.getElementsByTagName("div")[cal.date.getMonth()];
    Calendar.addClass(mon, "active");
    cal.activeMonth = mon;
    var s = mc.style;
    s.display = "block";
    if (cd.navtype < 0) s.left = cd.offsetLeft + "px"; else {
        var mcw = mc.offsetWidth;
        if (typeof mcw == "undefined") mcw = 50;
        s.left = cd.offsetLeft + cd.offsetWidth - mcw + "px";
    }
    s.top = cd.offsetTop + cd.offsetHeight + "px";
};

Calendar.showYearsCombo = function(fwd) {
    var cal = Calendar._C;
    if (!cal) {
        return false;
    }
    var cal = cal;
    var cd = cal.activeDiv;
    var yc = cal.yearsCombo;
    if (cal.hilitedYear) {
        Calendar.removeClass(cal.hilitedYear, "hilite");
    }
    if (cal.activeYear) {
        Calendar.removeClass(cal.activeYear, "active");
    }
    cal.activeYear = null;
    var Y = cal.date.getFullYear() + (fwd ? 1 : -1);
    var yr = yc.firstChild;
    var show = false;
    for (var i = 12; i > 0; --i) {
        if (Y >= cal.minYear && Y <= cal.maxYear) {
            yr.innerHTML = Y;
            yr.year = Y;
            yr.style.display = "block";
            show = true;
        } else {
            yr.style.display = "none";
        }
        yr = yr.nextSibling;
        Y += fwd ? cal.yearStep : -cal.yearStep;
    }
    if (show) {
        var s = yc.style;
        s.display = "block";
        if (cd.navtype < 0) s.left = cd.offsetLeft + "px"; else {
            var ycw = yc.offsetWidth;
            if (typeof ycw == "undefined") ycw = 50;
            s.left = cd.offsetLeft + cd.offsetWidth - ycw + "px";
        }
        s.top = cd.offsetTop + cd.offsetHeight + "px";
    }
};

Calendar.tableMouseUp = function(ev) {
    var cal = Calendar._C;
    if (!cal) {
        return false;
    }
    if (cal.timeout) {
        clearTimeout(cal.timeout);
    }
    var el = cal.activeDiv;
    if (!el) {
        return false;
    }
    var target = Calendar.getTargetElement(ev);
    ev || (ev = window.event);
    Calendar.removeClass(el, "active");
    if (target == el || target.parentNode == el) {
        Calendar.cellClick(el, ev);
    }
    var mon = Calendar.findMonth(target);
    var date = null;
    if (mon) {
        date = new CalendarDateObject(cal.date);
        if (mon.month != date.getMonth()) {
            date.setMonth(mon.month);
            cal.setDate(date);
            cal.dateClicked = false;
            cal.callHandler();
        }
    } else {
        var year = Calendar.findYear(target);
        if (year) {
            date = new CalendarDateObject(cal.date);
            if (year.year != date.getFullYear()) {
                date.setFullYear(year.year);
                cal.setDate(date);
                cal.dateClicked = false;
                cal.callHandler();
            }
        }
    }
    with (Calendar) {
        removeEvent(document, "mouseup", tableMouseUp);
        removeEvent(document, "mouseover", tableMouseOver);
        removeEvent(document, "mousemove", tableMouseOver);
        cal._hideCombos();
        _C = null;
        return stopEvent(ev);
    }
};

Calendar.tableMouseOver = function(ev) {
    var cal = Calendar._C;
    if (!cal) {
        return;
    }
    var el = cal.activeDiv;
    var target = Calendar.getTargetElement(ev);
    if (target == el || target.parentNode == el) {
        Calendar.addClass(el, "hilite active");
        Calendar.addClass(el.parentNode, "rowhilite");
    } else {
        if (typeof el.navtype == "undefined" || el.navtype != 50 && (el.navtype == 0 || Math.abs(el.navtype) > 2)) Calendar.removeClass(el, "active");
        Calendar.removeClass(el, "hilite");
        Calendar.removeClass(el.parentNode, "rowhilite");
    }
    ev || (ev = window.event);
    if (el.navtype == 50 && target != el) {
        var pos = Calendar.getAbsolutePos(el);
        var w = el.offsetWidth;
        var x = ev.clientX;
        var dx;
        var decrease = true;
        if (x > pos.x + w) {
            dx = x - pos.x - w;
            decrease = false;
        } else dx = pos.x - x;
        if (dx < 0) dx = 0;
        var range = el._range;
        var current = el._current;
        var count = Math.floor(dx / 10) % range.length;
        for (var i = range.length; --i >= 0; ) if (range[i] == current) break;
        while (count-- > 0) if (decrease) {
            if (--i < 0) i = range.length - 1;
        } else if (++i >= range.length) i = 0;
        var newval = range[i];
        el.innerHTML = newval;
        cal.onUpdateTime();
    }
    var mon = Calendar.findMonth(target);
    if (mon) {
        if (mon.month != cal.date.getMonth()) {
            if (cal.hilitedMonth) {
                Calendar.removeClass(cal.hilitedMonth, "hilite");
            }
            Calendar.addClass(mon, "hilite");
            cal.hilitedMonth = mon;
        } else if (cal.hilitedMonth) {
            Calendar.removeClass(cal.hilitedMonth, "hilite");
        }
    } else {
        if (cal.hilitedMonth) {
            Calendar.removeClass(cal.hilitedMonth, "hilite");
        }
        var year = Calendar.findYear(target);
        if (year) {
            if (year.year != cal.date.getFullYear()) {
                if (cal.hilitedYear) {
                    Calendar.removeClass(cal.hilitedYear, "hilite");
                }
                Calendar.addClass(year, "hilite");
                cal.hilitedYear = year;
            } else if (cal.hilitedYear) {
                Calendar.removeClass(cal.hilitedYear, "hilite");
            }
        } else if (cal.hilitedYear) {
            Calendar.removeClass(cal.hilitedYear, "hilite");
        }
    }
    return Calendar.stopEvent(ev);
};

Calendar.tableMouseDown = function(ev) {
    if (Calendar.getTargetElement(ev) == Calendar.getElement(ev)) {
        return Calendar.stopEvent(ev);
    }
};

Calendar.calDragIt = function(ev) {
    var cal = Calendar._C;
    if (!(cal && cal.dragging)) {
        return false;
    }
    var posX;
    var posY;
    if (Calendar.is_ie) {
        posY = window.event.clientY + document.body.scrollTop;
        posX = window.event.clientX + document.body.scrollLeft;
    } else {
        posX = ev.pageX;
        posY = ev.pageY;
    }
    cal.hideShowCovered();
    var st = cal.element.style;
    st.left = posX - cal.xOffs + "px";
    st.top = posY - cal.yOffs + "px";
    return Calendar.stopEvent(ev);
};

Calendar.calDragEnd = function(ev) {
    var cal = Calendar._C;
    if (!cal) {
        return false;
    }
    cal.dragging = false;
    with (Calendar) {
        removeEvent(document, "mousemove", calDragIt);
        removeEvent(document, "mouseup", calDragEnd);
        tableMouseUp(ev);
    }
    cal.hideShowCovered();
};

Calendar.dayMouseDown = function(ev) {
    var el = Calendar.getElement(ev);
    if (el.disabled) {
        return false;
    }
    var cal = el.calendar;
    cal.activeDiv = el;
    Calendar._C = cal;
    if (el.navtype != 300) with (Calendar) {
        if (el.navtype == 50) {
            el._current = el.innerHTML;
            addEvent(document, "mousemove", tableMouseOver);
        } else addEvent(document, Calendar.is_ie5 ? "mousemove" : "mouseover", tableMouseOver);
        addClass(el, "hilite active");
        addEvent(document, "mouseup", tableMouseUp);
    } else if (cal.isPopup) {
        cal._dragStart(ev);
    }
    if (el.navtype == -1 || el.navtype == 1) {
        if (cal.timeout) clearTimeout(cal.timeout);
        cal.timeout = setTimeout("Calendar.showMonthsCombo()", 250);
    } else if (el.navtype == -2 || el.navtype == 2) {
        if (cal.timeout) clearTimeout(cal.timeout);
        cal.timeout = setTimeout(el.navtype > 0 ? "Calendar.showYearsCombo(true)" : "Calendar.showYearsCombo(false)", 250);
    } else {
        cal.timeout = null;
    }
    return Calendar.stopEvent(ev);
};

Calendar.dayMouseDblClick = function(ev) {
    Calendar.cellClick(Calendar.getElement(ev), ev || window.event);
    if (Calendar.is_ie) {
        document.selection.empty();
    }
};

Calendar.dayMouseOver = function(ev) {
    var el = Calendar.getElement(ev);
    if (Calendar.isRelated(el, ev) || Calendar._C || el.disabled) {
        return false;
    }
    if (el.ttip) {
        if (el.ttip.substr(0, 1) == "_") {
            el.ttip = el.caldate.print(el.calendar.ttDateFormat) + el.ttip.substr(1);
        }
        el.calendar.tooltips.innerHTML = el.ttip;
    }
    if (el.navtype != 300) {
        Calendar.addClass(el, "hilite");
        if (el.caldate) {
            Calendar.addClass(el.parentNode, "rowhilite");
        }
    }
    return Calendar.stopEvent(ev);
};

Calendar.dayMouseOut = function(ev) {
    with (Calendar) {
        var el = getElement(ev);
        if (isRelated(el, ev) || _C || el.disabled) return false;
        removeClass(el, "hilite");
        if (el.caldate) removeClass(el.parentNode, "rowhilite");
        if (el.calendar) el.calendar.tooltips.innerHTML = _TT["SEL_DATE"];
        return stopEvent(ev);
    }
};

Calendar.cellClick = function(el, ev) {
    var cal = el.calendar;
    var closing = false;
    var newdate = false;
    var date = null;
    if (typeof el.navtype == "undefined") {
        if (cal.currentDateEl) {
            Calendar.removeClass(cal.currentDateEl, "selected");
            Calendar.addClass(el, "selected");
            closing = cal.currentDateEl == el;
            if (!closing) {
                cal.currentDateEl = el;
            }
        }
        cal.date.setDateOnly(el.caldate);
        date = cal.date;
        var other_month = !(cal.dateClicked = !el.otherMonth);
        if (!other_month && !cal.currentDateEl) cal._toggleMultipleDate(new CalendarDateObject(date)); else newdate = !el.disabled;
        if (other_month) cal._init(cal.firstDayOfWeek, date);
    } else {
        if (el.navtype == 200) {
            Calendar.removeClass(el, "hilite");
            cal.callCloseHandler();
            return;
        }
        date = new CalendarDateObject(cal.date);
        if (el.navtype == 0) date.setDateOnly(new CalendarDateObject());
        cal.dateClicked = false;
        var year = date.getFullYear();
        var mon = date.getMonth();
        function setMonth(m) {
            var day = date.getDate();
            var max = date.getMonthDays(m);
            if (day > max) {
                date.setDate(max);
            }
            date.setMonth(m);
        }
        switch (el.navtype) {
          case 400:
            Calendar.removeClass(el, "hilite");
            var text = Calendar._TT["ABOUT"];
            if (typeof text != "undefined") {
                text += cal.showsTime ? Calendar._TT["ABOUT_TIME"] : "";
            } else {
                text = "Help and about box text is not translated into this language.\n" + "If you know this language and you feel generous please update\n" + 'the corresponding file in "lang" subdir to match calendar-en.js\n' + "and send it back to <mihai_bazon@yahoo.com> to get it into the distribution  ;-)\n\n" + "Thank you!\n" + "http://dynarch.com/mishoo/calendar.epl\n";
            }
            alert(text);
            return;

          case -2:
            if (year > cal.minYear) {
                date.setFullYear(year - 1);
            }
            break;

          case -1:
            if (mon > 0) {
                setMonth(mon - 1);
            } else if (year-- > cal.minYear) {
                date.setFullYear(year);
                setMonth(11);
            }
            break;

          case 1:
            if (mon < 11) {
                setMonth(mon + 1);
            } else if (year < cal.maxYear) {
                date.setFullYear(year + 1);
                setMonth(0);
            }
            break;

          case 2:
            if (year < cal.maxYear) {
                date.setFullYear(year + 1);
            }
            break;

          case 100:
            cal.setFirstDayOfWeek(el.fdow);
            return;

          case 50:
            var range = el._range;
            var current = el.innerHTML;
            for (var i = range.length; --i >= 0; ) if (range[i] == current) break;
            if (ev && ev.shiftKey) {
                if (--i < 0) i = range.length - 1;
            } else if (++i >= range.length) i = 0;
            var newval = range[i];
            el.innerHTML = newval;
            cal.onUpdateTime();
            return;

          case 0:
            if (typeof cal.getDateStatus == "function" && cal.getDateStatus(date, date.getFullYear(), date.getMonth(), date.getDate())) {
                return false;
            }
            break;
        }
        if (!date.equalsTo(cal.date)) {
            cal.setDate(date);
            newdate = true;
        } else if (el.navtype == 0) newdate = closing = true;
    }
    if (newdate) {
        ev && cal.callHandler();
    }
    if (closing) {
        Calendar.removeClass(el, "hilite");
        ev && cal.callCloseHandler();
    }
};

Calendar.prototype.create = function(_par) {
    var parent = null;
    if (!_par) {
        parent = document.getElementsByTagName("body")[0];
        this.isPopup = true;
    } else {
        parent = _par;
        this.isPopup = false;
    }
    this.date = this.dateStr ? new CalendarDateObject(this.dateStr) : new CalendarDateObject();
    var table = Calendar.createElement("table");
    this.table = table;
    table.cellSpacing = 0;
    table.cellPadding = 0;
    table.calendar = this;
    Calendar.addEvent(table, "mousedown", Calendar.tableMouseDown);
    var div = Calendar.createElement("div");
    this.element = div;
    div.className = "calendar";
    if (this.isPopup) {
        div.style.position = "absolute";
        div.style.display = "none";
    }
    div.appendChild(table);
    var thead = Calendar.createElement("thead", table);
    var cell = null;
    var row = null;
    var cal = this;
    var hh = function(text, cs, navtype) {
        cell = Calendar.createElement("td", row);
        cell.colSpan = cs;
        cell.className = "button";
        if (navtype != 0 && Math.abs(navtype) <= 2) cell.className += " nav";
        Calendar._add_evs(cell);
        cell.calendar = cal;
        cell.navtype = navtype;
        cell.innerHTML = "<div unselectable='on'>" + text + "</div>";
        return cell;
    };
    row = Calendar.createElement("tr", thead);
    var title_length = 6;
    this.isPopup && --title_length;
    this.weekNumbers && ++title_length;
    hh("?", 1, 400).ttip = Calendar._TT["INFO"];
    this.title = hh("", title_length, 300);
    this.title.className = "title";
    if (this.isPopup) {
        this.title.ttip = Calendar._TT["DRAG_TO_MOVE"];
        this.title.style.cursor = "move";
        hh("&#x00d7;", 1, 200).ttip = Calendar._TT["CLOSE"];
    }
    row = Calendar.createElement("tr", thead);
    row.className = "headrow";
    this._nav_py = hh("&#x00ab;", 1, -2);
    this._nav_py.ttip = Calendar._TT["PREV_YEAR"];
    this._nav_pm = hh("&#x2039;", 1, -1);
    this._nav_pm.ttip = Calendar._TT["PREV_MONTH"];
    this._nav_now = hh(Calendar._TT["TODAY"], this.weekNumbers ? 4 : 3, 0);
    this._nav_now.ttip = Calendar._TT["GO_TODAY"];
    this._nav_nm = hh("&#x203a;", 1, 1);
    this._nav_nm.ttip = Calendar._TT["NEXT_MONTH"];
    this._nav_ny = hh("&#x00bb;", 1, 2);
    this._nav_ny.ttip = Calendar._TT["NEXT_YEAR"];
    row = Calendar.createElement("tr", thead);
    row.className = "daynames";
    if (this.weekNumbers) {
        cell = Calendar.createElement("td", row);
        cell.className = "name wn";
        cell.innerHTML = Calendar._TT["WK"];
    }
    for (var i = 7; i > 0; --i) {
        cell = Calendar.createElement("td", row);
        if (!i) {
            cell.navtype = 100;
            cell.calendar = this;
            Calendar._add_evs(cell);
        }
    }
    this.firstdayname = this.weekNumbers ? row.firstChild.nextSibling : row.firstChild;
    this._displayWeekdays();
    var tbody = Calendar.createElement("tbody", table);
    this.tbody = tbody;
    for (i = 6; i > 0; --i) {
        row = Calendar.createElement("tr", tbody);
        if (this.weekNumbers) {
            cell = Calendar.createElement("td", row);
        }
        for (var j = 7; j > 0; --j) {
            cell = Calendar.createElement("td", row);
            cell.calendar = this;
            Calendar._add_evs(cell);
        }
    }
    if (this.showsTime) {
        row = Calendar.createElement("tr", tbody);
        row.className = "time";
        cell = Calendar.createElement("td", row);
        cell.className = "time";
        cell.colSpan = 2;
        cell.innerHTML = Calendar._TT["TIME"] || "&nbsp;";
        cell = Calendar.createElement("td", row);
        cell.className = "time";
        cell.colSpan = this.weekNumbers ? 4 : 3;
        (function() {
            function makeTimePart(className, init, range_start, range_end) {
                var part = Calendar.createElement("span", cell);
                part.className = className;
                part.innerHTML = init;
                part.calendar = cal;
                part.ttip = Calendar._TT["TIME_PART"];
                part.navtype = 50;
                part._range = [];
                if (typeof range_start != "number") part._range = range_start; else {
                    for (var i = range_start; i <= range_end; ++i) {
                        var txt;
                        if (i < 10 && range_end >= 10) txt = "0" + i; else txt = "" + i;
                        part._range[part._range.length] = txt;
                    }
                }
                Calendar._add_evs(part);
                return part;
            }
            var hrs = cal.date.getHours();
            var mins = cal.date.getMinutes();
            var t12 = !cal.time24;
            var pm = hrs > 12;
            if (t12 && pm) hrs -= 12;
            var H = makeTimePart("hour", hrs, t12 ? 1 : 0, t12 ? 12 : 23);
            var span = Calendar.createElement("span", cell);
            span.innerHTML = ":";
            span.className = "colon";
            var M = makeTimePart("minute", mins, 0, 59);
            var AP = null;
            cell = Calendar.createElement("td", row);
            cell.className = "time";
            cell.colSpan = 2;
            if (t12) AP = makeTimePart("ampm", pm ? "pm" : "am", [ "am", "pm" ]); else cell.innerHTML = "&nbsp;";
            cal.onSetTime = function() {
                var pm, hrs = this.date.getHours(), mins = this.date.getMinutes();
                if (t12) {
                    pm = hrs >= 12;
                    if (pm) hrs -= 12;
                    if (hrs == 0) hrs = 12;
                    AP.innerHTML = pm ? "pm" : "am";
                }
                H.innerHTML = hrs < 10 ? "0" + hrs : hrs;
                M.innerHTML = mins < 10 ? "0" + mins : mins;
            };
            cal.onUpdateTime = function() {
                var date = this.date;
                var h = parseInt(H.innerHTML, 10);
                if (t12) {
                    if (/pm/i.test(AP.innerHTML) && h < 12) h += 12; else if (/am/i.test(AP.innerHTML) && h == 12) h = 0;
                }
                var d = date.getDate();
                var m = date.getMonth();
                var y = date.getFullYear();
                date.setHours(h);
                date.setMinutes(parseInt(M.innerHTML, 10));
                date.setFullYear(y);
                date.setMonth(m);
                date.setDate(d);
                this.dateClicked = false;
                this.callHandler();
            };
        })();
    } else {
        this.onSetTime = this.onUpdateTime = function() {};
    }
    var tfoot = Calendar.createElement("tfoot", table);
    row = Calendar.createElement("tr", tfoot);
    row.className = "footrow";
    cell = hh(Calendar._TT["SEL_DATE"], this.weekNumbers ? 8 : 7, 300);
    cell.className = "ttip";
    if (this.isPopup) {
        cell.ttip = Calendar._TT["DRAG_TO_MOVE"];
        cell.style.cursor = "move";
    }
    this.tooltips = cell;
    div = Calendar.createElement("div", this.element);
    this.monthsCombo = div;
    div.className = "combo";
    for (i = 0; i < Calendar._MN.length; ++i) {
        var mn = Calendar.createElement("div");
        mn.className = Calendar.is_ie ? "label-IEfix" : "label";
        mn.month = i;
        mn.innerHTML = Calendar._SMN[i];
        div.appendChild(mn);
    }
    div = Calendar.createElement("div", this.element);
    this.yearsCombo = div;
    div.className = "combo";
    for (i = 12; i > 0; --i) {
        var yr = Calendar.createElement("div");
        yr.className = Calendar.is_ie ? "label-IEfix" : "label";
        div.appendChild(yr);
    }
    this._init(this.firstDayOfWeek, this.date);
    parent.appendChild(this.element);
};

Calendar._keyEvent = function(ev) {
    var cal = window._dynarch_popupCalendar;
    if (!cal || cal.multiple) return false;
    Calendar.is_ie && (ev = window.event);
    var act = Calendar.is_ie || ev.type == "keypress", K = ev.keyCode;
    if (ev.ctrlKey) {
        switch (K) {
          case 37:
            act && Calendar.cellClick(cal._nav_pm);
            break;

          case 38:
            act && Calendar.cellClick(cal._nav_py);
            break;

          case 39:
            act && Calendar.cellClick(cal._nav_nm);
            break;

          case 40:
            act && Calendar.cellClick(cal._nav_ny);
            break;

          default:
            return false;
        }
    } else switch (K) {
      case 32:
        Calendar.cellClick(cal._nav_now);
        break;

      case 27:
        act && cal.callCloseHandler();
        break;

      case 37:
      case 38:
      case 39:
      case 40:
        if (act) {
            var prev, x, y, ne, el, step;
            prev = K == 37 || K == 38;
            step = K == 37 || K == 39 ? 1 : 7;
            function setVars() {
                el = cal.currentDateEl;
                var p = el.pos;
                x = p & 15;
                y = p >> 4;
                ne = cal.ar_days[y][x];
            }
            setVars();
            function prevMonth() {
                var date = new CalendarDateObject(cal.date);
                date.setDate(date.getDate() - step);
                cal.setDate(date);
            }
            function nextMonth() {
                var date = new CalendarDateObject(cal.date);
                date.setDate(date.getDate() + step);
                cal.setDate(date);
            }
            while (1) {
                switch (K) {
                  case 37:
                    if (--x >= 0) ne = cal.ar_days[y][x]; else {
                        x = 6;
                        K = 38;
                        continue;
                    }
                    break;

                  case 38:
                    if (--y >= 0) ne = cal.ar_days[y][x]; else {
                        prevMonth();
                        setVars();
                    }
                    break;

                  case 39:
                    if (++x < 7) ne = cal.ar_days[y][x]; else {
                        x = 0;
                        K = 40;
                        continue;
                    }
                    break;

                  case 40:
                    if (++y < cal.ar_days.length) ne = cal.ar_days[y][x]; else {
                        nextMonth();
                        setVars();
                    }
                    break;
                }
                break;
            }
            if (ne) {
                if (!ne.disabled) Calendar.cellClick(ne); else if (prev) prevMonth(); else nextMonth();
            }
        }
        break;

      case 13:
        if (act) Calendar.cellClick(cal.currentDateEl, ev);
        break;

      default:
        return false;
    }
    return Calendar.stopEvent(ev);
};

Calendar.prototype._init = function(firstDayOfWeek, date) {
    var today = new CalendarDateObject(), TY = today.getFullYear(), TM = today.getMonth(), TD = today.getDate();
    this.table.style.visibility = "hidden";
    var year = date.getFullYear();
    if (year < this.minYear) {
        year = this.minYear;
        date.setFullYear(year);
    } else if (year > this.maxYear) {
        year = this.maxYear;
        date.setFullYear(year);
    }
    this.firstDayOfWeek = firstDayOfWeek;
    this.date = new CalendarDateObject(date);
    var month = date.getMonth();
    var mday = date.getDate();
    var no_days = date.getMonthDays();
    date.setDate(1);
    var day1 = (date.getDay() - this.firstDayOfWeek) % 7;
    if (day1 < 0) day1 += 7;
    date.setDate(-day1);
    date.setDate(date.getDate() + 1);
    var row = this.tbody.firstChild;
    var MN = Calendar._SMN[month];
    var ar_days = this.ar_days = new Array();
    var weekend = Calendar._TT["WEEKEND"];
    var dates = this.multiple ? this.datesCells = {} : null;
    for (var i = 0; i < 6; ++i, row = row.nextSibling) {
        var cell = row.firstChild;
        if (this.weekNumbers) {
            cell.className = "day wn";
            cell.innerHTML = date.getWeekNumber();
            cell = cell.nextSibling;
        }
        row.className = "daysrow";
        var hasdays = false, iday, dpos = ar_days[i] = [];
        for (var j = 0; j < 7; ++j, cell = cell.nextSibling, date.setDate(iday + 1)) {
            iday = date.getDate();
            var wday = date.getDay();
            cell.className = "day";
            cell.pos = i << 4 | j;
            dpos[j] = cell;
            var current_month = date.getMonth() == month;
            if (!current_month) {
                if (this.showsOtherMonths) {
                    cell.className += " othermonth";
                    cell.otherMonth = true;
                } else {
                    cell.className = "emptycell";
                    cell.innerHTML = "&nbsp;";
                    cell.disabled = true;
                    continue;
                }
            } else {
                cell.otherMonth = false;
                hasdays = true;
            }
            cell.disabled = false;
            cell.innerHTML = this.getDateText ? this.getDateText(date, iday) : iday;
            if (dates) dates[date.print("%Y%m%d")] = cell;
            if (this.getDateStatus) {
                var status = this.getDateStatus(date, year, month, iday);
                if (this.getDateToolTip) {
                    var toolTip = this.getDateToolTip(date, year, month, iday);
                    if (toolTip) cell.title = toolTip;
                }
                if (status === true) {
                    cell.className += " disabled";
                    cell.disabled = true;
                } else {
                    if (/disabled/i.test(status)) cell.disabled = true;
                    cell.className += " " + status;
                }
            }
            if (!cell.disabled) {
                cell.caldate = new CalendarDateObject(date);
                cell.ttip = "_";
                if (!this.multiple && current_month && iday == mday && this.hiliteToday) {
                    cell.className += " selected";
                    this.currentDateEl = cell;
                }
                if (date.getFullYear() == TY && date.getMonth() == TM && iday == TD) {
                    cell.className += " today";
                    cell.ttip += Calendar._TT["PART_TODAY"];
                }
                if (weekend.indexOf(wday.toString()) != -1) cell.className += cell.otherMonth ? " oweekend" : " weekend";
            } else {
                this.currentDateEl = cell;
            }
        }
        if (!(hasdays || this.showsOtherMonths)) row.className = "emptyrow";
    }
    this.title.innerHTML = Calendar._MN[month] + ", " + year;
    this.onSetTime();
    this.table.style.visibility = "visible";
    this._initMultipleDates();
};

Calendar.prototype._initMultipleDates = function() {
    if (this.multiple) {
        for (var i in this.multiple) {
            var cell = this.datesCells[i];
            var d = this.multiple[i];
            if (!d) continue;
            if (cell) cell.className += " selected";
        }
    }
};

Calendar.prototype._toggleMultipleDate = function(date) {
    if (this.multiple) {
        var ds = date.print("%Y%m%d");
        var cell = this.datesCells[ds];
        if (cell) {
            var d = this.multiple[ds];
            if (!d) {
                Calendar.addClass(cell, "selected");
                this.multiple[ds] = date;
            } else {
                Calendar.removeClass(cell, "selected");
                delete this.multiple[ds];
            }
        }
    }
};

Calendar.prototype.setDateToolTipHandler = function(unaryFunction) {
    this.getDateToolTip = unaryFunction;
};

Calendar.prototype.setDate = function(date) {
    if (!date.equalsTo(this.date)) {
        this._init(this.firstDayOfWeek, date);
    }
};

Calendar.prototype.refresh = function() {
    this._init(this.firstDayOfWeek, this.date);
};

Calendar.prototype.setFirstDayOfWeek = function(firstDayOfWeek) {
    this._init(firstDayOfWeek, this.date);
    this._displayWeekdays();
};

Calendar.prototype.setDateStatusHandler = Calendar.prototype.setDisabledHandler = function(unaryFunction) {
    this.getDateStatus = unaryFunction;
};

Calendar.prototype.setRange = function(a, z) {
    this.minYear = a;
    this.maxYear = z;
};

Calendar.prototype.callHandler = function() {
    if (this.onSelected) {
        this.onSelected(this, this.date.print(this.dateFormat));
    }
};

Calendar.prototype.callCloseHandler = function() {
    if (this.onClose) {
        this.onClose(this);
    }
    this.hideShowCovered();
};

Calendar.prototype.destroy = function() {
    var el = this.element.parentNode;
    el.removeChild(this.element);
    Calendar._C = null;
    window._dynarch_popupCalendar = null;
};

Calendar.prototype.reparent = function(new_parent) {
    var el = this.element;
    el.parentNode.removeChild(el);
    new_parent.appendChild(el);
};

Calendar._checkCalendar = function(ev) {
    var calendar = window._dynarch_popupCalendar;
    if (!calendar) {
        return false;
    }
    var el = Calendar.is_ie ? Calendar.getElement(ev) : Calendar.getTargetElement(ev);
    for (;el != null && el != calendar.element; el = el.parentNode) ;
    if (el == null) {
        window._dynarch_popupCalendar.callCloseHandler();
        return Calendar.stopEvent(ev);
    }
};

Calendar.prototype.show = function() {
    var rows = this.table.getElementsByTagName("tr");
    for (var i = rows.length; i > 0; ) {
        var row = rows[--i];
        Calendar.removeClass(row, "rowhilite");
        var cells = row.getElementsByTagName("td");
        for (var j = cells.length; j > 0; ) {
            var cell = cells[--j];
            Calendar.removeClass(cell, "hilite");
            Calendar.removeClass(cell, "active");
        }
    }
    this.element.style.display = "block";
    this.hidden = false;
    if (this.isPopup) {
        window._dynarch_popupCalendar = this;
        Calendar.addEvent(document, "keydown", Calendar._keyEvent);
        Calendar.addEvent(document, "keypress", Calendar._keyEvent);
        Calendar.addEvent(document, "mousedown", Calendar._checkCalendar);
    }
    this.hideShowCovered();
};

Calendar.prototype.hide = function() {
    if (this.isPopup) {
        Calendar.removeEvent(document, "keydown", Calendar._keyEvent);
        Calendar.removeEvent(document, "keypress", Calendar._keyEvent);
        Calendar.removeEvent(document, "mousedown", Calendar._checkCalendar);
    }
    this.element.style.display = "none";
    this.hidden = true;
    this.hideShowCovered();
};

Calendar.prototype.showAt = function(x, y) {
    var s = this.element.style;
    s.left = x + "px";
    s.top = y + "px";
    this.show();
};

Calendar.prototype.showAtElement = function(el, opts) {
    var self = this;
    var p = Calendar.getAbsolutePos(el);
    if (!opts || typeof opts != "string") {
        this.showAt(p.x, p.y + el.offsetHeight);
        return true;
    }
    function fixPosition(box) {
        if (box.x < 0) box.x = 0;
        if (box.y < 0) box.y = 0;
        var cp = document.createElement("div");
        var s = cp.style;
        s.position = "absolute";
        s.right = s.bottom = s.width = s.height = "0px";
        document.body.appendChild(cp);
        var br = Calendar.getAbsolutePos(cp);
        document.body.removeChild(cp);
        if (Calendar.is_ie) {
            br.y += document.documentElement.scrollTop;
            br.x += document.documentElement.scrollLeft;
        } else {
            br.y += window.scrollY;
            br.x += window.scrollX;
        }
        var tmp = box.x + box.width - br.x;
        if (tmp > 0) box.x -= tmp;
        tmp = box.y + box.height - br.y;
        if (tmp > 0) box.y -= tmp;
    }
    this.element.style.display = "block";
    Calendar.continuation_for_the_fucking_khtml_browser = function() {
        var w = self.element.offsetWidth;
        var h = self.element.offsetHeight;
        self.element.style.display = "none";
        var valign = opts.substr(0, 1);
        var halign = "l";
        if (opts.length > 1) {
            halign = opts.substr(1, 1);
        }
        switch (valign) {
          case "T":
            p.y -= h;
            break;

          case "B":
            p.y += el.offsetHeight;
            break;

          case "C":
            p.y += (el.offsetHeight - h) / 2;
            break;

          case "t":
            p.y += el.offsetHeight - h;
            break;

          case "b":
            break;
        }
        switch (halign) {
          case "L":
            p.x -= w;
            break;

          case "R":
            p.x += el.offsetWidth;
            break;

          case "C":
            p.x += (el.offsetWidth - w) / 2;
            break;

          case "l":
            p.x += el.offsetWidth - w;
            break;

          case "r":
            break;
        }
        p.width = w;
        p.height = h + 40;
        self.monthsCombo.style.display = "none";
        fixPosition(p);
        self.showAt(p.x, p.y);
    };
    if (Calendar.is_khtml) setTimeout("Calendar.continuation_for_the_fucking_khtml_browser()", 10); else Calendar.continuation_for_the_fucking_khtml_browser();
};

Calendar.prototype.setDateFormat = function(str) {
    this.dateFormat = str;
};

Calendar.prototype.setTtDateFormat = function(str) {
    this.ttDateFormat = str;
};

Calendar.prototype.parseDate = function(str, fmt) {
    if (!fmt) fmt = this.dateFormat;
    this.setDate(Date.parseDate(str, fmt));
};

Calendar.prototype.hideShowCovered = function() {
    if (!Calendar.is_ie && !Calendar.is_opera) return;
    function getVisib(obj) {
        var value = obj.style.visibility;
        if (!value) {
            if (document.defaultView && typeof document.defaultView.getComputedStyle == "function") {
                if (!Calendar.is_khtml) value = document.defaultView.getComputedStyle(obj, "").getPropertyValue("visibility"); else value = "";
            } else if (obj.currentStyle) {
                value = obj.currentStyle.visibility;
            } else value = "";
        }
        return value;
    }
    var tags = new Array("applet", "iframe", "select");
    var el = this.element;
    var p = Calendar.getAbsolutePos(el);
    var EX1 = p.x;
    var EX2 = el.offsetWidth + EX1;
    var EY1 = p.y;
    var EY2 = el.offsetHeight + EY1;
    for (var k = tags.length; k > 0; ) {
        var ar = document.getElementsByTagName(tags[--k]);
        var cc = null;
        for (var i = ar.length; i > 0; ) {
            cc = ar[--i];
            p = Calendar.getAbsolutePos(cc);
            var CX1 = p.x;
            var CX2 = cc.offsetWidth + CX1;
            var CY1 = p.y;
            var CY2 = cc.offsetHeight + CY1;
            if (this.hidden || CX1 > EX2 || CX2 < EX1 || CY1 > EY2 || CY2 < EY1) {
                if (!cc.__msh_save_visibility) {
                    cc.__msh_save_visibility = getVisib(cc);
                }
                cc.style.visibility = cc.__msh_save_visibility;
            } else {
                if (!cc.__msh_save_visibility) {
                    cc.__msh_save_visibility = getVisib(cc);
                }
                cc.style.visibility = "hidden";
            }
        }
    }
};

Calendar.prototype._displayWeekdays = function() {
    var fdow = this.firstDayOfWeek;
    var cell = this.firstdayname;
    var weekend = Calendar._TT["WEEKEND"];
    for (var i = 0; i < 7; ++i) {
        cell.className = "day name";
        var realday = (i + fdow) % 7;
        if (i) {
            cell.ttip = Calendar._TT["DAY_FIRST"].replace("%s", Calendar._DN[realday]);
            cell.navtype = 100;
            cell.calendar = this;
            cell.fdow = realday;
            Calendar._add_evs(cell);
        }
        if (weekend.indexOf(realday.toString()) != -1) {
            Calendar.addClass(cell, "weekend");
        }
        cell.innerHTML = Calendar._SDN[(i + fdow) % 7];
        cell = cell.nextSibling;
    }
};

Calendar.prototype._hideCombos = function() {
    this.monthsCombo.style.display = "none";
    this.yearsCombo.style.display = "none";
};

Calendar.prototype._dragStart = function(ev) {
    if (this.dragging) {
        return;
    }
    this.dragging = true;
    var posX;
    var posY;
    if (Calendar.is_ie) {
        posY = window.event.clientY + document.body.scrollTop;
        posX = window.event.clientX + document.body.scrollLeft;
    } else {
        posY = ev.clientY + window.scrollY;
        posX = ev.clientX + window.scrollX;
    }
    var st = this.element.style;
    this.xOffs = posX - parseInt(st.left);
    this.yOffs = posY - parseInt(st.top);
    with (Calendar) {
        addEvent(document, "mousemove", calDragIt);
        addEvent(document, "mouseup", calDragEnd);
    }
};

Date._MD = new Array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);

Date.SECOND = 1e3;

Date.MINUTE = 60 * Date.SECOND;

Date.HOUR = 60 * Date.MINUTE;

Date.DAY = 24 * Date.HOUR;

Date.WEEK = 7 * Date.DAY;

Date.parseDate = function(str, fmt) {
    var today = new CalendarDateObject();
    var y = 0;
    var m = -1;
    var d = 0;
    var a = str;
    var i;
    for (i = 0; i < Calendar._MN.length; i++) {
        a = a.replace(Calendar._MN[i], enUS.m.wide[i]);
    }
    for (i = 0; i < Calendar._SMN.length; i++) {
        a = a.replace(Calendar._SMN[i], enUS.m.abbr[i]);
    }
    a = a.replace(Calendar._am, "am");
    a = a.replace(Calendar._am.toLowerCase(), "am");
    a = a.replace(Calendar._pm, "pm");
    a = a.replace(Calendar._pm.toLowerCase(), "pm");
    a = a.split(/\W+/);
    var b = fmt.match(/%./g);
    var i = 0, j = 0;
    var hr = 0;
    var min = 0;
    for (i = 0; i < a.length; ++i) {
        if (!a[i]) continue;
        switch (b[i]) {
          case "%d":
          case "%e":
            d = parseInt(a[i], 10);
            break;

          case "%m":
            m = parseInt(a[i], 10) - 1;
            break;

          case "%Y":
          case "%y":
            y = parseInt(a[i], 10);
            y < 100 && (y += y > 29 ? 1900 : 2e3);
            break;

          case "%b":
            for (j = 0; j < 12; ++j) {
                if (enUS.m.abbr[j].substr(0, a[i].length).toLowerCase() == a[i].toLowerCase()) {
                    m = j;
                    break;
                }
            }
            break;

          case "%B":
            for (j = 0; j < 12; ++j) {
                if (enUS.m.wide[j].substr(0, a[i].length).toLowerCase() == a[i].toLowerCase()) {
                    m = j;
                    break;
                }
            }
            break;

          case "%H":
          case "%I":
          case "%k":
          case "%l":
            hr = parseInt(a[i], 10);
            break;

          case "%P":
          case "%p":
            if (/pm/i.test(a[i]) && hr < 12) hr += 12; else if (/am/i.test(a[i]) && hr >= 12) hr -= 12;
            break;

          case "%M":
            min = parseInt(a[i], 10);
            break;
        }
    }
    if (isNaN(y)) y = today.getFullYear();
    if (isNaN(m)) m = today.getMonth();
    if (isNaN(d)) d = today.getDate();
    if (isNaN(hr)) hr = today.getHours();
    if (isNaN(min)) min = today.getMinutes();
    if (y != 0 && m != -1 && d != 0) return new CalendarDateObject(y, m, d, hr, min, 0);
    y = 0;
    m = -1;
    d = 0;
    for (i = 0; i < a.length; ++i) {
        if (a[i].search(/[a-zA-Z]+/) != -1) {
            var t = -1;
            for (j = 0; j < 12; ++j) {
                if (Calendar._MN[j].substr(0, a[i].length).toLowerCase() == a[i].toLowerCase()) {
                    t = j;
                    break;
                }
            }
            if (t != -1) {
                if (m != -1) {
                    d = m + 1;
                }
                m = t;
            }
        } else if (parseInt(a[i], 10) <= 12 && m == -1) {
            m = a[i] - 1;
        } else if (parseInt(a[i], 10) > 31 && y == 0) {
            y = parseInt(a[i], 10);
            y < 100 && (y += y > 29 ? 1900 : 2e3);
        } else if (d == 0) {
            d = a[i];
        }
    }
    if (y == 0) y = today.getFullYear();
    if (m != -1 && d != 0) return new CalendarDateObject(y, m, d, hr, min, 0);
    return today;
};

Date.prototype.getMonthDays = function(month) {
    var year = this.getFullYear();
    if (typeof month == "undefined") {
        month = this.getMonth();
    }
    if (0 == year % 4 && (0 != year % 100 || 0 == year % 400) && month == 1) {
        return 29;
    } else {
        return Date._MD[month];
    }
};

Date.prototype.getDayOfYear = function() {
    var now = new CalendarDateObject(this.getFullYear(), this.getMonth(), this.getDate(), 0, 0, 0);
    var then = new CalendarDateObject(this.getFullYear(), 0, 0, 0, 0, 0);
    var time = now - then;
    return Math.floor(time / Date.DAY);
};

Date.prototype.getWeekNumber = function() {
    var d = new CalendarDateObject(this.getFullYear(), this.getMonth(), this.getDate(), 0, 0, 0);
    var DoW = d.getDay();
    d.setDate(d.getDate() - (DoW + 6) % 7 + 3);
    var ms = d.valueOf();
    d.setMonth(0);
    d.setDate(4);
    return Math.round((ms - d.valueOf()) / (7 * 864e5)) + 1;
};

Date.prototype.equalsTo = function(date) {
    return this.getFullYear() == date.getFullYear() && this.getMonth() == date.getMonth() && this.getDate() == date.getDate() && this.getHours() == date.getHours() && this.getMinutes() == date.getMinutes();
};

Date.prototype.setDateOnly = function(date) {
    var tmp = new CalendarDateObject(date);
    this.setDate(1);
    this.setFullYear(tmp.getFullYear());
    this.setMonth(tmp.getMonth());
    this.setDate(tmp.getDate());
};

Date.prototype.print = function(str) {
    var m = this.getMonth();
    var d = this.getDate();
    var y = this.getFullYear();
    var wn = this.getWeekNumber();
    var w = this.getDay();
    var s = {};
    var hr = this.getHours();
    var pm = hr >= 12;
    var ir = pm ? hr - 12 : hr;
    var dy = this.getDayOfYear();
    if (ir == 0) ir = 12;
    var min = this.getMinutes();
    var sec = this.getSeconds();
    s["%a"] = Calendar._SDN[w];
    s["%A"] = Calendar._DN[w];
    s["%b"] = Calendar._SMN[m];
    s["%B"] = Calendar._MN[m];
    s["%C"] = 1 + Math.floor(y / 100);
    s["%d"] = d < 10 ? "0" + d : d;
    s["%e"] = d;
    s["%H"] = hr < 10 ? "0" + hr : hr;
    s["%I"] = ir < 10 ? "0" + ir : ir;
    s["%j"] = dy < 100 ? dy < 10 ? "00" + dy : "0" + dy : dy;
    s["%k"] = hr;
    s["%l"] = ir;
    s["%m"] = m < 9 ? "0" + (1 + m) : 1 + m;
    s["%M"] = min < 10 ? "0" + min : min;
    s["%n"] = "\n";
    s["%p"] = pm ? Calendar._pm.toUpperCase() : Calendar._am.toUpperCase();
    s["%P"] = pm ? Calendar._pm.toLowerCase() : Calendar._am.toLowerCase();
    s["%s"] = Math.floor(this.getTime() / 1e3);
    s["%S"] = sec < 10 ? "0" + sec : sec;
    s["%t"] = "\t";
    s["%U"] = s["%W"] = s["%V"] = wn < 10 ? "0" + wn : wn;
    s["%u"] = w + 1;
    s["%w"] = w;
    s["%y"] = ("" + y).substr(2, 2);
    s["%Y"] = y;
    s["%%"] = "%";
    var re = /%./g;
    if (!Calendar.is_ie5 && !Calendar.is_khtml) return str.replace(re, function(par) {
        return s[par] || par;
    });
    var a = str.match(re);
    for (var i = 0; i < a.length; i++) {
        var tmp = s[a[i]];
        if (tmp) {
            re = new RegExp(a[i], "g");
            str = str.replace(re, tmp);
        }
    }
    return str;
};

Date.prototype.__msh_oldSetFullYear = Date.prototype.setFullYear;

Date.prototype.setFullYear = function(y) {
    var d = new CalendarDateObject(this);
    d.__msh_oldSetFullYear(y);
    if (d.getMonth() != this.getMonth()) this.setDate(28);
    this.__msh_oldSetFullYear(y);
};

CalendarDateObject.prototype = new Date();

CalendarDateObject.prototype.constructor = CalendarDateObject;

CalendarDateObject.prototype.parent = Date.prototype;

function CalendarDateObject() {
    var dateObj;
    if (arguments.length > 1) {
        dateObj = eval("new this.parent.constructor(" + Array.prototype.slice.call(arguments).join(",") + ");");
    } else if (arguments.length > 0) {
        dateObj = new this.parent.constructor(arguments[0]);
    } else {
        dateObj = new this.parent.constructor();
        if (typeof CalendarDateObject._SERVER_TIMZEONE_SECONDS != "undefined") {
            dateObj.setTime((CalendarDateObject._SERVER_TIMZEONE_SECONDS + dateObj.getTimezoneOffset() * 60) * 1e3);
        }
    }
    return dateObj;
}

window._dynarch_popupCalendar = null;

Calendar.setup = function(params) {
    function param_default(pname, def) {
        if (typeof params[pname] == "undefined") {
            params[pname] = def;
        }
    }
    param_default("inputField", null);
    param_default("displayArea", null);
    param_default("button", null);
    param_default("eventName", "click");
    param_default("ifFormat", "%Y/%m/%d");
    param_default("daFormat", "%Y/%m/%d");
    param_default("singleClick", true);
    param_default("disableFunc", null);
    param_default("dateStatusFunc", params["disableFunc"]);
    param_default("dateText", null);
    param_default("firstDay", null);
    param_default("align", "Br");
    param_default("range", [ 1900, 2999 ]);
    param_default("weekNumbers", true);
    param_default("flat", null);
    param_default("flatCallback", null);
    param_default("onSelect", null);
    param_default("onClose", null);
    param_default("onUpdate", null);
    param_default("date", null);
    param_default("showsTime", false);
    param_default("timeFormat", "24");
    param_default("electric", true);
    param_default("step", 2);
    param_default("position", null);
    param_default("cache", false);
    param_default("showOthers", false);
    param_default("multiple", null);
    var tmp = [ "inputField", "displayArea", "button" ];
    for (var i in tmp) {
        if (typeof params[tmp[i]] == "string") {
            params[tmp[i]] = document.getElementById(params[tmp[i]]);
        }
    }
    if (!(params.flat || params.multiple || params.inputField || params.displayArea || params.button)) {
        alert("Calendar.setup:\n  Nothing to setup (no fields found).  Please check your code");
        return false;
    }
    function onSelect(cal) {
        var p = cal.params;
        var update = cal.dateClicked || p.electric;
        if (update && p.inputField) {
            p.inputField.value = cal.date.print(p.ifFormat);
            if (typeof p.inputField.onchange == "function") p.inputField.onchange();
            if (typeof fireEvent == "function") fireEvent(p.inputField, "change");
        }
        if (update && p.displayArea) p.displayArea.innerHTML = cal.date.print(p.daFormat);
        if (update && typeof p.onUpdate == "function") p.onUpdate(cal);
        if (update && p.flat) {
            if (typeof p.flatCallback == "function") p.flatCallback(cal);
        }
        if (update && p.singleClick && cal.dateClicked) cal.callCloseHandler();
    }
    if (params.flat != null) {
        if (typeof params.flat == "string") params.flat = document.getElementById(params.flat);
        if (!params.flat) {
            alert("Calendar.setup:\n  Flat specified but can't find parent.");
            return false;
        }
        var cal = new Calendar(params.firstDay, params.date, params.onSelect || onSelect);
        cal.showsOtherMonths = params.showOthers;
        cal.showsTime = params.showsTime;
        cal.time24 = params.timeFormat == "24";
        cal.params = params;
        cal.weekNumbers = params.weekNumbers;
        cal.setRange(params.range[0], params.range[1]);
        cal.setDateStatusHandler(params.dateStatusFunc);
        cal.getDateText = params.dateText;
        if (params.ifFormat) {
            cal.setDateFormat(params.ifFormat);
        }
        if (params.inputField && typeof params.inputField.value == "string") {
            cal.parseDate(params.inputField.value);
        }
        cal.create(params.flat);
        cal.show();
        return false;
    }
    var triggerEl = params.button || params.displayArea || params.inputField;
    triggerEl["on" + params.eventName] = function() {
        var dateEl = params.inputField || params.displayArea;
        var dateFmt = params.inputField ? params.ifFormat : params.daFormat;
        var mustCreate = false;
        var cal = window.calendar;
        if (dateEl) params.date = Date.parseDate(dateEl.value || dateEl.innerHTML, dateFmt);
        if (!(cal && params.cache)) {
            window.calendar = cal = new Calendar(params.firstDay, params.date, params.onSelect || onSelect, params.onClose || function(cal) {
                cal.hide();
            });
            cal.showsTime = params.showsTime;
            cal.time24 = params.timeFormat == "24";
            cal.weekNumbers = params.weekNumbers;
            mustCreate = true;
        } else {
            if (params.date) cal.setDate(params.date);
            cal.hide();
        }
        if (params.multiple) {
            cal.multiple = {};
            for (var i = params.multiple.length; --i >= 0; ) {
                var d = params.multiple[i];
                var ds = d.print("%Y%m%d");
                cal.multiple[ds] = d;
            }
        }
        cal.showsOtherMonths = params.showOthers;
        cal.yearStep = params.step;
        cal.setRange(params.range[0], params.range[1]);
        cal.params = params;
        cal.setDateStatusHandler(params.dateStatusFunc);
        cal.getDateText = params.dateText;
        cal.setDateFormat(dateFmt);
        if (mustCreate) cal.create();
        cal.refresh();
        if (!params.position) cal.showAtElement(params.button || params.displayArea || params.inputField, params.align); else cal.showAt(params.position[0], params.position[1]);
        return false;
    };
    return cal;
};

var Translate = Class.create();

Translate.prototype = {
    initialize: function(data) {
        this.data = $H(data);
    },
    translate: function() {
        var args = arguments;
        var text = arguments[0];
        if (this.data.get(text)) {
            return this.data.get(text);
        }
        return text;
    },
    add: function() {
        if (arguments.length > 1) {
            this.data.set(arguments[0], arguments[1]);
        } else if (typeof arguments[0] == "object") {
            $H(arguments[0]).each(function(pair) {
                this.data.set(pair.key, pair.value);
            }.bind(this));
        }
    }
};

if (!window.Mage) var Mage = {};

Mage.Cookies = {};

Mage.Cookies.expires = null;

Mage.Cookies.path = "/";

Mage.Cookies.domain = null;

Mage.Cookies.secure = false;

Mage.Cookies.set = function(name, value) {
    var argv = arguments;
    var argc = arguments.length;
    var expires = argc > 2 ? argv[2] : Mage.Cookies.expires;
    var path = argc > 3 ? argv[3] : Mage.Cookies.path;
    var domain = argc > 4 ? argv[4] : Mage.Cookies.domain;
    var secure = argc > 5 ? argv[5] : Mage.Cookies.secure;
    document.cookie = name + "=" + escape(value) + (expires == null ? "" : "; expires=" + expires.toUTCString()) + (path == null ? "" : "; path=" + path) + (domain == null ? "" : "; domain=" + domain) + (secure == true ? "; secure" : "");
};

Mage.Cookies.get = function(name) {
    var arg = name + "=";
    var alen = arg.length;
    var clen = document.cookie.length;
    var i = 0;
    var j = 0;
    while (i < clen) {
        j = i + alen;
        if (document.cookie.substring(i, j) == arg) return Mage.Cookies.getCookieVal(j);
        i = document.cookie.indexOf(" ", i) + 1;
        if (i == 0) break;
    }
    return null;
};

Mage.Cookies.clear = function(name) {
    if (Mage.Cookies.get(name)) {
        Mage.Cookies.set(name, "", new Date(0));
    }
};

Mage.Cookies.getCookieVal = function(offset) {
    var endstr = document.cookie.indexOf(";", offset);
    if (endstr == -1) {
        endstr = document.cookie.length;
    }
    return unescape(document.cookie.substring(offset, endstr));
};

if (typeof Customweb == "undefined") {
    var Customweb = {};
}

Customweb.PayUnityCw = Class.create({
    initialize: function(hiddenFieldsUrl, visibleFieldsUrl, serverUrl, javascriptUrl, saveShippingUrl, methodCode, processingText) {
        this.hiddenFieldsUrl = hiddenFieldsUrl;
        this.visibleFieldsUrl = visibleFieldsUrl;
        this.serverUrl = serverUrl;
        this.javascriptUrl = javascriptUrl;
        this.saveShippingUrl = saveShippingUrl;
        this.methodCode = methodCode;
        this.processingText = processingText;
        this.paymentInfoSaved = false;
        this.defaultFormValidation = function(successCallback, failureCallback) {
            var validateFunctionName = "cwValidateFields" + this.methodCode;
            var validateFunction = window[validateFunctionName];
            if (typeof validateFunction != "undefined") {
                validateFunction(successCallback, failureCallback);
                return;
            }
            successCallback(new Array());
        };
        this.formValidation = this.defaultFormValidation;
        this.onOrderCreated = this.onOrderCreated.bindAsEventListener(this);
        this.onReceivedHiddenFields = this.gatherHiddenFields.bindAsEventListener(this);
        this.onReceivedVisibleFields = this.displayVisibleFields.bindAsEventListener(this);
        this.onReceiveJavascript = this.runAjaxAuthorization.bindAsEventListener(this);
        if (typeof checkout != "undefined" && typeof Review != "undefined" && typeof FireCheckout == "undefined" && (typeof IWD == "undefined" || typeof IWD.OPC == "undefined") && typeof OneStepCheckoutLoginPopup == "undefined") {
            checkout.accordion.openSection = checkout.accordion.openSection.wrap(this.opcGotoSection.bind(this));
            Review.prototype.save = Review.prototype.save.wrap(this.beforePlaceOrder.bind(this));
            Payment.prototype.save = Payment.prototype.save.wrap(this.beforePaymentSave.bind(this));
            if (typeof shippingMethod != "undefined") {
                shippingMethod.onSave = this.loadPaymentForm.bindAsEventListener(this);
                shippingMethod.saveUrl = this.saveShippingUrl;
            }
        } else if (typeof AWOnestepcheckoutForm != "undefined") {
            awOSCForm.placeOrderButton.stopObserving("click");
            AWOnestepcheckoutPayment.prototype.savePayment = AWOnestepcheckoutPayment.prototype.savePayment.wrap(this.awcheckoutPaymentSave.bind(this));
            AWOnestepcheckoutForm.prototype.placeOrder = AWOnestepcheckoutForm.prototype.placeOrder.wrap(this.awcheckoutPlaceOrder.bind(this));
            awOSCForm.placeOrderButton.observe("click", awOSCForm.placeOrder.bind(awOSCForm));
            this.formValidation = function(successCallback, failureCallback) {
                if (!awOSCForm.validate()) {
                    failureCallback({}, []);
                    return;
                }
                this.defaultFormValidation(successCallback, failureCallback);
            };
        } else if (typeof checkout != "undefined" && typeof checkout.LightcheckoutSubmit != "undefined") {
            Lightcheckout.prototype.LightcheckoutSubmit = Lightcheckout.prototype.LightcheckoutSubmit.wrap(this.lightcheckoutBeforePaymentSave.bind(this));
            Lightcheckout.prototype.saveorder = Lightcheckout.prototype.saveorder.wrap(this.lightcheckoutSaveOrder.bind(this));
            this.formValidation = function(successCallback, failureCallback) {
                if (!checkoutForm.validator.validate()) {
                    failureCallback({}, []);
                    return;
                }
                this.defaultFormValidation(successCallback, failureCallback);
            };
        } else if (typeof FireCheckout != "undefined") {
            FireCheckout.prototype.save = FireCheckout.prototype.save.wrap(this.firecheckoutSave.bind(this));
            FireCheckout.prototype.update = FireCheckout.prototype.update.wrap(this.firecheckoutUpdate.bind(this));
            FireCheckout.prototype.setResponse = FireCheckout.prototype.setResponse.wrap(this.firecheckoutSetResponse.bind(this));
            this.formValidation = function(successCallback, failureCallback) {
                if (checkout.validate && !checkout.validate() || checkout.validator.validate && !checkout.validator.validate()) {
                    failureCallback({}, []);
                    return;
                }
                this.defaultFormValidation(successCallback, failureCallback);
            };
        } else if (typeof IWD != "undefined" && typeof IWD.OPC != "undefined") {
            IWD.OPC.savePayment = IWD.OPC.savePayment.wrap(this.iwdSavePayment.bind(this));
            IWD.OPC.saveOrder = IWD.OPC.saveOrder.wrap(this.iwdSaveOrder.bind(this));
            IWD.OPC.prepareOrderResponse = IWD.OPC.prepareOrderResponse.wrap(this.iwdPrepareOrderResponse.bind(this));
            this.formValidation = function(successCallback, failureCallback) {
                successCallback(new Array());
            };
        } else if (typeof oscPlaceOrder != "undefined") {
            window.save_shipping_method = window.save_shipping_method.wrap(this.magestoreSaveShippingMethod.bind(this));
            window.oscPlaceOrder = window.oscPlaceOrder.wrap(this.magestorePlaceOrder.bind(this));
            this.formValidation = function(successCallback, failureCallback) {
                if (!(new Validation("one-step-checkout-form").validate() && checkpayment())) {
                    failureCallback({}, []);
                    return;
                }
                this.defaultFormValidation(successCallback, failureCallback);
            };
        } else if (typeof OnePage != "undefined") {
            PaymentMethod.prototype.init = PaymentMethod.prototype.init.wrap(this.iwdSuitePaymentMethodInit.bind(this));
            PaymentMethod.prototype.decorateFields = PaymentMethod.prototype.decorateFields.wrap(this.iwdSuitePaymentMethodDecorateFields.bind(this));
            PaymentMethod.prototype.selectPaymentMethod = PaymentMethod.prototype.selectPaymentMethod.wrap(this.iwdSuiteSelectPaymentMethod.bind(this));
            PaymentMethod.prototype.saveSection = PaymentMethod.prototype.saveSection.wrap(this.iwdSuitePaymentMethodSaveSection.bind(this));
            OnePage.prototype.saveSection = OnePage.prototype.saveSection.wrap(this.iwdSuiteSaveSection.bind(this));
            OnePage.prototype.tryPlaceOrder = OnePage.prototype.tryPlaceOrder.wrap(this.iwdSuiteTryPlaceOrder.bind(this));
            OnePage.prototype.parseSuccessResult = OnePage.prototype.parseSuccessResult.wrap(this.iwdSuiteParseSuccessResult.bind(this));
        } else {
            var onestepcheckoutPlaceOrder = $("onestepcheckout-place-order");
            if (typeof onestepcheckoutPlaceOrder != "undefined") {
                onestepcheckoutPlaceOrder.observe("click", this.createOrder.bind(this));
                var form = new VarienForm("onestepcheckout-form");
                this.originalFunction = Validation.prototype.validate.bind(form.validator);
                Validation.prototype.validate = Validation.prototype.validate.wrap(this.onestepValidate.bind(this));
                this.formValidation = function(successCallback, failureCallback) {
                    if (!this.originalFunction()) {
                        failureCallback({}, []);
                        return;
                    }
                    this.defaultFormValidation(successCallback, failureCallback);
                };
            } else {
                console.log("You should use either one of the supported one page checkouts or the magento default onestepcheckout.");
            }
        }
    },
    loadPaymentForm: function(transport) {
        if (transport && transport.responseText) {
            try {
                response = eval("(" + transport.responseText + ")");
            } catch (e) {
                response = {};
            }
        }
        shippingMethod.nextStep(transport);
        if (!response.error && response.update_section.js) {
            eval.call(window, response.update_section.js);
        }
    },
    loadAliasData: function(element) {
        var sel = element;
        var value = sel.options[sel.selectedIndex].value;
        new Ajax.Request(this.visibleFieldsUrl, {
            method: "get",
            parameters: "alias_id=" + value + "&payment_method=" + this.methodCode,
            onSuccess: this.onReceivedVisibleFields
        });
    },
    displayVisibleFields: function(transport) {
        if (transport && transport.responseText) {
            try {
                response = eval("(" + transport.responseText + ")");
            } catch (e) {
                response = {};
            }
        }
        if (response.error) {
            alert(response.message);
            return false;
        }
        var container = $("payment_form_fields_" + this.methodCode);
        container.update(response.html);
        eval.call(window, response.js);
    },
    isModulePaymentMethod: function() {
        var result = false;
        var currentMethod;
        if (typeof payment != "undefined" && typeof payment.currentMethod != "undefined") {
            currentMethod = payment.currentMethod;
        } else if (typeof awOSCPayment != "undefined" && typeof awOSCPayment.currentMethod != "undefined") {
            currentMethod = awOSCPayment.currentMethod;
        } else if (typeof oscPlaceOrder != "undefined") {
            currentMethod = $RF(form, "payment[method]");
        } else if (typeof PaymentMethod != "undefined") {
            currentMethod = Singleton.get(PaymentMethod).getPaymentMethodCode();
        }
        if (currentMethod && currentMethod == this.methodCode) {
            if (document.getElementById(currentMethod + "_authorization_method")) {
                result = true;
            }
        }
        return result;
    },
    isAuthorization: function(method) {
        var result = false;
        var currentMethod;
        if (typeof payment != "undefined" && typeof payment.currentMethod != "undefined") {
            currentMethod = payment.currentMethod;
        } else if (typeof awOSCPayment != "undefined" && typeof awOSCPayment.currentMethod != "undefined") {
            currentMethod = awOSCPayment.currentMethod;
        } else if (typeof oscPlaceOrder != "undefined") {
            currentMethod = $RF(form, "payment[method]");
        } else if (typeof PaymentMethod != "undefined") {
            currentMethod = Singleton.get(PaymentMethod).getPaymentMethodCode();
        }
        if (currentMethod && currentMethod == this.methodCode) {
            if (document.getElementById(currentMethod + "_authorization_method")) {
                if (document.getElementById(currentMethod + "_authorization_method").value == method) {
                    result = true;
                }
            }
        }
        return result;
    },
    requestHiddenFields: function(transport, onComplete) {
        var response = false;
        if (transport && transport.responseText) {
            try {
                response = eval("(" + transport.responseText + ")");
            } catch (e) {
                response = {};
            }
        } else if (typeof transport == "object") {
            response = transport;
        }
        if (response && typeof response == "object") {
            if (!response.success) {
                var msg = response.error_messages;
                if (typeof msg == "object") {
                    msg = msg.join("\n");
                }
                onComplete();
                if (msg) {
                    alert(msg);
                }
            } else if (this.isAuthorization("hidden")) {
                new Ajax.Request(this.hiddenFieldsUrl, {
                    onSuccess: this.onReceivedHiddenFields,
                    onFailure: onComplete
                });
            } else if (this.isAuthorization("ajax")) {
                new Ajax.Request(this.javascriptUrl, {
                    onSuccess: this.onReceiveJavascript,
                    onFailure: onComplete
                });
            } else if (this.isAuthorization("server")) {
                this.sendFieldsToUrl(this.serverUrl);
            } else {
                this.sendFieldsToUrl(response.redirect);
            }
        }
    },
    runAjaxAuthorization: function(transport) {
        var data = eval("(" + transport.responseText + ")");
        if (typeof IWD != "undefined") {
            IWD.OPC.Checkout.hideLoader();
            IWD.OPC.Checkout.unlockPlaceOrder();
            IWD.OPC.saveOrderStatus = false;
        }
        if (data.error == "no") {
            var javascriptUrl = data.javascriptUrl;
            var callbackFunction = data.callbackFunction;
            this.loadJavascript(javascriptUrl, function() {
                callbackFunction(this.formFields);
            }.bind(this));
        } else {
            alert(data.message);
        }
    },
    gatherHiddenFields: function(transport) {
        var formInfo = eval("(" + transport.responseText + ")");
        this.extendMaps(this.formFields, formInfo.fields);
        this.sendFieldsToUrl(formInfo.actionUrl);
    },
    sendFieldsToUrl: function(url, params) {
        if (typeof url == "undefined") {
            alert("Something went wrong, checkout will reload, please try again.");
            window.location.reload();
            return;
        }
        var me = this, tmpForm = new Element("form", {
            action: url,
            method: "post",
            id: "customweb_payunitycw_form"
        });
        $$("body")[0].insert(tmpForm);
        var fields = $H(this.formFields);
        fields.each(function(pair) {
            me.insertHiddenField(tmpForm, pair.key, pair.value);
        }, this);
        if (params) {
            params = $H(params);
            params.each(function(pair) {
                me.insertHiddenField(tmpForm, pair.key, pair.value);
            }, this);
        }
        tmpForm.submit();
    },
    insertHiddenField: function(form, key, value) {
        if (value == null) {
            value = "";
        }
        if (typeof value == "object") {
            for (var i = 0; i < value.length; i++) {
                form.insert(new Element("input", {
                    type: "hidden",
                    name: key + "[]",
                    value: value[i]
                }));
            }
        } else {
            form.insert(new Element("input", {
                type: "hidden",
                name: key,
                value: value
            }));
        }
    },
    extendMaps: function(destination, source) {
        for (var property in source) {
            if (source.hasOwnProperty(property)) {
                destination[property] = source[property];
            }
        }
        return destination;
    },
    removeErrorMsg: function() {
        var messageContainer = $$(".messages");
        messageContainer.each(function(item) {
            item.update("");
        });
    },
    savePaymentInfoInBrowser: function() {
        if (this.paymentInfoSaved) {
            return;
        }
        this.paymentInfoSaved = true;
        var fields = {};
        var tmp = "#payment_form_" + this.methodCode;
        var remove = this.methodCode + "[";
        var inputs = $$(tmp + " input");
        inputs.each(function(i) {
            var name = i.name.replace(remove, "");
            name = name.replace("]", "");
            if (i.readAttribute("data-cloned-element-id")) {
                i.value = "";
                i.removeClassName("required-entry");
            } else if (name != "") {
                if (i.type == "radio") {
                    if (i.checked) {
                        fields[name] = i.value;
                    }
                } else {
                    fields[name] = i.value;
                    i.value = "";
                }
                i.removeClassName("required-entry");
            }
        });
        var selects = $$(tmp + " select");
        selects.each(function(s) {
            var name = s.name.replace(remove, "");
            name = name.replace("]", "");
            fields[name] = s.options[s.selectedIndex].value;
            s.selectedIndex = 0;
            s.removeClassName("required-entry");
            s.removeClassName("validate-select");
        });
        this.removeErrorMsg();
        this.formFields = fields;
    },
    refillPaymentForm: function(fields) {
        this.paymentInfoSaved = false;
        if (fields) {
            var tmp = "#payment_form_" + this.methodCode;
            var remove = this.methodCode + "[";
            $$(tmp + " input").each(function(i) {
                if (i.type != "hidden" || i.readAttribute("originalElement")) {
                    var name = i.name.replace(remove, "");
                    name = name.replace("]", "");
                    if (i.type == "radio") {
                        if (i.value == fields[name]) {
                            i.checked = true;
                        }
                    } else {
                        i.value = fields[name];
                    }
                    if (i.readAttribute("originalElement")) {
                        $(i.readAttribute("originalElement")).value = i.value;
                    }
                }
            });
            $$(tmp + " select").each(function(s) {
                var name = s.name.replace(remove, "");
                name = name.replace("]", "");
                s.value = fields[name];
            });
        }
    },
    loadJavascript: function(url, callback) {
        var head = document.getElementsByTagName("head")[0] || document.documentElement;
        var script = document.createElement("script");
        script.src = url;
        var done = false;
        script.onload = script.onreadystatechange = function() {
            if (!done && (!this.readyState || this.readyState === "loaded" || this.readyState === "complete")) {
                done = true;
                callback();
                script.onload = script.onreadystatechange = null;
                if (head && script.parentNode) {
                    head.removeChild(script);
                }
            }
        };
        head.insertBefore(script, head.firstChild);
    },
    beforePaymentSave: function(callOriginal) {
        if (this.isModulePaymentMethod()) {
            checkout.setLoadWaiting("payment");
            this.formValidation(function(valid) {
                this.beforePaymentSaveValidationSuccess(callOriginal);
            }.bind(this), function(errors, valid) {
                this.beforePaymentSaveValidationFailure(errors, valid);
            }.bind(this));
            return false;
        }
        callOriginal();
    },
    beforePaymentSaveValidationSuccess: function(callOriginal) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
        }
        checkout.setLoadWaiting(false);
        callOriginal();
    },
    beforePaymentSaveValidationFailure: function(errors, valid) {
        if (Object.keys(errors).length > 0) {
            alert(errors[Object.keys(errors)[0]]);
        }
        checkout.setLoadWaiting(false);
    },
    beforePlaceOrder: function(callOriginal) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            if (checkout.loadWaiting != false) return;
            checkout.setLoadWaiting("review");
            var params = Form.serialize(payment.form);
            if (review.agreementsForm) {
                params += "&" + Form.serialize(review.agreementsForm);
            }
            params.save = true;
            var request = new Ajax.Request(review.saveUrl, {
                method: "post",
                parameters: params,
                onSuccess: this.onOrderCreated,
                onFailure: function() {
                    review.onComplete();
                    checkout.ajaxFailure();
                }
            });
        } else {
            callOriginal();
        }
    },
    onOrderCreated: function(transport) {
        return this.requestHiddenFields(transport, review.onComplete);
    },
    opcGotoSection: function(callOriginal, section) {
        if (typeof section != "string") {
            section = section.id;
        }
        if (section == "opc-payment" && $("payment_form_" + this.methodCode)) {
            this.refillPaymentForm(this.formFields);
            if ($("payment_form_" + this.methodCode)) {
                $("payment_form_" + this.methodCode).observe("payment-method:switched", this.onMethodSwitch.bind(this));
            }
        }
        callOriginal(section);
    },
    iframeReloaded: false,
    onMethodSwitch: function() {
        if (this.iframeReloaded) return;
        var iframes = $$("#payment_form_" + this.methodCode + " iframe");
        iframes.each(function(iframe) {
            if (iframe.src) {
                iframe.src = iframe.src;
            }
        });
        this.iframeReloaded = true;
    },
    awcheckoutPaymentSave: function(callOriginal) {
        var fields = {};
        var tmp = "#payment_form_" + this.methodCode;
        var remove = this.methodCode + "[";
        var inputs = $$(tmp + " input");
        var selects = $$(tmp + " select");
        inputs.each(function(i) {
            if (i.readAttribute("data-cloned-element-id")) {
                i.value = "";
            } else if (i.type != "hidden" || i.readAttribute("originalElement")) {
                var name = i.name.replace(remove, "");
                name = name.replace("]", "");
                fields[name] = i.value;
                i.value = "";
            }
        });
        selects.each(function(s) {
            var name = s.name.replace(remove, "");
            name = name.replace("]", "");
            fields[name] = s.options[s.selectedIndex].value;
            s.selectedIndex = 0;
        });
        this.removeErrorMsg();
        callOriginal();
        this.refillPaymentForm(fields);
    },
    awcheckoutPlaceOrder: function(callOriginal) {
        if (this.isModulePaymentMethod()) {
            awOSCForm.showOverlay();
            awOSCForm.showPleaseWaitNotice();
            awOSCForm.disablePlaceOrderButton();
            this.formValidation(function(valid) {
                this.awcheckoutPlaceOrderValidationSuccess(callOriginal);
            }.bind(this), function(errors, valid) {
                this.awcheckoutPlaceOrderValidationFailure(errors, valid);
            }.bind(this));
            return false;
        }
        callOriginal();
    },
    awcheckoutPlaceOrderValidationSuccess: function(callOriginal) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
            var parameters = Form.serialize(awOSCForm.form.form, true);
            this.refillPaymentForm(this.formFields);
            new Ajax.Request(awOSCForm.placeOrderUrl, {
                method: "post",
                parameters: parameters,
                onComplete: function(transport) {
                    if (transport && transport.responseText) {
                        try {
                            response = eval("(" + transport.responseText + ")");
                        } catch (e) {
                            response = {};
                        }
                        if (response.redirect) {
                            this.requestHiddenFields(transport);
                            return;
                        }
                        var msg = response.messages;
                        if (typeof msg == "object") {
                            msg = msg.join("\n");
                        }
                        if (msg) {
                            alert(msg);
                        }
                        awOSCForm.enablePlaceOrderButton();
                        awOSCForm.hidePleaseWaitNotice();
                        awOSCForm.hideOverlay();
                    }
                }.bind(this)
            });
        } else {
            callOriginal();
        }
    },
    awcheckoutPlaceOrderValidationFailure: function(errors, valid) {
        if (Object.keys(errors).length > 0) {
            alert(errors[Object.keys(errors)[0]]);
        }
        awOSCForm.enablePlaceOrderButton();
        awOSCForm.hidePleaseWaitNotice();
        awOSCForm.hideOverlay();
    },
    lightcheckoutBeforePaymentSave: function(callOriginal) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
        }
        callOriginal();
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.refillPaymentForm(this.formFields);
        }
    },
    lightcheckoutSaveOrder: function(callOriginal) {
        if (this.isModulePaymentMethod()) {
            this.formValidation(function(valid) {
                this.lightcheckoutSaveOrderValidationSuccess(callOriginal);
            }.bind(this), function(errors, valid) {
                this.lightcheckoutSaveOrderValidationFailure(errors, valid);
            }.bind(this));
            return false;
        }
        callOriginal();
    },
    lightcheckoutSaveOrderValidationSuccess: function(callOriginal) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
            var params = checkout.getFormData();
            var request = new Ajax.Request(checkout.save_order_url, {
                method: "post",
                parameters: params,
                onSuccess: function(transport) {
                    eval("var response = " + transport.responseText);
                    if (response.redirect) {
                        this.requestHiddenFields(transport, checkout.hideLoadinfo.bind(checkout));
                        return;
                    } else if (response.error) {
                        if (response.message) {
                            alert(response.message);
                        }
                    } else if (response.update_section) {
                        this.accordion.currentSection = "opc-review";
                        this.innerHTMLwithScripts($("checkout-update-section"), response.update_section.html);
                    }
                    checkout.hideLoadinfo();
                }.bind(this),
                onFailure: function() {}
            });
        } else {
            callOriginal();
        }
    },
    lightcheckoutSaveOrderValidationFailure: function(errors, valid) {
        if (Object.keys(errors).length > 0) {
            alert(errors[Object.keys(errors)[0]]);
        }
        checkout.hideLoadinfo();
    },
    firecheckoutSave: function(callOriginal, urlSuffix, forceSave) {
        if (this.isModulePaymentMethod()) {
            checkout.setLoadWaiting(true);
            this.formValidation(function(valid) {
                this.firecheckoutSaveValidationSuccess(callOriginal, urlSuffix, forceSave);
            }.bind(this), function(errors, valid) {
                this.firecheckoutSaveValidationFailure(errors, valid);
            }.bind(this));
            return false;
        }
        callOriginal(urlSuffix, forceSave);
    },
    firecheckoutSaveValidationSuccess: function(callOriginal, urlSuffix, forceSave) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
        }
        checkout.setLoadWaiting(false);
        callOriginal(urlSuffix, forceSave);
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.refillPaymentForm(this.formFields);
        }
    },
    firecheckoutSaveValidationFailure: function(errors, valid) {
        checkout.setLoadWaiting(false);
        if (Object.keys(errors).length > 0) {
            alert(errors[Object.keys(errors)[0]]);
        }
    },
    firecheckoutUpdate: function(callOriginal, url, params, callback) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
        }
        callOriginal(url, params, callback);
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.refillPaymentForm(this.formFields);
        }
    },
    firecheckoutSetResponse: function(callOriginal, transport) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            try {
                response = transport.responseText.evalJSON();
            } catch (err) {
                alert("An error has occured during request processing. Try again please");
                checkout.setLoadWaiting(false);
                $("review-please-wait").hide();
                return false;
            }
            if (response.redirect || response.order_created) {
                this.requestHiddenFields(transport);
            } else {
                callOriginal(transport);
            }
        } else {
            callOriginal(transport);
        }
    },
    iwdSavePayment: function(callOriginal) {
        if (this.isModulePaymentMethod()) {
            if (!IWD.OPC.saveOrderStatus) {
                if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
                    this.savePaymentInfoInBrowser();
                }
                callOriginal();
                if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
                    this.refillPaymentForm(this.formFields);
                }
            } else {
                setTimeout(function() {
                    IWD.OPC.Checkout.showLoader();
                }, 600);
                IWD.OPC.Checkout.lockPlaceOrder();
                IWD.OPC.Checkout.showLoader();
                this.defaultFormValidation(function(valid) {
                    this.iwdSavePaymentValidationSuccess(callOriginal);
                }.bind(this), function(errors, valid) {
                    this.iwdSavePaymentValidationFailure(errors, valid);
                }.bind(this));
            }
            return;
        }
        callOriginal();
    },
    iwdSavePaymentValidationSuccess: function(callOriginal) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
        }
        callOriginal();
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.refillPaymentForm(this.formFields);
        }
    },
    iwdSavePaymentValidationFailure: function(errors, valid) {
        IWD.OPC.Checkout.hideLoader();
        IWD.OPC.Checkout.unlockPlaceOrder();
        IWD.OPC.saveOrderStatus = false;
        if (Object.keys(errors).length > 0) {
            alert(errors[Object.keys(errors)[0]]);
        }
        return;
    },
    iwdSaveOrder: function(callOriginal) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
        }
        callOriginal();
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.refillPaymentForm(this.formFields);
        }
    },
    iwdPrepareOrderResponse: function(callOriginal, response) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            if (response.redirect) {
                response.success = true;
                this.requestHiddenFields(response);
            } else {
                callOriginal(response);
            }
        } else {
            callOriginal(response);
        }
    },
    magestoreSaveShippingMethod: function(callOriginal, shipping_method_url, update_shipping_payment, update_shipping_review) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
        }
        callOriginal(shipping_method_url, update_shipping_payment, update_shipping_review);
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.refillPaymentForm(this.formFields);
        }
    },
    magestorePlaceOrder: function(callOriginal, element) {
        if (this.isModulePaymentMethod()) {
            element.disabled = true;
            disable_payment();
            $("onestepcheckout-place-order-loading").show();
            $("onestepcheckout-button-place-order").removeClassName("onestepcheckout-btn-checkout");
            $("onestepcheckout-button-place-order").addClassName("place-order-loader");
            this.formValidation(function(valid) {
                this.magestorePlaceOrderValidationSuccess(callOriginal, element);
            }.bind(this), function(errors, valid) {
                this.magestorePlaceOrderValidationFailure(element, errors, valid);
            }.bind(this));
            return false;
        }
        callOriginal(element);
    },
    magestorePlaceOrderValidationSuccess: function(callOriginal, element) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
            var form = $("one-step-checkout-form");
            var formUrl = form.readAttribute("action");
            formUrl = formUrl.slice(0, -1) + "/";
            form.writeAttribute("action", "javascript:void(0);");
            var params = Form.serialize(form);
            this.refillPaymentForm(this.formFields);
            var request = new Ajax.Request(formUrl, {
                method: "post",
                parameters: params,
                onSuccess: function(transport) {
                    eval("var response = " + transport.responseText);
                    if (response.success) {
                        this.requestHiddenFields(transport, function() {
                            $("onestepcheckout-place-order-loading").hide();
                            $("onestepcheckout-button-place-order").removeClassName("place-order-loader");
                            $("onestepcheckout-button-place-order").addClassName("onestepcheckout-btn-checkout");
                            element.disabled = false;
                        });
                        return;
                    } else if (response.error) {
                        if (response.message) {
                            alert(response.message);
                        }
                    }
                    $("onestepcheckout-place-order-loading").hide();
                    $("onestepcheckout-button-place-order").removeClassName("place-order-loader");
                    $("onestepcheckout-button-place-order").addClassName("onestepcheckout-btn-checkout");
                    element.disabled = false;
                }.bind(this),
                onFailure: function() {}
            });
        } else {
            callOriginal(element);
        }
    },
    magestorePlaceOrderValidationFailure: function(element, errors, valid) {
        if (Object.keys(errors).length > 0) {
            alert(errors[Object.keys(errors)[0]]);
        }
        $("onestepcheckout-place-order-loading").hide();
        $("onestepcheckout-button-place-order").removeClassName("place-order-loader");
        $("onestepcheckout-button-place-order").addClassName("onestepcheckout-btn-checkout");
        element.disabled = false;
    },
    iwdSuiteParseSuccessResult: function(callOriginal, result) {
        if ((this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) && (typeof result.redirect_url !== "undefined" && result.redirect_url)) {
            console.log(result);
            result.success = result.status;
            this.requestHiddenFields(result);
            return false;
        } else {
            return callOriginal(result);
        }
    },
    iwdSuiteTryPlaceOrder: function(callOriginal) {
        if (this.isModulePaymentMethod()) {
            this.defaultFormValidation(function(isValid) {
                Singleton.get(OnePage).toggleCheckoutNotification(false);
                callOriginal();
            }, function(errors, isValid) {
                Singleton.get(OnePage).toggleCheckoutNotification(true);
            });
        } else {
            callOriginal();
        }
    },
    iwdSuitePaymentMethodSaveSection: function(callOriginal) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
        }
        callOriginal();
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.refillPaymentForm(this.formFields);
        }
    },
    iwdSuiteValidatePaymentMethod: function(callOriginal) {
        callOriginal();
        if (this.isModulePaymentMethod()) {
            this.defaultFormValidation(function(isValid) {
                Singleton.get(PaymentMethod).toggleFormValidClass(isValid);
                Singleton.get(PaymentMethod).togglePlaceOrderButton();
            }, function(errors, isValid) {
                Singleton.get(PaymentMethod).toggleFormValidClass(isValid);
                Singleton.get(PaymentMethod).togglePlaceOrderButton();
            });
        }
    },
    iwdSuiteSaveSection: function(callOriginal) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
        }
        callOriginal();
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.refillPaymentForm(this.formFields);
        }
    },
    iwdSuitePaymentMethodDecorateFields: function(callOriginal) {
        var paymentForm = $("payment_form_" + Singleton.get(PaymentMethod).getPaymentMethodCode());
        if (paymentForm) {
            paymentForm.select("li.control-group").each(function(a) {
                a.addClassName("iwd_opc_universal_wrapper");
            });
            paymentForm.select(".input-text").each(function(a) {
                a.addClassName("iwd_opc_field iwd_opc_input");
            });
            paymentForm.select(".select").each(function(a) {
                a.addClassName("iwd_opc_select iwd_opc_field");
            });
            paymentForm.show();
        }
        callOriginal();
    },
    iwdSuitePaymentMethodInit: function(callOriginal) {
        callOriginal();
        this.iwdSuitePaymentMethodDecorateFields(function() {});
    },
    iwdSuiteSelectPaymentMethod: function(callOriginal) {
        callOriginal();
        this.iwdSuitePaymentMethodDecorateFields(function() {});
    },
    submittingOrder: false,
    onestepValidate: function(callOriginal) {
        if (this.isModulePaymentMethod() && !this.submittingOrder) {
            return false;
        }
        return callOriginal();
    },
    createOrder: function(event) {
        if (this.isModulePaymentMethod() && !this.submittingOrder) {
            this.formValidation(function(valid) {
                this.createOrderSuccessCallback(event);
            }.bind(this), function(errors, valid) {
                this.createOrderFailureCallback(errors, valid);
            }.bind(this));
            return;
        }
    },
    createOrderSuccessCallback: function(event) {
        if (this.isAuthorization("hidden") || this.isAuthorization("server") || this.isAuthorization("ajax")) {
            this.savePaymentInfoInBrowser();
            var form = $("onestepcheckout-form");
            var formUrl = form.readAttribute("action");
            formUrl = formUrl.slice(0, -1) + "/";
            this.disableOneStepCheckoutSubmitButton();
            var params = Form.serialize(form);
            this.refillPaymentForm(this.formFields);
            var request = new Ajax.Request(formUrl, {
                method: "post",
                parameters: params,
                onSuccess: this.checkOrderStatus.bindAsEventListener(this),
                onFailure: checkout.ajaxFailure.bind(checkout)
            });
        } else {
            this.submittingOrder = true;
            var element = $("onestepcheckout-place-order");
            if (document.createEvent) {
                var oEvent = document.createEvent("MouseEvents");
                oEvent.initMouseEvent("click", true, true, document.defaultView, 0, 0, 0, 0, 0, false, false, false, false, 0, element);
                element.dispatchEvent(oEvent);
            } else {
                var oEvent = Object.extend(document.createEventObject(), {});
                element.fireEvent("onclick", oEvent);
            }
            setTimeout(function() {
                this.submittingOrder = false;
            }.bind(this), 500);
        }
    },
    createOrderFailureCallback: function(errors, valid) {
        if (Object.keys(errors).length > 0) {
            alert(errors[Object.keys(errors)[0]]);
            this.enableOneStepCheckoutSubmitButton();
        }
    },
    checkOrderStatus: function(transport) {
        var html = transport.responseText;
        try {
            response = eval("(" + html + ")");
        } catch (e) {
            response = {};
        }
        if (response.success) {
            this.requestHiddenFields(transport);
        } else {
            var formStartTag = '<form id="onestepcheckout-form"';
            var formEndTag = "</form>";
            var start = html.indexOf(formStartTag);
            var stop = html.indexOf(formEndTag, start) + formEndTag.length;
            var formData = html.substr(start, stop - start);
            $("onestepcheckout-form").replace(formData);
            $("onestepcheckout-place-order").observe("click", this.createOrder.bind(this));
        }
    },
    disableOneStepCheckoutSubmitButton: function() {
        var submitelement = $("onestepcheckout-place-order");
        submitelement.removeClassName("orange").addClassName("grey");
        submitelement.disabled = true;
        var loaderelement = new Element("span").addClassName("onestepcheckout-place-order-loading").update(this.processingText);
        submitelement.parentNode.appendChild(loaderelement);
    },
    enableOneStepCheckoutSubmitButton: function() {
        var submitelement = $("onestepcheckout-place-order");
        submitelement.removeClassName("grey").addClassName("orange");
        submitelement.disabled = false;
        if ($("onestepcheckout-place-order-loading") != undefined) {
            $("onestepcheckout-place-order-loading").remove();
        }
    }
});

if (!Customweb.CheckoutPreload) {
    Customweb.CheckoutPreloadFlag = false;
    Customweb.CheckoutPreload = Class.create({
        initialize: function(onepagePreloadUrl) {
            this.onepagePreloadUrl = onepagePreloadUrl;
            if (!Customweb.CheckoutPreloadFlag) {
                if (typeof checkout != "undefined" && typeof Review != "undefined" && typeof FireCheckout == "undefined" && typeof IWD == "undefined") {
                    this.preloadCheckout();
                }
                Customweb.CheckoutPreloadFlag = true;
            }
        },
        hasLoadFailed: function() {
            if (typeof customweb_on_load_called == "undefined") {
                var params = document.URL.toQueryParams();
                if (params.hasOwnProperty("loadFailed")) {
                    var loadFailed = params["loadFailed"];
                    if (loadFailed != "undefined" && loadFailed == "true") {
                        return true;
                    }
                }
            }
            return false;
        },
        preloadCheckout: function() {
            var me = this;
            if (this.hasLoadFailed()) {
                if (checkout && checkout.gotoSection) {
                    checkout.gotoSection("payment");
                    if (this.onepagePreloadUrl) {
                        checkout.setLoadWaiting("payment");
                        new Ajax.Request(this.onepagePreloadUrl, {
                            onSuccess: function(transport) {
                                if (transport && transport.responseText) {
                                    try {
                                        response = eval("(" + transport.responseText + ")");
                                    } catch (e) {
                                        response = {};
                                    }
                                }
                                if (response.update_section) {
                                    for (var i = 0; i < response.update_section.length; i++) {
                                        if ($("checkout-" + response.update_section[i].name + "-load")) {
                                            $("checkout-" + response.update_section[i].name + "-load").update(response.update_section[i].html);
                                        }
                                    }
                                }
                                me.allowCheckoutSteps("payment");
                                checkout.setLoadWaiting(false);
                            }
                        });
                    } else {
                        me.allowCheckoutSteps("payment");
                    }
                }
            }
        },
        allowCheckoutSteps: function(gotoSection) {
            for (var s = 0; s < checkout.steps.length; s++) {
                if (checkout.steps[s] == gotoSection) {
                    break;
                }
                if (document.getElementById("opc-" + checkout.steps[s])) {
                    document.getElementById("opc-" + checkout.steps[s]).addClassName("allow");
                }
            }
        }
    });
}

if (typeof Product == "undefined") {
    var Product = {};
}

Product.Bundle = Class.create();

Product.Bundle.prototype = {
    initialize: function(config) {
        this.config = config;
        if (config.defaultValues) {
            for (var option in config.defaultValues) {
                if (this.config["options"][option].isMulti) {
                    var selected = new Array();
                    for (var i = 0; i < config.defaultValues[option].length; i++) {
                        selected.push(config.defaultValues[option][i]);
                    }
                    this.config.selected[option] = selected;
                } else {
                    this.config.selected[option] = new Array(config.defaultValues[option] + "");
                }
            }
        }
        this.reloadPrice();
    },
    changeSelection: function(selection) {
        var parts = selection.id.split("-");
        if (this.config["options"][parts[2]].isMulti) {
            selected = new Array();
            if (selection.tagName == "SELECT") {
                for (var i = 0; i < selection.options.length; i++) {
                    if (selection.options[i].selected && selection.options[i].value != "") {
                        selected.push(selection.options[i].value);
                    }
                }
            } else if (selection.tagName == "INPUT") {
                selector = parts[0] + "-" + parts[1] + "-" + parts[2];
                selections = $$("." + selector);
                for (var i = 0; i < selections.length; i++) {
                    if (selections[i].checked && selections[i].value != "") {
                        selected.push(selections[i].value);
                    }
                }
            }
            this.config.selected[parts[2]] = selected;
        } else {
            if (selection.value != "") {
                this.config.selected[parts[2]] = new Array(selection.value);
            } else {
                this.config.selected[parts[2]] = new Array();
            }
            this.populateQty(parts[2], selection.value);
            var tierPriceElement = $("bundle-option-" + parts[2] + "-tier-prices"), tierPriceHtml = "";
            if (selection.value != "" && this.config.options[parts[2]].selections[selection.value].customQty == 1) {
                tierPriceHtml = this.config.options[parts[2]].selections[selection.value].tierPriceHtml;
            }
            tierPriceElement.update(tierPriceHtml);
        }
        this.reloadPrice();
    },
    reloadPrice: function() {
        var calculatedPrice = 0;
        var dispositionPrice = 0;
        var includeTaxPrice = 0;
        for (var option in this.config.selected) {
            if (this.config.options[option]) {
                for (var i = 0; i < this.config.selected[option].length; i++) {
                    var prices = this.selectionPrice(option, this.config.selected[option][i]);
                    calculatedPrice += Number(prices[0]);
                    dispositionPrice += Number(prices[1]);
                    includeTaxPrice += Number(prices[2]);
                }
            }
        }
        if (taxCalcMethod == CACL_TOTAL_BASE) {
            var calculatedPriceFormatted = calculatedPrice.toFixed(10);
            var includeTaxPriceFormatted = includeTaxPrice.toFixed(10);
            var tax = includeTaxPriceFormatted - calculatedPriceFormatted;
            calculatedPrice = includeTaxPrice - Math.round(tax * 100) / 100;
        }
        if (this.config.priceType == "0") {
            calculatedPrice = Math.round(calculatedPrice * 100) / 100;
            dispositionPrice = Math.round(dispositionPrice * 100) / 100;
            includeTaxPrice = Math.round(includeTaxPrice * 100) / 100;
        }
        var event = $(document).fire("bundle:reload-price", {
            price: calculatedPrice,
            priceInclTax: includeTaxPrice,
            dispositionPrice: dispositionPrice,
            bundle: this
        });
        if (!event.noReloadPrice) {
            optionsPrice.specialTaxPrice = "true";
            optionsPrice.changePrice("bundle", calculatedPrice);
            optionsPrice.changePrice("nontaxable", dispositionPrice);
            optionsPrice.changePrice("priceInclTax", includeTaxPrice);
            optionsPrice.reload();
        }
        return calculatedPrice;
    },
    selectionPrice: function(optionId, selectionId) {
        if (selectionId == "" || selectionId == "none" || typeof this.config.options[optionId].selections[selectionId] == "undefined") {
            return 0;
        }
        var qty = null;
        var tierPriceInclTax, tierPriceExclTax;
        if (this.config.options[optionId].selections[selectionId].customQty == 1 && !this.config["options"][optionId].isMulti) {
            if ($("bundle-option-" + optionId + "-qty-input")) {
                qty = $("bundle-option-" + optionId + "-qty-input").value;
            } else {
                qty = 1;
            }
        } else {
            qty = this.config.options[optionId].selections[selectionId].qty;
        }
        if (this.config.priceType == "0") {
            price = this.config.options[optionId].selections[selectionId].price;
            tierPrice = this.config.options[optionId].selections[selectionId].tierPrice;
            for (var i = 0; i < tierPrice.length; i++) {
                if (Number(tierPrice[i].price_qty) <= qty && Number(tierPrice[i].price) <= price) {
                    price = tierPrice[i].price;
                    tierPriceInclTax = tierPrice[i].priceInclTax;
                    tierPriceExclTax = tierPrice[i].priceExclTax;
                }
            }
        } else {
            selection = this.config.options[optionId].selections[selectionId];
            if (selection.priceType == "0") {
                price = selection.priceValue;
            } else {
                price = this.config.basePrice * selection.priceValue / 100;
            }
        }
        var disposition = this.config.options[optionId].selections[selectionId].plusDisposition + this.config.options[optionId].selections[selectionId].minusDisposition;
        if (this.config.specialPrice) {
            newPrice = price * this.config.specialPrice / 100;
            price = Math.min(newPrice, price);
        }
        selection = this.config.options[optionId].selections[selectionId];
        if (tierPriceInclTax !== undefined && tierPriceExclTax !== undefined) {
            priceInclTax = tierPriceInclTax;
            price = tierPriceExclTax;
        } else if (selection.priceInclTax !== undefined) {
            priceInclTax = selection.priceInclTax;
            price = selection.priceExclTax !== undefined ? selection.priceExclTax : selection.price;
        } else {
            priceInclTax = price;
        }
        if (this.config.priceType == "1" || taxCalcMethod == CACL_TOTAL_BASE) {
            var result = new Array(price * qty, disposition * qty, priceInclTax * qty);
            return result;
        } else if (taxCalcMethod == CACL_UNIT_BASE) {
            price = (Math.round(price * 100) / 100).toString();
            disposition = (Math.round(disposition * 100) / 100).toString();
            priceInclTax = (Math.round(priceInclTax * 100) / 100).toString();
            var result = new Array(price * qty, disposition * qty, priceInclTax * qty);
            return result;
        } else {
            price = (Math.round(price * qty * 100) / 100).toString();
            disposition = (Math.round(disposition * qty * 100) / 100).toString();
            priceInclTax = (Math.round(priceInclTax * qty * 100) / 100).toString();
            var result = new Array(price, disposition, priceInclTax);
            return result;
        }
    },
    populateQty: function(optionId, selectionId) {
        if (selectionId == "" || selectionId == "none") {
            this.showQtyInput(optionId, "0", false);
            return;
        }
        if (this.config.options[optionId].selections[selectionId].customQty == 1) {
            this.showQtyInput(optionId, this.config.options[optionId].selections[selectionId].qty, true);
        } else {
            this.showQtyInput(optionId, this.config.options[optionId].selections[selectionId].qty, false);
        }
    },
    showQtyInput: function(optionId, value, canEdit) {
        elem = $("bundle-option-" + optionId + "-qty-input");
        elem.value = value;
        elem.disabled = !canEdit;
        if (canEdit) {
            elem.removeClassName("qty-disabled");
        } else {
            elem.addClassName("qty-disabled");
        }
    },
    changeOptionQty: function(element, event) {
        var checkQty = true;
        if (typeof event != "undefined") {
            if (event.keyCode == 8 || event.keyCode == 46) {
                checkQty = false;
            }
        }
        if (checkQty && (Number(element.value) == 0 || isNaN(Number(element.value)))) {
            element.value = 1;
        }
        parts = element.id.split("-");
        optionId = parts[2];
        if (!this.config["options"][optionId].isMulti) {
            selectionId = this.config.selected[optionId][0];
            this.config.options[optionId].selections[selectionId].qty = element.value * 1;
            this.reloadPrice();
        }
    },
    validationCallback: function(elmId, result) {
        if (elmId == undefined || $(elmId) == undefined) {
            return;
        }
        var container = $(elmId).up("ul.options-list");
        if (typeof container != "undefined") {
            if (result == "failed") {
                container.removeClassName("validation-passed");
                container.addClassName("validation-failed");
            } else {
                container.removeClassName("validation-failed");
                container.addClassName("validation-passed");
            }
        }
    }
};

ieHover = function() {
    var items, iframe;
    items = $$("#nav ul", "#nav div", ".truncated_full_value .item-options", ".tool-tip");
    $$("#checkout-step-payment", ".tool-tip").each(function(el) {
        el.show();
        el.setStyle({
            visibility: "hidden"
        });
    });
    for (var j = 0; j < items.length; j++) {
        iframe = document.createElement("IFRAME");
        iframe.src = BLANK_URL;
        iframe.scrolling = "no";
        iframe.frameBorder = 0;
        iframe.className = "hover-fix";
        iframe.style.width = items[j].offsetWidth + "px";
        iframe.style.height = items[j].offsetHeight + "px";
        items[j].insertBefore(iframe, items[j].firstChild);
    }
    $$(".tool-tip", "#checkout-step-payment").each(function(el) {
        el.hide();
        el.setStyle({
            visibility: "visible"
        });
    });
};

Event.observe(window, "load", ieHover);