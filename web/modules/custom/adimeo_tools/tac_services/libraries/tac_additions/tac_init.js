/**
 * Retourne vrai si le contexte est 'document'
 * @param context
 * @returns {boolean}
 */
function contextIsRoot(context){
    return 'HTML' === jQuery(jQuery(context).children()[0]).prop("tagName");
}

var TacEventsHandlers = {
    onServiceAllowAll: function (event) {
        var elem = document.getElementById('tarteaucitronMainLineOffset'),
            $elem = jQuery(elem);
        $elem.addClass('allow');
        $elem.removeClass('deny');
    },
    onServiceDenyAll: function (event) {
        var elem = document.getElementById('tarteaucitronMainLineOffset'),
            $elem = jQuery(elem);
        $elem.addClass('deny');
        $elem.removeClass('allow');
    },
    onServiceUpdateStatus: function (event) {
        var elem = document.getElementById(event.data.key + 'Line'),
            $elem = jQuery(elem);
        switch (event.data.status) {
            case true:
                $elem.addClass("allow");
                $elem.removeClass("deny");
                break;
            case false:
                $elem.addClass("deny");
                $elem.removeClass("allow");
                break;
        }
    },
    onLoadLanguage: function (event) {
        tarteaucitron.lang = {
            "adblock": Drupal.t("Hello! This site is transparent and lets you chose the 3rd party services you want to allow."),
            "adblock_call": Drupal.t("Please disable your adblocker to start customizing."),
            "reload": Drupal.t("Refresh the page"),

            "alertBigScroll": Drupal.t("By continuing to scroll,"),
            "alertBigClick": Drupal.t("If you continue to browse this website,"),
            "alertBig": Drupal.t("you are allowing all third-party services"),

            "alertBigPrivacy": Drupal.t("This site uses cookies and gives you control over what you want to activate"),
            "alertSmall": Drupal.t("Manage services"),
            "personalize": Drupal.t("Personalize"),
            "acceptAll": Drupal.t("OK, accept all"),
            "close": Drupal.t("Close"),

            "all": Drupal.t("Preference for all services"),

            "info": Drupal.t("Protecting your privacy"),
            "disclaimer": Drupal.t("By allowing these third party services, you accept their cookies and the use of tracking technologies necessary for their proper functioning."),
            "allow": Drupal.t("Allow"),
            "deny": Drupal.t("Deny"),
            "noCookie": Drupal.t("This service does not use cookie."),
            "useCookie": Drupal.t("This service can install"),
            "useCookieCurrent": Drupal.t("This service has installed"),
            "useNoCookie": Drupal.t("This service has not installed any cookie."),
            "more": Drupal.t("Read more"),
            "source": Drupal.t("View the official website"),
            "credit": Drupal.t("Cookies manager by tarteaucitron.js"),

            "fallback": Drupal.t("is disabled."),

            "ads": {
                "title": Drupal.t("Advertising network"),
                "details": Drupal.t("Ad networks can generate revenue by selling advertising space on the site.")
            },
            "analytic": {
                "title": Drupal.t("Audience measurement"),
                "details": Drupal.t("The audience measurement services used to generate useful statistics attendance to improve the site.")
            },
            "social": {
                "title": Drupal.t("Social networks"),
                "details": Drupal.t("Social networks can improve the usability of the site and help to promote it via the shares.")
            },
            "video": {
                "title": Drupal.t("Videos"),
                "details": Drupal.t("Video sharing services help to add rich media on the site and increase its visibility.")
            },
            "comment": {
                "title": Drupal.t("Comments"),
                "details": Drupal.t("Comments managers facilitate the filing of comments and fight against spam.")
            },
            "support": {
                "title": Drupal.t("Support"),
                "details": Drupal.t("Support services allow you to get in touch with the site team and help to improve it.")
            },
            "api": {
                "title": Drupal.t("APIs"),
                "details": Drupal.t("APIs are used to load scripts: geolocation, search engines, translations, ...")
            },
            "other": {
                "title": Drupal.t("Other"),
                "details": Drupal.t("Services to display web content.")
            }
        };
    }
};

(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.tacServices = {
    attach: function attach(context) {
      if (contextIsRoot(context)) {
        var settings = drupalSettings.tacServices.globalSettings;
        tarteaucitron.init({
          "hashtag": "#tarteaucitron", /* Ouverture automatique du panel avec le hashtag */
          "highPrivacy": settings.high_privacy, /* désactiver le consentement implicite (en naviguant) ? */
          "allowedButton": settings.allowed_button, /* Active le bouton "accepter tout les cookies". */
          "orientation": settings.orientation, /* le bandeau doit être en haut (top) ou en bas (bottom) ? */
          "adblocker": settings.adblocker, /* Afficher un message si un adblocker est détecté */
          "showAlertSmall": settings.show_alert_small, /* afficher le petit bandeau en bas à droite ? */
          "cookieslist": settings.cookie_list, /* Afficher la liste des cookies installés ? */
          "removeCredit": true /* supprimer le lien vers la source ? */
        });
        document.addEventListener(tarteaucitronEvents.TARTEAUCITRON_LOAD_LANGUAGE, TacEventsHandlers.onLoadLanguage);
        document.addEventListener(tarteaucitronEvents.TARTEAUCITRON_SERVICE_UPDATE_STATUS, TacEventsHandlers.onServiceUpdateStatus);
        document.addEventListener(tarteaucitronEvents.TARTEAUCITRON_SERVICE_ALLOW_ALL, TacEventsHandlers.onServiceAllowAll);
        document.addEventListener(tarteaucitronEvents.TARTEAUCITRON_SERVICE_DENY_ALL, TacEventsHandlers.onServiceDenyAll);
      }
    }
  };
})(jQuery, Drupal, drupalSettings);