(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.tacServiceTwitterTimelines = {
        attach: function attach(context) {
            if (contextIsRoot(context)) {
                (tarteaucitron.job = tarteaucitron.job || []).push('twittertimeline');
            }
        }
    };
})(jQuery, Drupal, drupalSettings);