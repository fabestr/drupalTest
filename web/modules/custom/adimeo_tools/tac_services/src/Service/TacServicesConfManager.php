<?php

namespace Drupal\tac_services\Service;

/**
 * Class TacServicesConfManager.
 *
 * @package tac_services
 */
class TacServicesConfManager {

  const SERVICE_NAME = 'tac_services.conf_manager';

  private $configFactory;

  /**
   * Constructs a new TacServicesConfManager object.
   */
  public function __construct() {
    $this->configFactory = \Drupal::service('config.factory');
  }

  /**
   * Store the Tac Services configuration data.
   *
   * @param array $data
   *    The data to store in conf.
   */
  public function setTacServicesConf(array $data) {
    $this->configFactory
      ->getEditable(self::SERVICE_NAME)
      ->set('data', $data)
      ->save();
  }

  /**
   * Get the Tac Services configuration data.
   *
   * @return array|string|null
   *    The data stored in conf.
   */
  public function getTacServicesConf() {
    return \Drupal::config(self::SERVICE_NAME)->get('data');
  }

}
