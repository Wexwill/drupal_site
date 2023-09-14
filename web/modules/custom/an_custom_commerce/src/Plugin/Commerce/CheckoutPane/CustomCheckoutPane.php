<?php

namespace Drupal\an_custom_commerce\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom message pane.
 *
 * @CommerceCheckoutPane(
 *   id = "my_checkout_pane_custom_comment",
 *   label = @Translation("Custom comment"),
 * )
 */
class CustomCheckoutPane extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $comment = $this->order->getData('order_comment');
    $pane_form['comment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Optional order comment'),
      '#default_value' => $comment ? $comment : '',
      '#size' => 60,
    ];
    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    if ($order_comment = $this->order->getData('order_comment')) {
      return [
        '#plain_text' => $order_comment,
      ];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $this->order->setData('order_comment', $values['comment']);
  }

}
