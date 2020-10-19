<?php

namespace Drupal\bo_additions\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\system\SystemManager;

class ContribMenuOverviewController extends ControllerBase{


  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  /**
   * TwigFilters constructor.
   *
   * @param MenuLinkTree $menu_link_tree
   *   The entity type manager service.
   * @param SystemManager $system_manager
   */
  public function __construct(MenuLinkTree $menu_link_tree, SystemManager $system_manager) {
    $this->menuLinkTree = $menu_link_tree;
    $this->systemManager = $system_manager;
  }

  /**
   * Provide the administration menu overview page.
   *
   * @param string $menu_name
   *   The ID of the menu for which to display child links.
   *
   * @return array
   *   A renderable array of the administration menu overview page.
   */
  public function overview($menu_name) {
    $parameters = new MenuTreeParameters();
    $tree = $this->menuLinkTree->load($menu_name,$parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuLinkTree->transform($tree, $manipulators);
    $tree_access_cacheability = new CacheableMetadata();
    $blocks = [];
    foreach ($tree as $key => $element) {
      $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($element->access));

      // Only render accessible links.
      if (!$element->access->isAllowed()) {
        continue;
      }

      $link = $element->link;
      $block['title'] = $link->getTitle();
      $block['description'] = $link->getDescription();
      $block['content'] = [
        '#theme' => 'admin_block_content',
        '#content' => $this->systemManager->getAdminBlock($link),
      ];

      if (!empty($block['content']['#content'])) {
        $blocks[$key] = $block;
      }
    }

    if ($blocks) {
      ksort($blocks);
      $build = [
        '#theme' => 'admin_page',
        '#blocks' => $blocks,
      ];
      $tree_access_cacheability->applyTo($build);
      return $build;
    }
    else {
      $build = [
        '#markup' => $this->t('You do not have any administrative items.'),
      ];
      $tree_access_cacheability->applyTo($build);
      return $build;
    }
  }


}
