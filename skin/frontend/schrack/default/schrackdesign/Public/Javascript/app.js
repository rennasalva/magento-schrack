
function setTableHeights() {
    var oldRows = jQuery('.table > .row, .table > .row-fine, .table.row, .table.row-fine').not('.no-table-heights');

    for (var ir = 0; ir < oldRows.length; ++ir) {
        var oldRow, maxHeight, ic, inc, oldCols, oldCol, newRow, newCols;
        oldRow = oldRows[ir];
        if ( !jQuery(oldRow).hasClass('cart-header') && !jQuery(oldRow).hasClass('cart-buttons') ) {
            maxHeight = -1;
            oldCols = jQuery('> .columns, > .columns-fine', jQuery(oldRow));
            for (ic = 0; ic < oldCols.length; ++ic) {
                oldCol = oldCols[ic];
                maxHeight = maxHeight > jQuery(oldCol).height() ? maxHeight : jQuery(oldCol).height();
                var cssMinHeight = jQuery(oldCol).css('min-height');
                var pncmh = ( window.parseInt ? window.parseInt(cssMinHeight) : ( Number.parseInt ? Number.parseInt(cssMinHeight) : 0 ) );
                if ( cssMinHeight.include('px') && pncmh > maxHeight ) {
                    maxHeight = pncmh;
                }
            }
            newRow = oldRow.cloneNode(true);
            newRow.parentElement = oldRow.parentElement; // those two get lost somehow...
            newRow.parentNode = oldRow.parentNode;
            newCols = jQuery('> .columns, > .columns-fine', jQuery(newRow));
            for (inc = 0; inc < newCols.length; ++inc) {
                newCols[inc].style.height = '' + maxHeight + 'px';
            }
            oldRow.parentElement.replaceChild(newRow, oldRow);
        }
    }
}
jQuery(document).ready(function() {
    setTableHeights();
    jQuery('select.dropdown-menu').dropdown({activateOnClick: false});
});
jQuery(window).load(function() {
    jQuery('.body .price-col > .table > .row > .columns > div').addClass('bottom-align-container-0'); // @todo, in produkt-view(???) verschieben
    window.onbeforeprint = function() { setTableHeights();};
});


