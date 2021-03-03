<?php

namespace Reporter;

class ReportCollector {

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var String
     */
    protected $period;

    /**
     * @var Integer
     */
    protected $limit;

    /**
     * @var String
     */
    protected $date;

    protected function fetch(Query $query) {

        return $this->client->send(

            $this->client->getRequest()->push($query)
        );
    }

    protected function amendQuery(Query $q): Query {

        if (!$q->has('period') && !$q->has('date')) {

            $q->push('period', $this->period)
              ->push('date', $this->date)
              ->push('filter_limit', $this->limit);
        }

        return $q;
    }

    public function setPeriod(string $period) {
        
        $this->period = $period;
    }

    public function setDate(string $date) {

        $this->date = $date;
    }

    public function setLimit(int $limit) {

        $this->limit = $limit;
    }

    public function fetchVersion() {
        
        return $this->fetch(new Query(['method' => 'API.getMatomoVersion']));
    }

    public function fetchApiDocs() {
        
        return $this->fetch(new Query(['method' => 'API.getReportMetadata']));
    }
    
    public function fetchCoreMetrics() {
        
        return $this->fetch($this->amendQuery(new Query([

            'method' => 'API.get'
        ])));
    }
    
    public function fetchUserFlow() {
        
        return $this->fetch($this->amendQuery(new Query([

            'method' => 'UsersFlow.getUsersFlowPretty', 
            'flat'   => 1
        ])));
    }

    public function fetchPages() {
        
        return $this->fetch($this->amendQuery(new Query([

            'method' => 'Actions.getPageUrls', 
            'flat'   => 1
        ])));
    }

    public function fetchEntryPages() {
        
        return $this->fetch($this->amendQuery(new Query([

            'method' => 'Actions.getEntryPageUrls', 
            'flat'   => 1
        ])));
    }

    public function fetchExitPages() {
        
        return $this->fetch($this->amendQuery(new Query([

            'method' => 'Actions.getExitPageUrls', 
            'flat'   => 1
        ])));
    }
    
    /**
     * @param Client
     */
    public function __construct(Client $client, string $period = '', string $week = '') {

        $this->client = $client;

        $this->setPeriod('week');
        $this->setDate('previous1');
        $this->setLimit(1);
    }
}