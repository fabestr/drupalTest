<?php

namespace Drupal\adimeo_tools\Service;

class EnvironmentService {
  /**
   * Service name.
   */
  const SERVICE_NAME = 'adimeo_tools.environment';

  /**
   * Production value.
   */
  const ENV_PRODUCTION = 'production';

  /**
   * Staging value.
   */
  const ENV_STAGING = 'staging';

  /**
   * Dev value.
   */
  const ENV_DEVELOPMENT = 'development';

  /**
   * Retourne le singleton (quand pas d'injection de dépendances possible)
   *
   * @return static
   *    Static object.
   */
  public static function me() {
    return \Drupal::service(static::SERVICE_NAME);
    //$envService = EnvironmentService::me();
  }

  /**
   * Return the current environment value.
   *
   * @return null|string
   *   The current environment.
   */
  public function getEnvironment() {
    if (defined('ENV')) {
      return ENV;
    }

    // By default we considere there is no env settings:
    return NULL;
  }

  /**
   * Return true if the environment is production.
   *
   * @return bool
   *   Environment is production
   */
  public function isProduction() {
    return in_array($this->getEnvironment(), [self::ENV_PRODUCTION]);
  }

  /**
   * Return true if the environment is staging.
   *
   * @return bool
   *   Environment is staging
   */
  public function isStaging() {
    return in_array($this->getEnvironment(), [
      self::ENV_STAGING
    ]);
  }

  /**
   * Return true if the environment is development.
   *
   * @return bool
   *   Environment is development
   */
  public function isDevelopment() {
    return in_array($this->getEnvironment(), [self::ENV_DEVELOPMENT]);
  }

}
