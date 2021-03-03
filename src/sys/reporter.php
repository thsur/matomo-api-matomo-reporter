#! /usr/bin/env php
<?php 

namespace Reporter;

/**
 * Constants
 */
define('ROOT_DIR',    dirname(__DIR__));
define('SYS_DIR',     ROOT_DIR.'/sys');
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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Internal dependencies
 */
use Writer\Writer;

/**
 * Helper
 */
require_once SYS_DIR.'/library/functions.php';

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
 * Finder
 */
$services->set('finder', new Finder());

/**
 * Filesystem
 */
$services->set('fs', new Filesystem());

/**
 * File system storage writer
 */
$services->set('writer', new Writer(STORAGE_DIR));

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

foreach ([2016, 2017, 2018, 2019, 2020] as $year) {

    // By week    

    $period = 'week';
    $date   = "{$year}-01-01,{$year}-12-31";

    // By week only

    $name           = "{$year}-by-week";
    $queries[$name] = getBaseQuery($period, $date);

    // By week & segment    

    foreach ($segments as $segment) {

        $created = date('Y', strtotime($segment['ts_created']));

        if ($created > $year) {

            continue;
        }

        $slug   = preg_replace(

            '/[^\w0-9]+/u', '-', $name.'-by-segment-'.strtolower($segment['name'])
        );

        $queries[$slug] = getBaseQuery($period, $date, $segment['definition']);
    }

    // By month

    $period = 'month';
    
    // By month    

    $name           = "{$year}-by-month";
    $queries[$name] = getBaseQuery($period, $date);

    // By month & segment    

    foreach ($segments as $segment) {

        $created = date('Y', strtotime($segment['ts_created']));

        if ($created > $year) {

            continue;
        }

        $slug  = preg_replace(

            '/[^\w0-9]+/u', '-', $name.'-by-segment-'.strtolower($segment['name'])
        );

        $queries[$slug] = getBaseQuery($period, $date, $segment['definition']);
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

                $data['data'][$period] = filter($set);
            } 
            else {

                $data['data'][$period] = filterSegment($set);
            }
        }
            
        // Inject range into sets

        foreach ($data['data'] as $period => &$set) {

            if (strpos($period, ',') !== false) {

                $range = explode(',', $period);
                $set   = array_merge(['from' => $range[0], 'to' => $range[1]], $set);
            }
            else {
                
                $set   = array_merge(['from' => $period], $set);
            }
        }

        return $data;
    };
}

// Execute reports & write results

purgeStorage(

    STORAGE_DIR,
    $services->get('fs'), 
    $services->get('finder')
);

executeReports(

    $reports, 
    $services->get('client'), 
    $services->get('writer'), 
    $services->get('logger')
);

purgeExcel(

    STORAGE_DIR,
    $services->get('fs'), 
    $services->get('finder')
);

createExcel(

    STORAGE_DIR,
    $services->get('fs'), 
    $services->get('finder')
);