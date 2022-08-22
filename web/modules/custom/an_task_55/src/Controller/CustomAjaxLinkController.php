<?php

namespace Drupal\an_task_55\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class CustomAjaxLinkController
 *
 * @package Drupal\an_task_55\Controller
 */
class CustomAjaxLinkController extends ControllerBase{

  /**
   * @param $name
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function customAjaxLinkAlert($name) {
    $response = new AjaxResponse();
    $response->addCommand(new AlertCommand('Hello ' . $name));

    return $response;
  }
}
