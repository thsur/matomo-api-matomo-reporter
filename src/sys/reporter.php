#! /usr/bin/env php
<?php 

namespace Reporter;

/**
 * Constants
 */
define('ROOT_DIR',    dirname(__DIR__));
define('CONFIG_DIR',  ROOT_DIR.'/config');
define('STORAGE_DIR', ROOT_DIR.'/storage');

/**
 * Bind autoloader
 */
require_once ROOT_DIR.'/vendor/autoload.php';

/**
 * External dependencies
 */
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * Internal dependencies
 */
use Writer\Writer;

/**
 * Error & exception handling
 */

/* 
 * Turn errors into exceptions.
 * 
 * To temporarily turn errors into exceptions:
 * @see https://stackoverflow.com/a/1241751
 *
 * To permanently turn errors to exceptions:
 * @see https://www.php.net/manual/en/language.exceptions.php
 */
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    
    if (0 === error_reporting()) { // error was suppressed with the @-operator
    
        return false;
    }
    
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

/**
 * Catch-all exception handling.
 */
set_exception_handler(function(\Throwable $exception) {

    if ($exception instanceof Exception\Base) {

        printf($exception->getMessage());
        exit();
    }

    throw $exception;
});

/**
 * Container
 *
 * To store application-wide objects a.k.a. services.
 */
$services = new ContainerBuilder();

/**
 * Logging 
 *
 * Just to give some feedback, not to do anything real (like storing logs). 
 * 
 * @see https://github.com/Seldaek/monolog/blob/main/doc/01-usage.md 
 */

// Create formatter

$formatter = new LineFormatter(

    "%datetime% - %level_name% - %message%\n",
    'Y-m-d H:i:s'
);

// Create a handler

$stream = new StreamHandler('php://stdout');
$stream->setFormatter($formatter);

// Create a log channel

$logger = new Logger('Reporter');
$logger->pushHandler($stream);

// Store as a service
// 
// Example usage:
// 
// $services->get('logger')->info('...');

$services->set('logger', $logger);

/**
 * Config
 */
 try {
            
    $services->set(

        'config', 
        new Config(Yaml::parseFile(CONFIG_DIR.'/settings.yml'))
    );

} catch (\Throwable $exception) {
    
    throw new Exception\Base('Unable to process config.');
}

/**
 * HTTP client
 */
$services->set('client', new Client($services->get('config')->get('client.settings')));

/**
 * File system storage writer
 */
$services->set('writer', new Writer(STORAGE_DIR));

/**
 * Helper function to send a request & pre-process its data.
 */
function retrieve(Client $client, Request $request) {

    // Return early on failed requests

    $data = $client->send($request);

    if ($data['status'] != 200) {

       throw new Exception\Base("Request failed with status code {$data['status']}."); 
    }

    // Turn JSON to array

    $data['data'] = json_decode($data['data'], true);

    return $data;
}

function filter(array $data) {

    $filtered = [];
    $keep     = [

        'nb_visits',
        'nb_actions',
        'max_actions',
        'bounce_count',
        'sum_visit_length',
        'Referrers_visitorsFromSearchEngines',
        'Referrers_visitorsFromSocialNetworks',
        'Referrers_visitorsFromDirectEntry',
        'Referrers_visitorsFromWebsites',
        'Referrers_visitorsFromCampaigns',
        'Referrers_distinctSearchEngines',
        'Referrers_distinctSocialNetworks',
        'Referrers_distinctWebsites',
        'Referrers_distinctWebsitesUrls',
        'Referrers_distinctCampaigns',
        'nb_conversions',
        'nb_visits_converted',
        'conversion_rate',
        'nb_pageviews',
        'nb_downloads',
        'nb_uniq_outlinks',
        'avg_time_generation',
        'bounce_rate',
        'nb_actions_per_visit',
        'avg_time_on_site',
    ];

    foreach ($data as $k => $v) {

        if (in_array($k, $keep)) {

            $filtered[$k] = $v;
        }
    }

    return $filtered;
}
function filterSegment(array $data) {

    $filtered = [];
    $keep     = [

        'nb_uniq_visitors',
        'nb_visits',
        'nb_users',
        'nb_actions',
        'max_actions',
        'bounce_count',
        'sum_visit_length',
        'nb_visits_new',
        'nb_actions_new',
        'nb_uniq_visitors_new',
        'nb_users_new',
        'max_actions_new',
        'bounce_rate_new',
        'nb_actions_per_visit_new',
        'avg_time_on_site_new',
        'nb_visits_returning',
        'nb_actions_returning',
        'nb_uniq_visitors_returning',
        'nb_users_returning',
        'max_actions_returning',
        'bounce_rate_returning',
        'nb_actions_per_visit_returning',
        'avg_time_on_site_returning',
        'Referrers_visitorsFromSearchEngines',
        'Referrers_visitorsFromSocialNetworks',
        'Referrers_visitorsFromDirectEntry',
        'Referrers_visitorsFromWebsites',
        'Referrers_visitorsFromCampaigns',
        'Referrers_distinctSearchEngines',
        'Referrers_distinctSocialNetworks',
        'Referrers_distinctKeywords',
        'Referrers_distinctWebsites',
        'Referrers_distinctWebsitesUrls',
        'Referrers_distinctCampaigns',
        'Referrers_visitorsFromDirectEntry_percent',
        'Referrers_visitorsFromSearchEngines_percent',
        'Referrers_visitorsFromCampaigns_percent',
        'Referrers_visitorsFromSocialNetworks_percent',
        'Referrers_visitorsFromWebsites_percent',
        'nb_conversions',
        'nb_visits_converted',
        'conversion_rate',
        'nb_conversions_new_visit',
        'nb_visits_converted_new_visit',
        'conversion_rate_new_visit',
        'nb_conversions_returning_visit',
        'nb_visits_converted_returning_visit',
        'revenue_returning_visit',
        'conversion_rate_returning_visit',
        'nb_pageviews',
        'nb_uniq_pageviews',
        'nb_downloads',
        'nb_uniq_downloads',
        'nb_outlinks',
        'nb_uniq_outlinks',
        'nb_searches',
        'nb_keywords',
        'bounce_rate',
        'nb_actions_per_visit',
        'avg_time_on_site',

    ];

    foreach ($data as $k => $v) {

        if (in_array($k, $keep)) {

            $filtered[$k] = $v;
        }
    }

    return $filtered;
}

function fill(array $data) {

    $largest_set = [];

    foreach ($data as $index => $set) {

        if (!is_array($set)) {

            dump($set);
            dump($data);
            exit;
        }

        if (count($set) > count($largest_set)) {

            $largest_set = $set;
        }
    }

    foreach ($data as $index => $set) {

        if (count($set) < count($largest_set)) {

            $diff = array_diff(array_keys($largest_set), array_keys($set));

            foreach ($diff as $missing) {

                $data[$index][$missing] = '-1';
            }
        }
    }

    return $data;    
}

function get_segments(Client $client){

    $request  = $client->getRequest()->push(

        new Query(['method' => 'SegmentEditor.getAll'])
    );

    $data     = retrieve($client, $request);
    $segments = [];

    foreach ($data['data'] as $segment) {

        $segments[$segment['idsegment']] = $segment;
    }

    return $data;
}

/**
 * Reports
 */
$reports = [];

// Basic reports

$reports['annotations'] = function(Client $client) {

    $request = $client->getRequest()->push(

        new Query(['method' => 'Annotations.getAll'])
    );

    $data         = retrieve($client, $request);
    $data['data'] = array_pop($data['data']); 

    return $data;
};

$reports['segments'] = function(Client $client) {

    return get_segments($client);
};

// Advanced reports

$segments = get_segments($services->get('client'))['data'];
$queries  = [];

function getQuery($period, $date, $segment = null) {
    
    $query  = new Query(['method' => 'API.get']);

    $query->push('period', $period);
    $query->push('date',   $date);

    if ($segment) {

        $query->push('segment', $segment);
    }

    return $query;
}

foreach ([2016, 2017, 2018, 2019, 2020] as $year) {

    $query  = new Query(['method' => 'API.get']);
    
    // By week    

    $query->push('period', 'week');
    $query->push('date',   "{$year}-01-01,{$year}-12-31");

    $name           = "{$year}-by-week";
    $queries[$name] = getQuery('week', "{$year}-01-01,{$year}-12-31");

    // By week & segment    

    foreach ($segments as $segment) {

        $created = date('Y', strtotime($segment['ts_created']));

        if ($created > $year) {

            continue;
        }

        $query  = new Query(['method' => 'API.get']);
        $slug   = preg_replace(

            '/[^\w0-9]+/u', '-', $name.'-by-segment-'.strtolower($segment['name'])
        );

        $queries[$slug] = $query->push('segment', $segment['definition']);
    }

    $query  = new Query(['method' => 'API.get']);

    // By month    

    $query->push('period', 'month');
    $query->push('date',   "{$year}-01-01,{$year}-12-31");

    $name           = "{$year}-by-month";
    $queries[$name] = $query;

    // By month & segment    

    foreach ($segments as $segment) {

        $created = date('Y', strtotime($segment['ts_created']));

        if ($created > $year) {

            continue;
        }

        $query = new Query(['method' => 'API.get']);
        $slug  = preg_replace(

            '/[^\w0-9]+/u', '-', $name.'-by-segment-'.strtolower($segment['name'])
        );

        $queries[$slug] = $query->push('segment', $segment['definition']);
    }
}

foreach ($queries as $name => $query) {

    $reports[$name] = function(Client $client) use ($query, $name) {

        // Prepare request
        
        $request = $client->getRequest()->push($query);

        // Get data

        $data = retrieve($client, $request);

        // Keep only a subset of data per entry

        foreach ($data['data'] as $period => $set) {

            if (strpos($name, 'by-segment') == false) {

                // $data['data'][$period] = filterSegment($set);
            } 
            else {

                // $data['data'][$period] = filterSegment($set);
            }
        }
            

        // Make sure all data sets are equally sized
        dump($name);
        dump($data['request']);
        $data['data'] = fill($data['data']);

        // Inject range into sets

        foreach ($data['data'] as $period => &$set) {

            if (strpos($period, ',') !== false) {

                $range = explode(',', $period);
                $set   = array_merge(['from' => $range[0], 'to' => $range[1]], $set);
            }
        }

        return $data;
    };
}

// Execute reports & write results

$writer      = $services->get('writer');
$logger      = $services->get('logger');

$num_reports = count($reports);

foreach ($reports as $name => $func) {

    $data = $func($services->get('client'));

    $writer->toJson("{$name}.json", $data['data'])
           ->toExcelCsv("{$name}.csv", $data['data']);

    $num_reports--;
    $logger->info("Report {$name} executed and written, {$num_reports} remaining.");
}
