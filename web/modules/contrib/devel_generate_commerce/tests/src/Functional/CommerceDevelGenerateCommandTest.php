<?php

namespace Drupal\Tests\devel_generate_commerce\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\devel_generate\Traits\DevelGenerateSetupTrait;
use Drush\TestTraits\DrushTestTrait;

/**
 * Devel Generate Commerce Module Drush Command Test.
 *
 * @group devel_generate_commerce
 */
class CommerceDevelGenerateCommandTest extends BrowserTestBase {

  use DrushTestTrait;
  use DevelGenerateSetupTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'block', 'devel_generate_commerce'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests generating commerce entities via drush.
   */
  public function testDrushGenerateCommand() {

    // Creates commerce entities With out Kill Option.
    $options = [
      'kill' => NULL,
      'products_num' => 55,
      'product_types_num' => 1,
      'product_variations_num' => 1,
      'product_price_from' => 1,
      'product_price_max' => 100,
      'orders_num' => 50,
      'time_range' => 604800,
      'order_statuses' => 'completed',
    ];

    $this->drush('devel-generate:commerce', [], $options);
    $commerce_entities = \Drupal::entityQuery('commerce_product')->accessCheck(TRUE)->execute();
    $this->assertCount(55, $commerce_entities);
  }

}
