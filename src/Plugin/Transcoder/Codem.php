<?php

namespace Drupal\transcoding_codem\Plugin\Transcoder;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\transcoding\Plugin\TranscoderBase;
use Drupal\transcoding\Annotation\Transcoder;

/**
 * @Transcoder (
 *   id = "codem",
 *   label = "Codem
 * )
 */
class Codem extends TranscoderBase {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function defaultConfiguration() {
    return [
      'schedulerUrl' => '',
    ];
  }

  /**
   * @inheritDoc
   */
  public function calculateDependencies() {
    return [
      'module' => ['transcoding'],
    ];
  }

  /**
   * @inheritDoc
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $values = $this->getConfiguration() + $this->defaultConfiguration();
    $form['scheduler'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scheduler base URL'),
      '#default_value' => $values['schedulerUrl'],
      '#attributes' => ['placeholder' => 'https://scheduler,']
    ];
  }

  /**
   * @inheritDoc
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!UrlHelper::isValid($form_state->getValue('scheduler'))) {
      $form_state->setErrorByName('scheduler', $this->t('Scheduler URL is invalid.'));
    }
  }

  /**
   * @inheritDoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $config = NestedArray::getValue($form_state->getValues(), $form['#parents']);
    if ($config) {
      $this->setConfiguration($config);
    }
  }

}
