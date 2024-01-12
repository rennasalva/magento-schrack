function quickadd_blank(a) { if(a.value == a.defaultValue) a.value = ""; }
function quickadd_unblank(a) { if(a.value == "") a.value = a.defaultValue; }
            
quickaddsearchForm = Class.create();
quickaddsearchForm.prototype = {
    initialize : function(form, field, emptyText, nextfield ){
        this.form   = $(form);
        this.field  = $(field);
        this.nextfield = $(nextfield);
        this.emptyText = emptyText;

        Event.observe(this.form,  'submit', this.submit.bind(this));
        Event.observe(this.field, 'focus', this.focus.bind(this));
        Event.observe(this.field, 'blur', this.blur.bind(this));
        this.blur();
    },

    submit : function(event){
        if (this.field.value == this.emptyText || this.field.value == ''){
            Event.stop(event);
            return false;
        }
        grayDefaultTexts();
        return true;
    },

    focus : function(event){
        if(this.field.value==this.emptyText){
            this.field.value='';
        }
        grayDefaultTexts();
    },

    blur : function(event){
        if(this.field.value==''){
            this.field.value=this.emptyText;
        }
        grayDefaultTexts();
    },

    initAutocomplete : function(url, destinationElement){
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
                    update.style.position = 'absolute';
                    Element.clonePosition(update, element, {
                        setHeight: false,
                        offsetTop: element.offsetHeight
                    });
                    update.style.left='0px';
                    update.style.top='26px';
                    update.show();   
                }

            }
        );
    },

    _selectAutocompleteItem : function(element){
        if(element.title){
            this.field.value = element.title;
            this.nextfield.focus();
        }
    }
};
