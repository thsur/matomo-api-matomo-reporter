<?php

namespace Reporter;

class Query {

    /**
     * @var Array
     */
    protected $query;

    public function push($k, $v) {

        $this->query[$k] = $v;
        return $this;
    }
    
    public function has($k) {

        return isset($this->query[$k]);
    }

    public function toString() {

        $query = http_build_query($this->query);

        // Replace all occurences of some_param[0] with some_param[].
        // Regex adapted from https://www.php.net/manual/en/function.http-build-query.php#111819
        return preg_replace('/%5B[0-9]+%5D/', '%5B%5D', $query); 
    }

    public function __construct($params) {

        foreach ($params as $k => $v) {

            $this->push($k, $v);
        }
    }
}