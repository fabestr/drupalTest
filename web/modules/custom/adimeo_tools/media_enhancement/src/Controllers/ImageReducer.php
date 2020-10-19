<?php

namespace Drupal\media_enhancement\Controllers;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class ImageReducer.
 *
 * Permet de créer un fichier image en fonction d'un fichier passé en paramètre
 * et des conditions passées aussi en paramètre.
 *
 * @package Drupal\media_enhancement\Controllers
 */
class ImageReducer extends ControllerBase {

  protected $fileId;
  protected $fileName;
  protected $rule;
  protected $width;
  protected $height;
  protected $styleName;
  protected $extension;

  /**
   * Retourne l'image diminuée.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   La réponse en binaire.
   */
  public function showImage() {
    // Initialisaiton des données.
    $this->fileId = \Drupal::request()->get('file');
    $this->fileName = \Drupal::request()->query->get('filename');
    $this->rule = \Drupal::request()->get('rule');
    $this->width = \Drupal::request()->get('w');
    $this->height = \Drupal::request()->get('h');
    $this->styleName = \Drupal::request()->get('style_name') ?: 'default';
    $this->extension = \Drupal::request()->get('ext');

    // 1. On récupère le nom du fichier final de l'image.
    $destination = $this->getDestination();

    // 2. ON vérifie si l'image existe déjà.
    if (!file_exists($destination)) {
      $destination = $this->createFile($destination);
    }

    // On va chercher le fichier.
    $response = new BinaryFileResponse($destination, 200);
    return $response;

  }

  /**
   * Retourne le path final de l'image.
   *
   * @return string
   *   La destinations.
   */
  protected function getDestination() {
    return 'public://styles/' . $this->rule . '/public/medias/' . urldecode($this->fileName) . '.' . $this->extension;
  }

  /**
   * On construit l'image.
   *
   * @return string
   *   L'url du format créé.
   */
  protected function createFile() {
    if ($file = File::load($this->fileId)) {
      // 4. On duplique le style d'image initial et on le renomme avec la rule.
      $imageStyle = $this->getImageStyle();

      // 5. On ajoute le style d'image de redimensionnement.
      $imageStyle->addImageEffect([
        'id'     => 'image_scale',
        'weight' => 1000,
        'data'   => [
          'width'  => $this->width,
          'height' => $this->height,
        ],
      ]);

      // 6. On génère le fichier.
      $derivativeUri = $imageStyle->buildUri($file->getFileUri());
      if (file_exists($derivativeUri) || $imageStyle->createDerivative($file->getFileUri(), $derivativeUri)) {
        // 7. On retourne l'url.
        return $derivativeUri;
      }
    }
  }

  /**
   * Retourne l'image style à utiliser.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\image\Entity\ImageStyle|null
   *   L'imageStyle.
   */
  protected function getImageStyle() {
    $rule = $this->rule . '__' . $this->styleName;
    if ($baseImageStyle = ImageStyle::load($this->styleName)) {
      $imageStyle = clone $baseImageStyle;
      $imageStyle->setName($rule);
    }
    else {
      $imageStyle = ImageStyle::create(['name' => $rule]);
    }

    return $imageStyle;
  }

  /**
   * Delete les images générées par uri au cas où une image serait amenée à
   * changer.
   *
   * @param $uri
   */
  public static function flushByUri($uri) {
    $fileName = pathinfo($uri)['filename'];
    /** @var \Drupal\Core\File\FileSystem $fileSystem */
    $fileSystem =\Drupal::service('file_system');
    $files = $fileSystem->scanDirectory('public://styles', '/'.$fileName.'/');
    foreach( $files as $data ){
      // Si l'image est bien dans un répertoire généré par ImageReducer
      if( strpos($data->uri, 'w__') !== FALSE || strpos($data->uri, 'h__') !== FALSE ){
        $fileSystem->delete($data->uri);
      }
    }
  }

}
