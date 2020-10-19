<?php

namespace Drupal\adimeo_tools\Service;

use Drupal\adimeo_tools\Service\LanguageService;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Render\Element;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Cocur\Slugify\Slugify;
use Drupal\node\Entity\Node;

use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent as MenuLinkContentPlugin;
use Drupal\menu_link_content\Entity\MenuLinkContent as MenuLinkContentEntity;

/**
 * Contains declaration of all custom twig filters.
 */
class TwigFilters extends \Twig_Extension {

  /**
   * The entity type manager service.
   *
   * @var EntityTypeManager
   */
  private $entityTypeManager;
  /**
   * @var LanguageService
   */
  private $languageService;

  /**
   * TwigFilters constructor.
   *
   * @param EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   *
   * @param LanguageService $languageService
   */
  public function __construct(EntityTypeManager $entityTypeManager, LanguageService $languageService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageService = $languageService;
  }

  /**
   * Returns the list of available functions.
   *
   * @return array
   *   List of functions.
   */
  public function getFunctions() {
    $twigFunctions = $this->getAdimeoFunctions();

    // On duplique les fonctions en ajoutant un préfixe 'at_'.
    /** @var \Twig_SimpleFunction $twigFunction */
    foreach ($twigFunctions as $twigFunction) {
      $twigFunctions[] = new \Twig_SimpleFunction('at_' . $twigFunction->getName(), $this, $twigFunction->getCallable());
    }

    return $twigFunctions;
  }

  /**
   * Récupère les fonctions twig.
   *
   * Afin de dupliquer les fonctions et permettre de cibler les fonctions Adimeo par
   * le préfixe "at_", on les déclare ici.
   *
   * @return array
   *   Liste des fonctions twigs.
   */
  protected function getAdimeoFunctions() {
    return [
      new \Twig_SimpleFunction('render', array($this, 'renderEntity')),
      new \Twig_SimpleFunction('renderFieldLink', array($this, 'renderFieldLink')),
      new \Twig_SimpleFunction('getNodeUrl', array($this, 'getNodeUrl')),
      new \Twig_SimpleFunction('getGlobalLanguage', array($this, 'getGlobalLanguage')),
      new \Twig_SimpleFunction('getEntityTranslation', array($this, 'getEntityTranslation')),
      new \Twig_SimpleFunction('getMenuItemAttribute', array($this, 'getMenuItemAttribute'
      )),
    ];
  }

  /**
   * Returns the list of available filters.
   *
   * @return array
   *   List of filters.
   */
  public function getFilters() {
    $twigFilters = $this->getAdimeoFilters();

    // On duplique les filtres en ajoutant un préfixe 'at_'.
    /** @var \Twig_SimpleFilter $twigFilter */
    foreach ($twigFilters as $twigFilter) {
      $twigFilters[] = new \Twig_SimpleFilter('at_' . $twigFilter->getName(), $twigFilter->getCallable());
    }

    return $twigFilters;
  }

  /**
   * Récupère les filtres twig.
   *
   * Afin de dupliquer les filtres et permettre de cibler les filtres Adimeo par
   * le préfixe "at_", on les déclare ici.
   *
   * @return array
   *   Liste des filtres twigs.
   */
  protected function getAdimeoFilters() {
    return array(
      new \Twig_SimpleFilter('imageStyle', array($this, 'imageStyle')),
      new \Twig_SimpleFilter('imageUrl', array($this, 'imageUrl')),
      new \Twig_SimpleFilter('maxLength', array($this, 'maxLength')),
      new \Twig_SimpleFilter('drupalField', array($this, 'drupalField')),
      new \Twig_SimpleFilter('halfRound', array($this, 'halfRound')),
      new \Twig_SimpleFilter('fieldValue', array($this, 'fieldValue')),
      new \Twig_SimpleFilter('children', array($this, 'children')),
      new \Twig_SimpleFilter('slugify', array($this, 'slugify')),
    );
  }

  /**
   * Returns the render array of the image applying required image style.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $field
   *   Instance of the image field.
   * @param string $imageStyle
   *   Style of the image to use.
   * @param string $class
   *   CSS class to use. Default NULL.
   *
   * @return array
   *   The render array of the image.
   *
   * @throws \Exception
   */
  public function imageStyle(TypedDataInterface $field = NULL, $imageStyle = NULL, $class = NULL) {
    if ($field instanceof ItemList) {
      $field = $field->first();
    }

    if (!$field instanceof FieldItemBase) {
      return $field;
    }

    // Generate render array of the field.
    $build = $field->view();

    if ($imageStyle) {
      // Make sure image style exists.
      $style = ImageStyle::load($imageStyle);

      if (empty($style)) {
        // If it doesn't, stop process right now.
        throw new \Exception(sprintf('Image style "%s" not found.', $imageStyle));
      }

      // Add image style.
      $build['#image_style'] = $imageStyle;
    }

    // Optionally, add CSS class.
    if (is_string($class)) {
      $build['#item_attributes']['class'][] = $class;
    }

    return $build;
  }

  /**
   * Return the url of an imageItem.
   *
   * @param array|null $data
   *   The ImageItem render array.
   *
   * @return string
   *   The url of the image.
   */
  public function imageUrl(array $data = NULL) {
    /** @var ImageItem $image */
    if (array_key_exists('#items', $data)) {
      $image = $data['#items'];
    }
    elseif (array_key_exists('#item', $data)) {
      $image = $data['#item'];
    }

    if (is_array($data) && isset($image)) {

      if (array_key_exists('#image_style', $data)) {
        /** @var File $file */
        return ImageStyle::load($data['#image_style'])
          ->buildUrl(File::load($image->target_id)->getFileUri()) . '';
      }
      else {
        return file_url_transform_relative(file_create_url(File::load($image->target_id)
          ->getFileUri())) . '';
      }
    }
    return $data;
  }

  /**
   * Truncates a string to wanted length and append ellipsis if needed.
   *
   * @param string $content
   *   Content to truncate.
   * @param int $maxLength
   *   Max length of the string.
   *
   * @return string
   *   The truncated string.
   */
  public function maxLength($content, $maxLength = 20) {
    if (!is_string($content)) {
      return $content;
    }

    if (strlen($content) > $maxLength) {
      $prefix = '...';
      $content = substr($content, 0, $maxLength - strlen($prefix)) . $prefix;
    }

    return $content;
  }

  /**
   * Display an entity field in the given view mode.
   *
   * @param FieldItemInterface $field
   *   The field to display.
   * @param string $viewMode
   *   View mode, default full (optional).
   * @param string|null $class
   *   CSS class to add to the field (optional).
   *
   * @return array
   *   Render array of the field.
   */
  public function drupalField(FieldItemInterface $field, $viewMode = 'full', $class = NULL) {
    $build = $field->view($viewMode);

    if (is_string($class)) {
      $build['#item_attributes']['class'][] = $class;
    }

    return $build;
  }

  /**
   * Round a positive number to the closest half.
   *
   * @param mixed $value
   *   Can be a string composed of numbers, an integer or a float.
   *
   * @throws \Exception
   *
   * @return float|int
   *   The rounded value.
   */
  public function halfRound($value) {
    if (!is_string($value) && !is_numeric($value)) {
      throw new \Exception('Invalid parameter type');
    }

    $pattern = '#^[0-9]+(\.[0-9]+)?$#';

    if (!preg_match($pattern, $value)) {
      return $value;
    }

    // First, transform potential string to float.
    $value = floatval($value);

    // Get the integer part of the number.
    $intVal = intval($value);

    // Get the floating part of the umber.
    $floatVal = $value - $intVal;

    if ($floatVal < 0.25) {
      $floatVal = 0;
    }
    elseif ($floatVal >= 0.25 && $floatVal < 0.75) {
      $floatVal = 0.5;
    }
    else {
      $floatVal = 1;
    }

    $value = $floatVal + $intVal;

    return $value;

  }

  /**
   * Return the render array of the given entity.
   *
   * @param EntityInterface $entity
   *   The entity object to render.
   * @param string $viewMode
   *   View mode to use (default full).
   * @param bool $currentLanguage
   *   Use current language if TRUE, the entity's language otherwise.
   *
   * @return array
   *   Render array of the entity.
   */
  public function renderEntity(EntityInterface $entity, $viewMode = 'full', $currentLanguage = FALSE) {
    $entityType = $entity->getEntityType()->id();

    if (!$currentLanguage) {
      $langCode = $entity->language()->getId();
    }
    else {
      $langCode = $this->languageService->getCurrentLanguageId();
    }

    $renderController = $this->entityTypeManager->getViewBuilder($entityType);

    return $renderController->view($entity, $viewMode, $langCode);
  }

  /**
   * Return the (first) value of a field.
   *
   * @param array|mixed $markup
   *   The field markup array.
   *
   * @return string
   *   The value of the field if exists.
   */
  public function fieldValue($markup) {
    if (is_array($markup)) {
      if (array_key_exists('#items', $markup)
        && $markup['#items'] instanceof FieldItemList
        && $item = $markup['#items']->first()
      ) {
        return $item->value;
      }
    }
    return '';
  }

  /**
   * Get only renderable element from a markup array.
   *
   * Use param "withKey" if you want to return element's id as array key.
   * Warning backward compatibility issues : test function on param "withKey".
   *
   * Ex: {% for item in content.field_list|children %}
   *     {% for key, item in content.field_list|children(true)%}
   *
   * @param mixed $markup
   *   The parent element.
   * @param bool $withKey
   *   Use element id if is TRUE.
   *
   * @return array
   *   The renderables elements.
   */
  public function children($markup, $withKey = FALSE) {
    $array = [];
    if (is_array($markup)) {
      if ($withKey) {
        foreach (Element::getVisibleChildren($markup) as $childId) {
          $array[$childId] = $markup[$childId];
        }
      }
      else {
        foreach (Element::getVisibleChildren($markup) as $childId) {
          $array[] = $markup[$childId];
        }
      }
    }
    return $array;
  }

  /**
   * Get slugified string.
   *
   * @param string $string
   *   The string to slugify.
   * @param null|string|array $separator
   *   The slugify options.
   *
   * @return string
   *   The slugified string.
   */
  public function slugify($string, $separator = '-') {
    $slugify = new Slugify();
    return $slugify->slugify($string, $separator);
  }

  /**
   * Return field link with options.
   *
   * @param mixed $data
   *   Data.
   * @param int $class
   *   CSS class to add to the field (optional).
   * @param string $target
   *   Target param to add to the field (optional).
   *
   * @return \Drupal\Core\GeneratedLink|string
   *   Return generated link.
   */
  public function renderFieldLink($data, $class = NULL, $target = '_self') {

    $link_options = array(
      'attributes' => array(
        'class'  => $class,
        'target' => $target
      ),
    );

    try {
      if ($data instanceof FieldItemList) {
        $url = Url::fromUri($data->uri, $link_options);

        $title = $data->title;
      }
      elseif (is_array($data)) {

        if (array_key_exists(0, $data)) {

          if (array_key_exists('#url', $data[0])) {
            $url = $data[0]['#url'];
            $url->setOptions($link_options);
          }

          if (array_key_exists('#title', $data[0])) {
            $title = $data[0]['#title'];
          }
        }
      }

      if (isset($title) && isset($url)) {
        return Link::fromTextAndUrl($title, $url)->toString();
      }

    }
    catch (\Exception $e) {
      return '';
    }

    return '';
  }

  /**
   * Return the stringed url of a node.
   *
   * @param mixed $data
   *   The node object, $buildArray or id.
   * @param bool $absolute
   *   The type of url.
   *
   * @return string
   *   The url
   */
  public function getNodeUrl($data, $absolute = FALSE) {
    $node = $data;

    // Passed data is the id of the node.
    if (is_numeric($node)) {
      $node = $this->load('node', $node);
    }
    // Passed data is an array containing #node.
    elseif (is_array($node) && array_key_exists('#node', $node)) {
      $node = $node['#node'];
    }

    if ($node instanceof Node) {
      /** @var \Drupal\Core\Url $url */
      $url = $node->toUrl();
      if ($absolute) {
        $url->setAbsolute(TRUE);
      }
      return $url->toString();
    }

    return '';
  }

  /**
   * Retourne le language global courant.
   *
   * @return string
   *   Le langcode courant.
   */
  public function getGlobalLanguage() {
    return $this->getCurrentLanguageId();
  }

  /**
   * Retourne l'entité dans le langage courant.
   *
   * @param mixed $entity
   *   L'entité.
   */
  public function getEntityTranslation($entity, $language = NULL) {
    if ($entity && method_exists($entity, 'getTranslation')) {
      $language = isset($language) ? $language : $this->getCurrentLanguageId();
      if ($entity->hasTranslation($language)) {
        return $entity->getTranslation($language);
      }
    }
    return $entity;
  }

  /**
   * Return the required attribute of the menu item.
   *
   * @param mixed $menuItem
   *   Menu item.
   * @param string $attribute
   *   Le nom de l'attribut.
   *
   * @return mixed
   *   La valeur de l'attribut de l'item.
   */
  public function getMenuItemAttribute($menuItem, $attribute) {
    /** @var MenuLinkTreeElement $menuItem */
    if ($menuItem instanceof MenuLinkTreeElement) {
      if ($definition = $menuItem->link->getPluginDefinition()) {
        if (isset($definition['options']['attributes'])) {
          $attributes = $definition['options']['attributes'];
          if (array_key_exists($attribute, $attributes)) {
            return $attributes[$attribute];
          }
        }
      }
    }
    elseif (is_array($menuItem)) {
      /** @var MenuLinkContentPlugin $link */
      if (isset($menuItem['original_link']) && $linkPlugin = $menuItem['original_link']) {
        if ($link = MenuLinkContentEntity::load($linkPlugin->getMetaData()['entity_id'])) {
          if ($detailled_link = $link->link->first()) {
            if ($detailled_link->options && array_key_exists('attributes', $detailled_link->options)) {
              if (array_key_exists($attribute, $detailled_link->options['attributes'])) {
                return $detailled_link->options['attributes'][$attribute];
              }
            }
          }
        }
      }
      else {
        if (isset($menuItem['attributes'])) {
          $attribute = $menuItem['attributes']->offsetGet($attribute)
            ->value();
          return $attribute;
        }
      }
    }
  }

}
