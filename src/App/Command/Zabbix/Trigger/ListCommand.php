<?php
namespace App\Command\Zabbix\Trigger;

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
class ListCommand extends AbstractCommand
{
  /**
   * @return void
   */
  protected function configure()
  {
    $this
      ->setName('trigger:list')
      ->addArgument('host', InputArgument::REQUIRED, 'Host to check')
      ->addOption('all', 'a', InputOption::VALUE_NONE, 'Show all triggers, disabled and enabled')
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
        'host' => $input->getArgument('host'),
        'status' => $input->getOption('all') ? null : 0,
      ]
    ]);

    foreach ($hosts as $host) {
      $output->writeln("<red>ID</red>: {$host['triggerid']}; {$host['description']}; Disabled: {$host['status']}");
    }

    return 0;
  }
}
