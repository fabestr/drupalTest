(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.tacServiceGmapsApi = {
    attach: function attach(context) {
      if (contextIsRoot(context)) {
        // Service google maps API
        tarteaucitron.services.googlemapsapi = {
          "key": "googlemapsapi",
          "type": "api",
          "name": "Google Maps API",
          "uri": "http://www.google.com/ads/preferences/",
          "needConsent": true,
          "cookies": [],
          "js": function () {
            tarteaucitron.reloadThePage = true;

            // Implémentation de l'appel à l'API GMaps dans la seconde
            // librairie qui vérifie de son coté si le service est
            // accepté.
            // => Voir
            // tac_services/libraries/tac_google_maps_api/gmaps_api.js
          }
        };

        // Rajout de gmaps API à la liste des services soumis à l'acceptation de
        // l'utilisateur.
        (tarteaucitron.job = tarteaucitron.job || []).push('googlemapsapi');
      }
    }
  };
})(jQuery, Drupal, drupalSettings);