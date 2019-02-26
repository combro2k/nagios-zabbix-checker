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
class GetCommand extends AbstractCommand
{
  /**
   * @return void
   */
  protected function configure()
  {
    $this
      ->setName('zabbix:trigger:get')
      ->addArgument('host', InputArgument::REQUIRED, 'Host to check')
      ->addOption('triggerid', 't', InputOption::VALUE_REQUIRED, 'Specify Trigger id', null)
      ->addOption('warning', 'w', InputOption::VALUE_REQUIRED, "", 1)
      ->addOption('critical', 'c', InputOption::VALUE_REQUIRED, "", 2)
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
        'status' => 0,
        'value' => null,
      ]
    ]);

    $errors = 0;
    $exitCode = 0;

    foreach ($hosts as $host) {
      if ($host['value'] > 0) {
        $output->writeln("{$host['description']}; Value: {$host['value']}");

        $errors++;
      }
    }

    if ($errors >= $input->getOption('critical')) {
      $exitCode = 2;
    } elseif ($errors >= $input->getOption('warning')) {
      $exitCode = 1;
    }

    return (int) $exitCode;
  }
}
