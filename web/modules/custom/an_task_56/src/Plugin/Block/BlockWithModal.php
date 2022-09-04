<?php

/**
 * @file
 * Contains \Drupal\an_task_56\Plugin\Block\BlockWithModal.
 */

namespace Drupal\an_task_56\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 *
 * @Block(
 *   id = "block_with_modal",
 *   admin_label = @Translation("Custom block with modal window"),
 * )
 */
class BlockWithModal extends BlockBase {

  /**
   * @return \Drupal\Core\Url
   */
  private function getUrl() {
    $url = Url::fromRoute('an_task_56.routing', [], ['absolute' => TRUE]);

    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = [
      '#theme' => 'custom_block_with_modal',
      '#attached' => [
        'library' => [
          'an_task_56/an_task_56'
        ],
      ],
      '#custom_url' => $this->getUrl(),
    ];

    return $block;
  }
}
