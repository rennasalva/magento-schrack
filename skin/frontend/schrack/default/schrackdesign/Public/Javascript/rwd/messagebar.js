jQuery(document).ready(function () {

    // TODO : Reading localstorage, if message is available:
    var date = new Date();
    var oneDayBeforeTS = date.setDate(date.getDate() - 1);
    var oneDayBeforeStr = new Date(oneDayBeforeTS).toLocaleString();
    var dateArrOneDayBefore = oneDayBeforeStr.split(",");
    //console_info(dateArrOneDayBefore[0]);
    var dateNow = new Date();
    var nowTS = dateNow.setDate(dateNow.getDate());
    var nowStr = new Date(nowTS).toLocaleString();
    var dateArrNow = nowStr.split(",");
    //console_info(dateArrNow[0]);

    var runMessageBarUpdate = false;
    var alreadyClosedMessageBars;

    if (localStorage.dayTicketMessageBars && localStorage.dayTicketMessageBars == dateArrNow[0]) {
        // alert('Day Ticket for messageBar is Up-To-Date');
    } else {
        if (dateArrNow[0] != dateArrOneDayBefore[0]) {
            cleanupMessageBars();
            localStorage.setItem('dayTicketMessageBars', dateArrNow[0]);
            localStorage.setItem('runRotateMessageBar', 'yes');
        }
    }

    if (localStorage.getItem("allMessageBars") === null) {
        localStorage.setItem('allMessageBars', '');
    }

    if (localStorage.getItem("no_other_proccess_running_on_MessageBars") === null) {
        localStorage.setItem('no_other_proccess_running_on_MessageBars', 'true');
    } else {
        localStorage.no_other_proccess_running_on_MessageBars = 'true';
    }

    if (localStorage.getItem("expiringMessageBarTime") === null) {
        localStorage.setItem('expiringMessageBarTime', 86400000);
    }

    if (localStorage.getItem("currentMessageBarLink") === null) {
        localStorage.setItem('currentMessageBarLink', '');
    }

    if (localStorage.getItem("currentMessageBarCampaign") === null) {
        localStorage.setItem('currentMessageBarCampaign', '');
    }

    if (localStorage.getItem("currentMessageBarContent") === null) {
        localStorage.setItem('currentMessageBarContent', '');
    }

    if (localStorage.getItem("currentMessageBar") === null) {
        localStorage.setItem('currentMessageBar', '');
    }

    if (localStorage.getItem("runRotateMessageBar") === null) {
        localStorage.setItem('runRotateMessageBar', 'no');
    }

    // Komma reparated list of UID's (Array)
    if (localStorage.getItem("alreadyClosedMessageBars") === null) {
        localStorage.setItem('alreadyClosedMessageBars', '');
    }

    if (localStorage.getItem("timeClosedLastMessageBar") === null) {
        localStorage.setItem('timeClosedLastMessageBar', '');
    }

    if (localStorage.getItem("timeClosedLastMessageBarHumanReadable") === null) {
        localStorage.setItem('timeClosedLastMessageBarHumanReadable', '');
    }

    if (localStorage.getItem("customerFormerlyNotLoggedInWhileFetchedMessageBar") === null) {
        localStorage.setItem('customerFormerlyNotLoggedInWhileFetchedMessageBar', '');
    }

    if (localStorage.getItem("trackingFetchedMessageBars") === null) {
        localStorage.setItem('trackingFetchedMessageBars', '');
    }

    if (localStorage.getItem("trackingClosedMessageBars") === null) {
        localStorage.setItem('trackingClosedMessageBars', '');
    }

    if (localStorage.getItem("trackingClickedMessageBars") === null) {
        localStorage.setItem('trackingClickedMessageBars', '');
    }

    if (localStorage.currentMessageBar == '') {
        refreshData('current messageBar is empty');
    } else {
        var expireTime = parseInt(localStorage.expiringMessageBarTime) + parseInt(localStorage.timeClosedLastMessageBar);
        if (expireTime < nowTS) {
            rotateMessageBar('expiration time reached for rotation');
        }
    }

    setTimeout(function() {
            jQuery('#closeMessageBarSymbol').on('click', function(){
                var uid = jQuery(this).attr('data-uid');
                closeMessageBar(uid);
            });


            function closeMessageBar(uid) {
                if (localStorage.alreadyClosedMessageBars == '') {
                    var closedMessageBar = [];
                    closedMessageBar.push(uid);
                    localStorage.alreadyClosedMessageBars = JSON.stringify(closedMessageBar);
                } else {
                    var alreadyClosedMessageBars = JSON.parse(localStorage.alreadyClosedMessageBars);
                    alreadyClosedMessageBars.push(uid);
                    localStorage.alreadyClosedMessageBars = JSON.stringify(alreadyClosedMessageBars);
                }
                var dateNow = new Date();
                var nowTS = dateNow.setDate(dateNow.getDate());

                // Tracking Part: "Closed"
                if (compareWithLocalStorage('trackingClosedMessageBars', localStorage.currentMessageBar) == false) {
                    messageBarTracking('Closed Message Bar', localStorage.currentMessageBarCampaign, localStorage.currentMessageBar);
                    addToLocalStorage('trackingClosedMessageBars', localStorage.currentMessageBar);
                }

                localStorage.timeClosedLastMessageBar = nowTS;
                localStorage.timeClosedLastMessageBarHumanReadable = new Date(nowTS).toLocaleString();
                localStorage.currentMessageBarContent = '';
                localStorage.currentMessageBarCampaign = '';
                localStorage.currentMessageBarLogin = '';
                localStorage.currentMessageBarLink = '';

                if(jQuery(window).width() <= 992) {
                    jQuery('#messagebar_form_mini_smartphone').removeClass('form_mini_message_bar_modus_smartphone');
                    jQuery('#messagebar_form_mini_smartphone').html('');
                } else {
                    jQuery('.main_header').removeClass('header_message_bar_modus');
                    jQuery('#messagebar_content').html('');
                }
            }

            jQuery('.linkTargetMessageBar').on('click', function() {
                // Tracking Part: "Clicked"
                if (compareWithLocalStorage('trackingClickedMessageBars', localStorage.currentMessageBar) == false) {
                    messageBarTracking('Clicked Message Bar', localStorage.currentMessageBarCampaign, localStorage.currentMessageBar);
                    addToLocalStorage('trackingClickedMessageBars', localStorage.currentMessageBar);
                }
                window.location.href = localStorage.currentMessageBarLink;
            });

        }, 3000);


    function cleanupMessageBars() {
        localStorage.removeItem("allMessageBars");
        localStorage.removeItem("alreadyClosedMessageBars");
        localStorage.removeItem("availableMessageBars");
        localStorage.removeItem("currentMessageBar");
        localStorage.removeItem("currentMessageBarCampaign");
        localStorage.removeItem("currentMessageBarContent");
        localStorage.removeItem("currentMessageBarLink");
        localStorage.removeItem("currentMessageBarLogin");
        localStorage.removeItem("customerFormerlyNotLoggedInWhileFetchedMessageBar");
        localStorage.removeItem("expiringMessageBarTime");
        localStorage.removeItem("runRotateMessageBar");
        localStorage.removeItem("timeClosedLastMessageBar");
        localStorage.removeItem("timeClosedLastMessageBarHumanReadable");
        localStorage.removeItem("trackingClickedMessageBars");
        localStorage.removeItem("trackingClosedMessageBars");
        localStorage.removeItem("trackingFetchedMessageBars");
    }


    function showMessageBar(content, section) {
        console_info('showMessageBar called');
        var currentUrl = window.location.href;
        if (currentUrl.indexOf('checkout/onepage') > 0) {
            // Don't show messagebars in checkout:
            console_info('WELCOME TO CHECKOUT -> no message bars');
            return false;
        }

        localStorage.no_other_proccess_running_on_MessageBars = 'false';
        console_info(section);
        console_info(content);

        setTimeout(function() {
            if (localStorage.customerNotLoggedIn == 1) {
                localStorage.customerFormerlyNotLoggedInWhileFetchedMessageBar = 1;
            }
            if (localStorage.customerFormerlyNotLoggedInWhileFetchedMessageBar == 1
                && localStorage.customerNotLoggedIn == 0) {
                localStorage.customerFormerlyNotLoggedInWhileFetchedMessageBar = 0;
                console_info_red('Customer logged in now - before not logged-in');
                refreshData('customer currently logged id now, but before logged out');
            }
            if (localStorage.customerNotLoggedIn == 1 && localStorage.currentMessageBarLogin == 2) {
                // Don't show messageBar
                return false;
            }

            if (localStorage.getItem("alreadyClosedMessageBars") != '') {
                var alreadyClosedMessageBars = JSON.parse(localStorage.alreadyClosedMessageBars);
                var currentMessageBar = localStorage.currentMessageBar;

                if (alreadyClosedMessageBars && inArray(currentMessageBar, alreadyClosedMessageBars)) {
                    console_info('current selected messageBar' + currentMessageBar + 'was closed already')
                    // Number of already closed messageBars is lower than all available messageBars:
                    var availableMessageBars = JSON.parse(localStorage.availableMessageBars);
                    if (alreadyClosedMessageBars.length < availableMessageBars.length) {
                        var result = rotateMessageBar('closed messageBar: not all messageBars closed');
                    } else {
                        localStorage.setItem('alreadyClosedMessageBars', '');
                        var result = rotateMessageBar('now closed all available messageBars');
                    }
                    if (result == false) {
                        return false;
                    }
                }
            }

            if(jQuery(window).width() <= 992) {
                jQuery('#messagebar_form_mini_smartphone').addClass('form_mini_message_bar_modus_smartphone');
                jQuery('#messagebar_form_mini_smartphone').html(content);
                jQuery('.closeMessageBar').addClass('closeMessageBarSmartphone')
                jQuery('.closeMessageBarSmartphone').removeClass('closeMessageBar')
                jQuery('.messagebar_html').addClass('messagebar_html_smartphone');
                jQuery('.messagebar_html_smartphone').removeClass('messagebar_html');
                jQuery('.innerTextMessageBarBig').addClass('innerTextMessageBarBigSmartphone');
                jQuery('.innerTextMessageBarBig').removeClass('innerTextMessageBarBig');
                // Detect, if a button is included:
                if (content.indexOf('innerTextMessageBarMedium') > 0) {
                    jQuery('.form_mini_message_bar_modus_smartphone').addClass('doubleHeight');
                    jQuery('.messagebar_html_smartphone').addClass('doubleHeight');
                }
                jQuery('#closeMessageBarSymbol').css('z-index', '-1');
                jQuery('#messagebar_form_mini_smartphone').fadeIn(500);
                setTimeout(function() {
                    if (jQuery('.messagebar_html_smartphone').length) {
                        var offset = jQuery('.messagebar_html_smartphone').offset();
                        var width = jQuery('.messagebar_html_smartphone').width();
                        var diff = 19;
                        var total = offset.left + width - diff;
                        jQuery('#closeMessageBarSymbol').css('left', total + 'px');
                        jQuery('#closeMessageBarSymbol').css('z-index', '1');
                    }}, 700);
            } else {
                jQuery('.main_header').addClass('header_message_bar_modus');
                jQuery('#messagebar_content').html(content);
                jQuery('#closeMessageBarSymbol').css('z-index', '-1');
                jQuery('#messagebar_content').fadeIn(500);
                setTimeout(function() {
                    if (jQuery('.messagebar_html').length) {
                        var offset = jQuery('.messagebar_html').offset();
                        var width = jQuery('.messagebar_html').width();
                        var diff = 19;
                        var total = offset.left + width - diff;
                        jQuery('#closeMessageBarSymbol').css('left', total + 'px');
                        jQuery('#closeMessageBarSymbol').css('z-index', '1');
                    }}, 700);
            }

            // Tracking Part: "Fetched"
            if (compareWithLocalStorage('trackingFetchedMessageBars', localStorage.currentMessageBar) == false) {
                messageBarTracking('Fetched Message Bar', localStorage.currentMessageBarCampaign, localStorage.currentMessageBar);
                addToLocalStorage('trackingFetchedMessageBars', localStorage.currentMessageBar);
            }
        }, 1000);
    }


    function inArray(needle, haystack) {
        var length = haystack.length;
        for(var i = 0; i < length; i++) {
            if(haystack[i] == needle) return true;
        }
        return false;
    }


    function rotateMessageBar(message) {
        localStorage.no_other_proccess_running_on_MessageBars = 'false';
        // Some condition with expiration time implementation needed  ??
        console_info_orange('rotateMessageBar ' + message);
        // Set the next messageBar to current messageBar:
        if (localStorage.timeClosedLastMessageBar != '') {
            var dateNow = new Date();
            var nowTS = dateNow.setDate(dateNow.getDate());
            var expireTime = parseInt(localStorage.expiringMessageBarTime) + parseInt(localStorage.timeClosedLastMessageBar);
            var currentMessageBar = localStorage.currentMessageBar;
            if (expireTime < nowTS) {
                console_info('Wait Time Expired');
                var availableMessageBars = JSON.parse(localStorage.availableMessageBars);
                console_info('still have some unclosed messageBars available');
                for (var i = 0; i < availableMessageBars.length; i++) {
                    if (parseInt(currentMessageBar) == parseInt(availableMessageBars[i])) {
                        var nextMessageBar = parseInt(availableMessageBars[i+1]);
                        if (isNaN(nextMessageBar)) {
                            localStorage.alreadyClosedMessageBars = '';
                            refreshData('messageBar is out of range');
                        } else {
                            localStorage.currentMessageBar = nextMessageBar;
                            console_info_pink('Changed Message Bar #2 To next value (UID) = ' + nextMessageBar);
                            var allMessagebars = JSON.parse(localStorage.allMessageBars);
                            localStorage.currentMessageBarContent  = allMessagebars[nextMessageBar].body;
                            localStorage.currentMessageBarCampaign = allMessagebars[nextMessageBar].campaignName;
                            localStorage.currentMessageBarLink     = allMessagebars[nextMessageBar].link;
                            localStorage.currentMessageBarLogin    = allMessagebars[nextMessageBar].login;

                            // Emulate expiretime:
                            dateNow = new Date();
                            nowTS = dateNow.setDate(dateNow.getDate());
                            localStorage.timeClosedLastMessageBar = nowTS
                            localStorage.timeClosedLastMessageBarHumanReadable = new Date(nowTS).toLocaleString();

                            showMessageBar(allMessagebars[nextMessageBar].body, 'section 1');
                            return true;
                        }
                    }
                }
            } else {
                // Show the active messagebar, if its not expired (already checked above!) or closed already:
                var alreadyClosedMessageBars;
                if (localStorage.alreadyClosedMessageBars == '') {
                    alreadyClosedMessageBars = '';
                } else {
                    alreadyClosedMessageBars = JSON.parse(localStorage.alreadyClosedMessageBars);
                }

                if (alreadyClosedMessageBars == '' || (alreadyClosedMessageBars != ''
                    && !inArray(currentMessageBar, alreadyClosedMessageBars))) {
                    showMessageBar(localStorage.currentMessageBarContent, 'section 14');
                }

                console_info('Wait Time Is Not Expired');
                return false;
            }
        } else {
            return false;
        }
    }

    // AJAX CAll to get messagebars for a user:
    function refreshData(message) {
       console_info_green('refreshData ' + message);
        // Reset all base params:
       localStorage.setItem('allMessageBars', '');
       localStorage.setItem('currentMessageBarLink', '');
       localStorage.setItem('currentMessageBarCampaign', '');
       localStorage.setItem('currentMessageBarContent', '');
       localStorage.setItem('runRotateMessageBar', 'no');

        var ajaxUrl = BASE_URL + 'sd/Api/getMessageBarTypoSnippet/';
        var params;
        /* Example:
             params = {
            'snippet_numbers':[1, 2],
            'override_cache':1,
            'customer_email':'Sampletext'
        }*/

        jQuery.ajax(ajaxUrl, {
            'type' : 'POST',
            'data': params,
            'success' : function(data) {
                var datax = JSON.parse(data);
                var messagesBars = [];
                /*for (var index = 0; index < datax.length; index++) {
                    console_info('**********************************');
                    console_info(datax[index]);
                }*/
                var messageBarsAvailable = true;
                if (Array.isArray(datax) && datax.length === 0) {
                    messageBarsAvailable = false;
                    console_info('No MessageBars available');
                }

                if (messageBarsAvailable == true) {
                    for(var uids in datax) {
                        messagesBars.push(uids);
                    }
                    localStorage.allMessageBars = JSON.stringify(datax);
                    var currentMessageBar = messagesBars[0];
                    localStorage.availableMessageBars = JSON.stringify(messagesBars);
                    if (typeof(currentMessageBar) != 'undefined') {
                        localStorage.currentMessageBar = currentMessageBar;
                        console_info('Changed Message Bar #1 To value (UID) = ' + currentMessageBar);
                    }

                    if (localStorage.alreadyClosedMessageBars == '') {
                        alreadyClosedMessageBars = '';
                    } else {
                        alreadyClosedMessageBars = JSON.parse(localStorage.alreadyClosedMessageBars);
                    }

                    if (typeof(currentMessageBar) != 'undefined' && alreadyClosedMessageBars == '' || (alreadyClosedMessageBars != ''
                        && !inArray(currentMessageBar, alreadyClosedMessageBars))) {
                        console_info('Current MessageBar-UID = ' + messagesBars[0]);

                        var currentMessageBarContent  = datax[currentMessageBar].body;
                        var currentMessageBarCampaign = datax[currentMessageBar].campaignName;
                        var currentMessageBarLink     = datax[currentMessageBar].link;
                        var currentMessageBarLogin    = datax[currentMessageBar].login;

                        localStorage.currentMessageBarContent  = currentMessageBarContent;
                        localStorage.currentMessageBarCampaign = currentMessageBarCampaign;
                        localStorage.currentMessageBarLink     = currentMessageBarLink;
                        localStorage.currentMessageBarLogin    = currentMessageBarLogin;

                        // Emulate expiretime:
                        dateNow = new Date();
                        nowTS = dateNow.setDate(dateNow.getDate());
                        localStorage.timeClosedLastMessageBar = nowTS
                        localStorage.timeClosedLastMessageBarHumanReadable = new Date(nowTS).toLocaleString();

                        showMessageBar(currentMessageBarContent, 'section 2');
                    }
                } else {
                    localStorage.setItem('currentMessageBar', '');
                    localStorage.setItem('availableMessageBars', '');
                    console_info('No ActiveMessageBar');
                }
                localStorage.runRotateMessageBar = 'no';
            }
        });
    }

    function console_info(message) {
        console.log("%c messageBar %c " + message, "color: white; background: #17a2b8;", "");
    }

    function console_info_green(message) {
        console.log("%c messageBar %c " + message, "color: white; background: #638F4C;", "");
    }

    function console_info_orange(message) {
        console.log("%c messageBar %c " + message, "color: white; background: #D97300;", "");
    }

    function console_info_pink(message) {
        console.log("%c messageBar %c " + message, "color: black; background: pink;", "");
    }

    function console_info_violet(message) {
        console.log("%c messageBar %c " + message, "color: white; background: violet;", "");
    }

    function console_info_red(message) {
        console.log("%c messageBar %c " + message, "color: white; background: red;", "");
    }

    var localstorage_messagebar_content = localStorage.getItem('currentMessageBarContent');
    if (localStorage.runRotateMessageBar == 'no'
        && localstorage_messagebar_content
        && localStorage.no_other_proccess_running_on_MessageBars == 'true') {

        var currentMessageBar = localStorage.currentMessageBar;
        var alreadyClosedMessageBars;
        if (localStorage.alreadyClosedMessageBars == '') {
            alreadyClosedMessageBars = '';
        } else {
            alreadyClosedMessageBars = JSON.parse(localStorage.alreadyClosedMessageBars);
        }

        if (alreadyClosedMessageBars == '' || (alreadyClosedMessageBars != ''
            && !inArray(currentMessageBar, alreadyClosedMessageBars))) {
            console_info_violet('Default: no rotation - just stay here until time expired or customer has closed');
            showMessageBar(localStorage.currentMessageBarContent, 'section 3');
        }
    }

    function messageBarTracking (eventAction, campaignName, campaignId) {
        if (dataLayer) {
            dataLayer.push({
                'eventAction': eventAction,
                'eventLabel': campaignName,
                //'eventId': campaignId,
                'event': 'eventMessageBar'
            });
        }
    }

    function addToLocalStorage(localstoragekey, currentMessageBar) {
        var values = [];
        if (localStorage.getItem(localstoragekey) != '') {
            values = JSON.parse(localStorage.getItem(localstoragekey));
        }
        values.push(currentMessageBar);
        localStorage.setItem(localstoragekey, JSON.stringify(values));
    }

    function compareWithLocalStorage(localstoragekey, currentMessageBar) {
        if (localStorage.getItem(localstoragekey) == '') {
            return false;
        } else {
            var values = JSON.parse(localStorage.getItem(localstoragekey));
            for (var i = 0; i < values.length; i++) {
                if (currentMessageBar == values[i]) {
                    return true;
                }
            }
        }
        return false;
    }

});
