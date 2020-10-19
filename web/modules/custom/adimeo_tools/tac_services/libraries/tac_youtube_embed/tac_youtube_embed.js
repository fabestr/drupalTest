(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.tacServiceYoutubeEmbeb = {
    attach: function attach(context) {
      if (contextIsRoot(context)) {
        (tarteaucitron.job = tarteaucitron.job || []).push('youtube');
      }
    }
  };
})(jQuery, Drupal, drupalSettings);