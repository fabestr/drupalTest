<?php

namespace Drupal\media_enhancement\Service;

use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\adimeo_tools\Base\ConfigServiceBase;
use Exception;

/**
 * Class MediaEnhancementManager.
 *
 * @package Drupal\media_enhancement\Services
 */
class MediaEnhancementManager extends ConfigServiceBase {

  /**
   * Le SERVICE_NAME.
   *
   * @const
   */
  const SERVICE_NAME = 'media_enhancement.manager';

  /**
   * Le ROUTE_REDUCER.
   *
   * @const
   */
  const ROUTE_REDUCER = 'media_enhancement.image_reducer';

  /**
   * Le FIELD_CUSTOM_SRC_SET.
   *
   * @const
   */
  const FIELD_CUSTOM_SRC_SET = 'custom_srcset';

  /**
   * Le FIELD_FILE_ID.
   *
   * @const
   */
  const FIELD_FILE_ID = 'file_id';

  /**
   * ID du field de la règle prioritaire.
   *
   * @const
   */
  const FIELD_MAIN_RULE = 'field_main_rule';

  /**
   * Le CONF_SRCSET_ENABLE.
   *
   * @const
   */
  const CONF_SRCSET_ENABLE = 'conf_srcset_enable';

  /**
   * clé indiquant la suppression des attributs "width" et "height" de la balise <img/>
   */
  const REMOVE_DIMENSIONS_IMG_ATTR = 'remove_dim_attrs';


  const LAZYLOAD_ATTR = 'lazyload';

  /**
   * Retourne le singleton (quand pas d'injection de dépendances possible)
   *
   * @return static
   *   Le singleton.
   */
  public static function me() {
    return \Drupal::service(static::SERVICE_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfId() {
    return static::SERVICE_NAME;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfAllowedKeysDefaultValues() {
    return [
      static::CONF_SRCSET_ENABLE => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStateId() {
    return static::SERVICE_NAME;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateAllowedKeysDefaultValues() {
    return [];
  }

  /**
   * Construit les srcset en fonction du custom srcset.
   *
   * @param array $variables
   *   Le build array.
   * @param array $customSrcSet
   *   La conf.
   */
  public function buildSrcSet(array &$variables, array $customSrcSet) {
    if (!$this->get(static::CONF_SRCSET_ENABLE)) {
      return;
    }
    $defaultUrl = file_create_url($variables['uri']);
    $fileId = $variables['attributes'][static::FIELD_FILE_ID];
    $styleName = $variables['style_name'];
    $pathInfo = pathinfo($variables['uri']);
    $fileName = $pathInfo['filename'];
    $extension = substr($pathInfo['extension'], 0, strpos($pathInfo['extension'], '?'));

    // Gestion de l'image source.
    $mainRule = array_key_exists(static::FIELD_MAIN_RULE, $variables['attributes']) ? $variables['attributes'][static::FIELD_MAIN_RULE] : FALSE;

    foreach ($customSrcSet as $type => $data) {
      if ($data === 'default') {
        $variables['attributes']['srcset'][] = $defaultUrl . ' ' . $type;
      }
      elseif ($setUrl = $this->getUrlOfSetSrc($variables, $data, $fileId, $styleName, $fileName, $extension)) {
        $variables['attributes']['srcset'][] = $setUrl . ' ' . $type;

        if ($mainRule === $type) {
          $variables['attributes']['src'] = $setUrl;
        }
      }
    }

    // On join les srcset.
    if (array_key_exists('srcset', $variables['attributes'])) {
      $variables['attributes']['srcset'] = implode(',', $variables['attributes']['srcset']);
    }
    unset($variables['attributes'][static::FIELD_CUSTOM_SRC_SET]);
    unset($variables['attributes'][static::FIELD_FILE_ID]);
    unset($variables['attributes'][static::FIELD_MAIN_RULE]);
  }

  /**
   * Retourne l'url de l'image max.
   *
   * Si l'image de base est déjà plus petite que l'image de destination, on ne
   * retourne rien.
   *
   * @param array $variables
   *   Le build array.
   * @param mixed $data
   *   Les données de conf.
   * @param int $fileId
   *   L'id du fichier.
   * @param string $styleName
   *   Le stylename.
   * @param string $fileName
   *   Le nom du fichier.
   * @param string $extension
   *   L'extension.
   *
   * @return \Drupal\Core\GeneratedUrl|null|string
   *   L'url de l'image.
   */
  protected function getUrlOfSetSrc(array $variables, $data, $fileId, $styleName, $fileName, $extension) {
    $type = strpos($data, 'h') != FALSE ? 'height' : 'width';
    $query = [
      'style_name' => $styleName,
      'filename'   => $fileName,
      'ext'        => $extension
    ];
    $url = Url::fromRoute(static::ROUTE_REDUCER, ['file' => $fileId]);

    if ($this->initQueryOptions($query, $type, $variables, $data)) {
      $url->setOption('query', $query);
      return $url->toString();
    }

    return NULL;
  }

  /**
   * Initialise les données de la query.
   *
   * @param array $query
   *   La query.
   * @param string $type
   *   Le type.
   * @param array $variables
   *   Les variables.
   * @param mixed $data
   *   Les données.
   *
   * @return bool
   *   Vrai si l'élément a besoin d'être modifié.
   */
  protected function initQueryOptions(array &$query, $type, array $variables, $data) {
    $maxData = intval($data);
    $initialData = $variables[$type];

    if ($initialData > $maxData) {
      if ($type === 'height') {
        $query += [
          'h' => $maxData,
          'w' => round($maxData * $variables['width'] / $variables['height']),
        ];
      }
      else {
        $query += [
          'h' => round($maxData * $variables['height'] / $variables['width']),
          'w' => $maxData,
        ];
      }

      // On ajoute la roule.
      $query['rule'] = $data;

      return TRUE;
    }

    return FALSE;
  }

  /**
   * @param $variables
   * @throws \Exception
   */
  public function buildLazyloadAttributes(&$variables) {
    if($variables['style_name']) {
      $imageStyleEntity = ImageStyle::load($variables['style_name']);
      $image_style_uri = $imageStyleEntity->buildUri($variables['uri']);
      $image_url_root = \Drupal::service('file_system')->realpath($image_style_uri);
      //le fichier n'existe pas encore, on va le créer
      if(!file_exists($image_url_root)) {
        if(!$imageStyleEntity->createDerivative($variables['uri'], $image_style_uri)) {
          throw new Exception('Le fichier original d’image n’existe pas');
        }else {
          $image_url_root = \Drupal::service('file_system')->realpath($image_style_uri);
        }
      }
    }else {
      $image_url_root = \Drupal::service('file_system')->realpath($variables['uri']);
    }

    if(!file_exists($image_url_root)) {
      throw new Exception('Le fichier d’image n’existe pas');
    }

    $image_data = getimagesize($image_url_root);
    list($image_width, $image_height) = $image_data;
    $placeholder_width = $image_width;
    $placeholder_height = $image_height;
    $image_ratio = $image_width / $image_height;

    if(array_key_exists(MediaEnhancementManager::FIELD_CUSTOM_SRC_SET, $variables['attributes'])) {
      $placeholder_width = min(preg_replace('/w/', '', $variables['attributes'][MediaEnhancementManager::FIELD_CUSTOM_SRC_SET]['1x']), $placeholder_width);
      $placeholder_height = $placeholder_width / $image_ratio;
    }

    $variables['attributes']['placeholder_width'] = $placeholder_width;
    $variables['attributes']['placeholder_height'] = $placeholder_height;
  }

  public function preprocessImage(&$variables) {
    if(array_key_exists(MediaEnhancementManager::LAZYLOAD_ATTR, $variables['attributes']) && $variables['attributes'][MediaEnhancementManager::LAZYLOAD_ATTR]) {
      try{
        MediaEnhancementManager::me()->buildLazyloadAttributes($variables);
      }catch(Exception $e) {
        // mute error
      }
    }

    if( array_key_exists(MediaEnhancementManager::FIELD_CUSTOM_SRC_SET, $variables['attributes']) && !empty($variables['attributes'][MediaEnhancementManager::FIELD_CUSTOM_SRC_SET])){
      MediaEnhancementManager::me()->buildSrcSet($variables, $variables['attributes'][MediaEnhancementManager::FIELD_CUSTOM_SRC_SET]);
    }


    if(array_key_exists(MediaEnhancementManager::REMOVE_DIMENSIONS_IMG_ATTR, $variables['attributes'])) {
      unset($variables['attributes']['width']);
      unset($variables['attributes']['height']);
      unset($variables['attributes'][MediaEnhancementManager::REMOVE_DIMENSIONS_IMG_ATTR]);
    }
  }

}
