<?php
namespace App\Command\Zabbix;

use App\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use kamermans\ZabbixClient\ZabbixClient;

/**
 *  * Class MyCommand
 *
 * @package App\Command
 */
class TriggerCommand extends AbstractCommand
{
  /**
   * @return void
   */
  protected function configure()
  {
    $this
      ->setName('zabbix:trigger')
      ->addArgument('host', InputArgument::REQUIRED, "Host to check")
      ->addOption('triggerid', 't', InputOption::VALUE_REQUIRED, "Specify Trigger id", null)
      ->addOption('all', 'a', InputOption::VALUE_NONE, "Show all triggers for host")
    ;
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return void
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $client = new ZabbixClient(
      $this->getConfig('zabbix.url'),
      $this->getConfig('zabbix.username'),
      $this->getConfig('zabbix.password')
    );

    $hosts = $client->request('trigger.get', [
      'output' => 'extend',
      'expandDescription' => true,
      'expandComment' => true,
      'filter' => [
        'triggerid' => $input->getOption('triggerid'),
        'host' => $input->getArgument('host'),
        'status' => $input->getOption('all') ? null : 0,
      ]
    ]);

    $errors = 0;
    $exitCode = 0;

    foreach ($hosts as $host) {
      $output->writeln("ID: {$host['triggerid']}; Description: {$host['description']}; Status: {$host['status']}; Value: {$host['value']}");

      if ($host['value'] > 0) {
        $errors++;
        $exitCode = 1;
      }
    }

    if ($errors > 1) {
      $exitCode = 2;
    }

    return (int) $exitCode;
  }
}
