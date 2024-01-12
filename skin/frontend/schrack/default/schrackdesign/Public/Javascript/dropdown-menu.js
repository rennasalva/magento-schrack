/* 
 * Simple jquery dropdown menu
 * NOTE: this works only on selectboxes!
 */


(function(jQuery) {
    jQuery.widget('ui.dropdown', {
        options: {
            activateOnClick: false
        },
        _create: function() {
            var self = this,
                o = self.options,
                el = self.element;
            var div = jQuery('<div></div>').insertAfter(el);
            div.copyAttributes(el);
            var span;
            jQuery('option:selected', el).each(function() {
                span = jQuery(this).changeElementType('span')
                    .html('')
                    .appendTo(div)
                    .click(function(evt) { self._handleClick(self, evt); })
            });

            var ul = jQuery('<ul></ul>').addClass('dropdown-list').hide().appendTo(jQuery('body'));

            div.hoverIntent({over: function(evt) {
                var span = jQuery(evt.currentTarget).find('span');
                var pos = span.offset();
                ul.css({left: span.offset().left, top: span.offset().top + span.height() + 1});
                jQuery(this).addClass('active');
                ul.show();
                //jQuery('input').prop('disabled', true);
            },out: function() {
                jQuery(this).removeClass('active');
                ul.hide();},
                timeout: 3000,
                preDefinedEvent: 'click'});
            ul.hoverIntent({over:function(){}, out: function() {
                jQuery(this).removeClass('active');
                ul.hide();
                //jQuery('input').prop('disabled', false);
            }, timeout: 1000,
                preDefinedEvent: 'click'});
            jQuery('option', el).each(function() {
                var li = jQuery(this).changeElementType('li')
                    .appendTo(ul)
                    .click(function(evt) { self._handleClick(self, evt); })
            });

            // finally, hide our original element
            el.removeClass('dropdown-menu');
            el.hide();
        },
        _handleClick: function(self, evt) {
            var el = evt.target;
            if (el.tagName === 'LI')
                jQuery(el).parent().hide();
            if (self.options.activateOnClick && !jQuery(el).hasClass('no-auto-activate'))
                self._replaceActiveElement(el);
            jQuery('input').prop('disabled', false);
        },
        destroy: function() {
            this.element.next().remove();
            this.element.addClass('dropdown-menu');
            this.element.show();
            jQuery('input').prop('disabled', false);
        },
        _setOption: function(option, value) {
            jQuery.Widget.prototype._setOption.apply(this, arguments);
            switch (option) {
                case 'activateOnClick':
                    this.options.activateOnClick = value;
                    break;
            }
        },
        _replaceActiveElement: function(el) {
            var menu = jQuery(el).parents('.dropdown-menu');
            var newSpan = jQuery(el).changeElementType('span');
            var oldSpan = jQuery('> span:first-of-type', menu);
            var newLi = jQuery(oldSpan).changeElementType('li');
            oldSpan.replaceWith(newSpan);
            var self = this;
            jQuery(newLi).prop('selected', false).click(function(evt) { self._replaceElement(self, evt); });
            jQuery(el).replaceWith(newLi);
        }
    });
})(jQuery);
