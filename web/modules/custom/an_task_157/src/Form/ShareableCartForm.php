<?php

namespace Drupal\an_task_157\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Implements Form API.
 */
class ShareableCartForm extends FormBase {

  /**
   * @var array
   */
  private $cart_data;

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'shareable_cart_form';
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->getRequest();
    $query_parameters = $request->query->all();

    if (isset($query_parameters['key'])) {
      $verification_key = $query_parameters['key'];
      $cart_data = getProductsFromDatabase($verification_key);
    }

    // Prepare rows data for the table.
    if (!empty($cart_data)) {
      $header = ['Product', 'Price', 'Quantity', 'Total price'];

      $cart_data = json_decode($cart_data, true);
      $this->cart_data = $cart_data;

      foreach ($cart_data as $key => $product) {
        $sku = array_pop($product);

        $link = Url::fromRoute('an_task_157.add_one_product', ['pid' => $sku, 'quantity' => $product['quantity']])
                ->toString();

        $product[] = [
          'data' => [
            '#markup' => '<a href="' . $link . '" class="use-ajax -add-to-cart">Add product</a>',
          ],
        ];

        $cart_data[$key] = $product;
      }

      $form['cart_table'] = [
        '#type' => 'table',
        '#title' => 'Table',
        '#header' => $header,
        '#rows' => $cart_data,
      ];

      $form['actions'] = [
        '#type' => 'actions',
      ];

      $form['actions']['add_to_cart'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add all products'),
      ];
    }

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cart_data = $this->cart_data;

    if (!empty($cart_data)) {
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

      // Push each product to the users cart.
      foreach ($cart_data as $product) {
        $product_variation = $entity_manager->getStorage('commerce_product_variation')
          ->load($product['sku']);

        // Create new order item.
        $order_item = $entity_manager->getStorage('commerce_order_item')->create([
          'type' => 'default',
          'purchased_entity' => (string) $product['sku'],
          'quantity' => $product['quantity'],
          'unit_price' => $product_variation->getPrice(),
        ]);
        $order_item->save();

        $cart_manager->addOrderItem($cart, $order_item);
      }

      $form_state->setRedirect('commerce_cart.page');
    }
  }
}
