<?php

namespace Drupal\tac_services\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class TacServicePluginManager.
 *
 * @package tac_services
 */
class TacServicePluginManager extends DefaultPluginManager {

  /**
   * Service Id.
   */
  const SERVICE_NAME = 'tac_service.plugin.manager';

  /**
   * Plugin package.
   */
  const PACKAGE_NAME = 'tac_service';

  /**
   * Plugin mapper.
   *
   * @var \Drupal\tac_services\Interfaces\TacServiceInterface[]
   */
  protected $mapper = [];

  /**
   * TacServicePluginManager constructor.
   *
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/' . self::PACKAGE_NAME, $namespaces, $module_handler, 'Drupal\tac_services\Interfaces\TacServiceInterface', 'Drupal\tac_services\Annotation\TacServiceAnnotation');
  }

  /**
   * Get instance of the plugin.
   *
   * @param array $options
   *    Array of option.
   *
   * @return \Drupal\tac_services\Interfaces\TacServiceInterface|false|null|object
   *    An instance of the plugin class
   */
  public function getInstance(array $options) {
    if (array_key_exists('id', $options) && $pluginId = $options['id']) {
      if (!array_key_exists($pluginId, $this->mapper)) {
        if ($instance = $this->createInstance($pluginId)) {
          $this->mapper[$pluginId] = $instance;
        }
      }

      return $this->mapper[$pluginId];
    }
    return NULL;
  }

}
