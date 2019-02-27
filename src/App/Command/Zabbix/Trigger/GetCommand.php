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
      ->setName('trigger:get')
      ->addArgument('host', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Host to check')
      ->addOption('triggerid', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Specify Trigger id(s)', null)
      ->addOption('warning', 'w', InputOption::VALUE_REQUIRED, 'Set warning threshold', 1)
      ->addOption('critical', 'c', InputOption::VALUE_REQUIRED, 'Set critical threshold', 2)
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
        'triggerid' => $input->getOption('triggerid'),
        'host' => $input->getArgument('host'),
        'status' => $input->getOption('all') ? null : 0,
        'value' => null,
      ]
    ]);

    $errors = [];
    $exitCode = 0;

    $out = '';
    foreach ($hosts as $host) {
      if ($host['value'] > 0) {
        $errors[$host['triggerid']][] = $host;
      }

      if ($input->getOption('verbose') || $host['value'] > 0) {
        $out .= "{$host['description']} - STATUS: {$host['value']}; ";
      }
    }

    if (count($errors) >= $input->getOption('critical')) {
      $out = "CRITICAL: {$out}";

      $exitCode = 2;
    } elseif (count($errors) >= $input->getOption('warning')) {
      $out = "WARNING: {$out}";

      $exitCode = 1;
    } else {
        if ($input->getOption('triggerid')) {
          $out = "";
        }

        $out = "OK: {$out}";
    }

    if (!empty($out)) {
      $output->writeln($out);
    }

    return (int) $exitCode;
  }
}
