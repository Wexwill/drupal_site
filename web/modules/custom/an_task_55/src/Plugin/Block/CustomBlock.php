<?php

/**
 * @file
 * Contains \Drupal\an_task_55\Plugin\Block\CustomBlock.
 */

namespace Drupal\an_task_55\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 *
 * @Block(
 *   id = "custom_block",
 *   admin_label = @Translation("Custom block with ajax link"),
 * )
 */
class CustomBlock extends BlockBase {

  /**
   * @return \Drupal\Core\Url
   */
  private function getUrl() {
    $url = Url::fromRoute('an_task_55.routing', [], ['absolute' => TRUE]);

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [
      '#theme' => 'custom_block_with_ajax',
      '#custom_url' => $this->getUrl(),
    ];

    return $block;
  }
}
