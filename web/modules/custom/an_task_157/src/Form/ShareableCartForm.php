<?php

namespace Drupal\an_task_157\Form;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Form API.
 */
class ShareableCartForm extends FormBase {

  /**
   * Contains shopping cart data.
   *
   * @var array
   */
  private $cartData;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart provider.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider')
    );
  }

  /**
   * Form id.
   *
   * @inheritDoc
   */
  public function getFormId() {
    return 'shareable_cart_form';
  }

  /**
   * Form constructor.
   *
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

      $cart_data = json_decode($cart_data, TRUE);
      $this->cartData = $cart_data;

      foreach ($cart_data as $key => $product) {
        $sku = array_pop($product);

        $link = Url::fromRoute('an_task_157.add_one_product',
                ['pid' => $sku, 'quantity' => $product['quantity']])
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
   * Form submission handler.
   *
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cart_data = $this->cartData;

    if (!empty($cart_data)) {
      $store_id = 1;
      $order_type = 'default';

      $store = $this->entityTypeManager->getStorage('commerce_store')->load($store_id);

      // Load current users cart.
      $cart = $this->cartProvider->getCart($order_type, $store);

      if (!$cart) {
        $cart = $this->cartProvider->createCart($order_type, $store);
      }

      // Push each product to the users cart.
      foreach ($cart_data as $product) {
        $product_variation = $this->entityTypeManager->getStorage('commerce_product_variation')
          ->load($product['sku']);

        // Create new order item.
        $order_item = $this->entityTypeManager->getStorage('commerce_order_item')->create([
          'type' => 'default',
          'purchased_entity' => (string) $product['sku'],
          'quantity' => $product['quantity'],
          'unit_price' => $product_variation->getPrice(),
        ]);
        $order_item->save();

        $this->cartManager->addOrderItem($cart, $order_item);
      }

      $form_state->setRedirect('commerce_cart.page');
    }
  }

}
