<?php

namespace Drupal\transcoding_codem\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\transcoding\TranscodingMedia;
use Drupal\Core\PrivateKey;

/**
 * Class CallbackController.
 *
 * @package Drupal\transcoding_codem\Controller
 */
class CallbackController extends ControllerBase {

  /**
   * Drupal\transcoding\TranscodingMedia definition.
   *
   * @var Drupal\transcoding\TranscodingMedia
   */
  protected $transcoding_media;

  /**
   * Drupal\Core\PrivateKey definition.
   *
   * @var Drupal\Core\PrivateKey
   */
  protected $private_key;
  /**
   * {@inheritdoc}
   */
  public function __construct(TranscodingMedia $transcoding_media, PrivateKey $private_key) {
    $this->transcoding_media = $transcoding_media;
    $this->private_key = $private_key;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('transcoding.media'),
      $container->get('private_key')
    );
  }

  /**
   * Process.
   *
   * @return string
   *   Return Hello string.
   */
  public function process($transcoding_job, $key) {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: process with parameter(s): $transcoding_job, $key'),
    ];
  }

}
