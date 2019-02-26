#!/usr/bin/env php
<?php

use \Zend\ServiceManager\ServiceManager;
use \Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$application    = new Application('PHP-SMS');
$serviceManager = new ServiceManager(require __DIR__ . '/../src/services.php');

foreach (require __DIR__ . '/../src/commands.php' as $commandName) {
  $application->add($serviceManager->get($commandName));
}

try {
  $application->run();
} catch (\Exception $e) {
  // Handle application's exceptions
}
