<?php

namespace Drupal\tac_services\Plugin\tac_service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tac_services\Interfaces\TacServiceInterface;
use Drupal\tac_services\Service\TacServicesConfManager;

/**
 * Plugin implementation of the tac_service.
 *
 * @TacServiceAnnotation(
 *   id = "matomo_tac_service",
 *   label = "Matomo",
 *   weight = 0
 * )
 */
class MatomoTacService implements TacServiceInterface {

  /**
   * Constant which stores the plugin ID.
   */
  const PLUGIN_ID = 'matomo_tac_service';

  /**
   * Constant which stores the site id.
   */
  const SITE_ID = 'site_id';

  /**
   * Constant which stores the piwik server URL.
   */
  const SERVER_URL = 'server_url';

  /**
   * Constant which stores the library name.
   */
  const LIBRARY_NAME = 'tac_services/tac_matomo';

  /**
   * The conf manager.
   *
   * @var TacServicesConfManager
   */
  protected $servicesManager;

  /**
   * GoogleMapsApiTacService constructor.
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
        static::SITE_ID => '',
        static::SERVER_URL => '',
      ];
    }

    $form[static::SITE_ID] = [
      '#type' => 'textfield',
      '#title' => t('Matomo site id'),
      '#default_value' => $data[static::SITE_ID],
    ];
    $form[static::SERVER_URL] = [
      '#type' => 'textfield',
      '#title' => t('Matomo server url'),
      '#default_value' => $data[static::SERVER_URL],
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
      static::SITE_ID => $form_state->getValue(static::SITE_ID),
      static::SERVER_URL => $form_state->getValue(static::SERVER_URL),
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
