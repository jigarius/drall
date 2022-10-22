<?php

namespace Drall\Commands;

use Drall\Traits\SiteDetectorAwareTrait;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command {

  use LoggerAwareTrait;
  use SiteDetectorAwareTrait;

  protected function configure() {
    $this->addOption(
      'drall-group',
      NULL,
      InputOption::VALUE_OPTIONAL,
      'Site group identifier.'
    );
  }

  /**
   * Gets the active Drall group.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   *
   * @return null|string
   *   Drall group, if any. Otherwise, NULL.
   */
  protected function getDrallGroup(InputInterface $input): ?string {
    if ($group = $input->getOption('drall-group')) {
      return $group;
    }

    return getenv('DRALL_GROUP') ?: NULL;
  }

  protected function preExecute(InputInterface $input, OutputInterface $output) {
    if (!$this->logger) {
      $this->logger = new ConsoleLogger($output);
    }
  }

}
