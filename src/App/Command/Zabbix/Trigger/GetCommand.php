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

    $request = [
      'output' => 'extend',
      'expandDescription' => true,
      'expandComment' => true,
      'filter' => [
        'host' => $input->getArgument('host'),
        'status' => [0],
      ]
    ];

    if ($input->getOption('all')) {
      $request['filter']['status'] = [0, 1];
    }

    if ($input->getOption('triggerid')) {
      $request['filter']['triggerid'] = $input->getOption('triggerid');
    }

    $results = $client->request('trigger.get', $request);

    $exitCode = $rows = $err = 0;
    $out = '';

    foreach ($results as $result) {
      $rows++;
      if ($result['value'] > 0) {
        $err++;
      }

      if ($input->getOption('triggerid') || $result['value'] > 0 || $input->getOption('verbose')) {
        $out .= "{$result['description']} - STATUS: {$result['value']} - Disabled: {$result['status']}; ";
      }
    }

    if ($err >= $input->getOption('critical')) {
      $out = "CRITICAL: ({$err}/{$rows}) | {$out}";

      $exitCode = 2;
    } elseif ($err >= $input->getOption('warning')) {
      $out = "WARNING: ({$err}/{$rows}) | {$out}";

      $exitCode = 1;
    } elseif ($rows <= 0) {
        $out = "UNKNOWN: no triggers found or host is wrong";

        $exitCode = 3;
    } else {
        $out = empty($out) ? "OK" : "OK: ({$err}/{$rows}) | {$out}";
    }

    if (!empty($out)) {
      $output->writeln($out);
    }

    return (int) $exitCode;
  }
}
