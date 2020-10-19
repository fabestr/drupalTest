<?php

namespace Drupal\bo_additions\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EntityEditRedirectController extends ControllerBase{

  /**
   * The entity type manager service.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * EntityEditRedirectController constructor.
   *
   * @param EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Redirect to node edit form selected by the query params
   *
   * @return RedirectResponse
   *   The redirect response.
   */
  public function nodeEditFormRedirect () {
    return $this->entityEditFormRedirect('node');
  }

  /**
   * Redirect to term edit form selected by the query params
   *
   * @return RedirectResponse
   *   The redirect response.
   */
  public function termEditFormRedirect () {
    return $this->entityEditFormRedirect('taxonomy_term');
  }

  /**
   * Redirect to entity edit form selected by the query params
   *
   * @param string $entity_type
   *
   * @return RedirectResponse
   *   The redirect response.
   */
  public function entityEditFormRedirect ($entity_type) {
    $entities = [];
    $conditions = \Drupal::request()->query->all();

    try {
      $entityStorage = $this->entityTypeManager->getStorage($entity_type);
      $query = $entityStorage->getQuery();
      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }
      $query->range(0, 1);
      $query->sort('changed', 'DESC');
      $ids = $query->execute();
    } catch (InvalidPluginDefinitionException $e) {
    } catch (PluginNotFoundException $e) {
    }

    $entityId = end($ids);

    if($entityId){
      return $this->redirect('entity.'.$entity_type.'.edit_form',[$entity_type => $entityId]);
    }
    // IF NO CONTENT
    return $this->redirect('system.404');
  }
}
