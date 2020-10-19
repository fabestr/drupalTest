<?php

namespace Drupal\adimeo_tools\Shared;

/**
 * Class BatchTrait.
 *
 * @package Drupal\adimeo_tools
 */
trait BatchTrait {

  /**
   * Get the list of operations from a list of elements to treat.
   *
   * @param string $method
   *   The method to call for each operation.
   * @param array $fullList
   *   The list of elements.
   * @param int $numberOfProcessByIteration
   *   The number of elements to treat in an operation.
   *
   * @return array
   *   The list of operations.
   */
  public function getBatchOperations($method, array $fullList, $numberOfProcessByIteration = 1) {
    $operations = [];

    for ($i = 0; $i < count($fullList); $i += $numberOfProcessByIteration) {
      $operations[] = [
        $method,
        [array_slice($fullList, $i, $numberOfProcessByIteration)]
      ];
    }

    return $operations;
  }

}
