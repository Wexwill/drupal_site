<?php

namespace Drupal\an_task_113\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 * @Filter(
 *   id = "capitalized_word",
 *   title = @Translation("Making words capitalized."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class CapitalizedWord extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['words'] = [
      '#type' => 'textarea',
      '#title' => $this->t('List of words'),
      '#description' => $this->t('List of words in which the first letter will be converted to uppercase. Words should be added in lowercase and separated by spaces. Example: "apple banana strawberry".'),
      '#default_value' => $this->settings['words'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\filter\FilterProcessResult
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    $words = $this->settings['words'];
    if (!empty($words)) {
      $text = $this->capitalizeWords($text, $words);
    }

    $result->setProcessedText($text);
    return $result;
  }

  /**
   * Method for capitalize words from text
   *
   * @param $text
   * @param $words
   *
   * @return array|mixed|string|string[]
   */
  public function capitalizeWords($text, $words) {
    $wordsArray = explode(" ", $words);
    $initialWords = [];
    $finalWords = [];
    foreach ($wordsArray as $word) {
      if (strpos($text, $word) === false) continue;
      $initialWords[] = $word;
      $word = ucfirst($word);
      $finalWords[] = $word;
    }
    return str_replace($initialWords, $finalWords, $text);
  }
}
