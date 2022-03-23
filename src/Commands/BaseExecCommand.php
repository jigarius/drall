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
class BaseExecCommand extends BaseCommand {

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

    $this->ignoreValidationErrors();
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    // Symfony Console only recognizes options that are defined in the
    // ::configure() method. Since our goal is to catch all arguments and
    // options and send them to drush, we do it ourselves using $argv.
    //
    // @todo Is there a way to catch all options from $input?
    return $this->doExecute(
      RawCommand::fromArgv($this->argv),
      $input->getOption('drall-group')
    );
  }

  protected function doExecute(
    RawCommand $command,
    InputInterface $input,
    OutputInterface $output
  ): int {
    $siteGroup = $input->getOption('drall-group');

    $shellCommands = [];
    if ($command->hasPlaceholder('uri')) {
      foreach ($this->siteDetector()->getSiteDirNames($siteGroup) as $dirName) {
        // @todo Should the keys of the $sites array be used instead?
        // @todo Can sites exist with sites/GROUP/SITE/settings.php?
        //   If yes, then does --uri=GROUP/SITE work correctly?
        $shellCommands[$dirName] = $command->with(['uri' => $dirName]);
      }
    }
    elseif ($command->hasPlaceholder('site')) {
      foreach ($this->siteDetector()->getSiteAliasNames($siteGroup) as $siteName) {
        $shellCommands[$siteName] = $command->with(['site' => $siteName]);
      }
    }
    else {
      $this->logger->warning('The command has no placeholders. Consider running it without Drall.');
    }

    if (empty($shellCommands)) {
      $this->logger->warning('No Drupal sites found.');
      return 0;
    }

    $errorCodes = [];
    foreach ($shellCommands as $key => $shellCommand) {
      // @todo Only show the full command in verbose mode.
      // @todo Show progress, i.e. current and total.
      $output->writeln("Current site: $key");
      $this->logger->debug("Running: {$shellCommand}");
      $exitCode = $this->runner->execute($shellCommand);

      if ($exitCode !== 0) {
        $errorCodes[$key] = $exitCode;
      }
    }

    if (empty($errorCodes)) {
      return 0;
    }

    // @todo Display summary of errors in verbose mode.
    return 1;
  }

}
