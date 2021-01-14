<?php

namespace Reporter;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;

use Symfony\Component\HttpClient\HttpClient;

/**
 * Send an API request to Matomo.
 *
 * Usage:
 *
 * $client   = Drupal::service('matomo_reporter.client');
 * $request  = $client->getRequest();
 *
 * // Base query (do not mix with bulk queries)
 *
 * $query = new Query(['method' => 'API.get', 'period' => 'day', 'date' => 'today']);
 * $request->push($query);
 *
 * // Bulk query (do not mix with base queries)
 *
 * $query = new BulkQuery(['method' => 'API.get', 'period' => 'day', 'date' => 'today']);
 * $request->push($query);
 *
 * // Get request URL
 *
 * $request->toString();
 *
 * // Or send it
 *
 * $data = $client->send($request);
 */
class Client {

    /**
     * @var Config
     */
    protected $options;

    /**
     * @var HttpClient
     */
    protected $client;

    protected function setClient() {

        $this->client = HttpClient::create();
    }

    public function getRequest(): Request {

        $options = $this->options;
        return new Request($options);
    }

    public function send(Request $request) {
        
        $url       = $this->options->get('endpoint');
        $payload   = $request->toString();

        $response  = $this->client->request('POST', $url, ['body' => $payload]);

        return [

            'url'     => $url, 
            'request' => urldecode($request->toString(true)), 
            'status'  => $response->getStatusCode(), 
            'data'    => $response->getContent()
        ];
    }
    
    /**
     * @param RequestStack
     */
    public function __construct(ConfigFactory $config) {

        $this->options = $config->get('matomo_reporter.settings');
        $this->setClient();
    }
}