<?php

namespace Drupal\adimeo_tools\Service;

use Cocur\Slugify\Slugify;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Menu\MenuLinkManager;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\adimeo_tools\Service\LanguageService;

/**
 * Class Misc.
 *
 * @package Drupal\adimeo_tools\Service
 */
class Misc {

  const SERVICE_NAME = 'adimeo_tools.misc';

  /**
   * Current page node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $currentPageNode;

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
   * @var MenuLinkManager
   */
  private $menuLinkManager;

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
   * Constructor
   *
   * @param EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   *
   * @param LanguageService $languageService
   * @param MenuLinkManagerInterface $menuLinkManager
   */
  public function __construct(EntityTypeManager $entityTypeManager, LanguageService $languageService, MenuLinkManagerInterface $menuLinkManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageService = $languageService;
    $this->menuLinkManager = $menuLinkManager;
  }

  /**
   * Load Vocabulary Tree.
   *
   * @param string $vid
   *   Vocabulary ID to retrieve terms for.
   * @param int $parent
   *   The term ID under which to generate the tree.
   * @param int $max_depth
   *   The number of levels of the tree to return.
   *
   * @return array
   *   Return array
   */
  public function loadVocabularyTree($vid, $parent = 0, $max_depth = NULL) {

    // Get tree.
    /** @var \Drupal\taxonomy\TermStorage $taxonomy_storage */
    $taxonomy_storage = $this->entityTypeManager
      ->getStorage('taxonomy_term');
    $taxonomy = $taxonomy_storage->loadTree($vid, $parent, $max_depth, FALSE);

    // Get terms of the passed vid.
    $terms = $taxonomy_storage->loadByProperties(['vid' => $vid]);

    // Init result array.
    $result = [];

    foreach ($taxonomy as $taxo) {
      if ($taxo->depth == 0) {
        $result[$taxo->tid] = $terms[$taxo->tid];
      }
      else {
        $parent = reset($taxo->parents);

        if ($parent != 0 && array_key_exists($parent, $terms)) {
          if (!is_array($terms[$parent]->children)) {
            $terms[$parent]->children = [];
          }
          $terms[$parent]->children[$taxo->tid] = $terms[$taxo->tid];
        }
      }
    }
    return $result;
  }

  /**
   * Associe les enfants au terms parent.
   *
   * Associe les enfants dans $parentTerm->children dans la liste de tous les
   * enfants du term passé.
   *
   * @param \Drupal\taxonomy\Entity\Term $parentTerm
   *   Le terme parent.
   */
  public function initTreeForParentTerm(Term $parentTerm, $maxDepth = -1) {
    if ($parentTerm) {

      if (!$parentTerm->children) {
        /** @var \Drupal\taxonomy\TermStorage $termStorage */
        $termStorage = $this->entityTypeManager
          ->getStorage('taxonomy_term');
        $parentTerm->children = $termStorage->loadChildren($parentTerm->id());
      }

      if ($maxDepth !== 0) {
        /** @var Term $child */
        foreach ($parentTerm->children as $child) {
          $this->initTreeForParentTerm($child, $maxDepth - 1);
        }
      }
    }
  }

  /**
   * Return the parents of a term.
   *
   * @param Term $term
   *   The child term.
   *
   * @return Term[]
   *   The array of parent Terms (Can be multiple...)
   */
  public function getParentTerms(Term $term) {
    $storage = $this->entityTypeManager
      ->getStorage('taxonomy_term');
    return $storage->loadParents($term->id());
  }

  /**
   * Traduit l'arborescence de term dans la langue voulue.
   *
   * @param array $terms
   *   List de terme parents à parcourir.
   * @param string|null $languageId
   *   Le code de la langue de traduction. Traduit dans la langue courante si
   *   null.
   * @param string $mode
   *   Le mode de traduction par défaut de l'enfant.
   */
  public function translateTermTree(array &$terms, $languageId = NULL, $mode = NULL) {
    if ($mode == NULL) {
      $mode = $this->languageService::MODE_DEFAULT_LANGUAGE_IF_NO_TRANSLATION_EXISTS;
    }
    /** @var Term $term */
    foreach ($terms as &$term) {
      $term = $this->languageService->translate($term, $languageId, $mode);
      if (is_array($term->children)) {
        $this->translateTermTree($term->children, $languageId, $mode);
      }
    }
  }

  /**
   * Get the parent of the current active menu link (if it's a node).
   * @TODO gérer plus de cas, en fournissant la route par exemple.
   *
   * @return Drupal\Core\Menu\MenuLinkInterface|bool
   */
  public function getCurrentNodeParent() {
    $node_id = Drupal::routeMatch()->getRawParameter('node');
    if ($node_id) {
      $menu_link = $this->menuLinkManager->loadLinksByRoute('entity.node.canonical', array('node' => $node_id));
    }
    else {
      return '';
    }

    if (is_array($menu_link) && count($menu_link)) {
      $menu_link = reset($menu_link);
      if ($menu_link->getParent()) {
        $parents = $this->menuLinkManager->getParentIds($menu_link->getParent());
        $parents = array_reverse($parents);
        $parent = reset($parents);

        try {
          return $this->menuLinkManager->createInstance($parent);
        }
        catch (\Drupal\Component\Plugin\Exception\PluginException $pluginException) {
          return FALSE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Turn a string into CamelCase.
   *
   * @param string $data
   *   The string to convert.
   *
   * @return string
   *   The converted string.
   *
   * @throws \Exception
   */
  static public function toCamelCase($data) {
    if (is_string($data)) {
      $slug = (new Slugify())->slugify($data);
      $replacement = [
        '-' => ' ',
        '_' => ' ',
      ];
      return str_replace(' ', '', ucwords(str_replace(array_keys($replacement), $replacement, $slug)));
    }
    throw new \Exception('Element is not convertible to CamelCase');
  }

  /**
   * Return the node of the current page.
   *
   * @return \Drupal\Node\Entity\Node|null
   *   The current node.
   */
  public function getCurrentPageNode() {
    if (!isset($this->currentPageNode)) {
      if ($node = \Drupal::routeMatch()->getParameter('node')) {
        if (is_numeric($node)) {
          $this->currentPageNode = $this->languageService->load('node', $node);
        }
        elseif ($node instanceof Node) {
          $this->currentPageNode = $this->languageService->translate($node);
        }
      }
    }

    return $this->currentPageNode;
  }

  /**
   * Retourne la liste des ids des éléments référencés.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemList $field
   *   The field.
   * @param string $attribute
   *   The attributes.
   *
   * @return array
   *   The list of ids.
   */
  public function getIdsFromReferenceFieldList(EntityReferenceFieldItemList $field, $attribute = 'target_id') {
    $result = [];
    foreach ($field->getIterator() as $item) {
      $result[] = $item->{$attribute};
    }

    return $result;
  }

}
