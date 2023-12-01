### Description
This module is a development purpose solution that is used to auto-generation of
- Products
- Product Types
- Product Variations
- Orders

with the possibility to delete all the data before generation and also to set up
the specific amount of each of the commerce entity type.

There is also the possibility to set the how old may products, product types,
product variations, orders be.

### Dependencies
- commerce
- devel
- devel_generate
- commerce_order
- commerce_product
- commerce_checkout
- commerce_store
- commerce_price

### Usage
There is a separated admin page for manual generation:
`admin/config/development/generate/commerce`.

Second method is to use drush: `devel-generate:commerce` or alias `gencom`.
These are the options that can be used to generation:
- `products_num` Specify a number of products to generate.
- `product_types_num` Specify a number of product types to generate.
- `product_variations_num` Specify a number of product variations to generate
  for a certain product.
- `product_price_from` Specify the min product price.
- `product_price_max` Specify the max product price.
- `orders_num` Specify a number of orders to generate.
- `time_range` Specify a time framce for "created" from current time.
- `order_statuses` Specify the random order workflow statuses for generation.
- `kill` Delete all entities before generation. A "true" or "false" flag.

Example command: `drush devel-generate:commerce --products_num=10`.

It will override the `products_num` to `10` (default value is `50`).

### Related modules
- https://www.drupal.org/project/devel_generate_plus
- https://www.drupal.org/project/commerce_devel
