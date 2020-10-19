<?php
namespace Drupal\tac_services\Plugin\tac_service;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tac_services\Interfaces\TacServiceInterface;
use Drupal\tac_services\Service\TacServicesConfManager;

/**
 * Plugin implementation of the tac_service.
 *
 * @TacServiceAnnotation(
 *   id = "facebook_tac_services",
 *   label = "Facebook",
 *   weight = 2
 * )
 */
class FacebookTacService implements TacServiceInterface {

  /**
   * Constant which stores the plugin ID.
   */
  const PLUGIN_ID = 'facebook_tac_services';

  /**
   * Constant which stores the facebook key field name.
   */
  const FIELD_KEY = 'facebook_key';

  /**
   * Constant which stores the library name.
   */
  const LIBRARY_NAME = 'tac_services/tac_facebook';

  /**
   * The conf manager.
   *
   * @var TacServicesConfManager
   */
  protected $servicesManager;

  /**
   * FacebookTacService constructor.
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
        static::FIELD_KEY => '',
      ];
    }

    $form[static::FIELD_KEY] = [
      '#type' => 'textfield',
      '#title' => t('Facebook Key'),
      '#default_value' => $data[static::FIELD_KEY],
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
      static::FIELD_KEY => $form_state->getValue(static::FIELD_KEY),
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
