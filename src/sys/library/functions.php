<?php

namespace Reporter;

/**
 * External dependencies
 */
use Monolog\Logger;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Internal dependencies
 */
use Writer\Writer;
use Writer\ExcelWriter;

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

/**
 * Data filter function.
 *
 * Collect all data from the given array.
 * Checks against a whitelist of allowed keys.
 * 
 * @param  Array  $data 
 * @return Array
 */
function filter(array $data) {

    $filtered  = [];
    $whitelist = [

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

    // Keep only what's whitelisted above. Also, make sure 
    // to maintain order, and fill in missing values.

    foreach ($whitelist as $index) {

        if (isset($data[$index])) {

            $filtered[$index] = $data[$index];
        }
        else {

            $filtered[$index] = '';
        }
    }

    return $filtered;
}

/**
 * Data filter function.
 *
 * Collect all data from the given array.
 * Checks against a whitelist of allowed keys.
 * 
 * @param  Array  $data 
 * @return Array
 */
function filterSegment(array $data) {

    $filtered  = [];
    $whitelist = [

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

    // Keep only what's whitelisted above. Also, make sure 
    // to maintain order, and fill in missing values.

    foreach ($whitelist as $index) {

        if (isset($data[$index])) {

            $filtered[$index] = $data[$index];
        }
        else {

            $filtered[$index] = '';
        }
    }

    return $filtered;
}

/**
 * Get all defined segments.
 * Queries the Matomo API.
 * 
 * @param  Client $client 
 * @return Array
 */
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
 * Provides a pre-loaded Query object to use in 
 * a Matomo API request.
 *
 * Matomo API docs:
 * https://developer.matomo.org/api-reference/reporting-api#api-request
 * 
 * @param  String $period  - cf. Matomo API docs
 * @param  String $date    - cf. Matomo API docs
 * @param  String $segment - a segment definition (use data of @see get_segments)
 * @return Query
 */
function getBaseQuery($period, $date, $segment = null) {
    
    $query  = new Query(['method' => 'API.get']);

    $query->push('period', $period);
    $query->push('date',   $date);

    if ($segment) {

        $query->push('segment', $segment);
    }

    return $query;
}

/**
 * Delete every file from the storage dir.
 * 
 * @param  String     $path   
 * @param  Filesystem $fs     
 * @param  Finder     $finder 
 * @return Void             
 */
function purgeStorage($path, Filesystem $fs, Finder $finder) {

    $finder->files()->in($path);

    if (!$finder->hasResults()) {

        return;
    }

    foreach ($finder as $file) {

        $fs->remove($file->getRealPath());
    }
}

/**
 * Delete every Excel file from the storage dir.
 * 
 * @param  String     $path   
 * @param  Filesystem $fs     
 * @param  Finder     $finder 
 * @return Void             
 */

function purgeExcel($path, Filesystem $fs, Finder $finder) {

    $finder->files()->name('*.xlsx')->in($path);

    if (!$finder->hasResults()) {

        return;
    }

    foreach ($finder as $file) {

        $fs->remove($file->getRealPath());
    }
}

/**
 * Execute a set of given reports.
 *
 * Every report entry expected to be a function to handle 
 * the nuts and bolds to collect data, and to return an
 * appropriate data set.
 *
 * Cf. Writer\Writer and Writer\ExcelWriter to get an idea of how 
 * the data should be structured.
 *
 * Logs its progress.
 * 
 * @param  Array  $reports 
 * @param  Client $client  
 * @param  Writer $writer  
 * @param  Logger $logger  
 * @return Void          
 */
function executeReports(array $reports, Client $client, Writer $writer, Logger $logger) {

    $num_reports = count($reports);

    foreach ($reports as $name => $func) {

        $data = $func($client);

        $writer->toJson("{$name}.json", $data['data'])
               ->toExcelCsv("{$name}.csv", $data['data']);

        $num_reports--;
        $logger->info("Report {$name} executed and written, {$num_reports} remaining.");
    }
}

/**
 * Write data to Excel.
 *
 * Collects some files into an associative array and groups them
 * by certain criteria based on the file names.
 * 
 * Cf. Writer\ExcelWriter for more info. 
 * 
 * @param  String     $path   
 * @param  Filesystem $fs     
 * @param  Finder     $finder 
 * @return Void             
 */
function createExcel($path, Filesystem $fs, Finder $finder) {

    $reports = [];

    // Collect all files starting with a year

    $finder->files()->name('/^\d{4}.*\.json/')->in($path);

    if (!$finder->hasResults()) {

        return;
    }

    foreach ($finder as $file) {

        // Get file name without file extension
        
        $name = basename($file->getRelativePathname(), '.json');

        // Strip year

        $year = substr($name, 0, 4);
        $name = trim(substr($name, 4), '-');

        // Create reports group

        if (!isset($reports[$name])) {

            $reports[$name] = [];
        }

        // Push file to group
    
        $reports[$name][$year] = $file->getRealPath();
    }

    foreach ($reports as $group => $member) {

        $writer = new ExcelWriter($path);
        $writer->write($group.'.xlsx', $member); 
    }
}