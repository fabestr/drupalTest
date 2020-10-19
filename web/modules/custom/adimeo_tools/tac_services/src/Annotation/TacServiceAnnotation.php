<?php

namespace Drupal\tac_services\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Define a Plugin annotation object.
 *
 * @Annotation
 *
 * @ingroup
 */
class TacServiceAnnotation extends Plugin {

  /**
   * The plugin ID : Machine name of the webservice to encapsulate in tarteaucitron.js.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin Label : Human name of the webservice to encapsulate in tarteaucitron.js.
   *
   * @var string
   */
  public $label;

  /**
   * The plugin Weight : Used to sort the list of Services in the conf form.
   *
   * @var int
   */
  public $weight;

}
