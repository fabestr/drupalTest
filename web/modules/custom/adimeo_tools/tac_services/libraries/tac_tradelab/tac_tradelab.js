// tradelab
tarteaucitron.services.tradelab = {
    "key": "tradelab",
    "type": "ads",
    "name": "Tradelab",
    "uri": "http://tradelab.com/vie-privee-2/",
    "needConsent": true,
    "cookies": ['IDE', 'ATN', 'uuid', 'uuid2', 'uuid2'], //domaine des cookies : ".tradelab.fr, .atdmt.com, .adnxs.com, .doubleclick.net"
    "js": function () {
        tarteaucitron.addScript('//cdn.tradelab.fr/tag/' + tarteaucitron.user.tradelabId + '.js');
    }
};
(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.tacServiceTradelab = {
    attach: function attach(context) {
      if (contextIsRoot(context)) {
        tarteaucitron.user.tradelabId = drupalSettings.tacServices.tradelab_tac_service.tradelab_id;
        (tarteaucitron.job = tarteaucitron.job || []).push('tradelab');
      }
    }
  };
})(jQuery, Drupal, drupalSettings);