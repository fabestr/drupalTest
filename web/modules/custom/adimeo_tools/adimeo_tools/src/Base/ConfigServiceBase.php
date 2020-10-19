<?php

namespace Drupal\adimeo_tools\Base;

/**
 * Allow to dispatch conf in state or yml.
 *
 * If you pass a table of data to setAllValues() (or set($key,$value),
 * all keys in the getConfAllowedKeysDefaultValues() array keys will be stored in conf (.yml).
 * all keys in the getStateAllowedKeysDefaultValues() array keys will be stored in state (database).
 * Other keys will not be saved.
 *
 * Class ConfigServiceBase
 *
 * @package Drupal\adimeo_tools\Base
 */
abstract class ConfigServiceBase {

  /**
   * Get the conf key.
   *
   * @return string
   *   The conf Id.
   */
  public abstract function getConfId();

  /**
   * Get the conf allowed keys with default values.
   *
   * @return array
   *   The conf default values.
   */
  public abstract function getConfAllowedKeysDefaultValues();

  /**
   * Get the state key.
   *
   * @return string
   *   The state id.
   */
  public abstract function getStateId();

  /**
   * Get the state allowed keys with default values.
   *
   * @return array
   *   The state default values.
   */
  public abstract function getStateAllowedKeysDefaultValues();

  /**
   * The conf value.
   *
   * @var array
   *   The conf value.
   */
  private $confStorageData;

  /**
   * Retourne la donnée si key est passée, toutes les données sinon.
   *
   * @param null|string $key
   *   La clé de l'élément à récupérer.
   *
   * @return array|mixed|null
   *   La valeur.
   */
  public function getAllValues($key = NULL) {
    if (is_null($this->confStorageData)) {
      // Default values.
      $this->confStorageData = $this->getConfAllowedKeysDefaultValues() + $this->getStateAllowedKeysDefaultValues();
      // Real values.
      $this->confStorageData = array_merge($this->confStorageData, $this->getConfData());
      $this->confStorageData = array_merge($this->confStorageData, $this->getStateData());
    }
    if ($key) {
      if (array_key_exists($key, $this->confStorageData)) {
        return $this->confStorageData[$key];
      }
      return NULL;
    }
    return $this->confStorageData;
  }

  /**
   * Return the list of conf data.
   *
   * @return array|mixed|null
   *   The data stored in conf.
   */
  protected function getConfData() {
    $data = [];
    if ($confId = $this->getConfId()) {
      $data = \Drupal::config($confId)
        ->get('data');
    }
    return is_array($data) ? $data : [];
  }

  /**
   * Return the list of state data.
   *
   * @return array|mixed
   *   The data stored in state.
   */
  protected function getStateData() {
    $data = [];
    if ($stateId = $this->getStateId()) {
      $data = \Drupal::state()->get($this->getStateId());
    }
    return is_array($data) ? $data : [];
  }

  /**
   * Edit conf.
   *
   * @param array $data
   *   The data.
   */
  public function setAllValues(array $data) {
    $allowedKeys = $this->getAllValues();
    $dataToSave = array_merge($allowedKeys, $data);
    $this->confStorageData = array_intersect_key($dataToSave, $allowedKeys);
    // Set conf.
    $this->setConfData();
    // Set state.
    $this->setStateData();
  }

  /**
   * Set conf data.
   */
  private function setConfData() {
    $confData = array_intersect_key($this->confStorageData, $this->getConfAllowedKeysDefaultValues());
    \Drupal::service('config.factory')
      ->getEditable($this->getConfId())
      ->set('data', $confData)
      ->save();
  }

  /**
   * Set State data.
   */
  private function setStateData() {
    $stateData = array_intersect_key($this->confStorageData, $this->getStateAllowedKeysDefaultValues());
    \Drupal::state()->set($this->getStateId(), $stateData);
  }

  /**
   * Return the value of conf from key.
   *
   * @param string $key
   *   The key of the element to get.
   *
   * @return array|mixed|null
   *   The value.
   */
  public function get($key) {
    if ($key) {
      return $this->getAllValues($key);
    }
    return NULL;
  }

  /**
   * Set Data for key.
   *
   * @param string $key
   *   The key of the element to get.
   * @param mixed $value
   *   The value.
   */
  public function set($key, $value) {
    $data = $this->getAllValues();
    $data[$key] = $value;
    $this->setAllValues($data);
  }

}
