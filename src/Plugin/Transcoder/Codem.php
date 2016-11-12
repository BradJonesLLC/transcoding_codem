<?php

namespace Drupal\transcoding_codem\Plugin\Transcoder;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\transcoding\Plugin\TranscoderBase;
use Drupal\transcoding\Annotation\Transcoder;

/**
 * @Transcoder (
 *   id = "codem",
 *   label = "Codem"
 * )
 */
class Codem extends TranscoderBase {

  use StringTranslationTrait;

  /**
   * @inheritDoc
   */
  public function defaultConfiguration() {
    return [
      'scheduler' => '',
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
      '#default_value' => $values['scheduler'],
      '#attributes' => ['placeholder' => 'https://scheduler']
    ];
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!UrlHelper::isValid($form_state->getValue('scheduler'), TRUE)) {
      $form_state->setErrorByName('scheduler', $this->t('Scheduler URL is invalid. Must be absolute.'));
    }
  }

  /**
   * @inheritDoc
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $config = $form_state->getValues();
    if ($config) {
      $this->setConfiguration($config);
    }
  }

}
