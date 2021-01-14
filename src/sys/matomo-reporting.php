<?php

# Usage:
# $ drush php:script script-name.php --uri=...

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpClient\HttpClient;

use Drupal\matomo_reporter\Matomo\Query;
use Drupal\matomo_reporter\Matomo\BulkQuery;
use Drupal\matomo_reporter\Matomo\Intervals;

/**
 * Methods
 */

// CLI helpers

// Declaring globals
// 
// Cf. https://drupal.stackexchange.com/questions/136717/global-keyword-does-not-work-from-within-drush-script

global $writer;

$writer = $this->output();

function out($v) {

    global $writer;

    if (!is_string($v)) {

        $v = print_r($v, true);
    }

    $writer->writeln($v); 
}

// Filesystem

/**
* Builds a file path with the appropriate directory separator.
* 
* @param  string $segments,... unlimited number of path segments
* @return string Path
*/
function file_build_path(...$segments) {

    return join(DIRECTORY_SEPARATOR, $segments);
}

/**
 * Matomo
 */

$reporter  = null;
$processor = Drupal::service('matomo_reporter.processor');

// Get raw data

$raw = $processor->getData();

dump($raw);
// dump(json_encode(json_decode($collector->fetchPages()['data']), JSON_PRETTY_PRINT));
exit;
