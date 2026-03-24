<?php

namespace Drall;

use Composer\InstalledVersions;
use Drall\Command\ExecCommand;
use Drall\Command\StopCommand;
use Drall\Command\SiteAliasesCommand;
use Drall\Command\SiteDirectoriesCommand;
use Drall\Command\SiteKeysCommand;
use Drall\Trait\SiteDetectorAwareTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class Drall extends Application {

  const NAME = 'Drall';

  use SiteDetectorAwareTrait;

  /**
   * Creates a Drall Application instance.
   */
  public function __construct() {
    parent::__construct();

    $this->setName(self::NAME);
    $this->setVersion(InstalledVersions::getPrettyVersion('jigarius/drall') ?? 'unknown');
    $this->setAutoExit(FALSE);

    $this->add(new SiteDirectoriesCommand());
    $this->add(new SiteKeysCommand());
    $this->add(new SiteAliasesCommand());
    $this->add(new ExecCommand());
    $this->add(new StopCommand());
  }

  protected function configureIO(InputInterface $input, OutputInterface $output): void {
    parent::configureIO($input, $output);

    if (
      $input->hasParameterOption('--debug', TRUE) ||
      $input->hasParameterOption('-d', TRUE)
    ) {
      $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
    }

    // The parent::configureIO sets verbosity in a SHELL_VERBOSITY. This causes
    // other Symfony Console apps to become verbose, for example, Drush. To
    // prevent such behavior, we force the SHELL_VERBOSITY to be normal.
    $shellVerbosity = 0;
    if (\function_exists('putenv')) {
      @putenv("SHELL_VERBOSITY=$shellVerbosity");
    }
    $_ENV['SHELL_VERBOSITY'] = $shellVerbosity;
    $_SERVER['SHELL_VERBOSITY'] = $shellVerbosity;
  }

  protected function getDefaultInputDefinition(): InputDefinition {
    $definition = parent::getDefaultInputDefinition();

    // Remove unneeded options.
    $options = $definition->getOptions();
    unset($options['silent']);
    $definition->setOptions($options);

    $definition->addOption(new InputOption(
      'debug',
      'd',
      InputOption::VALUE_NONE,
      'Display debugging output for Drall.'
    ));

    return $definition;
  }

  public function find(string $name): Command {
    try {
      return parent::find($name);
    }
    catch (CommandNotFoundException) {
      throw new CommandNotFoundException(<<<EOT
The command "$name" was not understood. Did you mean one of the following?

drall exec $name
drall exec drush $name

Alternatively, run "drall list" to see a list of all available commands.
EOT);
    }
  }

}
