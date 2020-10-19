<?php


namespace Drupal\media_enhancement\Service;


use Drupal;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\media\Entity\Media;

class MediaTools {
  /**
   * Service name.
   */
  const SERVICE_NAME = 'media_enhancement.tools';

  /**
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Retourne le singleton (quand pas d'injection de dépendances possible)
   *
   * @return static;
   */
  public static function me() {
    return Drupal::service(static::SERVICE_NAME);
    //$tools = MediaTools::me();
  }

  /**
   * constructor.
   *
   * @param EntityTypeManager $entityTypeManager
   *   Instance of EntityTypeManager.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @param Media $media
   *
   * @return array
   */
  public function buildMediaForPopinDisplay ($media) {
    $build = [];

    if ($media instanceof Media) {
      $view_builder = $this->entityTypeManager->getViewBuilder('media');
      $build = $view_builder->view($media,'popin');
    }



    return $build;
  }
}
