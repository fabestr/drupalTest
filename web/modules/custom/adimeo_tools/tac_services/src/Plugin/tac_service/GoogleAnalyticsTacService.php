<?php

namespace Drupal\tac_services\Plugin\tac_service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tac_services\Interfaces\TacServiceInterface;
use Drupal\tac_services\Service\TacServicesConfManager;

/**
 * Plugin implementation of the tac_service.
 *
 * @TacServiceAnnotation(
 *   id = "google_analytics_tac_service",
 *   label = "Google Analytics",
 *   weight = 2
 * )
 */
class GoogleAnalyticsTacService implements TacServiceInterface {

  /**
   * Constant which stores the plugin ID.
   */
  const PLUGIN_ID = 'google_analytics_tac_service';

  /**
   * Constant which stores the Google analytics key field name.
   */
  const FIELD_GA_KEY = 'google_ga_key';

  /**
   * Constant which stores the library name.
   */
  const LIBRARY_NAME = 'tac_services/tac_google_ga';

  /**
   * The conf manager.
   *
   * @var TacServicesConfManager
   */
  protected $servicesManager;

  /**
   * GoogleAnalyticsTacService constructor.
   */
  public function __construct() {
    $this->servicesManager = \Drupal::service(TacServicesConfManager::SERVICE_NAME);
  }

  /**
   * Return the form part to include in the main conf admin page.
   *
   * @return array
   *    Array containing the form part needed to get the specific data relative
   *   to the service. Can be empty.
   */
  public function getTacServiceConfForm() {
    $form = [];
    $conf = $this->servicesManager->getTacServicesConf();
    if (isset($conf[static::PLUGIN_ID]['data'])) {
      $data = $conf[static::PLUGIN_ID]['data'];
    }
    else {
      $data = [
        static::FIELD_GA_KEY => '',
      ];
    }

    $form[static::FIELD_GA_KEY] = [
      '#type' => 'textfield',
      '#title' => t('Google Analytics Key'),
      '#default_value' => $data[static::FIELD_GA_KEY],
    ];

    return $form;
  }

  /**
   * Prepare the data for conf save.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *    Array containing the data relative to the service prepared to be stored
   *   in configuration. Can be empty.
   */
  public function prepareTacServiceConfData(array $form, FormStateInterface $form_state) {
    $data = [
      static::FIELD_GA_KEY => $form_state->getValue(static::FIELD_GA_KEY),
    ];

    return $data;
  }

  /**
   * Return the Library to use to implement the service through Tarteaucitron.js.
   *
   * @return string
   *    Library name (use const to store it)
   */
  public function getTacServiceLibrary() {
    return static::LIBRARY_NAME;
  }

}
