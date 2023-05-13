<?php

namespace Drall\Commands;

use Amp\ByteStream;
use Amp\Iterator;
use Amp\Loop;
use Amp\Process\Process;
use Amp\Sync\ConcurrentIterator;
use Amp\Sync\LocalSemaphore;
use Drall\Drall;
use Drall\Models\EnvironmentId;
use Drall\Models\Placeholder;
use Drall\Models\RawCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to execute a shell command on multiple sites.
 */
class ExecCommand extends BaseCommand {

  /**
   * Maximum number of Drall workers.
   *
   * @var int
   */
  const WORKER_LIMIT = 16;

  /**
   * To be treated as the $argv array.
   *
   * @var array
   */
  protected array $argv;

  public function __construct(string $name = NULL) {
    parent::__construct($name);
    $this->argv = $GLOBALS['argv'];
  }

  protected function configure() {
    parent::configure();

    $this->setName('exec');
    $this->setAliases(['ex']);
    $this->setDescription('Execute a command on multiple Drupal sites.');
    $this->addUsage('drush core:status');
    $this->addUsage('--drall-group=bluish drush core:status');
    $this->addUsage('--drall-workers=4 drush cache:rebuild');
    $this->addUsage('./vendor/bin/drush core:status');
    $this->addUsage('ls web/sites/@@dir/settings.php');
    $this->addUsage('echo "Working on @@site" && drush @@site.local core:status');

    $this->addArgument(
      'cmd',
      InputArgument::REQUIRED | InputArgument::IS_ARRAY,
      'A drush command.'
    );

    $this->addOption(
      'drall-workers',
      NULL,
      InputOption::VALUE_OPTIONAL,
      'Number of commands to execute in parallel.',
      1,
    );

    $this->addOption(
      'drall-no-progress',
      NULL,
      InputOption::VALUE_OPTIONAL,
      'Do not show a progress bar.',
      0
    );

    $this->ignoreValidationErrors();
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

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->preExecute($input, $output);

    $command = $this->getCommand();
    $group = $this->getDrallGroup($input);
    $filter = $this->getDrallFilter($input);

    if (!$placeholder = $this->getUniquePlaceholder($command)) {
      return 1;
    }

    // Get all possible values for the placeholder.
    $values = match ($placeholder) {
      Placeholder::Directory => $this->siteDetector()->getSiteDirNames($group, $filter),
      Placeholder::Site => $this->siteDetector()->getSiteAliasNames($group, $filter),
      Placeholder::Key => $this->siteDetector()->getSiteKeys($group, $filter),
      Placeholder::UniqueKey => $this->siteDetector()->getSiteKeys($group, $filter, TRUE),
      default => throw new \RuntimeException('Unrecognized placeholder: ' . $placeholder->value),
    };

    if (empty($values)) {
      $this->logger->warning('No Drupal sites found.');
      return 0;
    }

    // Determine number of workers.
    $workers = $input->getOption('drall-workers');

    if ($workers > self::WORKER_LIMIT) {
      $this->logger->warning('Limiting workers to {count}, which is the maximum.', ['count' => self::WORKER_LIMIT]);
      $workers = self::WORKER_LIMIT;
    }

    if ($workers > 1) {
      $this->logger->notice("Using {count} workers.", ['count' => $workers]);
    }

    $progressBar = new ProgressBar(
      $this->isProgressBarHidden($input) ? new NullOutput() : $output,
      count($values)
    );
    $exitCode = 0;

    Loop::run(function () use ($values, $command, $placeholder, $output, $progressBar, $workers, &$exitCode) {
      yield ConcurrentIterator\each(
        Iterator\fromIterable($values),
        new LocalSemaphore($workers),
        function ($value) use ($command, $placeholder, $output, $progressBar, &$exitCode) {
          $sCommand = Placeholder::replace([$placeholder->value => $value], $command);
          $process = new Process("($sCommand) 2>&1", getcwd());

          yield $process->start();
          $this->logger->debug('Running: {command}', ['command' => $sCommand]);

          $sOutput = yield ByteStream\buffer($process->getStdout());
          if (0 !== yield $process->join()) {
            $exitCode = 1;
          }

          $progressBar->clear();
          $output->writeln("Finished: $value");
          $output->write($sOutput);

          $progressBar->advance();
          $progressBar->display();
        }
      );
    });

    $progressBar->finish();
    $output->writeln('');

    return $exitCode;
  }

  protected function getCommand(): RawCommand {
    // Symfony Console only recognizes options that are defined in the
    // ::configure() method. Since our goal is to catch all arguments and
    // options and send them to drush, we do it ourselves using $argv.
    //
    // @todo Is there a way to catch all options from $input?
    $command = RawCommand::fromArgv($this->argv);

    if (!str_contains($command, 'drush')) {
      return $command;
    }

    // Inject --uri=@@dir for Drush commands without placeholders.
    if (!Placeholder::search($command)) {
      $sCommand = preg_replace('/\b(drush) /', 'drush --uri=@@dir ', $command, -1);
      $command = new RawCommand($sCommand);
      $this->logger->debug('Injected --uri parameter for Drush command.');
    }

    return $command;
  }

  /**
   * Get unique placeholder from a command.
   */
  private function getUniquePlaceholder(RawCommand $command): ?Placeholder {
    if (!$placeholders = Placeholder::search($command)) {
      $this->logger->error('The command contains no placeholders. Please run it directly without Drall.');
      return NULL;
    }

    if (count($placeholders) > 1) {
      $tokens = array_column($placeholders, 'value');
      $this->logger->error('The command contains: ' . implode(', ', $tokens) . '. Please use only one.');
      return NULL;
    }

    return reset($placeholders);
  }

  /**
   * Whether the Drall progress bar should be hidden.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   *
   * @return bool
   *   True or false.
   */
  private function isProgressBarHidden(InputInterface $input): bool {
    if (
      Drall::isEnvironment(EnvironmentId::Test) ||
      $input->getOption('drall-no-progress')
    ) {
      return TRUE;
    }

    return FALSE;
  }

}
