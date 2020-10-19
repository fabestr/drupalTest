<?php

namespace Drupal\media_enhancement\Service;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;

/**
 * Class TwigTools.
 *
 * @package Drupal\media_enhancement\Services
 */
class TwigTools extends \Twig_Extension {

  /**
   * Returns the list f available functions.
   *
   * @return array
   *   List of functions.
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('buildImageTagFromMedia', [
        $this,
        'buildImageTagFromMedia'
      ]),
      new \Twig_SimpleFunction('imageUrlFromImgInfos', [
        $this,
        'imageUrlFromImgInfos'
      ]),
      new \Twig_SimpleFunction('getBaseUrl', [
        $this,
        'getBaseUrl'
      ]),
      new \Twig_SimpleFunction('getAttributeValue', [
        $this,
        'getAttributeValue'
      ])
    ];
  }

  /**
   * Renvoie le build array d'une image depuis une entité media.
   *
   * @param mixed $media
   *   Le media.
   * @param array $options
   *   Liste des options dispo.
   *     - image_style<string> : Style d'image.
   *     - class<string> : Classes.
   *     - item_prop<boolean> : Item prop.
   *     - srcset<Array> : Conf de source set.
   *
   * @return array
   *   Le tag.
   */
  public function buildImageTagFromMedia($media, array $options = NULL) {

    // Initialisation des options.
    $imageStyleName = array_key_exists('image_style', $options) ? $options['image_style'] : NULL;
    $class = array_key_exists('class', $options) ? $options['class'] : NULL;
    $withItemProp = array_key_exists('item_prop', $options) ? $options['item_prop'] : FALSE;
    $srcset = array_key_exists('srcset', $options) ? $options['srcset'] : [];
    $isRemoveDimensionsAttrs = array_key_exists(MediaEnhancementManager::REMOVE_DIMENSIONS_IMG_ATTR, $options) ? $options[MediaEnhancementManager::REMOVE_DIMENSIONS_IMG_ATTR] : FALSE;
    $lazyload = array_key_exists(MediaEnhancementManager::LAZYLOAD_ATTR, $options) ? $options[MediaEnhancementManager::LAZYLOAD_ATTR] : FALSE;
    $imageFieldName = array_key_exists('image_field_name', $options) ? $options['image_field_name'] : 'field_media_image';
  
    // Get media from build array.
    if (is_array($media)) {
      $media = $this->getMediaFromBuildArray($media);
    }
    // Get Media from field.
    if ($media instanceof EntityReferenceFieldItemListInterface) {
      $medias = $media->referencedEntities();
      if (!empty($medias)) {
        $media = $medias[0];
      }
      else {
        return '';
      }
    }
    
    $a = $media->$imageFieldName;

    // If media is not a Media entity, then we exit.
    if (is_null($media->$imageFieldName) || !($media instanceof Media) || count($media->$imageFieldName) == 0) {
      return '';
    }

    // Get image file.
    /** @var \Drupal\file\Entity\File $file */
    $file = $media->$imageFieldName->referencedEntities()[0];

    if (is_null($file)) {
      return '';
    }

    // Init metas.
    $image_metas = $media->$imageFieldName->first()->toArray();

    // Get default uri.
    $defaultImageUri = $file->getFileUri();

    // Init build.
    $build = $media->$imageFieldName->first()->view();
    $build['#alt'] = empty($image_metas['alt']) ? $media->label() : $image_metas['alt'];

    // Check image style.
    if (isset($imageStyleName) && $imageStyle = ImageStyle::load($imageStyleName)) {
      $build['#theme'] = 'image_formatter';
      $build['#image_style'] = $imageStyleName;
      $styledImageUri = $imageStyle->buildUri($defaultImageUri);
      if (file_exists($styledImageUri)) {
        list($image_width, $image_height) = getimagesize($styledImageUri);
        $build['#height'] = $image_height;
        $build['#width'] = $image_width;
      }
    }

    // Add class.
    if (isset($class)) {
      if (array_key_exists('#attributes', $build) && $build['#attributes']['class']) {
        $build['#attributes']['class'] = array_merge($build['#attributes']['class'], explode(' ', $class));
      }
      else {
        $build['#attributes']['class'] = explode(' ', $class);
      }
    }
    // Passe les données en rendu de l'image, car le theme image_formatter ne passe pas ces données.
    $build['#item_attributes'] = [
      'class' => array_key_exists('#attributes', $build) ? $build['#attributes']['class'] : [],
      'alt'   => $build['#alt']
    ];
    
    if($isRemoveDimensionsAttrs) {
      $build['#item_attributes'][MediaEnhancementManager::REMOVE_DIMENSIONS_IMG_ATTR] = true;
    }

    // On n'affiche le title que s'il est explicitement indiqué.
    if(array_key_exists('title', $image_metas) && !empty($image_metas['title'])) {
      $build['#item_attributes']['title'] = $image_metas['title'];
    }
    if ($withItemProp) {
      $build['#item_attributes']['itemprop'] = 'image';
    }

    // Si des données srcset sont données alors on ajoute le srcset.
    if (!empty($srcset)) {
      $build['#item_attributes'][MediaEnhancementManager::FIELD_CUSTOM_SRC_SET] = $srcset;

      $build['#item_attributes'][MediaEnhancementManager::FIELD_FILE_ID] = $media->field_media_image->target_id;

      // Ajout de l'option concernant l'image par défaut dans src :
      if (array_key_exists('srcset_main_rule', $options)) {
        $build['#item_attributes'][MediaEnhancementManager::FIELD_MAIN_RULE] = $options['srcset_main_rule'];
      }
    }
  
    $build['#item_attributes'][MediaEnhancementManager::LAZYLOAD_ATTR] = $lazyload;
  
    return $build;
  }

  /**
   * Return the media entity from the media build array.
   *
   * @param mixed $entity
   *   L'entité.
   *
   * @return mixed|null
   *   Le media.
   */
  protected function getMediaFromBuildArray($entity) {
    if (array_key_exists('#media', $entity)) {
      if ($entity['#media'] instanceof Media) {
        return $entity['#media'];
      }
    }
    else {
      if (array_key_exists('#items', $entity)) {
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $items */
        $items = $entity['#items'];
        $medias = $items->referencedEntities();
        $media = reset($medias);
        return $media instanceof Media ? $media : NULL;
      }
    }
    return NULL;
  }

  /**
   * Return the url of an imageItem.
   *
   * @param mixed $file_infos
   *   Les infos du fichier.
   * @param mixed $image_style
   *   The style of the image.
   *
   * @return string|array
   *   The url of the image.
   */
  public function imageUrlFromImgInfos($file_infos = NULL, $image_style = NULL) {

    $response = [];
    if (is_object($file_infos)) {

      if ($image_style) {
        return ImageStyle::load($image_style)
          ->buildUrl(File::load($file_infos->target_id)->getFileUri()) . '';
      }
      else {
        $response['url'] = file_url_transform_relative(file_create_url(File::load($file_infos->target_id)
          ->getFileUri())) . '';
        $response['width'] = $file_infos->width;
        $response['height'] = $file_infos->height;
        $response['alt'] = $file_infos->alt;
        $response['title'] = $file_infos->title;
        return $response;
      }
    }
    return $file_infos;
  }

  /**
   * Retourne le domaine.
   *
   * @return mixed
   *   Le domaine.
   */
  public function getBaseUrl() {
    global $base_url;
    return $base_url;
  }
  
  /**
   * @param \Drupal\Core\Template\AttributeValueBase|null $attribute
   * @return mixed
   */
  public function getAttributeValue($attribute) {
    if($attribute) {
      return $attribute->value();
    }
    return null;
  }

}
