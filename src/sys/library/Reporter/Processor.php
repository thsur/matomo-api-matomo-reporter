<?php

namespace Reporter;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

use \Exception;
use \Throwable;

class Processor {

    /**
     * @var Collector
     */
    protected $collector;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var Array
     */
    protected $aliases;

    /**
     * @var Array
     */
    protected $nodes;

    /**
     * Get aliases and their corresponding node ids.
     * 
     * @return Array - [alias => nid, ...]
     */
    protected function getAliases() {

        if (is_array($this->aliases)) {

            return $this->aliases;
        }

        $result = $this->db->query(

            "select alias, source from url_alias"

        )->fetchAll();

        $this->aliases = [];

        foreach ($result as $row) {

            $this->aliases[$row->alias] = substr($row->source, strrpos($row->source, '/') + 1);
        }

        return $this->aliases;
    }

    protected function getNodes() {

        if (is_array($this->nodes)) {

            return $this->nodes;
        }

        $this->nodes = $this->db->query(

            "select nid, type, title, created, changed from node_field_data where status = :status",
            [':status' => 1]

        )->fetchAllAssoc('nid');

        return $this->nodes;
    }

    /**
     * Get raw Matomo data.
     * 
     * @return Array
     */
    protected function getRaw() {

        $raw = [

            'core_metrics' => $this->collector->fetchCoreMetrics(),
            'pages'        => $this->collector->fetchPages(),
        ];

        foreach ($raw as $key => $items) {

            if ($items['status'] != 200) {

                throw new Exception('HTTP '.$items['status'].' for '.$items['request']);
            }

            $raw[$key]['data'] = json_decode($items['data']);
        }
        
        return $raw;
    }

    protected function process(array $raw) {

        $aliases = $this->getAliases();
        $nodes   = $this->getNodes();


        return $data;
    }

    public function getData() {

        // Cf. https://trowski.com/2015/06/24/throwable-exceptions-and-errors-in-php7/
        
        try {

            return $this->process($this->getRaw());
        }
        catch(Throwable $e) { 

            $this->logger->error($e->getMessage());
        }

    }

    /**
     * @param Client
     */
    public function __construct(ReportCollector $collector, LoggerInterface $logger, Connection $db) {

        $this->collector = $collector;
        $this->logger    = $logger;
        $this->db        = $db;
    }
}