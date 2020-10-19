(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.tacServiceFBPixel = {
    attach: function attach(context) {
      if (contextIsRoot(context)) {
        tarteaucitron.user.facebookpixelId = drupalSettings.tacServices.facebook_pixel_tac_service.facebook_pixel_id;
        tarteaucitron.user.facebookpixelMore = function () { /* add here your optionnal facebook pixel function */ };
        (tarteaucitron.job = tarteaucitron.job || []).push('facebookpixel');
      }
    }
  };
})(jQuery, Drupal, drupalSettings);