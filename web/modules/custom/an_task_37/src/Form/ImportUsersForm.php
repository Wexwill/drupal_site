<?php

namespace Drupal\an_task_37\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\an_task_37\CSVBatchImportUsers;

/**
 * Class ImportUsersForm.
 *
 * @package Drupal\custom_csv_import\Form
 */
class ImportUsersForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['an_task_37.import'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_users_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('an_task_37.import');

    $form['file'] = [
      '#title' => $this->t('CSV file'),
      '#type' => 'managed_file',
      '#upload_location' => 'public://',
      '#default_value' => $config->get('fid') ? [$config->get('fid')] : NULL,
      '#upload_validators' => array(
        'file_validate_extensions' => array('csv'),
      ),
      '#required' => TRUE,
    ];

    if (!empty($config->get('fid'))) {
      $file = File::load($config->get('fid'));
      $created = \Drupal::service('date.formatter')
        ->format($file->created->value, 'medium');

      $form['file_information'] = [
        '#markup' => $this->t('This file was uploaded at @created.', ['@created' => $created]),
      ];

      $form['actions']['start_import'] = [
        '#type' => 'submit',
        '#value' => $this->t('Start import'),
        '#submit' => ['::startImport'],
        '#weight' => 100,
      ];
    }

    $form['additional_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Additional settings'),
    ];

    $form['additional_settings']['skip_first_line'] = [
      '#type' => 'checkbox',
      '#title' => t('Skip first line'),
      '#default_value' => $config->get('skip_first_line'),
      '#description' => t('If file contain titles, this checkbox help to skip first line.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('an_task_37.import');
    $fid_old = $config->get('fid');
    $fid_form = $form_state->getValue('file')[0];
    if (empty($fid_old) || $fid_old != $fid_form) {
      if (!empty($fid_old)) {
        $previous_file = File::load($fid_old);
        \Drupal::service('file.usage')
          ->delete($previous_file, 'an_task_37', 'config_form', $previous_file->id());
      }

      $new_file = File::load($fid_form);
      $new_file->save();
      \Drupal::service('file.usage')
        ->add($new_file, 'an_task_37', 'config_form', $new_file->id());
      $config->set('fid', $fid_form)
        ->set('creation', time());
    }

    $config->set('skip_first_line', $form_state->getValue('skip_first_line'))
      ->save();
  }

  /**
   * {@inheritdoc}
   *
   * Method to start import from file.
   */
  public function startImport(array &$form, FormStateInterface $form_state) {
    $config = $this->config('an_task_37.import');
    $fid = $config->get('fid');
    $skip_first_line = $config->get('skip_first_line');
    $import = new CSVBatchImportUsers($fid, $skip_first_line);
    $batchOperations = $import->getOperations();
    if(!empty($batchOperations)) {
      $import->setBatch();
    } else {
      $messenger= \Drupal::messenger();
      $messenger->addMessage('Users from CSV file is already presented on the site.');
    }
  }
}
