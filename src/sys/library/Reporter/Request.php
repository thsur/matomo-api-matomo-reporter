<?php

namespace Reporter;

class Request {

    /**
     * @var Config
     */
    protected $options;

    /**
     * @var Array
     */
    protected $query;

    /**
     * @var Array
     */
    protected $default_params;

    protected function setDefault() {

        $this->default_params = [

            'module'     => 'API',
            'format'     => $this->options->get('format'),
            'idSite'     => $this->options->get('site_id'),
            'token_auth' => $this->options->get('token_auth')
        ];
    }

    public function setDefaultModule($m) {

        $this->default_params['module'] = $m;
        return $this;
    }

    public function push(Query $q) {
        
        $this->query[] = $q;
        return $this;
    }

    public function toString($confidential = false) {

        // Assemble query string data

        $default = $this->default_params;

        // Loop query objects
        
        $qs      = [];
        $is_bulk = !empty(array_filter($this->query, function ($v) {

            return $v instanceof BulkQuery;
        }));

        foreach ($this->query as $q) {

            if ($is_bulk) {

                if (!($q instanceof BulkQuery)) {

                    throw new \InvalidArgumentException('A bulk query can not be mixed with a base query object.');
                }

                $q->push('idSite', $default['idSite']);
            }

            $qs[] = $q->toString();
        }

        // Replace default values

        if ($is_bulk) {

            $default['module'] = 'API';
            $default['method'] = 'API.getBulkRequest';

            unset($default['idSite']);
        }

        if ($confidential) {

            unset($default['token_auth']);
        }

        // Build url

        $request = '';
        $default = http_build_query($default);

        if ($default) {

            $request .= $default;
        }

        if (!empty($qs)) {

            $request .= '&'.implode('&', $qs);
        }

        return $request;
    }

    public function getPayload() {

        list($url, $payload) = explode('?', $this->toString());
        return $payload;
    }

    /**
     * @param RequestStack
     */
    public function __construct(Config $config) {

        $this->options = $config;
        $this->query   = [];

        $this->setDefault();
    }
}