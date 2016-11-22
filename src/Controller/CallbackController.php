<?php

namespace Drupal\transcoding_codem\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\transcoding\Entity\TranscodingJob;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\transcoding\TranscodingMedia;
use Drupal\Core\PrivateKey;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Translation\Exception\InvalidResourceException;

/**
 * Class CallbackController.
 *
 * @package Drupal\transcoding_codem\Controller
 */
class CallbackController extends ControllerBase {

  /**
   * Drupal\Core\PrivateKey definition.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $private_key;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * {@inheritdoc}
   */
  public function __construct(PrivateKey $private_key, RequestStack $requestStack) {
    $this->private_key = $private_key;
    $this->currentRequest = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('private_key'),
      $container->get('request_stack')
    );
  }

  /**
   * Process the incoming report.
   */
  public function process($transcoding_job, $token) {
    // Check the token.
    $our_token = Crypt::hmacBase64($transcoding_job, $this->private_key->get() . Settings::getHashSalt());
    if ($our_token != $token) {
      throw new AccessDeniedHttpException('Invalid token.');
    }
    if (!$job = TranscodingJob::load($transcoding_job)) {
      throw new InvalidResourceException('Invalid job.');
    }
    if ($this->currentRequest->getMethod() != 'POST') {
      throw new MethodNotAllowedHttpException(['POST']);
    }
    $response = new Response();
    $report = $this->currentRequest->request->all();
    if (!in_array($report['state'], ['success', 'failed', 'processing'])) {
      return $response;
    }
    // Mark success as processed, since we'll move it on the next cron.
    $status = $report['state'] == 'success' ? 'processed' : $report['state'];
    $data = $job->getServiceData();
    $data['result'] = $report;
    $job->setServiceData($data)->set('status', $status)->save();
    return $response;
  }

}
