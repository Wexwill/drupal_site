<?php

namespace Drupal\an_task_83\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get users.
 *
 * @RestResource(
 *   id = "list_users_resource",
 *   label = @Translation("List users resource"),
 *   uri_paths = {
 *     "canonical" = "/service/getusers"
 *   }
 * )
 */
class GetUsersResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Default limit of entities per request.
   *
   * @var int
   */
  protected $users_per_page = 10;

  /**
   * Default page number.
   *
   * @var int
   */
  protected $page = 1;

  /**
   * Constructs a new GetUsersResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('an_task_83'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get() {
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    // Add default cache parameters.
    $cache = CacheableMetadata::createFromRenderArray([
      '#cache' => [
        'max-age' => 0,
        'contexts' => ['url.query_args'],
      ],
    ]);

    $response = [
      'users' => [],
    ];

    //Retrieve the data passed by the request
    $request = \Drupal::request();
    $request_query = $request->query;
    $limit = $request_query->get('users_per_page') ?? $this->users_per_page;
    $page = $request_query->get('page') ?? $this->page;
    $offset = ($page-1) * $limit;

    //Getting the required number of users
    $userStorage = \Drupal::entityTypeManager()->getStorage('user');
    $query = $userStorage->getQuery();
    $uids = $query
      ->condition('status', '1')
      ->range($offset, $limit)
      ->execute();
    $users = $userStorage->loadMultiple($uids);

    //Pushing users data into the response
    foreach ($users as $user) {
      $response['users'][] = [
        'name' => $user->getDisplayName(),
        'email' => $user->getEmail(),
      ];
      $cache->addCacheableDependency($user);
    }

    return (new ResourceResponse($response, 200))->addCacheableDependency($cache);
  }
}
