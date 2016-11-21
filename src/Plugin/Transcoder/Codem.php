<?php

namespace Drupal\transcoding_codem\Plugin\Transcoder;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\transcoding\TranscodingMedia;
use Drupal\transcoding_codem\CodemClient;
use Drupal\transcoding\Plugin\TranscoderBase;
use Drupal\transcoding\Annotation\Transcoder;
use Drupal\transcoding\TranscodingStatus;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Transcoder (
 *   id = "codem",
 *   label = "Codem"
 * )
 */
class Codem extends TranscoderBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * The transcoding media service.
   *
   * @var \Drupal\transcoding\TranscodingMedia
   */
  protected $transcodingMedia;

  /**
   * @inheritDoc
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PrivateKey $privateKey, TranscodingMedia $transcodingMedia) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->privateKey = $privateKey;
    $this->transcodingMedia = $transcodingMedia;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('private_key'),
      $container->get('transcoding.media')
    );  }

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
      '#attributes' => ['placeholder' => 'https://scheduler/api/']
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

  /**
   * @inheritDoc
   */
  public function buildJobForm(array $form, FormStateInterface $form_state) {
    $form['input'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input'),
      '#required' => TRUE,
    ];
    $form['output'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Output file'),
      '#required' => TRUE,
    ];
    $form['preset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preset'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitJobForm(array &$form, FormStateInterface $form_state) {
    return $form_state->getValues();
  }

  /**
   * @inheritDoc
   */
  public function processJob($job) {
    $data = $job->getServiceData();
    $status = $job->status->getString();
    if ($status == TranscodingStatus::PENDING) {
      $token = Crypt::hmacBase64($job->id(), $this->privateKey->get() . Settings::getHashSalt());
      $notify = Url::fromRoute('transcoding_codem.callback_controller_process', ['transcoding_job' => $job->id(), 'token' => $token], ['absolute' => TRUE]);
      try {
        $scheduledJob = (new CodemClient($this->getConfiguration()['scheduler']))
          ->createJob($data['input'], $data['output'], $data['preset'], $notify->toString());
        $job->status = TranscodingStatus::IN_PROGRESS;
        $data['scheduled_id'] = $scheduledJob->id;
      }
      catch (\Exception $e) {
        $job->status = TranscodingStatus::FAILED;
        $data['error'] = $e->getMessage();
      }
      $job->service_data = $data;
      $job->save();
    }
    if ($status == 'processed') {

      $this->transcodingMedia->complete($job, $uri);
    }
  }

}
