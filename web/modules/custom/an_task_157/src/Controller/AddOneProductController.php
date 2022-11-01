<?php

namespace Drupal\an_task_157\Controller;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AddOneProductController.
 *
 * @package Drupal\an_task_157\Controller
 */
class AddOneProductController extends ControllerBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * AddOneProductController constructor.
   *
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart provider.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider')
    );
  }

  /**
   * Provides ability to add shareable product to the cart.
   *
   * @param int $pid
   *   Product id.
   * @param int $quantity
   *   Amount of products.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response.
   *
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

      $store = $this->entityTypeManager()->getStorage('commerce_store')->load($store_id);

      // Load current users cart.
      $cart = $this->cartProvider->getCart($order_type, $store);
      if (!$cart) {
        $cart = $this->cartProvider->createCart($order_type, $store);
      }

      $product_variation = $this->entityTypeManager()->getStorage('commerce_product_variation')->load($pid);

      // Create new order item.
      $order_item = $this->entityTypeManager()->getStorage('commerce_order_item')->create([
        'type' => 'default',
        'purchased_entity' => (string) $pid,
        'quantity' => $quantity,
        'unit_price' => $product_variation->getPrice(),
      ]);
      $order_item->save();

      $this->cartManager->addOrderItem($cart, $order_item);

      $ajax_response->addCommand(new AlertCommand('The product has been added to your cart!'));
    }

    return $ajax_response;
  }

}
