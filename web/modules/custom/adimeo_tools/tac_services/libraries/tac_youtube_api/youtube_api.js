// Conditional call of the youtube api (according to tarteaucitron cookie)
var YoutubeApi = function ($) {
  var self = this;
  self.serviceId = 'youtubeapi';

  /**
   * Initialize the Youtube API if the user has accepted it
   */
  self.init = function () {

    if (TacHelpers.checkCookie(self.serviceId)) {
      // YOUTUBE
      // Load the IFrame Player API code asynchronously.
      var tag = document.createElement('script');
      tag.src = "https://www.youtube.com/player_api";
      var firstScriptTag = document.getElementsByTagName('script')[0];
      firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    }
  };
};

(function ($, Drupal) { // closure
  'use strict';
  Drupal.behaviors.youtubeApiInit = { // behaviors
    attach: function () {
      var Youtube = new YoutubeApi($);
      Youtube.init();
    }
  };
}(jQuery, Drupal));