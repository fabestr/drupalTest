<?php


namespace Drupal\bo_additions\Service;

use Drupal\bo_additions\Service\Toolbar;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * "Render callbacks must be a closure or implement TrustedCallbackInterface or RenderElementInterface"
 * See https://www.drupal.org/node/2966725
*/
class ToolbarPrerender implements TrustedCallbackInterface
{

  /**
   * Toolbar #pre_render callback.
   *
   * @param array $element
   *   A renderable array.
   *
   * @return array
   *   The updated renderable array.
   */
  public static function toolbar_content_pre_render($element) {
    /** @var Toolbar $toolbarTools */
    $toolbarTools = \Drupal::service(Toolbar::SERVICE_NAME);
    $toolbarTools->setCacheContext(['user.roles']);
    $toolbarTools->setMenuName('contrib');
    $element = $toolbarTools->populate($element);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['toolbar_content_pre_render'];
  }
}