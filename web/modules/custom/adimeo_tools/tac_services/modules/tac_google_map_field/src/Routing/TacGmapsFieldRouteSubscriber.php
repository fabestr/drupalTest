<?php

namespace Drupal\tac_google_map_field\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class TacGmapsFieldRouteSubscriber extends RouteSubscriberBase {

  /**
   * @inheritDoc
   */
  protected function alterRoutes(RouteCollection $collection) {
    $collection->remove('gmap.field.settings');
  }

}
