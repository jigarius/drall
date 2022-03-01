<?php

namespace Drall;

use Consolidation\SiteAlias\SiteAliasManagerInterface;
use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Commands\ExecCommand;
use Drall\Services\SiteDetector;
use DrupalCodeGenerator\Logger\ConsoleLogger;
use Drush\SiteAlias\SiteAliasFileLoader;
use DrupalFinder\DrupalFinder;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Drall\Commands\SiteDirectoriesCommand;
use Drall\Commands\SiteAliasesCommand;
use Symfony\Component\Console\Output\OutputInterface;

final class Drall extends Application {

  const NAME = 'Drall';

  use LoggerAwareTrait;

  /**
   * Creates a Phpake Application instance.
   */
  public function __construct() {
    parent::__construct();

    $this->setName(self::NAME);
    $this->setVersion('0.0.0');

    $this->input = new ArgvInput();
    $this->output = new ConsoleOutput();
    $this->configureIO($this->input, $this->output);

    // @todo Use dependency injection.
    $siteDetector = new SiteDetector(
      $this->getDrupalFinder(),
      $this->getSiteAliasManager()
    );

    $this->setLogger(new ConsoleLogger($this->output));

    $cmd = new SiteDirectoriesCommand();
    $cmd->setSiteDetector($siteDetector);
    $cmd->setLogger($this->logger);
    $this->add($cmd);

    $cmd = new SiteAliasesCommand();
    $cmd->setSiteDetector($siteDetector);
    $cmd->setLogger($this->logger);
    $this->add($cmd);

    $cmd = new ExecCommand();
    $cmd->setSiteDetector($siteDetector);
    $cmd->setLogger($this->logger);
    $this->add($cmd);
  }

  /**
   * {@inheritdoc}
   *
   * Based on Drush/Application::configureIO().
   */
  protected function configureIO(InputInterface $input, OutputInterface $output) {
    parent::configureIO($input, $output);

    // Symfony will set these later, but we want it set upfront.
    if ($input->getParameterOption(['--verbose', '-v'], FALSE, TRUE) !== FALSE) {
      $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
    }
    // We are not using "very verbose", but set this for completeness.
    if ($input->getParameterOption(['-vv'], FALSE, TRUE) !== FALSE) {
      $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
    }
    // Use -vvv of --debug for even more verbose logging.
    if ($input->getParameterOption(['--debug', '-d', '-vvv'], FALSE, TRUE) !== FALSE) {
      $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
    }
  }

  private function getDrupalFinder(): DrupalFinder {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    return $drupalFinder;
  }

  private function getSiteAliasManager(): SiteAliasManagerInterface {
    $aliasFileLoader = new SiteAliasFileLoader();
    $siteAliasManager = new SiteAliasManager($aliasFileLoader);
    $siteAliasManager->addSearchLocation('drush/sites');
    return $siteAliasManager;
  }

  public function run(InputInterface $input = NULL, OutputInterface $output = NULL): int {
    return parent::run($input ?? $this->input, $output ?? $this->output);
  }

}
