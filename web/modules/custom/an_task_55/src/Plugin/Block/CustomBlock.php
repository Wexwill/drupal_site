<?php

/**
 * @file
 * Contains \Drupal\an_task_55\Plugin\Block\CustomBlock.
 */

namespace Drupal\an_task_55\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 *
 * @Block(
 *   id = "custom_block",
 *   admin_label = @Translation("Custom block with ajax link"),
 * )
 */
class CustomBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [
      '#type' => 'markup',
      '#markup' => '<a class="use-ajax" href="/custom_ajax_link/Andrei">Open alert for ajax link testing</a>'
    ];
    return $block;
  }
}
