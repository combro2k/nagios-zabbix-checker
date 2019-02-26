<?php
use Zend\ServiceManager\ServiceManager;
use App\Command\Zabbix\TriggerCommand;
use Symfony\Component\Yaml\Yaml;

return [
  'factories' => [
    TriggerCommand::class => function (ServiceManager $serviceManager) {
      return new TriggerCommand($serviceManager);
    },
    Yaml::class => function (ServiceManager $serviceManager) {
      $configFile = '/etc/zabbix-triggers.yaml';
      $altConfigFile = __DIR__ . '/../config/parameters.yaml';

      if (file_exists($configFile)) {
        return Yaml::parseFile($configFile);
      } 
      if (file_exists($altConfigFile)) {
        return Yaml::parseFile($altConfigFile);
      }

      printf('No configuration found using defaults (parameters.yaml.dist)' . PHP_EOL . PHP_EOL, $altConfigFile);

      return Yaml::parseFile(sprintf('%s.dist', $altConfigFile));
    }
  ],
];
