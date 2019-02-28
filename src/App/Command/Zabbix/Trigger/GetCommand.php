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
  
  protected $severityMap = [
    0 => 'Not classified',
    1 => 'Information',
    2 => 'Warning',
    3 => 'Average',
    4 => 'High',
    5 => 'Disaster',
  ];

  /**
   * @return void
   */
  protected function configure()
  {
    $this
      ->setName('trigger:get')
      ->addArgument('host', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Host to check')
      ->addOption('triggerid', 't', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Specify Trigger id(s)', null)
      ->addOption('warning', 'w', InputOption::VALUE_REQUIRED, 'Set warning threshold', 2)
      ->addOption('critical', 'c', InputOption::VALUE_REQUIRED, 'Set critical threshold', 4)
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
    $exitCode = $rows = $err = $higestPriority = 0;
    $out = '';

    foreach ($results as $result) {
      $rows++;
      if ($result['value'] > 0) {
        $err++;
      }

      if ($input->getOption('triggerid') || $result['value'] > 0 || $input->getOption('verbose')) {
        $status = (int) $result['value'] === 0 ? 'OK' : $this->getSeverity($result['priority']);

        $out .= "{$result['description']} ({$status}); ";

        if ($higestPriority < $result['priority']) {
          $higestPriority = $result['priority'];
        }
      }
    }
    
    if ($rows <= 0) {
        $out = "ZABBIX TRIGGER UNKNOWN - no triggers found or host is wrong";

        $exitCode = 3;
    } elseif ($higestPriority >= 3 || $err >= $input->getOption('critical')) {
      $out = "ZABBIX TRIGGER CRITICAL (limit={$err}/{$rows}) - {$out}";

      $exitCode = 2;
    } elseif ($higestPriority <= 2 || $err >= $input->getOption('warning')) {
      $out = "ZABBIX TRIGGER WARNING (limit={$err}/{$rows}) - {$out}";

      $exitCode = 1;
    } else {
        $out = empty($out) ? "ZABBIX TRIGGER OK" : "ZABBIX TRIGGER OK - (limit={$err}/{$rows}) | {$out}";
    }

    if (!empty($out)) {
      $output->writeln($this->truncate($out));
    }

    return (int) $exitCode;
  }

  protected function getSeverity($id = 0) {
    return array_key_exists($id, $this->severityMap) ? $this->severityMap[$id] : 'Not classified';
  }

  protected function truncate($text, $length = 4000) {
    if (strlen($text) > $length) {
      $text = $text." ";
      $text = substr($text, 0, $length);
      $text = substr($text, 0, strrpos($text,' '));
      $text = $text . "...";
    }

		return $text;
	}
}
