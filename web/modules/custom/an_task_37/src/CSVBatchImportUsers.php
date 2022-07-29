<?php

namespace Drupal\an_task_37;

use Drupal\file\Entity\File;
use Drupal\user\Entity\User;

/**
 * Class CSVBatchImportUsers.
 *
 * @package Drupal\an_task_37
 */
class CSVBatchImportUsers {

  /**
   * @var array
   */
  private $batch;

  /**
   * @var \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface|\Drupal\file\Entity\File|null
   */
  private $file;

  /**
   * @var false
   */
  private $skip_first_line;


  /**
   * CSVBatchImportUsers constructor.
   *
   * @param $fid
   * @param false $skip_first_line
   * @param string $batch_name
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct($fid, $skip_first_line = FALSE, $batch_name = 'CSV users import') {
    $this->file = File::load($fid);
    $this->skip_first_line = $skip_first_line;
    $this->batch = [
      'title' => $batch_name,
      'finished' => [$this, 'finished'],
      'file' => \Drupal::service('extension.list.module')->getPath('an_task_37') . '/src/CSVBatchImportUsers.php',
    ];
    $this->parseCSV();
  }

  /**
   * @param $data
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * Method for adding batch operations.
   */
  public function setOperation($data) {
    if (empty($this->checkUser($data[0]))) {
      $this->batch['operations'][] = [[$this, 'processItem'], $data];
    }
  }

  /**
   * Method for registering a batch operation.
   */
  public function setBatch() {
    batch_set($this->batch);
  }

  /**
   * @return mixed
   *
   * Method for obtaining information about the presence of batch operations.
   */
  public function getOperations() {
    return $this->batch['operations'];
  }

  /**
   * @param $userName
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * Method for checking if a user exists.
   */
  public function checkUser($userName) {
    $userStorage = \Drupal::entityTypeManager()->getStorage('user');
    $query = $userStorage->getQuery();
    $uid = $query
      ->condition('status', '1')
      ->condition('name', $userName)
      ->execute();

    return $userStorage->loadMultiple($uid);
  }

  /**
   * @param $userName
   * @param $email
   * @param $pass
   * @param $context
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * Adding a user to the site.
   */
  public function processItem($userName, $email, $pass, &$context) {
    if (empty($this->checkUser($userName))) {
      $user = User::create([
        'name' => $userName,
        'mail' => $email,
        'pass' => $pass,
        'status' => 1,
        'roles' => array('content_editor'),
      ]);
      $user->save();
    }

    $context['message'] = 'Importing users';
  }

  /**
   * @param $success
   *
   * Method that fires after the end of the batch operation.
   */
  public function finished($success) {
    if ($success) {
      $message = t('Users have been successfully imported.');
    }
    else {
      $message = t('Finished with an error.');
    }
    $messenger= \Drupal::messenger();
    $messenger->addMessage($message);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * Line-by-line processing of csv file.
   */
  public function parseCSV() {
    if (($handle = fopen($this->file->getFileUri(), 'r')) !== FALSE) {
      if ($this->skip_first_line) {
        fgetcsv($handle, 0, ',');
      }

      while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
        $this->setOperation($data);
      }

      fclose($handle);
    }
  }
}
