<?php

namespace Drall;

use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Commands\ExecShellCommand;
use Drall\Commands\ExecDrushCommand;
use Drall\Commands\SiteDirectoriesCommand;
use Drall\Commands\SiteAliasesCommand;
use Drall\Services\SiteDetector;
use Drall\Traits\SiteDetectorAwareTrait;
use DrupalCodeGenerator\Logger\ConsoleLogger;
use Drush\SiteAlias\SiteAliasFileLoader;
use DrupalFinder\DrupalFinder;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class Drall extends Application {

  const NAME = 'Drall';

  const VERSION = '2.0.0-rc1';

  use LoggerAwareTrait;
  use SiteDetectorAwareTrait;

  /**
   * Creates a Phpake Application instance.
   */
  public function __construct(
    SiteDetector $siteDetector = NULL,
    ?InputInterface $input = NULL,
    ?OutputInterface $output = NULL
  ) {
    parent::__construct();

    $input = $input ?? new ArgvInput();
    $output = $output ?? new ConsoleOutput();

    $this->setName(self::NAME);
    $this->setVersion(self::VERSION);
    $this->configureIO($input, $output);
    $this->setLogger(new ConsoleLogger($output));

    $root = $input->getParameterOption('--root') ?: getcwd();
    $siteDetector ??= $this->createDefaultSiteDetector($root);
    $this->setSiteDetector($siteDetector);

    $cmd = new SiteDirectoriesCommand();
    $cmd->setSiteDetector($siteDetector);
    $cmd->setLogger($this->logger);
    $this->add($cmd);

    $cmd = new SiteAliasesCommand();
    $cmd->setSiteDetector($siteDetector);
    $cmd->setLogger($this->logger);
    $this->add($cmd);

    $cmd = new ExecDrushCommand();
    $cmd->setSiteDetector($siteDetector);
    $cmd->setLogger($this->logger);
    $this->add($cmd);

    $cmd = new ExecShellCommand();
    $cmd->setSiteDetector($siteDetector);
    $cmd->setLogger($this->logger);
    $this->add($cmd);
  }

  protected function configureIO(InputInterface $input, OutputInterface $output) {
    parent::configureIO($input, $output);

    if ($input->hasParameterOption('--drall-debug', TRUE)) {
      $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
    }
    elseif ($input->hasParameterOption('--drall-verbose', TRUE)) {
      $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
    }
    else {
      $output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
    }
  }

  protected function getDefaultInputDefinition(): InputDefinition {
    $definition = parent::getDefaultInputDefinition();
    $options = $definition->getOptions();
    unset($options['verbose'], $options['quiet']);
    $definition->setOptions($options);

    return $definition;
  }

  private function createDefaultSiteDetector(string $root): SiteDetector {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot($root);

    $siteAliasManager = new SiteAliasManager(new SiteAliasFileLoader());
    $siteAliasManager->addSearchLocation($drupalFinder->getComposerRoot() . '/drush/sites');

    return new SiteDetector($drupalFinder, $siteAliasManager);
  }

}
