(function ($, Drupal) {
  Drupal.behaviors.tacServiceYoutubeApi = {
    attach: function attach(context) {
      if (contextIsRoot(context)) {
        // Service Youtube API
        tarteaucitron.services.youtubeapi = {
          "key": "youtubeapi",
          "type": "api",
          "name": "Youtube API",
          "uri": "https://www.google.fr/intl/fr/policies/privacy/",
          "needConsent": true,
          "cookies": ['VISITOR_INFO1_LIVE', 'YSC', 'PREF', 'GEUP', 's_gl', 'SSID', 'SID', 'SAPISID', 'LOGIN_INFO', 'HSID', 'CONSENT', 'APISID'],
          "js": function () {
            tarteaucitron.reloadThePage = true;

            // Implémentation de l'appel à l'API Youtube dans la seconde
            // librairie qui vérifie de son coté si le service est accepté. =>
            // Voir tac_services/libraries/tac_youtube_api/youtube_api.js
          }
        };

        // Rajout de Youtbe API à la liste des services soumis à l'acceptation de
        // l'utilisateur.
        (tarteaucitron.job = tarteaucitron.job || []).push('youtubeapi');
      }
    }
  };
})(jQuery, Drupal);