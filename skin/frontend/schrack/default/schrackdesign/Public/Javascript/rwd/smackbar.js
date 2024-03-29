function smackbar (obj) {
    obj = obj || {}
    obj.timeout = obj.timeout || 4000

    if (!obj.message) {
        throw new Error('no message specified')
    }

    var $smackbar = jQuery('<div/>')
    $smackbar.addClass('smackbar')

    function close() {
        $smackbar.removeClass('smackbar--shown')
        setTimeout(function () {
            $smackbar.remove()
        }, 1100)
        if (obj.onclose) obj.onclose()
    }

    $smackbar.html('<div class="smackbar-text">' + obj.message + '</div>')

    if (obj.button) {
        var $smackbarButton = jQuery('<div/>')
        $smackbarButton.addClass('smackbar-button btn')
        $smackbarButton.html(obj.button.text)

        $smackbar.prepend($smackbarButton)

        if (obj.button.onclick) {
            $smackbarButton.click(function () {
                obj.button.onclick()

                if (!obj.preventClose) {
                    close()
                }
            })
        }
    }

    $smackbar.prepend('<div class="smackbar-close">&#10005;</div>')

    jQuery('body').append($smackbar)

    setTimeout(function () {
        $smackbar.addClass('smackbar--shown')
    }, 50)

    jQuery('.smackbar .smackbar-close').click(function () {
        close()
    })

    if (obj.timeout) {
        setTimeout(close, obj.timeout)
    }
}


