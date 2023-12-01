<?php

namespace Drupal\devel_generate_commerce\Plugin\DevelGenerate;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_price\Price;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\devel_generate\DevelGenerateBase;
use Drupal\state_machine\WorkflowManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a CommerceDevelGenerate plugin.
 *
 * @DevelGenerate(
 *   id = "commerce",
 *   label = @Translation("commerce"),
 *   description = @Translation("Generate a given number of products, product types, product variations, orders."),
 *   url = "commerce",
 *   permission = "administer devel_generate_commerce",
 *   settings = {
 *     "kill" = FALSE,
 *     "products_num" = 50,
 *     "product_types_num" = 1,
 *     "product_variations_num" = 1,
 *     "product_price_from" = 1,
 *     "product_price_max" = 100,
 *     "orders_num" = 50,
 *     "time_range" = 604800
 *   }
 * )
 */
class CommerceDevelGenerate extends DevelGenerateBase implements ContainerFactoryPluginInterface {

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Provides system time.
   *
   * @var \Drupal\Core\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The workflow plugin manager.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;

  /**
   * Constructs a new UserDevelGenerate object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The user storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Datetime\TimeInterface $time
   *   Provides system time.
   * @param \Drupal\state_machine\WorkflowManagerInterface $workflow_manager
   *   The workflow plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    DateFormatterInterface $date_formatter,
    TimeInterface $time,
    WorkflowManagerInterface $workflow_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
    $this->workflowManager = $workflow_manager;

    // Try to load the default store.
    $this->store = $this
      ->entityTypeManager
      ->getStorage('commerce_store')
      ->loadDefault();

    // If there is no default store, we need to create one.
    if (!$this->store instanceof StoreInterface) {
      $this->store = $this->entityTypeManager->getStorage('commerce_store')->create([
        'type' => 'online',
        'uid' => 1,
        'name' => 'Default store',
        'mail' => 'default_store@example.com',
        'default_currency' => 'USD',
        'timezone' => 'Australia/Sydney',
        'address' => [
          'country_code' => 'US',
          'address_line1' => $this->getRandom()->word(20),
          'locality' => $this->getRandom()->word(5),
          'administrative_area' => 'WI',
          'postal_code' => '53597',
        ],
        'billing_countries' => ['US'],
        'is_default' => TRUE,
      ]);
      $this->store->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('datetime.time'),
      $container->get('plugin.manager.workflow')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['products_num'] = [
      '#type' => 'number',
      '#title' => $this->t('Products'),
      '#description' => $this->t('How many products would you like to generate?'),
      '#default_value' => $this->getSetting('products_num'),
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 100,
    ];

    $form['product_types_num'] = [
      '#type' => 'number',
      '#title' => $this->t('Product types'),
      '#description' => $this->t('How many product types would you like to generate?'),
      '#default_value' => $this->getSetting('product_types_num'),
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 100,
    ];

    $form['product_variations_num'] = [
      '#type' => 'number',
      '#title' => $this->t('Product variations'),
      '#description' => $this->t('How many product variations would you like to generate for a certain product?'),
      '#default_value' => $this->getSetting('product_variations_num'),
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 10,
    ];

    $form['product_price_from'] = [
      '#type' => 'number',
      '#title' => $this->t('Product price from'),
      '#description' => $this->t('Set the min price for a certain product?'),
      '#default_value' => $this->getSetting('product_price_from'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $form['product_price_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Product price max'),
      '#description' => $this->t('Set the max price for a certain product?'),
      '#default_value' => $this->getSetting('product_price_max'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $form['orders_num'] = [
      '#type' => 'number',
      '#title' => $this->t('Orders'),
      '#description' => $this->t('How many orders would you like to generate?'),
      '#default_value' => $this->getSetting('orders_num'),
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 100,
    ];

    $form['order_workflow_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Order workflow'),
    ];
    $workflows = $this->workflowManager->getDefinitions();
    $order_workflows = [];
    foreach ($workflows as $workflow) {
      $id = $workflow['id'];
      $order_workflows[$id] = $workflow['label'];

      if (!$workflow['states']) {
        continue;
      }

      $state_options = [];
      foreach ($workflow['states'] as $state_id => $state) {
        $state_options[$state_id] = $state['label'];
      }
      $form['order_workflow_wrapper']['order_statuses_' . $id] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Order statuses'),
        '#options' => $state_options,
        '#description' => $this->t('Set the random statuses for order generation.
          If none is selected, all statuses will be taken in count.'),
        '#states' => [
          'visible' => [
            ':input[name="order_workflow"]' => ['value' => $id],
          ],
        ],
        '#weight' => 0,
      ];
    }

    $form['order_workflow_wrapper']['order_workflow'] = [
      '#type' => 'select',
      '#name' => 'order_workflow',
      '#options' => $order_workflows,
      '#title' => '',
      '#description' => $this->t('Select the order workflow.'),
      '#weight' => -1,
    ];

    $form['kill'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete all?'),
      '#description' => $this->t('Delete all the generated products, product types, product variations, orders before generating new.'),
      '#default_value' => $this->getSetting('kill'),
    ];

    $options = [1 => $this->t('Now')];
    foreach ([3600, 86400, 604800, 2592000, 31536000] as $interval) {
      $options[$interval] = $this->dateFormatter->formatInterval($interval, 1) . ' ' . $this->t('ago');
    }
    $form['time_range'] = [
      '#type' => 'select',
      '#title' => $this->t('How old may products, product types, product variations, orders be?'),
      '#description' => $this->t('Created date will be distributed randomly from the current time, back to the selected time.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('time_range'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function generateElements(array $values) {
    // Collect the submited form config values.
    $kill = $values['kill'];
    $products_num = $values['products_num'];
    $product_types_num = $values['product_types_num'];
    $product_variations_num = $values['product_variations_num'];
    $product_price_from = $values['product_price_from'];
    $product_price_max = $values['product_price_max'];
    $orders_num = $values['orders_num'];
    $time_range = $values['time_range'];

    // Get the config from "drush" command (if command fired).
    if (!empty($values['order_statuses'])) {
      $active_statuses = explode(',', $values['order_statuses']);
    }
    else {
      // Get the config from the admin UI.
      $order_workflow = $values['order_workflow'];
      $order_workflow_statuses = $values['order_statuses_' . $order_workflow];

      $active_statuses = [];
      foreach ($order_workflow_statuses as $id => $status) {
        if ($status) {
          $active_statuses[$id] = $status;
        }
      }

      if (!$active_statuses) {
        $active_statuses = array_keys($order_workflow_statuses);
      }
    }

    // Remove all the products, product types, product variations, orders.
    if ($kill) {
      $this->geleteAllEntities();
    }

    // Generate product types.
    if ($product_types_num > 0) {
      $this->generateProductTypes($product_types_num);
    }

    // Generate products.
    if ($products_num > 0) {
      $this->generateProducts(
        $products_num,
        $product_variations_num,
        $time_range,
        $product_price_from,
        $product_price_max
      );
    }

    // Generate orders.
    if ($orders_num > 0) {
      $this->generateOrders($orders_num, $time_range, $active_statuses);
    }
  }

  /**
   * Delete all the generated commerce entities.
   */
  protected function geleteAllEntities() {
    $kill_config = [
      'commerce_product' => [
        'singular' => 'product',
        'plural' => 'products',
      ],
      'commerce_product_type' => [
        'singular' => 'product type',
        'plural' => 'products types',
      ],
      'commerce_product_variation' => [
        'singular' => 'product variation',
        'plural' => 'products variations',
      ],
      'commerce_product_variation_type' => [
        'singular' => 'product variation type',
        'plural' => 'products variation types',
      ],
      'commerce_order' => [
        'singular' => 'order',
        'plural' => 'orders',
      ],
    ];

    foreach ($kill_config as $entity_name => $config) {
      // Load and delete all entities.
      $entities = $this
        ->entityTypeManager
        ->getStorage($entity_name)
        ->loadMultiple();

      if ($entities) {
        $this->entityTypeManager->getStorage($entity_name)->delete($entities);
        // Display a message after deletion.
        $this->setMessage(
          $this->formatPlural(
            count($entities),
            '1 ' . $config['singular'] . ' deleted',
            '@count ' . $config['plural'] . ' deleted.'
          )
        );
      }
      else {
        $this->setMessage('0 ' . $config['plural'] . ' deleted.');
      }
    }
  }

  /**
   * Generate orders by the given amount.
   *
   * @param int $orders_num
   *   The number of orders to generate.
   * @param int $time_range
   *   The time range to set for the order "created".
   * @param array $order_workflow_statuses
   *   The list of order workflow statuses.
   */
  protected function generateOrders(int $orders_num, int $time_range, array $order_workflow_statuses) {
    // Load and delete all entities.
    $variations = $this
      ->entityTypeManager
      ->getStorage('commerce_product_variation')
      ->loadMultiple();

    if (!$variations) {
      return $this->setMessage('There are no available product variations in oder to generate an order.');
    }

    $generated_orders = 0;
    while ($orders_num > 0) {
      // Get a random variation ID and then attach it to the order item.
      $variation_id = array_rand($variations, 1);
      // Prepare and save an order item before the actual order saving.
      $order_item = $this
        ->entityTypeManager
        ->getStorage('commerce_order_item')
        ->createFromPurchasableEntity($variations[$variation_id]);
      // Save the order item.
      $order_item->save();

      // Get a random order workflow status.
      $order_workflow_status = array_rand($order_workflow_statuses, 1);
      $email = $this->store->getEmail();
      // Order data preparation for saving.
      $order = $this->entityTypeManager
        ->getStorage('commerce_order')->create([
          'type' => 'default',
          'mail' => $email,
          'store_id' => $this->store->id(),
          'order_items' => [$order_item],
          'state' => $order_workflow_status,
          'placed' => $this->time->getRequestTime() - mt_rand(0, $time_range),
        ]);
      $currency = $this->store->getDefaultCurrencyCode();
      // Add a dummy order adjustment.
      $order->addAdjustment(new Adjustment([
        'type' => 'tax',
        'label' => $this->t('Tax'),
        'amount' => new Price(mt_rand(0, 100), $currency),
        'included' => TRUE,
      ]));
      // Re-calculate the total price.
      $order->recalculateTotalPrice();
      $order->save();
      // Decrease the orders number.
      $orders_num--;
      // Increase the number of the generated orders.
      $generated_orders++;
    }
    // Display a system message after products insertion.
    $this->setMessage(
      $this->t(
        '@orders_num created.',
        ['@orders_num' => $this->formatPlural($generated_orders, '1 order', '@count orders')]
      )
    );
  }

  /**
   * Generate products by the given amount.
   *
   * @param int $products_num
   *   The number of products to generate.
   * @param int $product_variations_num
   *   The number of variations for add for a certain product.
   * @param int $time_range
   *   The time range to set for the product "created".
   * @param int $product_price_from
   *   The product min price.
   * @param int $product_price_max
   *   The product max price.
   */
  protected function generateProducts(
    int $products_num,
    int $product_variations_num,
    int $time_range,
    int $product_price_from,
    int $product_price_max
  ) {
    // Load all the available product types.
    $product_types = $this
      ->entityTypeManager
      ->getStorage('commerce_product_type')
      ->loadMultiple();

    if (!$product_types) {
      return $this->setMessage('There are no available product types. Can not generate products.');
    }

    // Allow a product generation only when there is at least 1 product type.
    if (!empty($product_types)) {
      $product_types = array_keys($product_types);
      $generated_products = 0;
      while ($products_num > 0) {
        // Set a random product title.
        $title = $this->getRandom()->word(mt_rand(6, 20));
        // Get a random product type (if there are more than 1).
        $product_type = $product_types[array_rand($product_types, 1)];
        // Generate the product variations (if any).
        $variations = $this->generateProductVariations(
          $product_type,
          $product_variations_num,
          $product_price_from,
          $product_price_max
        );
        // Prepare the product.
        $product = $this->entityTypeManager
          ->getStorage('commerce_product')->create([
            'uid' => 1,
            'type' => $product_type,
            'title' => $title,
            'status' => TRUE,
            'stores' => [$this->store],
            'variations' => $variations,
            'created' => $this->time->getRequestTime() - mt_rand(0, $time_range),
          ]);
        // Populate all fields with sample values.
        $this->populateFields($product);
        $product->save();
        // Decrease the products number.
        $products_num--;
        // Increase the number of the generated products.
        $generated_products++;
      }
      // Display a system message after products insertion.
      $this->setMessage(
        $this->t(
          '@products_num created.',
          ['@products_num' => $this->formatPlural($generated_products, '1 product', '@count products')]
        )
      );
    }
  }

  /**
   * Generate the product types by the given amount.
   *
   * @param int $product_types_num
   *   The number of product types to generate.
   */
  protected function generateProductTypes(int $product_types_num) {
    $generated_product_types = 0;
    while ($product_types_num > 0) {
      // Set a random product type id, label, variation type.
      $type = $this->getRandom()->word(mt_rand(6, 20));
      $product_type = $this
        ->entityTypeManager
        ->getStorage('commerce_product_type')->create([
          'id' => $type,
          'label' => $type,
          'variationType' => $type,
          'multipleVariations' => TRUE,
          'injectVariationFields' => TRUE,
        ]);
      // Populate all fields with sample values.
      $this->populateFields($product_type);
      $product_type->save();

      // Once we have the product type we need to create the variation type.
      $this->entityTypeManager->getStorage('commerce_product_variation_type')->create([
        'id' => $type,
        'label' => $type,
        'status' => TRUE,
        'orderItemType' => 'default',
        'generateTitle' => TRUE,
        'trait' => ['purchasable_entity_shippable'],
      ])->save();

      // Decrease the product types number.
      $product_types_num--;
      // Increase the number of the generated products.
      $generated_product_types++;
    }
    // Display a system message after product types insertion.
    $this->setMessage(
      $this->t(
        '@product_types_num created.',
        ['@product_types_num' => $this->formatPlural($generated_product_types, '1 product type', '@count product types')]
      )
    );
  }

  /**
   * Generate product variations per product type (by the given amount).
   *
   * @param string $product_type
   *   The product type to generate variations for.
   * @param int $count
   *   The number of variations to generate.
   * @param int $product_price_from
   *   The product min price.
   * @param int $product_price_max
   *   The product max price.
   *
   * @return array
   *   The list of generated variations for the given product type.
   */
  protected function generateProductVariations(
    string $product_type,
    int $count,
    int $product_price_from,
    int $product_price_max
  ) {
    $variations = [];
    // Preprocess product variations import.
    while ($count > 0) {
      // Set a random price for each variation in part.
      $product_price = mt_rand($product_price_from, $product_price_max);
      // Let's get the currency from the default store.
      $currency = $this->store->getDefaultCurrencyCode();
      $price = new Price($product_price, $currency);
      // Set a random product variation name.
      $name = $this->getRandom()->word(mt_rand(6, 20));
      $product_variation = $this
        ->entityTypeManager
        ->getStorage('commerce_product_variation')->create([
          'type' => $product_type,
          'sku' => $product_type,
          'status' => TRUE,
          'title' => $name,
          'price' => $price,
          // Set unlimited stock.
          'commerce_stock_always_in_stock' => TRUE,
        ]);
      // Populate all fields with sample values.
      $this->populateFields($product_variation);
      $product_variation->save();
      $variations[] = $product_variation;
      // Decrease the product variations number.
      $count--;
    }
    return $variations;
  }

  /**
   * {@inheritdoc}
   */
  public function validateDrushParams(array $args, array $options = []) {
    // Prepare the sent values from terminal.
    $values = [
      'kill' => (bool) $options['kill'],
      'products_num' => $options['products_num'],
      'product_types_num' => $options['product_types_num'],
      'product_variations_num' => $options['product_variations_num'],
      'product_price_from' => $options['product_price_from'],
      'product_price_max' => $options['product_price_max'],
      'orders_num' => $options['orders_num'],
      'time_range' => $options['time_range'],
      'order_statuses' => $options['order_statuses'],
    ];
    return $values;
  }

}
