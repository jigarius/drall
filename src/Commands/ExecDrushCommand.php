<?php

namespace Drall\Commands;

use Drall\Models\RawCommand;
use Drall\Runners\PassthruRunner;
use Drall\Runners\RunnerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to execute a drush command on multiple sites.
 */
class ExecDrushCommand extends BaseCommand {

  /**
   * To be treated as the $argv array.
   *
   * @var array
   */
  protected array $argv;

  /**
   * The runner to use for executing commands.
   *
   * @var \Drall\Runners\RunnerInterface
   */
  protected RunnerInterface $runner;

  public function __construct(string $name = NULL) {
    parent::__construct($name);
    $this->argv = $GLOBALS['argv'];
    $this->runner = new PassthruRunner();
  }

  public function setRunner(RunnerInterface $runner): self {
    $this->runner = $runner;
    return $this;
  }

  /**
   * Sets an array to be treated as $argv, mostly for testing.
   *
   * The $argv array contains:
   *   - Script name as the first parameter, i.e. drall.
   *   - The Drall command as the second parameter, e.g. exec.
   *   - Options for the Drall command, e.g. --drall-group=bluish.
   *   - The Drush command and its arguments, e.g. pmu devel
   *   - Options for the Drush command, e.g. --fields=site.
   *
   * @code
   * $command->setArgv([
   *   '/opt/drall/bin/drall',
   *   'exec',
   *   '--drall-group=bluish',
   *   'core:status',
   *   '--fields=site',
   * ]);
   * @endcode
   *
   * @param array $argv
   *   An array matching the $argv array format.
   *
   * @return self
   *   The command.
   */
  public function setArgv(array $argv): self {
    $this->argv = $argv;
    return $this;
  }

  protected function configure() {
    parent::configure();

    $this->setName('exec:drush');
    $this->setAliases(['exd']);
    $this->setDescription('Execute a drush command.');

    $this->addArgument(
      'cmd',
      InputArgument::REQUIRED | InputArgument::IS_ARRAY,
      'A drush command.'
    );

    $this->addOption(
      'drall-group',
      NULL,
      InputOption::VALUE_OPTIONAL,
      'Site group identifier.'
    );

    $this->addUsage('core:status');
    $this->addUsage('--uri=@@uri core:status');
    $this->addUsage('@@site.ENV core:status');
    $this->addUsage('--drall-group=GROUP core:status');

    $this->ignoreValidationErrors();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    // Symfony Console only recognizes options that are defined in the
    // ::configure() method. Since our goal is to catch all arguments and
    // options and send them to drush, we do it ourselves using $argv.
    //
    // @todo Is there a way to catch all options from $input?
    $command = new RawCommand(static::getDrushCommandFromArgv($this->argv));
    $siteGroup = $input->getOption('drall-group');

    // Prepare all drush commands.
    if ($command->hasPlaceholder('site')) {
      $drushCommands = $this->generateCommandsWithAlias($command, $siteGroup);
    }
    else {
      $drushCommands = $this->generateCommandsWithUri($command, $siteGroup);
    }

    if (empty($drushCommands)) {
      $this->logger->warning('No Drupal sites found.');
      return 0;
    }

    $errorCodes = [];
    foreach ($drushCommands as $key => $drushCommand) {
      $output->writeln("Running: {$drushCommand['log']}");
      $exitCode = $this->runner->execute($drushCommand['run']);

      if ($exitCode !== 0) {
        $errorCodes[$key] = $exitCode;
      }
    }

    if (empty($errorCodes)) {
      return 0;
    }

    // @todo Display summary of errors as per verbosity level.
    return 1;
  }

  /**
   * Gets drush command from $argv, ignoring parts that are only for Drall.
   *
   * @param array $argv
   *   An $argv array.
   *
   * @return string
   *   A drush command.
   */
  private static function getDrushCommandFromArgv(array $argv): string {
    // Ignore the script name and the word "exec".
    $parts = array_slice($argv, 2);
    // Ignore options with --drall namespace.
    $parts = array_filter($parts, fn($w) => !str_starts_with($w, '--drall-'));

    return implode(' ', $parts);
  }

  /**
   * Prepares a list of drush commands with various site URIs.
   *
   * Results are keyed by unique site URIs.
   *
   * @param \Drall\Models\RawCommand $command
   *   The command to send to Drush. Example: core:status.
   * @param string|null $siteGroup
   *   A site group, if any.
   *
   * @return array
   *   An array of arrays containing commands.
   *
   *   Each item of the result array contains two keys:
   *   - run: The command to execute.
   *   - log: The command to display.
   *
   * @todo Revisit the "run" and "log" behavior.
   */
  private function generateCommandsWithUri(RawCommand $command, ?string $siteGroup = NULL): array {
    if (!$command->hasPlaceholder('uri')) {
      $command = new RawCommand("--uri=@@uri $command");
    }
    $command = new RawCommand("@@drush $command");
    $drushPath = $this->siteDetector->getDrushPath();
    $result = [];
    foreach ($this->siteDetector()->getSiteDirNames($siteGroup) as $dirName) {
      // @todo Should the keys of the $sites array be used instead?
      // @todo Can sites exist with sites/GROUP/SITE/settings.php?
      //   If yes, then does --uri=GROUP/SITE work correctly?
      $result[$dirName] = [
        'run' => $command->with(['drush' => $drushPath, 'uri' => $dirName]),
        'log' => $command->with(['drush' => 'drush', 'uri' => $dirName]),
      ];
    }
    return $result;
  }

  /**
   * Prepares a list of drush commands with various site aliases.
   *
   * Results are keyed by unique site aliases.
   *
   * @param \Drall\Models\RawCommand $command
   *   The command to send to Drush. Example: core:status.
   * @param string|null $siteGroup
   *   A site group, if any.
   *
   * @return array
   *   An array of arrays containing commands.
   *
   *   Each item of the result array contains two keys:
   *   - run: The command to execute.
   *   - log: The command to display.
   */
  private function generateCommandsWithAlias(RawCommand $command, ?string $siteGroup = NULL): array {
    $command = new RawCommand("@@drush $command");
    $drushPath = $this->siteDetector->getDrushPath();
    $result = [];
    foreach ($this->siteDetector()->getSiteAliasNames($siteGroup) as $siteName) {
      $result[$siteName] = [
        'run' => $command->with(['drush' => $drushPath, 'site' => $siteName]),
        'log' => $command->with(['drush' => 'drush', 'site' => $siteName]),
      ];
    }
    return $result;
  }

}
