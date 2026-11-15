<?php

namespace Drall;

use Composer\InstalledVersions;
use Drall\Trait\SiteDetectorAwareTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\FactoryCommandLoader;
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
    $this->setCommandLoader(new FactoryCommandLoader(self::discoverCommands()));
  }

  /**
   * Discovers commands in src/Command by their #[AsCommand] attribute.
   *
   * Abstract classes and classes without the attribute are skipped.
   *
   * @return array<string, callable>
   *   Command factories keyed by command name and alias.
   */
  private static function discoverCommands(): array {
    $factories = [];

    foreach (glob(__DIR__ . '/Command/*Command.php') as $path) {
      $class = __NAMESPACE__ . '\\Command\\' . basename($path, '.php');
      $reflection = new \ReflectionClass($class);
      if ($reflection->isAbstract() || !$reflection->isSubclassOf(Command::class)) {
        continue;
      }

      $attributes = $reflection->getAttributes(AsCommand::class);
      if (!$attributes) {
        continue;
      }

      // The attribute merges aliases into its name as "name|alias1|alias2".
      // A leading empty name marks a hidden command and is not a valid key.
      $names = explode('|', $attributes[0]->newInstance()->name);
      foreach (array_filter($names) as $name) {
        $factories[$name] = fn () => new $class();
      }
    }

    return $factories;
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
