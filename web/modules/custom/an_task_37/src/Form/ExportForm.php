<?php

namespace Drupal\an_task_37\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\an_task_37\CSVBatchExport;

/**
 * Class ExportForm.
 *
 * @package Drupal\an_task_37\Form
 */
class ExportForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'export_form';
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['users_amount'] = [
      '#title' => $this->t('Number of users'),
      '#type' => 'number',
      '#default_value' => 1,
      '#required' => TRUE,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start'),
      '#submit' => ['::startExport'],
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($this->t('You have successfully exported @number users into CSV file', ['@number' => $form_state->getValue('users_amount')]));
  }

  /**
   * Method to start exporting users
   */
  public function startExport(array &$form, FormStateInterface $form_state) {
    $users_amount = $form_state->getValue('users_amount');
    $import = new CSVBatchExport($users_amount);
    $import->setBatch();
  }
}
