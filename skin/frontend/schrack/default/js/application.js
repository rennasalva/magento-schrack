/* 
(C) 2010 Wolfgang Klinger, wk@plan2.net
(C) 2010 plan2net, info@plan2.net
*/

jQuery.noConflict();

/* Google Analytics functions */
function submitFormAndTrackGaEvent(form, gaCategory, gaAction, gaOtionLabel, gaOptionValue) {
    // Push form submit event to GA
    if (typeof(ga) !== 'undefined' && ga) {
        ga('send', 'event', gaCategory, gaAction, gaOtionLabel, gaOptionValue);
        setTimeout(function(){form.submit();},1000);
        return false;
    } else
        form.submit();
    return true;
}

function setLocationAndTrackGaEvent(url, gaCategory, gaAction, gaOtionLabel, gaOptionValue) {
    // Push setLocation event to GA
    if (typeof(ga) !== 'undefined' && ga) {
        ga('send', 'event', gaCategory, gaAction, gaOtionLabel, gaOptionValue);
        setTimeout(function(){setLocation(url);},1000);
        return false;
    }
    setLocation(url);
    return true;
}

function mailTo(email) {
    email = decryptMailto(email);
    if(typeof(ga) !== 'undefined' && ga){
        ga('send', 'event', 'page', 'send_email', email, email);
    }
    location.href = email;
    return true;
}

function decryptMailto(s) {
	var n = 0;
	var r = "";
	for( var i = 0; i < s.length; i++) {
		n = s.charCodeAt( i );
		if( n >= 8364 ) {
			n = 128;
		}
		r += String.fromCharCode( n - 1 );
	}
	return r;
}

/* TYPO3/CMS stuff */
function decryptCharcode(n,start,end,offset){n=n+offset;if(offset>0&&n>end){n=start+(n-end-1);}else if(offset<0&&n<start){n=end-(start-n-1);}
return String.fromCharCode(n);}
function decryptString(enc,offset){var dec="";var len=enc.length;for(var i=0;i<len;i++){var n=enc.charCodeAt(i);if(n>=0x2B&&n<=0x3A){dec+=decryptCharcode(n,0x2B,0x3A,offset);}else if(n>=0x40&&n<=0x5A){dec+=decryptCharcode(n,0x40,0x5A,offset);}else if(n>=0x61&&n<=0x7A){dec+=decryptCharcode(n,0x61,0x7A,offset);}else{dec+=enc.charAt(i);}}
return dec;}
function linkTo_UnCryptMailto(s){location.href=decryptString(s,-3);}

function selectDrum(value,amount){
  amount=parseInt(amount);
  var sd=jQuery("#product-info-drum option[value='"+value+"']");
  sd.attr('selected', true);
  if (sd.val()!="" && typeof(sd.val()) != 'undefined') {
    var sd_val=sd.val().split('|');
    if (amount!=0) jQuery("#product_addtocart_form input#qty").val(amount);
    jQuery("#product_addtocart_form input#schrack_drum_number").val(sd_val[0]);
  }
  else {
    jQuery("#product_addtocart_form input#schrack_drum_number").val('');
  }
}

function grayDefaultText(id) {
    var el = jQuery('#' + id);
    if (jQuery(el).val() == defaultTexts[id]) {
        jQuery(el).addClass('isdefault');
        jQuery(el).removeClass('nodefault');
    } else {
        jQuery(el).addClass('nodefault');
        jQuery(el).removeClass('isdefault');
    }
}

function addDefaultText(id, text) {
    if (typeof(defaultTexts) == 'undefined')
        defaultTexts = {};
    defaultTexts[id] = text;
}

function grayDefaultTexts() {
    if (typeof(defaultTexts) == 'object') {
        for (id in defaultTexts) {
            grayDefaultText(id);
        }
    }    
}



    /*
 * Image preview script 
 * powered by jQuery (http://www.jquery.com)
 * 
 * written by Alen Grakalic (http://cssglobe.com)
 * 
 * for more info visit http://cssglobe.com/post/1695/easiest-tooltip-and-image-preview-using-jquery
 *
 */

var imagePreview = function(){
    xOffset = 50;
    yOffset = 20;

    jQuery("a.preview").hoverIntent({over: function(e){
            var self = this;
            var img = jQuery("<img src='"+ jQuery(this).attr('data-image') +"' alt='Image preview'/>").load(function() {
                    var div = jQuery("<div class='image-preview' id='image-preview-" + Math.round(Math.random() * 50000) + "'></div>").append(img);
                    jQuery("body").append(div);
                    div.css("left", (jQuery(self).offset().left + xOffset) + "px")
                        .css("top", (jQuery(self).offset().top + yOffset) + "px")
                        .show();
                    var w = this.width;
                    var h = this.height;
                    var dim = scaleDimensions(w, h);
                    this.width = dim.w;
                    this.height = dim.h;
                    div.css('position', 'absolute').width(dim.w).height(dim.h);
            });
        }, out: function(){
             jQuery(".image-preview").remove();
        }, timeout: 100}
    );
};

var scaleDimensions = function(w, h) {
    if (w > 600 || h > 600) {
        var factor;
        if (w > h) {
            factor = 600 / w;
        } else {
            factor = 600 / h;
        }
        w = w * factor;
        h = h * factor;
    }

    return { 'w': w, 'h': h};
};

var scaleImage = function(img, maxWidth, maxHeight) {
    var w = img.width();
    var h = img.height();
    if (w > maxWidth || h > maxHeight) {
        var factor;
        if (img.width() > img.height()) {
            factor = maxWidth / w;
        } else {
            factor = maxHeight / h;
        }
        w = w * factor;
        h = h * factor;
        img.width(w);
        img.height(h);
    }
};

jQuery(document).ready(function(){
/* Sidebar right column */
/* select all divs on the first level */
	var sidebar_blocks = jQuery('#sidebar>div');
  if (sidebar_blocks.length > 0) {
    jQuery('#sidebar').prepend('<div class="first-block">&nbsp;</div>');
    sidebar_blocks.first().addClass('second-block');
    sidebar_blocks.last().addClass('last-block');
    sidebar_blocks.not(':last').after('<div class="spacer"></div>');
  }


  initShadowbox();

  jQuery('.accordion').each(function(){
    jQuery(this).accordion({
      active: false,
      collapsible: true,
      autoHeight: false,
      header: 'h3'
    });
    var accordion = jQuery(this).data('accordion');
    if (accordion) {
        accordion._std_clickHandler = accordion._clickHandler;
        accordion._clickHandler = function(e,t){
          var c = jQuery(e.currentTarget||t);
          if (!c.hasClass('ui-state-disabled'))
          this._std_clickHandler(e,t);
        };
    }
  });

    jQuery('button').hover(function() {
        jQuery(this).css('cursor','pointer');
    }, function() {
        jQuery(this).css('cursor','auto');
    });

    jQuery('#product-info select#product-info-drum').change(function() {
        var sd=jQuery('#product-info #product-info-drum option:selected');
        selectDrum(sd.val(),0);
    });
    jQuery('#product-info #open-stock-info-delivery').click(function(){
      var active=jQuery('#product-info-stock-details').accordion('option','active');
      if (active===false||active>0){
        jQuery('#product-info-stock-details').accordion('activate',0);
      }
    });
    jQuery('#product-info a[id^="open-stock-info-pickup"]').click(function(){
      var warehouse=parseInt(jQuery(this).attr('id').match(/\d+/));
      var active=jQuery('#product-info-stock-details').accordion('option','active');
      if (!active||jQuery('#product-info-stock-details h3').eq(active).attr('id')!='stock-info-section-pickup'+warehouse){
        jQuery('#product-info-stock-details').accordion('activate', '#stock-info-section-pickup'+warehouse);
      }
    });

    jQuery('#product-info-stock-details td a').hover(function(){
      jQuery(this).closest('tr').find('a').addClass('underline');
    },function(){
      jQuery(this).closest('tr').find('a').removeClass('underline');
    });

    grayDefaultTexts();
    
    // window.onbeforeunload = navigationError;
    imagePreview();

});

function initShadowbox() {
    jQuery('a.shadowbox').each(function(){
        jQuery(this).attr('rel', 'shadowbox;width=640;height=480px;player=swf');
    });
    Shadowbox.init({
        players:  ['html', 'iframe', 'swf'],
        overlayColor: '#000',
        overlayOpacity: 0.7,
        handleOversize: 'resize'
    });
}

var pageIsDirty = false;
var confirmLeave = false;
var leaveMessage = 'You sure you want to leave?';

function navigationError(e) {
    if(pageIsDirty && confirmLeave) {
      if(!e) e = window.event;
      //e.cancelBubble is supported by IE - this will kill the bubbling process.
      e.cancelBubble = true;
      e.returnValue = leaveMessage;
      //e.stopPropagation works in Firefox.
      if (e.stopPropagation) 
      {
        e.stopPropagation();
        e.preventDefault();
      }

      //return works for Chrome and Safari
      return leaveMessage;
    }
}
    

// we want to search only after 3 chars have been added
  Varien.searchForm.prototype.initAutocomplete = function(url, destinationElement){
    new Ajax.Autocompleter(
        this.field,
        destinationElement,
        url,
        {
            paramName: this.field.name,
            method: 'get',
            minChars: 3,
            updateElement: this._selectAutocompleteItem.bind(this),
            onShow : function(element, update) {
                if(!update.style.position || update.style.position=='absolute') {
                    update.style.position = 'absolute';
                    Position.clone(element, update, {
                        setHeight: false,
                        offsetTop: element.offsetHeight
                    });
                }
                update.style.left='0px';
                update.style.top='43px';
                update.show();                
            }
        }
    );
  };

