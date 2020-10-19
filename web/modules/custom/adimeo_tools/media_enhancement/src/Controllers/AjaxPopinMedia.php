<?php


namespace Drupal\media_enhancement\Controllers;


use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\media_enhancement\Service\MediaTools;

class AjaxPopinMedia extends ControllerBase {

  /**
   * @var MediaTools
   */
  protected $tools;

  public function __construct(MediaTools $tools) {
    $this->tools = $tools;
  }

  public function render($media) {


    $build = $this->tools->buildMediaForPopinDisplay($media);

    $selector = ".Popin-media .Popin-wrapper";

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand($selector,$build));
    return $response;
  }
}
