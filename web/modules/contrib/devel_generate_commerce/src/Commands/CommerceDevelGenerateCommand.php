<?php

namespace Drupal\devel_generate_commerce\Commands;

use Drupal\devel_generate\Commands\DevelGenerateCommands;

/**
 * Provide Drush commands for commerce devel generation.
 */
class CommerceDevelGenerateCommand extends DevelGenerateCommands {

  /**
   * Devel generate commerce command.
   *
   * @param array $options
   *   Array of options as described below.
   *
   * @command devel-generate:commerce
   * @aliases gencom, devel-generate-commerce
   * @pluginId commerce
   *
   * @option kill Delete all entities before generation. A "true" or "false"
   *   flag. For drush just include the param without any value.
   * @option products_num Specify a number of products to generate.
   * @option product_types_num Specify a number of product types to generate.
   * @option product_variations_num Specify a number of product variations to
   *   generate for a certain product.
   * @option product_price_from Specify the min product price.
   * @option product_price_max Specify the max product price.
   * @option orders_num Specify a number of orders to generate.
   * @option time_range Specify a time framce for "created" from current time.
   * @option order_statuses Specify the random order workflow statuses for
   *   generation.
   */
  public function commerce(
    array $options = [
      'kill' => FALSE,
      'products_num' => 50,
      'product_types_num' => 1,
      'product_variations_num' => 1,
      'product_price_from' => 1,
      'product_price_max' => 100,
      'orders_num' => 50,
      'time_range' => 604800,
      'order_statuses' => 'completed',
    ]
  ) {
    // Run the generate command.
    $this->generate();
  }

}
