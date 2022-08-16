// GTM consent mode
window.dataLayer = window.dataLayer || [];

function gtag() {
    window.dataLayer.push(arguments);
}

gtag('consent', 'default', {
    analytics_storage: 'denied',
    ad_storage: 'denied',
    personalization_storage: 'denied',
    wait_for_update: 500,
});

if (getCookie('cc_cookie')) {// get from stored cookie banner
    let consentLevel = JSON.parse(getCookie('cc_cookie')).level;
    gtag('consent', 'update', {
        'analytics_storage': consentLevel.indexOf(cookieCategoryAnalytics) > -1 ? 'granted' : 'denied',
        'ad_storage': consentLevel.indexOf(cookieCategoryMarketing) > -1 ? 'granted' : 'denied',
    });

    window.addEventListener('load', function () {
        if (consentLevel.indexOf(cookieCategoryAnalytics) > -1) {
            allowIframe(cookieCategoryAnalytics);
        }

        if (consentLevel.indexOf(cookieCategoryMarketing) > -1) {
            allowIframe(cookieCategoryMarketing)
        }
    });
}

if (gtmId) {
    (function (w, d, s, l, i) {
        w[l] = w[l] || [];
        w[l].push({
            'gtm.start':
                new Date().getTime(), event: 'gtm.js'
        });
        var f = d.getElementsByTagName(s)[0],
            j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
        j.async = true;
        j.src =
            'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
        f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-' + gtmId);
}

/**
 * Set GTM consent mode
 *
 * @param level
 */
function setGTM(level) {
    let storedLevels = JSON.parse(getCookie('cc_cookie')).level;
    // category's status was changed
    if (level.indexOf(cookieCategoryAnalytics) > -1) {
        // category is disabled
        if (!storedLevels.includes(cookieCategoryAnalytics)) {
            gtag('consent', 'update', {
                'analytics_storage': 'denied'
            });
        } else {
            gtag('consent', 'update', {
                'analytics_storage': 'granted'
            });

            allowIframe(cookieCategoryAnalytics);
        }
        window.dataLayer.push({'event': 'consent-update'});
    }
    if (level.indexOf(cookieCategoryMarketing) > -1) {
        // category is disabled
        if (!storedLevels.includes(cookieCategoryMarketing)) {
            gtag('consent', 'update', {
                'ad_storage': 'denied'
            });
        } else {
            gtag('consent', 'update', {
                'ad_storage': 'granted'
            });

            allowIframe(cookieCategoryMarketing);
        }
        window.dataLayer.push({'event': 'consent-update'});
    }
}

function generateID(length) {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * charactersLength));
    }
    return result;
}

function setCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }

    return null;
}

function saveConsent(cookieData, forceSave) {
    if (!getCookie(cookieName) || forceSave) {
        const cookieId = generateID(12);
        cookieData.cookie_id = cookieId;
        cookieData.cookie_expires_in_days = cookieConsentOptions.cookie_expiration;

        let data = {
            action: 'cookies-accepted',
            'ajax-cookies-accepted-nonce': cookiesAcceptedNonce,
            'cookie-data': JSON.stringify(cookieData),
        };

        let xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                // we do not need to show anything
            }
        };
        xhttp.open('POST', adminUrl, true);
        xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded;');
        xhttp.send(new URLSearchParams(data));

        setCookie(cookieName, cookieId, cookieConsentOptions.cookie_expiration);
    }
}

let orestbidaCookieConsent;
window.addEventListener('load', function () {
    orestbidaCookieConsent = initCookieConsent();

    cookieConsentOptions['onFirstAction'] = function (userPreferences, cookie) {
        saveConsent(cookie, false);
        setGTM(cookie.level);
        var settings = document.getElementById('c-settings');
        settings.classList.remove('hidden');
    };

    cookieConsentOptions['onChange'] = function (cookie, changedPreferences) {
        saveConsent(cookie, true);
        setGTM(changedPreferences);
    };

    orestbidaCookieConsent.run(cookieConsentOptions);
    window.dispatchEvent(new CustomEvent('orestbida-consent-loaded', {}));

    document.querySelectorAll('[data-iframe-cookie-needed]').forEach((div) => {
        div.innerHTML = '<div class="iframe-placeholder" style="background-image: url(' + div.getAttribute('data-iframe-placeholder-url') + ')"><div class="iframe-placeholder__overlay"><a href="javascript:void(0);" aria-label="View cookie settings" data-cc="c-settings" class="btn btn-primary iframe-placeholder__btn">' + placeholderButtonText + '</a></div></div>';
    });
});

function allowIframe(cookieCategory) {
    document.querySelectorAll('[data-iframe-cookie-needed="' + cookieCategory + '"]').forEach((div) => {
        let iframe = document.createElement('iframe');
        for (let i = 0, atts = div.attributes, n = atts.length; i < n; i++) {
            if ('data-iframe-cookie-needed' !== atts[i].nodeName && 'data-iframe-placeholder-url' !== atts[i].nodeName) {
                iframe.setAttribute(atts[i].nodeName.replace('data-', ''), atts[i].nodeValue);
            }
            div.innerHTML = '';
        }

        div.after(iframe);
        div.remove();
    });
}

function get_cookies_array() {

    var cookies = [];

    if (document.cookie && document.cookie != '') {
        var split = document.cookie.split(';');

        for (var i = 0; i < split.length; i++) {
            var name_value = split[i].split("=");
            name_value[0] = name_value[0].replace(/^ /, '');
            cookies.push( name_value[0] );
            // cookies[i] = decodeURIComponent(name_value[0]);
        }
    }

    return cookies;
   
}

document.addEventListener('click', function (event) {

	// If the clicked element doesn't have the right selector, bail
	if (!event.target.matches('.cc-delete')) return;

	// Don't follow the link
	event.preventDefault();

	// delete all cookies
    orestbidaCookieConsent.eraseCookies( get_cookies_array() );
    window.location.reload();

}, false);