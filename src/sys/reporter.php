#! /usr/bin/env php

<?php 

/**
 * Bind autoloader
 */
require dirname(__DIR__).'/vendor/autoload.php';

/**
 * External dependencies
 */
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Internal dependencies
 */
use Reporter\Intervals;

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

// Store as service

$services->set('logger', $logger);

/**
 * Main
 */

// Log result

$services->get('logger')->info(Intervals::now()->format('Y-m-d H:i:s'));
$services->get('logger')->warning('Foozie');
$services->get('logger')->error('Burz');
