jQuery.noConflict();

jQuery(document).ready(function () {

    //var ALTERNATE_BASE_URL = 'http://schrack-at.schrack.lan/shop/index.php/admin/customer/testio/key/8d4065e677b71909b519cf6eecc221e1/filter/ZW1haWw9dGVzdC4mY3VzdG9tZXJfc2luY2UlNUJsb2NhbGUlNUQ9ZGVfQVQ=/?ajax=true&isAjax=true';
    var ALTERNATE_BASE_URL = '';
    
    // Receive selecetd Section: -> Customer Edit Section:
    var currentURL = window.location.href;

    if (currentURL.indexOf('tl-wb4.tst.schrack.lan') >= 0) {
        console.log('Test-Site');
        jQuery('<div id="testsite" style="position: absolute; top: 10px; left: 671px; color: yellow !important; font-size: 35px; border: 2px dotted yellow; padding: 4px; z-index: 100000; background: black;">Test-Site</div>').insertAfter('.header-top');
        jQuery('#testsite').css({'position' : 'absolute', 'margin-top' : '4px', 'margin-left' : '84px'});
    } else {
        console.log('ATTENTION: Live-Site');
    }

    if (jQuery('#html-body.adminhtml-customer-edit').length) {
        console.log('>>> Current Section: Customer Edit Module <<<');

        /*
        console.log(ALTERNATE_BASE_URL);

        jQuery('<button id="test">Testbutton</button>').insertAfter('#_accountconfirmation');
        jQuery('#test').css({'position' : 'absolute', 'margin-top' : '4px', 'margin-left' : '84px'});

        jQuery('#test').on('click', function (evt) {
            evt.preventDefault();

            // TODO: AJAX Call to controller, if user is allowed
            jQuery.ajax({
                url: ALTERNATE_BASE_URL,
                type: 'post',
                dataType: 'json',
                data: 'test',
                success: function(data) {
                }
            });
        });
         */

        // Removes action-buttons for normal users:
        /*
        jQuery('.form-buttons').find(':nth-child(2)').remove();
        jQuery('.form-buttons').find(':nth-child(2)').remove();
        jQuery('.form-buttons').find(':nth-child(2)').remove();
        jQuery('.form-buttons').find(':nth-child(2)').remove();
        */
    }

});