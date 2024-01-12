
var i_barcodePicker = null;
var i_initDone = false;
var i_afterInitOrigin = null;
var i_afterInitStartFunc = null;
var i_originElementStack = [];
var i_imageOverlayLoaderUrl = "https://www.schrack.at/shop/skin/frontend/schrack/default/schrackdesign/Public/Images/download_ajax_loader.gif"; // fallback, will cause SPC probs
var i_storedOverlayElementCss = null;
var i_hintText = null;

function mayScan () {
    // always on local development environment
    if ( location.hostname == "www.schrack.at.localhost" || location.hostname == "www.schrack.at.local" || location.hostname == "test-cz.schrack.com" ) {
        return true;
    }
    // see https://stackoverflow.com/questions/11381673/detecting-a-mobile-browser
    !function(a){var b=/iPhone/i,c=/iPod/i,d=/iPad/i,e=/(?=.*\bAndroid\b)(?=.*\bMobile\b)/i,f=/Android/i,g=/(?=.*\bAndroid\b)(?=.*\bSD4930UR\b)/i,h=/(?=.*\bAndroid\b)(?=.*\b(?:KFOT|KFTT|KFJWI|KFJWA|KFSOWI|KFTHWI|KFTHWA|KFAPWI|KFAPWA|KFARWI|KFASWI|KFSAWI|KFSAWA)\b)/i,i=/IEMobile/i,j=/(?=.*\bWindows\b)(?=.*\bARM\b)/i,k=/BlackBerry/i,l=/BB10/i,m=/Opera Mini/i,n=/(CriOS|Chrome)(?=.*\bMobile\b)/i,o=/(?=.*\bFirefox\b)(?=.*\bMobile\b)/i,p=new RegExp("(?:Nexus 7|BNTV250|Kindle Fire|Silk|GT-P1000)","i"),q=function(a,b){return a.test(b)},r=function(a){var r=a||navigator.userAgent,s=r.split("[FBAN");return"undefined"!=typeof s[1]&&(r=s[0]),s=r.split("Twitter"),"undefined"!=typeof s[1]&&(r=s[0]),this.apple={phone:q(b,r),ipod:q(c,r),tablet:!q(b,r)&&q(d,r),device:q(b,r)||q(c,r)||q(d,r)},this.amazon={phone:q(g,r),tablet:!q(g,r)&&q(h,r),device:q(g,r)||q(h,r)},this.android={phone:q(g,r)||q(e,r),tablet:!q(g,r)&&!q(e,r)&&(q(h,r)||q(f,r)),device:q(g,r)||q(h,r)||q(e,r)||q(f,r)},this.windows={phone:q(i,r),tablet:q(j,r),device:q(i,r)||q(j,r)},this.other={blackberry:q(k,r),blackberry10:q(l,r),opera:q(m,r),firefox:q(o,r),chrome:q(n,r),device:q(k,r)||q(l,r)||q(m,r)||q(o,r)||q(n,r)},this.seven_inch=q(p,r),this.any=this.apple.device||this.android.device||this.windows.device||this.other.device||this.seven_inch,this.phone=this.apple.phone||this.android.phone||this.windows.phone,this.tablet=this.apple.tablet||this.android.tablet||this.windows.tablet,"undefined"==typeof window?this:void 0},s=function(){var a=new r;return a.Class=r,a};"undefined"!=typeof module&&module.exports&&"undefined"==typeof window?module.exports=r:"undefined"!=typeof module&&module.exports&&"undefined"!=typeof window?module.exports=s():"function"==typeof define&&define.amd?define("isMobile",[],a.isMobile=s()):a.isMobile=s()}(this);
    return isMobile.any || isIpad();
}

function isIpad () {
    var ua = window.navigator.userAgent;
    if ( ua.indexOf('iPad') > -1 ) {
        return true;
    }

    if ( ua.indexOf('Macintosh') > -1 ) {
        try {
            document.createEvent('TouchEvent');
            return true;
        } catch ( e ) {}
    }

    return false;
}

function initScan ( baseUrl, idsToShowAfterInit, idBarcodePicker, imgOverlayLoaderUrl, hintText ) {
    if ( ! mayScan() ) {
        return;
    }
    if ( imgOverlayLoaderUrl != null ) {
        i_imageOverlayLoaderUrl = imgOverlayLoaderUrl;
    }
    var licenceKey = null;
    if ( location.hostname == "www.schrack.at.local" || (location.hostname.startsWith('test-') && location.hostname.endsWith('.schrack.com')) ) {
        // all test servers (test-*.schrack.com) and local VMs with domain www.schrack.at.local:
        licenceKey = "AfqM4VhORx04MGjA1BBW7tg0GJdPLMwMEEJsvb827QdWNoEn3W21GuJcygYSJk49lEdfCW93bMYhTt/nAHyLGiJlKscvYJKqsxuWqYEVfkCfTmbcy1X+xhhZC5HvXs5BZF56O65+1W+uZrSiBGu1ynwyRSVuA3i9+zm02DJJ6zD4b51pnXueFJVx5Dj+fcAZn2bEfCdsbfEpZ4jU+VvQAnse/FZWQ8XpsGcx2u9BIC8MXELuHGVr/QtL3NOJVuQFBkbHIEkwNvaraCJYbl7QvL1Mxn76U2tlsWj+D0NurbNIbE8BIlG9Hx1TgILtc+zg325QmDlaMD2WNwgZS0Tg4nB3AWJvQ6CDkW/fO71KGjecY+2GrWGwyHFdkERwAn/1RnGfuCBe7recYM0oMl4znl9y0I14KbbVn0koV4pcZEGYeoUCj2a+OAAzD69la7ylqkB0UYZo7eXWYdcZC0Kk1EFOzyKaIf1X02o/Vup6R5OqY3s9pmoZ08hrsTy3R3lswUTGqflgaIB8V2OesFLu9xxVK+98VbqdhFmZiUlUOERNEOKDbHEmTIIca4MpeKhinSpCPG51M/CVa8qAexTn9/o9KuAWRDbNH5dYZ4AjQjfHmG9hhdAxoGWyl2pmtgSpQa0Nj23Dgr9N0Bp1sse5JBTieTxOyVHliPDuu1ngEiXJTe6E+c5MXCv6+wONz3TSK54w+uQQ7KetZCZiVaVodxqyYqZpIId/3HY7ASNG1AG0nGHw9CL9GKzi0jH44Uc8Ex9fi2peiEqrPrN+Frq7ePTqNs6ge/VsKe8+Oxt465Dw+rm0RKAE78k6jBpPWwOo5BgH9M5kUHNflLdoR9c+ITy2eGBOkAZYiu5cwyD11n15vXJ9Gd/dc4jc4QLz8kTNU/H/pX6q/CfoyDX1Eh7989V2s70vIdbcQz15zJ8xhd8ZfH9vG60VxZKGJxZFWX9FcYxbIDAaydH1e2e8Ax+K/Uobe3sHgOyY3Aal2s8Vp4zUndnVJ9n6j8/BP/TuQYaf57YVK0bBgxsgCMzcydKsnt1cOUx5dfXuenI40qR+fsBWtSERvhtaDSE2u4yKCF1XkoTtBWj5uPZstPGM1f83njHlsL7Alo7OEsgihwt3yRFINLVNt+axXff8aivIgAmrotlGqZgzoQ/cXz97vPDvq1ncN0NuX3XaeaBDr0U+n4QqUV3yl0NEYxf3HwdaiEtyWh7eU5tv4PB65AhP/KiU4VHcfqSS4vHZaww8XF+heQuF73jox53p4yyyVlfd07zqKa8erH6XpR4=";
    } else {
        // all productive servers
        licenceKey = "AWBsLYmBHZNPG18CMjHgp6YARpP2P9WWHEMQ9lhpSvXLQawnSlqSwvhcQBBVBY78/kKVslVeBskBU9V3V0Z8cphOSHgmcemSqg4ldXlNkN5JWlwmX0i8xOhPIbYCWWOBfF0zz+hcRn2cSldivGFXuoN3+b7RVPqMTUmdtTgxVT9NIXwuqDnGTIBlS/mgJc+FeFWxyjRjcVPLQygxPnrV0SNwSBZOJUQMrnhKeHFhcdkIcmVf+0UKkTF7iWavKu7N/C+0kMBay6nfbTAlRU985ydPciI7WH7+G0gSLsBNEdH4ctdkGkWeCg5jRRngQQtCQ2x5HGR9hoLvZs/qLTXgx11TfLZoHpasx0uMvJEbTvVuSne6KHUNdtNH529UXQ193G7dYoFov3BmYss0KGptyJZtSBlOSCXJN318+PJcuIHCVr1VmkvaJVF47DcZQHhzYzTcbeNxOCAVL4qAkmWrCPZik1rwNKvWdgvm4vU61QbaoHYpbRDQfffPm58eDyq09v7xMBSDLsFmN+QS0RL/l8ti18rkKbCSWSbpQvHG+rS01RJv3+Fq4NEWuImjpuRcIt6rsygT18MA9UXDQTWJ9zNE0GAYLGy5QDMI1loa033T0XTd0dIbHrKjkxQ36n64ntnJhr2sUU9e/z2SNK3M+qMH+sIbUq9b3pcUqqBXFuQNHsybLtkZjs6eJScjB0xxQ6lJgYcWAyuhaCdKffw/cDGdlRpeIaSWRHj2eKgYeqPGoVcODie55SEN6kWwOzR28DnKjKqB+Vsp+vofGe00prqlMYUlEkV+lKEYn4XY7GR1wPiHxYzSk+FOl1/guXAksUNGe1ns59RSilNJ78TcWxrhQcBDNrVJKcMwGuoNOBr4uqPWgtVAzaQQWX4nSe8Kq91zWPfXMRZMKmEDssgRAqcAJELZe3kVUQZpMySMM5s/8gLpwzyS3cHUh8w3ujbwr72d4GWisvjQclzCmqilLIIJ/YGidAR9V1WbdB9TQjLr2cRFFLUxLAHEANZWjjT9s7braFkKCel7DB7/eYgWGl9gRWKrd61eZHIEcebKKHtEr6m4Xjs/69XLR30u9R/wwIZf5K0iYAyKx7QZr7SsYlMT4awxox/Usb6D0UW13JPLY9py5fJPSP9l+ICJE6bdWpiP+6yHsKFNE+g6H1e+aw==";
    }

    var scanditUrl = baseUrl + "skin/frontend/schrack/default/schrackdesign/Public/Javascript/scandit/browser/index.min.js";
    var engineParams = { engineLocation: baseUrl + "skin/frontend/schrack/default/schrackdesign/Public/Javascript/scandit" };
    var scannerParams = { accessCamera: false, visible: false, playSoundOnScan: true, vibrateOnScan: true };
    var scanSettings = { enabledSymbologies: ["ean13", "qr", "code128", "code39"], codeDuplicateFilter: 1000, searchArea: { x: 0.05, y: 0.2, width: 0.9, height: 0.6 } };
    var initialized = false;
    if ( hintText != null ) {
        i_hintText = hintText;
    }

    var onReadyFunction = function()
    {
        console.log("Start of onReadyFunction()");
        // new Promise((resolve) => setTimeout(resolve, 5000)).then(function () { // just for testing longer init with superfast Firefox ;-)

        if ( idsToShowAfterInit != null ) {
            if ( Array.isArray(idsToShowAfterInit) ) {
                idsToShowAfterInit.forEach(function (id) {
                    jQuery('#' + id).show();
                });
            } else {
                jQuery('#' + idsToShowAfterInit).show();
            }
        }
        if ( i_afterInitOrigin != null ) {
            i_barcodePicker.reassignOriginElement(document.getElementById(i_afterInitOrigin));
            i_afterInitOrigin = null;
        }
        if ( i_afterInitStartFunc != null ) {
            i_afterInitStartFunc();
            i_afterInitStartFunc = null;
        }
        i_initDone = true;

        // }); // just for testing longer init with superfast Firefox ;-)
    };

    var initAfterLoadingScandit = function ()
    {
        console.log("Start of initAfterLoadingScandit()");
        if ( ! initialized ) {
            initialized = true;
            ScanditSDK.configure(licenceKey,engineParams).then(function ()
            {
                i_originElementStack.unshift(idBarcodePicker);
                return ScanditSDK.BarcodePicker.create(document.getElementById(idBarcodePicker), scannerParams)
                    .then(function ( bcp ) {
                        i_barcodePicker = bcp;
                        i_barcodePicker.applyScanSettings(new ScanditSDK.ScanSettings(scanSettings));
                        i_barcodePicker.setVideoFit('cover');
                        i_barcodePicker.setGuiStyle('viewfinder');
                        i_barcodePicker.setCameraSwitcherEnabled(false);
                        i_barcodePicker.on('scanError', function ( error ) {
                            console.error(error.message);
                        });
                        i_barcodePicker.on('ready', onReadyFunction);
                    })
                    .catch(function (error) {
                        alert(error);
                    });
            });
        }
    };

    console.log("Starting load scandit");
    i_loadScript(scanditUrl,initAfterLoadingScandit);
}

function openPopupAndScan ( popupID, resultHandler ) {
    if ( i_barcodePicker != null ) {
        jQuery('#' + popupID).modal();
        i_showSomethingAndScan(resultHandler);
    }
}

function showElementAndScan ( elementID, resultHandler ) {
    if ( i_barcodePicker != null ) {
        jQuery('#' + elementID).show();
        i_showSomethingAndScan(resultHandler);
    }
}

function showAndStartScan ( resultHandler ) {
    if ( i_barcodePicker != null ) {
        i_showSomethingAndScan(resultHandler);
    }
}

function stopScan () {
    if ( i_barcodePicker != null ) {
        if ( i_initDone ) {
            console.log("stopping Scan...");
            i_barcodePicker.setVisible(false);
            i_barcodePicker.pauseScanning(true);
            if ( i_hintText != null ) {
                jQuery('#ScanHintText').remove();
            }
        } else {
            i_afterInitStartFunc = null;
        }
    }
}

function pauseScan () {
    if ( i_barcodePicker != null && i_initDone ) {
        i_barcodePicker.pauseScanning(false);
    }
}

function resumeScan () {
    if ( i_barcodePicker != null && i_initDone ) {
        i_barcodePicker.resumeScanning();
    }
}

function pushScannerOriginElement ( idElement ) {
    if ( i_barcodePicker != null ) {
        i_originElementStack.unshift(idElement);
        if ( i_initDone ) {
            i_barcodePicker.reassignOriginElement(document.getElementById(idElement));
        } else {
            i_afterInitOrigin = idElement;
        }
    }
}

function popScannerOriginElement () {
    if ( i_barcodePicker != null && i_originElementStack.length > 1 ) {
        i_originElementStack.shift();
        if ( i_initDone ) {
            i_barcodePicker.reassignOriginElement(document.getElementById(i_originElementStack[0]));
        } else {
            i_afterInitOrigin = null;
        }
    }
}

function i_loadScript ( url, callback ) {
    // Adding the script tag to the head as suggested before
    var head = document.getElementsByTagName('head')[0];
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = url;

    // Then bind the event to the callback function.
    // There are several events for cross browser compatibility.
    script.onreadystatechange = callback;
    script.onload = callback;

    // Fire the loading
    head.appendChild(script);
}

function i_showSomethingAndScan ( resultHandler ) {
    if ( i_initDone ) {
        i_scan(resultHandler);
    } else {
        i_setScannerOverlayLoader();
        i_afterInitStartFunc = function () {
            i_unsetScannerOverlayLoader();
            i_scan(resultHandler);
        };
    }
}

function i_scan ( resultHandler ) {
    if ( i_barcodePicker != null ) {
        console.log("starting Scan...");
        i_barcodePicker.on('scan', resultHandler);
        i_barcodePicker.setVisible(true);
        i_barcodePicker.accessCamera();
        i_barcodePicker.resumeScanning();
        if ( i_hintText != null ) {
            /* does not work with gotten percentage :-(
            var h = jQuery('.scandit-video').height();
            console.log("h = " + h);
            h *= 0.05;
            */
            var h = 14;
            jQuery('<div id="ScanHintText" style="position: absolute; left: 2%; top: 2%; z-index: 3000; color:#FFFFFF; font-size:' + h + 'px;" onclick="switchToEditMode()">' + i_hintText + '</div>').insertAfter('.scandit-logo');
        }
        jQuery('.scandit-torch-toggle').each(function () {
            this.style.setProperty( 'top',    'auto', 'important' );
            this.style.setProperty( 'right',  'auto', 'important' );
            this.style.setProperty( 'bottom', '2%',   'important' );
            this.style.setProperty( 'left',   '2%',   'important' );
        });
    }
}

function i_setScannerOverlayLoader () {
    if ( i_storedOverlayElementCss == null ) {
        var elementID = '#' + i_originElementStack[0];
        var width = '100%'; // jQuery(elementID).css("width");
        var height = '300px';
        var div = '<div id="scannerOverlayLoaderXYZ" style="width:' + width + '; height:' + height + '; background: url(' + i_imageOverlayLoaderUrl + ') no-repeat center center; z-index: 1000;"/>';
        jQuery(div).insertBefore(elementID);
        i_storedOverlayElementCss = true;
    }
}

function i_unsetScannerOverlayLoader () {
    if ( i_storedOverlayElementCss != null ) {
        jQuery('#scannerOverlayLoaderXYZ').remove();
        i_storedOverlayElementCss = null;
    }
}
