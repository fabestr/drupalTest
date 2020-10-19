// Conditional call of the google maps api (according to tarteaucitron cookie)
var GMaps = function (serviceData) {
  var self = this;
  self.serviceId = 'googlemapsapi';

  /**
   * Initialize the gmaps API if the user has accepted it
   */
  self.init = function () {
    if (TacHelpers.checkCookie(self.serviceId)) {
      self.gmapsApiKey = serviceData.google_maps_api_key;
      tarteaucitron.addScript('//maps.googleapis.com/maps/api/js?key=' + self.gmapsApiKey + '&callback=google_maps_api_initMap');
    }
  };
};

(function ($, Drupal, DrupalSettings) { // closure
  'use strict';
  Drupal.behaviors.gmapsApiInit = { // behaviors
    attach: function (context) {
      if (contextIsRoot(context)) {
        var serviceData = drupalSettings.tacServices.google_map_api_tac_service;
        var Map = new GMaps(serviceData);
        Map.init();
      }
    }
  };
}(jQuery, Drupal, drupalSettings));