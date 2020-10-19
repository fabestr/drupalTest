// clic2drive
tarteaucitron.services.clic2drive = {
    "key": "clic2drive",
    "type": "ads",
    "name": "Clic 2 Drive",
    "uri": "https://www.clic2buy.com/",
    "needConsent": true,
    "cookies": ['fingerprint-0.0.4','visit-0.0.4','basket-0.0.4','_-0.0.4','hubspotutk',
        '__hssc','__hssrc','__hstc','__utma','__utmb','__utmc','__utmt','__utmz'], //domaine des cookies : ".clic2buy.com"
    "js": function () {
        tarteaucitron.addScript('https://widget.clic2drive.com/assets/c2d.js?ver=1.0');
    }
};
(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.tacServiceC2D = {
        attach: function attach(context) {
            if (contextIsRoot(context)) {
                (tarteaucitron.job = tarteaucitron.job || []).push('clic2drive');
            }
        }
    };
})(jQuery, Drupal, drupalSettings);