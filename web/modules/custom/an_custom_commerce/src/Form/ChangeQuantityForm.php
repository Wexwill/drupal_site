<?php

namespace Drupal\an_custom_commerce\Form;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements Form API.
 */
class ChangeQuantityForm extends FormBase {

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
   * The order item storage.
   *
   * @var \Drupal\commerce_order\OrderItemStorageInterface
   */
  protected $orderItemStorage;

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
    $this->orderItemStorage = $entity_type_manager->getStorage('commerce_order_item');
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'change_quantity_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $quantity = NULL, $purchased_entity = NULL) {

    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Name'),
      '#default_value' => $quantity,
    ];

    $form['purchased_entity'] = [
      '#type' => 'hidden',
      '#value' => $purchased_entity,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::submitAjaxForm',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * SubmitAjaxForm.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function submitAjaxForm(array &$form, FormStateInterface $form_state) {
    $quantity = (int) $form_state->getValue('quantity');
    $purchased_entity = $form_state->getValue('purchased_entity');
    $store = $this->entityTypeManager->getStorage('commerce_store')->load(1);
    $order_type = 'default';

    // Load current users cart.
    $cart = $this->cartProvider->getCart($order_type, $store);
    $cart_id = $cart->id();

    // Load the order items by the cart ID using the order item storage service.
    $order_items = $this->orderItemStorage->loadByProperties(['order_id' => $cart_id]);

    foreach ($order_items as $order_item) {
      $item_quantity = (int) $order_item->getQuantity();
      $product_variation = $order_item->getPurchasedEntity();
      $sku = $product_variation->getSku();

      // Check if the item quantity has been changed.
      if ($sku === $purchased_entity && $item_quantity != $quantity && $quantity > 0) {
        $order_item->setQuantity($quantity);

        // Update the order item.
        $this->cartManager->updateOrderItem($cart, $order_item);
        $order_item->save();
      }

    }
    // Save the cart.
    $cart->save();

    // Redirect to the cart page.
    $response = new AjaxResponse();
    $currentURL = Url::fromRoute('commerce_cart.page');
    $response->addCommand(new RedirectCommand($currentURL->toString()));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
