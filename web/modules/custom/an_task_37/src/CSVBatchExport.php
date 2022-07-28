<?php

namespace Drupal\an_task_37;

use Drupal\user\Entity\User;

/**
 * Class CSVBatchExport.
 *
 * @package Drupal\an_task_37
 */
class CSVBatchExport {

  /**
   * @var array
   */
  private $batch;

  /**
   * @var array
   */
  private $users;

  public function __construct($users, $batch_name = 'Users export') {
    $this->users = $users;
    $this->batch = [
      'title' => $batch_name,
      'finished' => [$this, 'finished'],
      'file' => \Drupal::service('extension.list.module')->getPath('an_task_37') . '/src/CSVBatchExport.php',
    ];
    $this->setOperation();
  }

  /**
   * Method for registering a batch operation.
   */
  public function setBatch() {
    batch_set($this->batch);
  }

  /**
   * @param $user
   *
   * Adding batch operations to the queue.
   */
  public function setOperation() {
    $users = $this->getUsers();

    foreach ($users as $user) {
      $this->batch['operations'][] = [[$this, 'processItem'], [$user]];
    }
  }

  /**
   * @param $user
   * @param $context
   *
   * Adding a line to a csv file.
   */
  public function processItem($user, &$context) {
    $handle = fopen("public://users.csv",'a+');
    fputcsv($handle, $user);
    $context['message'] = 'gooo';
    fclose($handle);
  }

  /**
   * @return array
   *
   * Gets a list of users to export.
   */
  public function getUsers() {
    $users_array = [];
    $ids = \Drupal::entityQuery('user')
      ->execute();
    $users = User::loadMultiple($ids);
    foreach($users as $user){
      $user_array = [];
      $username = $user->get('name')->getString();
      if (!empty($username)) {
        $user_array[] = $username;
        $user_array[] =  $user->get('mail')->getString();
        $user_array[] =  $user->get('pass')->getString();
        array_push($users_array, $user_array);
      }
      if (count($users_array) >= $this->users) break;
    }
    return $users_array;
  }

  /**
   * Method to be called when finished batch operation
   */
  public function finished($success) {
    if ($success) {
      $message = t('Finished successfully.');
    }
    else {
      $message = t('Finished with an error.');
    }
    $messenger= \Drupal::messenger();
    $messenger->addMessage($message);
  }
}
