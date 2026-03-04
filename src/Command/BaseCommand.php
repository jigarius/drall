<?php

namespace Drall\Command;

use Drall\Model\SiteDetectorOptions;
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
      'group',
      'g',
      InputOption::VALUE_OPTIONAL,
      'Site group identifier.'
    );

    $this->addOption(
      'filter',
      'f',
      InputOption::VALUE_OPTIONAL,
      'Filter sites based on provided expression.'
    );

    $this->addOption(
      'offset',
      'o',
      InputOption::VALUE_OPTIONAL,
      'Number of items to skip.',
      0,
    );

    $this->addOption(
      'limit',
      'l',
      InputOption::VALUE_OPTIONAL,
      'Number of items to process.',
    );
  }

  protected function initialize(InputInterface $input, OutputInterface $output): void {
    if (!$this->logger) {
      $this->logger = new ConsoleLogger($output);
    }

    parent::initialize($input, $output);
  }

  /**
   * Formats a DateTime object for display in logs and messages.
   *
   * @param \DateTime $dateTime
   *   The DateTime object to format.
   *
   * @return string
   *   A human-readable date string, e.g. "4 Mar, 2026 @ 14:30:00".
   */
  public static function formatDateTime(\DateTime $dateTime): string {
    return $dateTime->format('j M, Y @ H:i:s');
  }

  protected function preExecute(InputInterface $input, OutputInterface $output) {
    if (!$this->hasSiteDetector()) {
      $this->setSiteDetector(new SiteDetector());
    }

    $options = SiteDetectorOptions::fromInput($input);

    if ($group = $options->getGroup()) {
      $this->logger->info('Using group: {group}', ['group' => $group]);
    }

    if ($filter = $options->getFilter()) {
      $this->logger->info('Using filter: {filter}', ['filter' => $filter]);
    }

    if ($offset = $options->getOffset()) {
      $this->logger->info('Using offset: {offset}', ['offset' => $offset]);
    }

    if ($limit = $options->getLimit()) {
      $this->logger->info('Using limit: {limit}', ['limit' => $limit]);
    }
  }

}
