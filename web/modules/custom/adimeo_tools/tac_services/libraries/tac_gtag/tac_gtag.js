(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.tacServiceGTag = {
    attach: function attach(context) {
        if (contextIsRoot(context)) {
        tarteaucitron.user.googleGTagId = drupalSettings.tacServices.google_gtag_tac_service.google_gtag_key;
        (tarteaucitron.job = tarteaucitron.job || []).push('gtag');
      }
    }
  };
})(jQuery, Drupal, drupalSettings);