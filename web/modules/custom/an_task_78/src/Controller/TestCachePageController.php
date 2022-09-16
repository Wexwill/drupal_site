<?php

namespace Drupal\an_task_78\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class TestCachePageController
 *
 * @package Drupal\an_task_78\Controller
 */
class TestCachePageController extends ControllerBase{

  /**
   * @return \Drupal\Component\Render\MarkupInterface|string
   */
  public function getCachedUser() {
    $user = \Drupal::currentUser()->getDisplayName();
    $cid = 'current_user:' . \Drupal::currentUser()->id();
    $cache = \Drupal::cache()->get($cid);

    if ($cache && $user === $cache->data) {
      $user = $cache->data;
    } else {
      \Drupal::cache()->set($cid, $user);
    }

    return $user;
  }

  /**
   * @return array
   */
  public function content() {

    return [
      '#theme' => 'testcache_page',
      '#current_user' => $this->getCachedUser(),
    ];
  }
}

