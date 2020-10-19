
var google_map_field_map;

function google_maps_api_initMap() {
  jQuery('.google-map-field .map-container').once('.google-map-field-processed').each(function(index, item) {
    // Get the settings for the map from the Drupal.settings object.
    var lat = jQuery(this).attr('data-lat');
    var lon = jQuery(this).attr('data-lon');
    var zoom = parseInt(jQuery(this).attr('data-zoom'));
    var type = jQuery(this).attr('data-type');
    var show_marker = jQuery(this).attr('data-marker-show') === "true";
    var show_controls = jQuery(this).attr('data-controls-show') === "true";
    var info_window = jQuery(this).attr('data-infowindow') === "true";

    // Create the map coords and map options.
    var latlng = new google.maps.LatLng(lat, lon);
    var mapOptions = {
      zoom: zoom,
      center: latlng,
      streetViewControl: false,
      //mapTypeId: type,
      disableDefaultUI: show_controls ? false : true,
    };

    var google_map_field_map = new google.maps.Map(this, mapOptions);

    google.maps.event.addDomListener(window, 'resize', function() {
      var center = google_map_field_map.getCenter();
      google.maps.event.trigger(google_map_field_map, "resize");
      google_map_field_map.setCenter(center);
    });

    // Drop a marker at the specified position.
    var marker = new google.maps.Marker({
      position: latlng,
      optimized: false,
      visible: show_marker,
      map: google_map_field_map
    });

    if (info_window) {
      var info_markup = jQuery(this).parent().find('.map-infowindow').html();
      var infowindow = new google.maps.InfoWindow({
        content: info_markup
      });

      marker.addListener('click', function () {
        infowindow.open(google_map_field_map, marker);
      });
    }

  });
}

(function ($, Drupal) {

  Drupal.behaviors.tac_google_map_field_renderer = {
    attach: function (context) {

      // If Google maps is not available because of cookies
      if (!TacHelpers.checkCookie('googlemapsapi')) {
        // affichage du placeholder tarteaucitron
        $('.google-map-field .map-container').append(TacHelpers.getPlaceholder('Vos préférences en matière de cookies ne vous permettent pas de visionner cette <b>carte Google Maps</b>'));
      }
    }
  }

})(jQuery, Drupal);
