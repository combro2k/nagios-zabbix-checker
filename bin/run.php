#!/usr/bin/env php
<?php

use \Zend\ServiceManager\ServiceManager;
use \Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$application    = new Application('Nagios Zabbix Checker', '@git_commit_short@');
$serviceManager = new ServiceManager(require __DIR__ . '/../src/services.php');

foreach (require __DIR__ . '/../src/commands.php' as $commandName) {
  $application->add($serviceManager->get($commandName));
}

try {
  $application->run();
} catch (\Exception $e) {
  // Handle application's exceptions
}
