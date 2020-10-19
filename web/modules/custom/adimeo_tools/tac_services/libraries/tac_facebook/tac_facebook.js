(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.facebook = {
    attach: function attach(context) {
      if (contextIsRoot(context)) {
     window.fbAsyncInit = function() {
    FB.init({
        appId      : drupalSettings.tacServices.facebook_tac_services.facebook_key,
        xfbml      : true,
        version    : 'v2.8'
    });
    FB.AppEvents.logPageView();
};
        (tarteaucitron.job = tarteaucitron.job || []).push('facebook');
      }
    }
  };
})(jQuery, Drupal, drupalSettings);