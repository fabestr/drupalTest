(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.tacMatomo = {
    attach: function attach(context) {
      tarteaucitron.user.matomoId = drupalSettings.tacServices.matomo_tac_service.site_id;
      tarteaucitron.user.matomoHost = drupalSettings.tacServices.matomo_tac_service.server_url;
      (tarteaucitron.job = tarteaucitron.job || []).push('matomo');
    }
  };
})(jQuery, Drupal, drupalSettings);
