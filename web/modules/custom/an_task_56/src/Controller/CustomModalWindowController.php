<?php

namespace Drupal\an_task_56\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class CustomModalWindowController
 *
 * @package Drupal\an_task_56\Controller
 */
class CustomModalWindowController extends ControllerBase {

  /**
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function customModalWindow() {
    $response = new AjaxResponse();
    $modal = $this->t('Hi!');
    $options = [
      'width' => '75%',
    ];
    $response->addCommand(new OpenModalDialogCommand('My Modal', $modal, $options));
    return $response;
  }

}
