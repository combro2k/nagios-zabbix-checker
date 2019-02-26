<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Zend\ServiceManager\ServiceManager;

abstract class AbstractCommand extends Command {
  private $serviceManager;

  private $config;

  protected $code;

  function __construct(ServiceManager $serviceManager) {
    $this->serviceManager = $serviceManager;
    $this->config = $this->serviceManager->get(\Symfony\Component\Yaml\Yaml::class);
    if (!is_array($this->config)) {
      $this->config = [];
    }
    $this->flatten($this->config);

    // you *must* call the parent constructor
    parent::__construct();
  }

  protected function getConfig($name = null, $default = null)
  {
    if (empty($name)) {
      return $this->config;
    }

    if (array_key_exists($name, $this->config)) {
      return $this->config[$name];
    }

    return $default;
  }

  private function flatten(array &$array, array $subnode = null, $path = null)
  {
    if (null === $subnode) {
      $subnode = &$array;
    }
    foreach ($subnode as $key => $value) {
      if (\is_array($value)) {
        $nodePath = $path ? $path.'.'.$key : $key;
        $this->flatten($array, $value, $nodePath);
        if (null === $path) {
          unset($array[$key]);
        }
      } elseif (null !== $path) {
        $array[$path.'.'.$key] = $value;
      }
    }
  }
}
