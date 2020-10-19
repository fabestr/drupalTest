(function ($, Drupal, DrupalSettings) { // closure
    'use strict';
    Drupal.behaviors.icon_selector = { // behaviors
        attach: function(context) {

            $(context).find('.icon_selector_details').each(function(){
                var $wrapper = $(this);

                $('.icon_selector_item').click(function(){
                    var $parent = $($(this).parents('.icon_selector_details')[0]);

                    // Delete other.
                    $parent.find('.icon_selector_item').removeClass('selected');

                    $(this).addClass('selected');
                    $(this).find('[type=radio]').prop('checked','checked');

                    $parent.find( '[type="hidden"]' ).val($(this).find('[type=radio]').val());
                });

                // init
                $wrapper.find('.icon_selector_item.selected').each(function () {
                   // $(this).click();
                });
            })

            $(function () {
                var lazyloadImages = document.querySelectorAll("img.lazy");
                var lazyloadThrottleTimeout;

                if(lazyloadThrottleTimeout) {
                    clearTimeout(lazyloadThrottleTimeout);
                }
                setTimeout(function() {
                    lazyloadThrottleTimeout = setTimeout(function() {
                        lazyloadImages.forEach(function(img) {
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                        });
                    }, 250);
                },500);
            })
        }
    };
}(jQuery, Drupal, drupalSettings));