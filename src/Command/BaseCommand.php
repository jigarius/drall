<?php

namespace Drall\Command;

use Drall\Service\SiteDetector;
use Drall\Trait\SiteDetectorAwareTrait;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
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

    $this->addOption(
      'drall-filter',
      NULL,
      InputOption::VALUE_OPTIONAL,
      'Filter sites based on provided expression.'
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

  /**
   * Get the --filter parameter (if any).
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   *
   * @return null|string
   *   A filter expression.
   *
   * @see https://packagist.org/packages/consolidation/filter-via-dot-access-data
   */
  protected function getDrallFilter(InputInterface $input): ?string {
    return $input->getOption('drall-filter') ?: NULL;
  }

  protected function preExecute(InputInterface $input, OutputInterface $output) {
    if (!$this->logger) {
      $this->logger = new ConsoleLogger($output);
    }

    if (!$this->hasSiteDetector()) {
      $root = $input->getParameterOption('--root') ?: getcwd();
      $siteDetector = SiteDetector::create($root);
      $this->setSiteDetector($siteDetector);
    }

    if ($group = $this->getDrallGroup($input)) {
      $this->logger->debug('Detected group: {group}', ['group' => $group]);
    }

    if ($filter = $this->getDrallFilter($input)) {
      $this->logger->debug('Detected filter: {filter}', ['filter' => $filter]);
    }
  }

}
