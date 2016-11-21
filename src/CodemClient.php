<?php

namespace Drupal\transcoding_codem;

use GuzzleHttp\Client;

/**
 * Class CodemClient
 * @package Drupal\transcoding
 */
class CodemClient {

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * @inheritDoc
   */
  public function __construct($schedulerUrl) {
    $this->client = new Client([
      'base_uri' => $schedulerUrl,
    ]);
  }

  public function createJob($input, $output, $preset, $notify) {
    $data = [
      'input' => $input,
      'output' => $output,
      'preset' => $preset,
      'notify' => $notify,
    ];
    return $this->post('jobs', $data)->job;
  }

  protected function post($endpoint, $data) {
    try {
      $response = $this->client->post($endpoint, [
        'form_params' => $data,
        'headers' => [
          'Accept' => '*/*'
        ],
      ]);
      return \GuzzleHttp\json_decode($response->getBody());
    }
    catch (\Exception $e) {
      throw new \Exception('Job creation failed', $e->getCode(), $e);
    }
  }

}
