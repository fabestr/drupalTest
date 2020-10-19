(function () {
  'use strict';

  function _classCallCheck(instance, Constructor) {
    if (!(instance instanceof Constructor)) {
      throw new TypeError("Cannot call a class as a function");
    }
  }

  function _defineProperties(target, props) {
    for (var i = 0; i < props.length; i++) {
      var descriptor = props[i];
      descriptor.enumerable = descriptor.enumerable || false;
      descriptor.configurable = true;
      if ("value" in descriptor) descriptor.writable = true;
      Object.defineProperty(target, descriptor.key, descriptor);
    }
  }

  function _createClass(Constructor, protoProps, staticProps) {
    if (protoProps) _defineProperties(Constructor.prototype, protoProps);
    if (staticProps) _defineProperties(Constructor, staticProps);
    return Constructor;
  }

  var $ = jQuery;

  var Lazyloading =
  /*#__PURE__*/
  function () {
    function Lazyloading() {
      _classCallCheck(this, Lazyloading);
    }

    _createClass(Lazyloading, [{
      key: "process",
      value: function process(context) {
        $(context).find('img.lazyload').each(function (i, elem) {
          var $image = $(elem);
          $image.on('load', function (ev) {
            return Lazyloading.onImageLoaded(ev);
          });
          $image.attr('src', $image.attr('data-src')).removeAttr('data-src');

          if (typeof $image.attr('data-srcset') !== 'undefined') {
            $image.attr('srcset', $image.attr('data-srcset')).removeAttr('data-srcset');
          }
        });
      }
    }], [{
      key: "onImageLoaded",
      value: function onImageLoaded(ev) {
        $(ev.currentTarget).addClass('lazyloaded');
      }
    }]);

    return Lazyloading;
  }();

  var lazyloading = new Lazyloading();

  Drupal.behaviors.lazyloading = {
    attach: function attach(context, settings) {
      lazyloading.process(context);
    }
  };

}());
