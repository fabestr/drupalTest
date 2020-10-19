<?php

namespace Drupal\bo_additions\Service;

use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;

class Toolbar
{
  const SERVICE_NAME = 'bo_additions.toolbar';

  /**
   * @var MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * @var string
   */
  protected $menuName = NULL;

  /**
   * @var string[]
   */
  protected $cacheContext = [];

  /**
   * Constructor.
   *
   * @param MenuLinkTreeInterface $menuLinkTreeInterface
   *   The menu link tree.
   */
  public function __construct(MenuLinkTreeInterface $menuLinkTreeInterface) {
    $this->menuTree = $menuLinkTreeInterface;
  }

  /**
   *
   * @inheritDoc
   */
  public function populate(array $element) {
    $parameters = new MenuTreeParameters();
    $parameters->excludeRoot()->onlyEnabledLinks();
    $tree = $this->menuTree->load($this->menuName, $parameters);

    if (empty($tree)) {
      return $element;
    }

    $manipulators = array(
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );

    $tree = $this->menuTree->transform($tree, $manipulators);
    $menuElement = $this->menuTree->build($tree);

    $menuElement['#cache']['contexts'] += $this->cacheContext;

    $element['administration_menu'] = $menuElement;

    return $element;
  }

  /**
   * Set the menu.
   *
   * @param string $menuName
   *   The menu name.
   */
  public function setMenuName($menuName) {
    $this->menuName = $menuName;
  }

  /**
   * Set cache context.
   *
   * @param mixed $cacheContext
   *   The cache context.
   */
  public function setCacheContext($cacheContext) {
    $this->cacheContext = $cacheContext;
  }

}
