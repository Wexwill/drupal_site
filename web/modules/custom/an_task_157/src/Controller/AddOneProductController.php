<?php

namespace Drupal\an_task_157\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class AddOneProductController
 *
 * @package Drupal\an_task_157\Controller
 */
class AddOneProductController extends ControllerBase {

  /**
   * @param $pid
   * @param $quantity
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addToCart($pid, $quantity) {
    $ajax_response = new AjaxResponse();

    // Add shareable product to the cart.
    if (!empty($pid) && !empty($quantity)) {
      $store_id = 1;
      $order_type = 'default';

      $entity_manager = \Drupal::entityTypeManager();
      $cart_manager = \Drupal::service('commerce_cart.cart_manager');
      $cart_provider = \Drupal::service('commerce_cart.cart_provider');
      $store = $entity_manager->getStorage('commerce_store')->load($store_id);

      // Load current users cart.
      $cart = $cart_provider->getCart($order_type, $store);
      if (!$cart) {
        $cart = $cart_provider->createCart($order_type, $store);
      }

      $product_variation = $entity_manager->getStorage('commerce_product_variation')->load($pid);

      // Create new order item.
      $order_item = $entity_manager->getStorage('commerce_order_item')->create(array(
        'type' => 'default',
        'purchased_entity' => (string) $pid,
        'quantity' => $quantity,
        'unit_price' => $product_variation->getPrice(),
      ));
      $order_item->save();

      $cart_manager->addOrderItem($cart, $order_item);

      $ajax_response->addCommand(new AlertCommand('The product has been added to your cart!'));
    }

    return $ajax_response;
  }
}
