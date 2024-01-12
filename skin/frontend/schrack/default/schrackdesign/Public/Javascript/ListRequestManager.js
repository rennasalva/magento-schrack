/*
 * partslist + wishlist (+potential future list types) add/remove functions 
 * manages the ajax requests to/from the list, for:
 * - addToNewList (add to an implicitly created new list)
 * - addToActiveList (add to existing list)
 * - remove (remove from existing list)
 * The server may respond with a json object: { listId: <id>(, isNew: true)? }
 *  - if listId is set, the next call to .addProduct() will use this list
 *  - if created is set, all widgets with class "<idBaseName>-addToNewList" will be hidden, and all widgets with class "<idBaseName>-addToActiveList" will be shown instead
 */

var affectedDocumentId    = null;
var affectedDocumentType  = null;
var affectedFeatureSource = '-';

var ListRequestManager = {
    List: Class.create(),
    Frontend: Class.create(),
    ListItem: Class.create(),
    Document: Class.create(),
    Product: Class.create()
};

ListRequestManager.ListItem.prototype = {
    id: null,
    initialize: function(id) {
        this.id = id;
    },
    getId: function() {
        return this.id;
    }
};

ListRequestManager.Product.prototype = Object.extend(new ListRequestManager.ListItem(), {
    qty: 0,
    initialize: function(id, qty, sku) {
        this.id = id;
        this.qty = qty;
        this.sku = sku;
    },
    getQty: function() {
        return this.qty;
    },
    getSku: function() {
        return this.sku;
    }
});

ListRequestManager.Document.prototype = Object.extend(new ListRequestManager.ListItem(), {
    type: '',
    initialize: function(id, type) {
        this.id = id;
        this.type = type;
    },
    getType: function() {
        return this.type;
    }
});


ListRequestManager.List.prototype = {
    baseUrl: '',
    productAddUrl: null,
    productBatchAddUrl: null,
    productBatchRemoveUrl: null,
    productRemoveUrl: null,
    documentAddUrl: null,
    documentBatchAddUrl: null,
    listId: null,
    listName: null,
    batch: null,
    listIsSingleton: false, // there is only one list for this class, i.e. cart, so we don't have to send the id field
    initialize: function(baseUrl, listIsSingleton) {
        this.baseUrl = baseUrl;
        this.productAddUrl = baseUrl + 'add/';
        this.productRemoveUrl = baseUrl + 'remove/';
        this.productBatchAddUrl = baseUrl + 'batchAdd/';
        this.productBatchRemoveUrl = baseUrl + 'batchRemove/';
        this.documentBatchAddUrl = baseUrl + 'batchAddDocuments/';

        this.listIsSingleton = listIsSingleton;
        this.batch = new Array();

        if (arguments.length === 2) // optional partslist id parameter
            this.listId = arguments[1];
    },
    setListId: function(listId) {
        this.listId = listId;
    },
    setListName: function(listName) {
        this.listName = listName;
    },
    setListComment: function(listComment) {
        this.listComment = listComment;
    },
    addItemToBatch: function(item) {
        this.batch.push(item);
    },
    getListIsSingleton: function() {
        return this.listIsSingleton;
    },
    getBatchLength: function() {
        return this.batch.length;
    },
    setProductAddUrl: function(url) {
        this.productAddUrl = url;
    },
    setProductBatchAddUrl: function(url) {
        this.productBatchAddUrl = url;
    },
    setDocumentBatchAddUrl: function(url) {
        this.documentBatchAddUrl = url;
    },
    _getListIdOrNameUrlPart: function() { // with id, it's an existing list, with name it's a new one
        if (this.listId === null && !this.listIsSingleton)
            return  'name/' + encodeURIComponent(this.listName) + '/comment/' + encodeURIComponent(this.listComment) + '/';
        else if (this.listIsSingleton) // e.g., cart
            return '';
        else
            return  'id/' + this.listId + '/';
    },
    _performRequest: function(url, reloadPage) {
        jQuery('#communicating-with-server').addClass('active');
        var request = new Ajax.Request(url,
            {
                method: 'post',
                onSuccess: function(response) {
                    jQuery('#communicating-with-server').addClass('active');
                    checkMessages(JSON.parse(response.responseText));
                    jQuery('#communicating-with-server').removeClass('active');
                    if (reloadPage)
                        window.location.reload();
                }.bind(this)
            });
    },
    addItemToList: function(item) {
        var reloadPage = false;
        // Check optional argument-list: reloadPage == true
        if((arguments.length === 2 && arguments[1] === true) || (arguments.length === 3 && arguments[1] === true)) {
            reloadPage = true;
            console.log('Page should be reloaded');
        }
        var url = null;
        var featureSource = '-';
        console.log('ListRequestManagerArgumentsList #1:');
        console.log(arguments);
        if (arguments[2]) {
            featureSource = arguments[2];
        }
        if (item instanceof ListRequestManager.Product) {
            url = this.productAddUrl + this._getListIdOrNameUrlPart() + 'product/' + item.getId() + '/qty/' + item.getQty();
            if (dataLayer) {
                dataLayer.push({
                    'event' : 'partlistModification',
                    'eventAction' : 'Add',
                    'eventLabel' : featureSource,
                    'partlistModificationSource' : 'Standard',
                    'productSku' : item.getSku()
                });
                console.log('State: Added Product (SKU = ' + item.getId() + ') to Partslist = ' + this.listId);
            }
        } else {
            url = this.documentAddUrl + this._getListIdOrNameUrlPart() + 'document/' + item.getId() + '/type/' + item.getType();
        }

        var query = (location.search.split('q' + '=')[1] || '').split('&')[0];
        if ( typeof query == 'string' && query > '' ) {
            url += '/query/' + query;
        }

        //jQuery("html, body").animate({ scrollTop: 0 }, "slow");
        //console.log('ScrollTop #13');
        setTimeout(this._performRequest(url, reloadPage), 2000);
    },
    removeItemFromList: function(item) {
        if (!(item instanceof ListRequestManager.Product))
            throw 'removeItem not implemented for this clazz';
        var url = this.productRemoveUrl + 'id/' + this.listId + '/product/' + item.getId();
        this._performRequest(url, false);
    },
    addBatchItemsToList: function() {
        var reloadPage = (arguments.length === 1 ? arguments[0] : false);
        var formkey = (arguments.length === 3 ? arguments[2] : false);
        var documentId = affectedDocumentId;
        var docType = affectedDocumentType;

        if (this.batch.length === 0)
            return;
        var itemsObject = this._createItemsObject();
        var url = null;
        if (itemsObject.clazz === ListRequestManager.Product) {
            url = this.productBatchAddUrl + this._getListIdOrNameUrlPart() + 'products/' + itemsObject.items.join(';');
        } else {
            url = this.documentBatchAddUrl + this._getListIdOrNameUrlPart() + 'documents/' + itemsObject.items.join(';');
            var getItemsListAjaxUrl = BASE_URL + 'wishlist/partslist/getProductslistAsSkulistByDocument/type/' + docType + '/documentId/' + documentId;
            jQuery.ajax({
                url: getItemsListAjaxUrl,
                data: {form_key: formkey},
                method: 'POST'
            }).done(function (data) {
                var itemsList = JSON.parse(data);
                var arrayLength = itemsList.length;
                for (var i = 0; i < arrayLength; i++) {
                    if (dataLayer) {
                        dataLayer.push({
                            'event' : 'partlistModification',
                            'eventAction' : 'Add',
                            'eventLabel' : affectedFeatureSource,
                            'partlistModificationSource' : 'Document',
                            'productSku' : itemsList[i]
                        });
                        console.log({'Event (adddocumenttopartlist) #1 tracked IDs ' : itemsList[i]});
                    }
                }
            });
        }
        this.batch = new Array();
        this._performRequest(url, reloadPage);
    },
    _createItemsObject: function() {
        var clazz = null;
        var newClazz = null;
        var items = new Array();
        for (i=0; i < this.batch.length; ++i) {
            if (this.batch[i] instanceof ListRequestManager.Product) {
                items.push(this.batch[i].getId() + ':' + this.batch[i].getQty());
                newClazz = ListRequestManager.Product;
            } else {
                items.push(this.batch[i].getId() + ':' + this.batch[i].getType());
                newClazz = ListRequestManager.Document;
            }
            if (clazz !== null && clazz !== newClazz)
                throw "Batch list must be uniform.";
            else
                clazz = newClazz;
        }
        return { 'items': items, 'clazz': clazz };
    },
    removeBatchItemsFromList: function() {
        if (this.batch.length === 0)
            return;
        var itemsObject = this._createItemsObject();
        var url = null;
        if (itemsObject.clazz === ListRequestManager.Product)
            url = this.productBatchRemoveUrl + this._getListIdOrNameUrlPart() + 'products/' + itemsObject.items.join(';');
        else
            url = this.documentBatchRemoveUrl + this._getListIdOrNameUrlPart() + 'documents/' + itemsObject.items.join(';');
        this._performRequest(url, true);
    },
    setItemDescription: function(itemId, description) {
        var reqString = this.baseUrl + 'setItemDescription/itemId/' + itemId + '/description/' + encodeURIComponent(description);
        jQuery('#communicating-with-server').addClass('active');
        var request = new Ajax.Request(reqString,
            {
                method: 'get',
                onSuccess: function(response) {
                    jQuery('#communicating-with-server').removeClass('active');
                    var json = JSON.parse(response.responseText);
                    checkMessages(json);
                }
            });
    }
};

ListRequestManager.Frontend.prototype = {
    list: null,
    listItemClazz: null,
    initialize: function(list, listItemClazz) {
        this.list = list;
        this.listItemClazz = listItemClazz;
    },
    setListItemClazz: function(clazz) {
        this.listItemClazz = clazz;
    },
    // for singleton list (like cart): addItemToList(item, <optional> reloadPage)
    // for non-singleton list (like partslist): addItemToList(listId, item, <optional> reloadPage)
    addItemToList: function() { // possibly listId, item, optional reloadPage
        var listId = null;
        var featureSource = '-';
        if (!this.list.getListIsSingleton())
            listId = [].shift.apply(arguments);
        var item = arguments[0];
        var reloadPage = (arguments.length === 2 ? arguments[1] : false); // optiaddCheckedItemsToNewListonal argument: reloadPage
        console.log('ListRequestManagerArgumentsList #2:');
        console.log(arguments);
        if (arguments[2] && arguments[2] == 'cart') {
            featureSource = 'cart';
        }
        if (arguments[2] && arguments[2] == 'product detail view') {
            featureSource = 'product detail view';
        }
        if (arguments[2] && arguments[2] == 'product list view') {
            featureSource = 'product list view';
        }
        if (arguments[2] && arguments[2] == 'listname_form_typo3') {
            featureSource = 'startpage slider';
        }
        if (arguments[2] && arguments[2] == 'listname_form_typo3_content') {
            featureSource = 'typo content products slider';
        }
        if (arguments[2] && arguments[2] == 'my account latest purchased slider') {
            featureSource = 'latest purchased slider';
        }
        if (arguments[2] && arguments[2] == 'product detail view accessories slider') {
            featureSource = 'detail view accessories slider';
        }
        if (arguments[2] && arguments[2] == 'search result page') {
            featureSource = 'search result page';
        }
        this.list.setListName(null);
        this.list.setListId(listId);
        // Google Tag Management:
        this.list.addItemToList(item, reloadPage, featureSource);
    },

    addItemToNewList: function(titleText, item, featureSrc) {
        var featureSource = '-';
        var id;


        if (featureSrc) {
            featureSource = featureSrc;
        }
        console.log('addItemToNewList called');
        console.log(arguments);

        var self = this;
        if (featureSource === 'listname_form_typo3') {
            id = 'listname_form_typo3';
        } else if (featureSource === 'listname_form_typo3_content') {
            id = 'listname_form_typo3';
        } else {
            id = "listname_form"
        }
        jQuery('#'+id).modal();
        jQuery('#'+id+' #listname_formSubmit_modified').off('click').click(function(){
            console.log('addItemToNewList : Ok-Button Clicked');
            var name = jQuery('#'+id+' #name').val();
            var comment = jQuery('#'+id+' #comment').val();
            if (name !== null) {
                self.list.setListId(null);
                self.list.setListName(name);
                self.list.setListComment(comment);
                jQuery('#'+id).modal('hide');
                self.list.addItemToList(item, true, featureSource);
            }
        });
        /*
        jQuery('#listname-form').dialog({ title: titleText,
            buttons: [
                { text: okButtonName, click: function() {
                    var name = jQuery('#listname-form #name').val();
                    var comment = jQuery('#listname-form #comment').val();
                    jQuery( this ).dialog( "close" );
                    if (name !== null) {
                        self.list.setListId(null);
                        self.list.setListName(name);
                        self.list.setListComment(comment);

                        self.list.addItemToList(item, true);
                    }
                } },
                { text: cancelButtonName, click: function() { jQuery(this).dialog('close'); } }
            ],
            open: function(event, ui) {
                jQuery('#listname-form input#name').removeAttr('disabled').focus();
            },
            width: 350
        }).dialog('open');
        */
    },
    _addCheckedItemsToBatch: function(checkboxClassName, itemIdBaseName, qtyOrTypeIdBaseName, featureSrc) {
        console.log('Show feature source: ' + featureSrc);
        var qtyIdBaseName  = null;
        var typeIdBaseName = null;
        var featureSource  = '-';

        if (featureSrc && featureSrc == 'cart') {
            featureSource = 'cart';
        }

        if (featureSrc && featureSrc.indexOf('detail') >= 0) {
            featureSource = featureSrc;
        }

        if (arguments.length !== 4)
            throw 'Wrong number of arguments (1).';
        else if (this.listItemClazz === ListRequestManager.Product)
            qtyIdBaseName = arguments[2];
        else
            typeIdBaseName = arguments[2];

        var rows = jQuery('.' + checkboxClassName + ':checked');
        if (rows.length === 0) {
            // showOverlayMessage('error', pleaseSelectSomeRowsFirstText, '');
            alert(pleaseSelectSomeRowsFirstText);
            if (jQuery('.listArea')) {
                unsetOverlayLoaderCentral('listArea');
            }
            if (jQuery('.productListArea')) {
                console.log('_addCheckedItemsToBatch');
                unsetOverlayLoaderCentral('productListArea');
            }
            return;
        }
        var self = this;
        rows.each(function(i, e) {
            var id = jQuery(this).prop('id');
            var rowId = id.split('-')[1];
            var itemId = jQuery('#' + itemIdBaseName + '-' + rowId).val();
            var qty = 0;
            var type = null;
            if (itemId) {
                if (self.listItemClazz === ListRequestManager.Product) {
                    qty = jQuery('#' + qtyIdBaseName + '-' + rowId).val();
                    self.list.addItemToBatch(new self.listItemClazz(itemId, qty));
                    if (dataLayer) {
                        if (featureSrc == 'toolSchrackProtect') {
                            // Added Google Tracking (add to partslist):
                            // Do nothing for tool tracking
                        } else {
                            dataLayer.push({
                                'event' : 'partlistModification',
                                'eventAction' : 'Add',
                                'eventLabel' : featureSource,
                                'partlistModificationSource' : 'Standard',
                                'productSku' : jQuery(this).attr('data-sku')
                            });
                            console.log({'Event (addtopartlist) tracked ID #1 ' : jQuery(this).attr('data-sku')});
                        }
                    }
                    console.log('_addCheckedItemsToBatch (1)');
                } else {
                    type = jQuery('#' + typeIdBaseName + '-' + rowId).val();
                    self.list.addItemToBatch(new self.listItemClazz(itemId, type));
                    console.log('_addCheckedItemsToBatch (2)');
                    affectedDocumentId = itemId;
                    affectedDocumentType = type;
                }
            }
        });
        if (dataLayer) {
            if (featureSrc == 'toolSchrackProtect') {
                dataLayer.push({
                    'event' : 'trackingActionFromOnlineTools',
                    'eventAction' : 'Schrack Protect',
                    'eventLabel' : 'Add to Partslist'
                });
                console.log('Event (addtopartlist from toolSchrackProtect');
            }
        }
    },
    _addCheckedItemsToBatchCart: function(checkboxClassName, itemIdBaseName) {
        var qtyIdBaseName = null;
        var typeIdBaseName = null;
        if (arguments.length !== 3)
            throw 'Wrong number of arguments (2).';
        else if (this.listItemClazz === ListRequestManager.Product)
            qtyIdBaseName = arguments[2];
        else
            typeIdBaseName = arguments[2];

        var rows = jQuery('.' + checkboxClassName + ':checked');
        if (rows.length === 0) {
            // showOverlayMessage('error', pleaseSelectSomeRowsFirstText, '');
            alert(pleaseSelectSomeRowsFirstText);
            if (jQuery('.listArea')) {
                unsetOverlayLoaderCentral('listArea');
            }
            if (jQuery('.productListArea')) {
                console.log('_addCheckedItemsToBatchCart');
                unsetOverlayLoaderCentral('productListArea');
            }
            return;
        }
        var self = this;
        var listItemClassProduct = false;
        var itemIdList   = [];
        var itemQtyList  = [];
        var itemNameList = [];
        var itemPriceList = [];
        var itemCategoryList = [];
        var itemPagetypeList = [];
        var itemCurrencyCodeList = [];
        var itemTrackingEnabledList = [];
        rows.each(function(i, e) {
            var id = jQuery(this).prop('id');
            var rowId = id.split('-')[1];
            var itemId = jQuery('#' + itemIdBaseName + '-' + rowId).val();
            var qty = 0;
            var name = '';
            var price = '';
            var category = '';
            var pagetype = '';
            var currencyCode = '';
            var trackingEnabled = '';
            var type = null;
            if (itemId) {
                if (self.listItemClazz === ListRequestManager.Product) {
                    qty = jQuery('#' + qtyIdBaseName + '-' + rowId).val();
                    name = jQuery('#name' + '-' + rowId).val();
                    price = jQuery('#price' + '-' + rowId).val();
                    category = jQuery('#category' + '-' + rowId).val();
                    pagetype = jQuery('#pagetype' + '-' + rowId).val();
                    currencyCode = jQuery('#currencyCode' + '-' + rowId).val();
                    trackingEnabled = jQuery('#trackingEnabled' + '-' + rowId).val();
                    self.list.addItemToBatch(new self.listItemClazz(itemId, qty));
                    listItemClassProduct = true;
                    // Collecting SKU's for push to dataLayer
                    itemIdList.push(jQuery(this).attr('data-sku'));
                    itemQtyList.push(qty);
                    itemNameList.push(name);
                    itemPriceList.push(price);
                    itemCategoryList.push(category);
                    itemPagetypeList.push(pagetype);
                    itemCurrencyCodeList.push(currencyCode);
                    itemTrackingEnabledList.push(trackingEnabled);
                } else {
                    type = jQuery('#' + typeIdBaseName + '-' + rowId).val();
                    self.list.addItemToBatch(new self.listItemClazz(itemId, type));
                    console.log('_addCheckedItemsToBatchCart (2)');
                    affectedDocumentId = itemId;
                    affectedDocumentType = type;
                }
            }
        });
        if (listItemClassProduct) {
            var arrayLength = itemIdList.length;

            for (var i = 0; i < arrayLength; i++) {
                var trackingData = new Object();
                trackingData.sku             = itemIdList[i];
                trackingData.quantity        = parseInt(itemQtyList[i]).toString();
                trackingData.pagetype        = itemPagetypeList[i];
                trackingData.name            = itemNameList[i];
                //trackingData.price           = parseFloat(itemPriceList[i]).toFixed(2);
                trackingData.category        = itemCategoryList[i];
                trackingData.currencyCode    = itemCurrencyCodeList[i];
                trackingData.trackingEnabled = itemTrackingEnabledList[i];

                addToCartTracking(trackingData, 'Document');

                console.log('_addCheckedItemsToBatchCart (1) -> Index : ' + i);
            }
            console.log({'Event (adddocumenttocart) tracked IDs ' : itemIdList});
        }

    },
    _addCheckedItemsToBatchFromDocument: function(checkboxClassName, itemIdBaseName) {
        var qtyIdBaseName  = null;
        var typeIdBaseName = null;
        var featureSource  = null;

        if (arguments.length > 4) {
            throw 'Wrong number of arguments > 4.';
        } else if (this.listItemClazz === ListRequestManager.Product) {
            qtyIdBaseName = arguments[2];
        } else {
            typeIdBaseName = arguments[2];
        }

        console.log('ListRequestManager-ArgumentsList');
        console.log(arguments);

        if (arguments[3] && arguments[3].indexOf('overview') >= 0) {
            featureSource = arguments[3];
        } else {
            featureSource = '-';
        }

        var rows = jQuery('.' + checkboxClassName + ':checked');
        if (rows.length === 0) {
            // showOverlayMessage('error', pleaseSelectSomeRowsFirstText, '');
            alert(pleaseSelectSomeRowsFirstText);
            if (jQuery('.listArea')) {
                unsetOverlayLoaderCentral('listArea');
            }
            if (jQuery('.productListArea')) {
                console.log('_addCheckedItemsToBatchFromDocument');
                unsetOverlayLoaderCentral('productListArea');
            }
            return;
        }
        var self = this;
        rows.each(function(i, e) {
            var id = jQuery(this).prop('id');
            var rowId = id.split('-')[1];
            var itemId = jQuery('#' + itemIdBaseName + '-' + rowId).val();
            var qty = 0;
            var type = null;
            var realtype = null;
            if (itemId) {
                if (self.listItemClazz === ListRequestManager.Product) {
                    qty = jQuery('#' + qtyIdBaseName + '-' + rowId).val();
                    self.list.addItemToBatch(new self.listItemClazz(itemId, qty));
                    dataLayer.push ({
                            'event' : 'partlistModification',
                            'eventAction' : 'Add',
                            'eventLabel' : featureSource,
                            'partlistModificationSource' : 'Document',
                            'productSku' : jQuery(this).attr('data-sku')
                    });
                    console.log({'Event (adddocumenttopartlist) tracked IDs ' : jQuery(this).attr('data-sku')});
                    console.log('_addCheckedItemsToBatchFromDocument (1)');
                } else {
                    type = jQuery('#' + typeIdBaseName + '-' + rowId).val();
                    realtype = jQuery('#real' + typeIdBaseName + '-' + rowId).val();
                    self.list.addItemToBatch(new self.listItemClazz(itemId, type));
                    console.log('_addCheckedItemsToBatchFromDocument (2)');
                    affectedDocumentId    = itemId;
                    affectedDocumentType  = type;
                    if (typeof realtype == 'undefined') {
                        realtype = jQuery('#real' + typeIdBaseName + '-' + itemId).val();
                    }
                    console.log('featureSourceLog #1 = ' + realtype + ' ' + featureSource);
                    if (featureSource) {
                        affectedFeatureSource = realtype + ' ' + featureSource;
                    }
                }
            }
        });
    },
    addCheckedItemsToList: function() { // listId, checkboxClassName, itemIdBaseName, qtyOrtypeBaseName, optional reloadPage
        var listId = null;
        if (!this.list.getListIsSingleton())
            listId = [].shift.apply(arguments);
        var reloadPage = (arguments.length === 4 ? [].pop.apply(arguments) : false);
        ListRequestManager.Frontend.prototype._addCheckedItemsToBatch.apply(this, arguments);
        if (this.list.getBatchLength() > 0) {
            this.list.setListId(listId);
            this.list.addBatchItemsToList(reloadPage);
        }
    },

    addCheckedItemsToCart: function() { // listId, checkboxClassName, itemIdBaseName, qtyOrtypeBaseName, optional reloadPage
        var listId = null;
        if (!this.list.getListIsSingleton())
            listId = [].shift.apply(arguments);
        var reloadPage = (arguments.length === 4 ? [].pop.apply(arguments) : false);
        ListRequestManager.Frontend.prototype._addCheckedItemsToBatchCart.apply(this, arguments);
        if (this.list.getBatchLength() > 0) {
            this.list.setListId(listId);
            this.list.addBatchItemsToList(reloadPage);
        }
    },

    addCheckedItemsToNewList: function(titleText, checkboxClassName, itemIdBaseName, qtyOrTypeIdBaseName, formKey, documentAdd, featureSrc) {
        var self = this;
        jQuery('#listname_form').modal();
        console.log('Placing Button Click Event (-> addCheckedItemsToNewList)');
        jQuery('#listname_form #listname_formSubmit_modified').off('click').click(function(){
            console.log('addCheckedItemsToNewList : Ok-Button Clicked');
            var name = jQuery('#listname_form #name').val();
            var comment = jQuery('#listname_form #comment').val();
            //console.log('--> name = ' + name);
            if (name !== null) {
                console.log('addCheckedItemsToNewList');
                if (documentAdd == true) {
                    console.log('addCheckedItemsToNewList number #1');
                    self._addCheckedItemsToBatchFromDocument(checkboxClassName, itemIdBaseName, qtyOrTypeIdBaseName, featureSrc);
                } else {
                    console.log('addCheckedItemsToNewList number #2');
                    self._addCheckedItemsToBatch(checkboxClassName, itemIdBaseName, qtyOrTypeIdBaseName, featureSrc);
                }
                self.list.setListId(null);
                self.list.setListName(name);
                self.list.setListComment(comment);
				jQuery('#listname_form').modal('hide');
                self.list.addBatchItemsToList(true, false, formKey);
            }
        });
        /*
        jQuery('#listname-form').dialog({ title: titleText,
            buttons: [
                { text: okButtonName, click: function() {
                    var name = jQuery('#listname-form #name').val();
                    var comment = jQuery('#listname-form #comment').val();
                    jQuery( this ).dialog( "close" );
                    if (name !== null) {
                        self._addCheckedItemsToBatch(checkboxClassName, itemIdBaseName, qtyOrTypeIdBaseName);
                        self.list.setListId(null);
                        self.list.setListName(name);
                        self.list.setListComment(comment);

                        self.list.addBatchItemsToList(true);
                    }
                } },
                { text: cancelButtonName, click: function() { jQuery(this).dialog('close'); } }
            ],
            open: function(event, ui) {
                jQuery('#listname-form input#name').removeAttr('disabled').focus();
            },
            width: 350
        });
        */
    },
    removeCheckedItemsFromList: function(listId, checkboxClassName, skuIdBaseName, qtyIdBaseName) {
        this._addCheckedItemsToBatch(checkboxClassName, skuIdBaseName, qtyIdBaseName);
        if (this.list.getBatchLength() > 0) {
            this.list.setListId(listId);
            this.list.removeBatchItemsFromList();
        }
    }
};



function createPartslist(titleText) {
    jQuery('#listname-formButton').click();
    jQuery('#listname_formSubmit_modified').off('click').click(function(){
        var name = jQuery('#listname_form #name').val();
        var comment = jQuery('#listname_form #comment').val();
        jQuery('#listname-formButton').click();
        if (name !== null) {
            var reqString = partslist.baseUrl + 'create/description/' + encodeURIComponent(name) + '/comment/' + encodeURIComponent(comment);
            jQuery('#communicating-with-server').addClass('active');
            var request = new Ajax.Request(reqString,
                {
                    method: 'get',
                    onSuccess: function(response) {
                        jQuery('#communicating-with-server').removeClass('active');
                        var json = JSON.parse(response.responseText);
                        checkMessages(json);
                        if (typeof(json.errors) !== 'undefined') {
                            throw (json.errors.join(', '));
                        }
                        if (typeof(json.listId) !== 'undefined') {
                            window.location.href = partslist.baseUrl + 'view/id/' + json.listId;
                        }
                    }
                });
        }
    });
    /*
    jQuery('#listname-form').dialog({ title: titleText,
        buttons: [
            { text: okButtonName, click: function() {
                var name = jQuery('#listname-form #name').val();
                var comment = jQuery('#listname-form #comment').val();
                jQuery( this ).dialog( "close" );
                if (name !== null) {
                    var reqString = partslist.baseUrl + 'create/description/' + encodeURIComponent(name) + '/comment/' + encodeURIComponent(comment);
                    jQuery('#communicating-with-server').addClass('active');
                    var request = new Ajax.Request(reqString,
                        {
                            method: 'get',
                            onSuccess: function(response) {
                                jQuery('#communicating-with-server').removeClass('active');
                                var json = JSON.parse(response.responseText);
                                checkMessages(json);
                                if (typeof(json.errors) !== 'undefined') {
                                    throw (json.errors.join(', '));
                                }
                                if (typeof(json.listId) !== 'undefined') {
                                    window.location.href = partslist.baseUrl + 'view/id/' + json.listId;
                                }
                            }
                        });
                }
            } },
            { text: cancelButtonName, click: function() { jQuery(this).dialog('close'); } }
        ],
        open: function(event, ui) {
            jQuery('#listname-form input#name').removeAttr('disabled').focus();
        },
        width: 350
    });
    */
}


function editPartslist(titleText, id) {
    jQuery('#listname-formButton').click();
    jQuery('#listname-formSubmit_modified').off('click').click(function(){
        var name = jQuery('#listname_form #name').val();
        var comment = jQuery('#listname_form #comment').val();
        jQuery('#listname-formButton').click();
        if (name !== null) {
            var reqString = partslist.baseUrl + 'edit/id/' + id + '/description/' + encodeURIComponent(name) + '/comment/' + encodeURIComponent(comment);
            jQuery('#communicating-with-server').addClass('active');
            var request = new Ajax.Request(reqString,
                {
                    method: 'get',
                    onSuccess: function(response) {
                        jQuery('#communicating-with-server').removeClass('active');
                        var json = JSON.parse(response.responseText);
                        checkMessages(json);
                        if (typeof(json.errors) !== 'undefined') {
                            throw (json.errors.join(', '));
                        }
                        if (typeof(json.listId) !== 'undefined') {
                            window.location.href = partslist.baseUrl + 'view/id/' + json.listId;
                        }
                    }
                });
        }
    });
    /*
    jQuery('#listname-form').dialog({ title: titleText,
        buttons: [
            { text: okButtonName, click: function() {
                var name = jQuery('#listname-form #name').val();
                var comment = jQuery('#listname-form #comment').val();
                jQuery( this ).dialog( "close" );
                if (name !== null) {
                    var reqString = partslist.baseUrl + 'edit/id/' + id + '/description/' + encodeURIComponent(name) + '/comment/' + encodeURIComponent(comment);
                    jQuery('#communicating-with-server').addClass('active');
                    var request = new Ajax.Request(reqString,
                        {
                            method: 'get',
                            onSuccess: function(response) {
                                jQuery('#communicating-with-server').removeClass('active');
                                var json = JSON.parse(response.responseText);
                                checkMessages(json);
                                if (typeof(json.errors) !== 'undefined') {
                                    throw (json.errors.join(', '));
                                }
                                if (typeof(json.listId) !== 'undefined') {
                                    window.location.href = partslist.baseUrl + 'view/id/' + json.listId;
                                }
                            }
                        });
                }
            } },
            { text: cancelButtonName, click: function() { jQuery(this).dialog('close'); } }
        ],
        open: function(event, ui) {
            jQuery('#listname-form input#name').removeAttr('disabled').focus();
        },
        width: 350
    });
    */
}

function printCheckedDocuments(baseUrl) {
    try {
        var rowIdClass;
        if (arguments.length === 2)
            rowIdClass = arguments[1];
        else
            rowIdClass = 'rowId';
        var rows = jQuery('.' + rowIdClass + ':checked');
        if (rows.length === 0) {
            // showOverlayMessage('error', pleaseSelectSomeRowsFirstText, '');
            alert(pleaseSelectSomeRowsFirstText);
            return;
        }
        rows.each(function(i, e) {
            var id = jQuery(this).prop('id');
            var rowId = id.split('-')[1];
            var documentId = jQuery('#documentId-' + rowId).val();
            var type = jQuery('#type-' + rowId).val();
            var orderId = jQuery('#orderId-' + rowId).val();
            if (documentId && type && orderId) {
                openUrl(baseUrl + 'documentId/' + documentId + '/type/' + type + '/id/' + orderId);
            }
        });
    } catch (e) {
        alert(e);
        console.log(e);
    }
    return false;
}

jQuery.fn.center = function() {
    this.css({
        'position': 'fixed',
        'left': '50%',
        'top': '50%'
    });
    this.css({
        'margin-left': -this.width() / 2 + 'px',
        'margin-top': -this.height() / 2 + 'px'
    });

    return this;
};

jQuery(document).ready(function() {
    jQuery('#communicating-with-server').center();
});

(function(jQuery) {
    jQuery.fn.copyAttributes = function(fromEl) {
        var attrs = {};
        var self = this;
        jQuery.each(fromEl[0].attributes, function(idx, attr) {
            if (attr.nodeName === 'class') {
                jQuery(attr.nodeValue.split(' ')).each(function (i, cl) {
                    self.addClass(cl);
                });
            } else
                self.attr(attr.nodeName, attr.nodeValue);
        });
    };

    jQuery.fn.changeElementType = function(newType) {
        var attrs = {};

        jQuery.each(this[0].attributes, function(idx, attr) {
            attrs[attr.nodeName] = attr.nodeValue;
        });
        return jQuery("<" + newType + "/>", attrs).append(jQuery(this).contents().clone());
    };
})(jQuery);


function appendMessageUl(messages, ulClazz, liClazz, iconClazz) {
    //console.log('ListRequestManager::appendMessageUl()');
    jQuery('.success-msg').remove();
    jQuery('.notice-msg').remove();
    jQuery('.error-msg').remove();
    jQuery('.smackbar').remove();

    if ( typeof(ulClazz) === 'undefined' )  {
        //ulClazz = 'messages';
        ulClazz = 'messages';
    }
    if ( typeof(liClazz) === 'undefined' )  {
        liClazz = 'success-msg';
    }
    var eternalSnackbar = false;
    if (ulClazz == 'messages_hidden_eternal') {
        eternalSnackbar = true;
        ulClazz = 'messages_hidden';
    }
    var ul = jQuery('ul.' + ulClazz);
    if (ul.length === 0) {
        ul = jQuery('<ul class="' + ulClazz + '"></ul>');
        ul.prependTo(jQuery('div#content'));
    }
    if (jQuery('.listArea')) {
        unsetOverlayLoaderCentral('listArea');
    }
    //jQuery('#notice-message-container-overview').show(); // This shows all message HTML (should be replaced by smackbar!)
    var msgContent = '<li class="' + liClazz + '">';
    msgContent += '<ul>';
    msgContent += '<li>';
    msgContent += '<span class="' + iconClazz + '"></span>';
    var joinContent = '</span></li><li><span class="' + iconClazz + '"></span> <span>';
    msgContent += ' <span>' + messages.join(joinContent);
    msgContent += '</span></li></ul></li>'
    ul.append(msgContent);
    var completeMsgHtml = jQuery(ul).html();
    var completeMsgHtmlModified = completeMsgHtml.replace('success-msg', 'success-msg messages_snackbar');
    completeMsgHtmlModified = completeMsgHtmlModified.replace('notice-msg', 'notice-msg messages_snackbar');
    completeMsgHtmlModified = completeMsgHtmlModified.replace('error-msg', 'error-msg messages_snackbar');

    var counter = 10000; // default = 10.000 = 10 seconds (duration of showing snackbar)
    if ( completeMsgHtmlModified.search(/onclick/i) > 0
        || completeMsgHtmlModified.search(/href/i) > 0
        || completeMsgHtmlModified.search(/javascript/i) > 0 ) {
        counter = 10000000; // Eternal view time of snackbar
        console.log('Show SnackBar eternally');
    }

    // Only shows snackbar if explicitely needed:
    //console.log(completeMsgHtmlModified);
    if (ulClazz == 'messages_hidden') {
        if (eternalSnackbar == true) {
            counter = 10000000; // Eternal view time of snackbar
        }
        smackbar({
            message: completeMsgHtmlModified, // this is the only required field
            timeout: counter, // sepcify time in ms after the toast closes. set to false or 0 to disable
            onclose: function() {
                console.log('Closing Snackbar Message Notification')
            }
        })
    }
}


function setOverlayLoader(area, ajaxLoaderGifPath) {
    // Set overlay for
    jQuery('.' + area).css({
        "position": "absolute",
        "height": "50%",
        "width": "100%",
        "background": "url(" + ajaxLoaderGifPath + ") no-repeat center center",
        "bottom": "-100px",
        "left": "0",
        "opacity": "0.4",
        "z-index": "99"});
}


function unsetOverlayLoaderCentral (areaClass) {
    console.log('Overlay Loader Unloaded: class"' + areaClass + '"');
    if (jQuery('.' + areaClass)) {
        jQuery('.' + areaClass).removeAttr('style');
    }
}


/*
 * check messages and errors and display them
 */
function checkMessages(json) {
    //console.log('ListRequestManager::checkMessages()');
    jQuery('ul.messages').empty();
    jQuery('ul.errors').empty();
    if (typeof(json.messages) !== 'undefined') {
        // For mini cart count update
        if (json.numberOfDifferentItemsInCart) {
            jQuery('.MyCart').append('<div id="cartNoBxItemCount" class="cartNoBx">' + json.numberOfDifferentItemsInCart + '</div>');
        }
        appendMessageUl(json.messages, 'messages_hidden', 'success-msg', 'glyphicon glyphicon-ok');
        console.log('appendMessageUl #44');
        // jQuery("html, body").animate({ scrollTop: 0 }, "slow"); // Global Message System
        // console.log('ScrollTop #11');
    }

    if (typeof(json.errors) !== 'undefined') {
        appendMessageUl(json.errors, 'messages_hidden', 'error-msg', 'glyphicon glyphicon-exclamation-sign');
        console.log('appendMessageUl #45');
        // jQuery("html, body").animate({ scrollTop: 0 }, "slow"); // Global Message System
        // console.log('ScrollTop #12');
    }
    if (jQuery('.controlsArea')) {
        jQuery('.controlsArea').removeAttr('style');
    }
    if (jQuery('.productListArea')) {
        jQuery('.productListArea').removeAttr('style');
    }
}

/*
 * Showa different kind (alternate) of overlay messages
 */
function showOverlayMessage(style, messageText, headline) {
    if (jQuery('#overlay-popup')) {
        jQuery('#overlay-popup').remove();
    }
    var messageStyleClass = 'schrack-message-' + style;

    var html  = '<div id="overlay-popup" class="schrack-popup-overlay">';
    html += '<div id="overlay-popup-message" class="schrack-popup ' + messageStyleClass + '">';
    html += '<h2>' + headline + '</h2>';
    html += '<a class="close" href="#">&#10006;</a>';
    html += '<div class="content">';
    html += '<span class="schrack-symbol-' + style + '">&nbsp;</span>' + messageText;
    html += '</div></div></div>';

    jQuery(document.body).append(html);
    window.location.href = "#overlay-popup";
    return false;
}
