(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.tacServiceGA = {
    attach: function attach(context) {
      if (contextIsRoot(context)) {
        tarteaucitron.user.analyticsUa = drupalSettings.tacServices.google_analytics_tac_service.google_ga_key;
        tarteaucitron.user.analyticsMore = function () { /* add here your optionnal ga.push() */
        };
        (tarteaucitron.job = tarteaucitron.job || []).push('analytics');
      }
    }
  };
})(jQuery, Drupal, drupalSettings);