<?php

namespace Drall;

use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Command\ExecCommand;
use Drall\Command\SiteDirectoriesCommand;
use Drall\Command\SiteAliasesCommand;
use Drall\Model\EnvironmentId;
use Drall\Service\SiteDetector;
use Drall\Trait\SiteDetectorAwareTrait;
use Drush\SiteAlias\SiteAliasFileLoader;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class Drall extends Application {

  const NAME = 'Drall';

  const VERSION = '2.0.0';

  use SiteDetectorAwareTrait;

  /**
   * Creates a Phpake Application instance.
   */
  public function __construct(
    SiteDetector $siteDetector = NULL,
    ?InputInterface $input = NULL
  ) {
    parent::__construct();
    $this->setName(self::NAME);
    $this->setVersion(self::VERSION);
    $this->setAutoExit(FALSE);

    // @todo Instead of using $input to create a SiteDetector here, we can
    //   create the SiteDetector in BaseCommand::preExecute(). That way,
    //   we won't need this extra dependency injection, thereby simplifying
    //   the code and the tests.
    $input = $input ?? new ArgvInput();
    $root = $input->getParameterOption('--root') ?: getcwd();
    $siteDetector ??= $this->createDefaultSiteDetector($root);
    $this->setSiteDetector($siteDetector);

    $cmd = new SiteDirectoriesCommand();
    $cmd->setSiteDetector($siteDetector);
    $this->add($cmd);

    $cmd = new SiteAliasesCommand();
    $cmd->setSiteDetector($siteDetector);
    $this->add($cmd);

    $cmd = new ExecCommand();
    $cmd->setSiteDetector($siteDetector);
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

    // Remove unneeded options.
    $options = $definition->getOptions();
    unset($options['verbose'], $options['quiet']);
    $definition->setOptions($options);

    $definition->addOption(new InputOption(
      'drall-verbose',
      NULL,
      InputOption::VALUE_NONE,
      'Display verbose output for Drall.'
    ));
    $definition->addOption(new InputOption(
      'drall-debug',
      NULL,
      InputOption::VALUE_NONE,
      'Display debugging output for Drall.'
    ));
    $definition->addOption(new InputOption(
      'root',
      NULL,
      InputOption::VALUE_OPTIONAL,
      'Drupal root or Composer root.'
    ));

    return $definition;
  }

  private function createDefaultSiteDetector(string $root): SiteDetector {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot($root);

    $siteAliasManager = new SiteAliasManager(new SiteAliasFileLoader());
    $siteAliasManager->addSearchLocation($drupalFinder->getComposerRoot() . '/drush/sites');

    return new SiteDetector($drupalFinder, $siteAliasManager);
  }

  public function find($name) {
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

  /**
   * Whether Drall is running in a specific environment.
   *
   * This helps with development and testing. For example, during tests,
   * Drall progress bars can pollute the output. Thus, we hide them for
   * the "test" environment.
   *
   * @param \Drall\Model\EnvironmentId $id
   *   Environment ID.
   *
   * @return bool
   *   True or False.
   */
  public static function isEnvironment(EnvironmentId $id): bool {
    return getenv('DRALL_ENVIRONMENT') === $id->value;
  }

}
