(function ($, Drupal) {
    Drupal.behaviors.tacServiceKissmetrics = {
        attach: function attach(context) {
            if (contextIsRoot(context)) {
                // Service Kissmetrics
                tarteaucitron.services.kissmetrics = {
                    "key": "kissmetrics",
                    "type": "analytic",
                    "name": "Kissmetrics",
                    "uri": "https://signin.kissmetrics.com/privacy",
                    "needConsent": true,
                    "cookies": ['km_ai', 'km_lv', 'km_vs', 'kvcd'],
                    "js": function () {
                        tarteaucitron.reloadThePage = true;
                        var _kmq = _kmq || [];
                        var _kmk = _kmk || tarteaucitron.user.kissmetricsKey;
                        //var _kmk = _kmk || 'e0f3fe6b2b0be2289ac29a65f9e866ea44c0819a';
                        function _kms(u){
                            setTimeout(function(){
                                var d = document, f = d.getElementsByTagName('script')[0],
                                    s = d.createElement('script');
                                s.type = 'text/javascript'; s.async = true; s.src = u;
                                f.parentNode.insertBefore(s, f);
                            }, 1);
                        }
                        _kms('//i.kissmetrics.com/i.js');
                        _kms('//scripts.kissmetrics.com/' + _kmk + '.2.js');
                    }
                };

                tarteaucitron.user.kissmetricsKey = drupalSettings.tacServices.kissmetrics_tac_service.kissmetrics_key;
                (tarteaucitron.job = tarteaucitron.job || []).push('kissmetrics');
            }
        }
    };
})(jQuery, Drupal);