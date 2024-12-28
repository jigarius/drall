<?php

namespace Drall\Command;

use Amp\ByteStream;
use Amp\Iterator;
use Amp\Loop;
use Amp\Process\Process;
use Amp\Sync\ConcurrentIterator;
use Amp\Sync\LocalSemaphore;
use Drall\Drall;
use Drall\Model\EnvironmentId;
use Drall\Model\Placeholder;
use Drall\Trait\SignalAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to execute a shell command on multiple sites.
 */
final class ExecCommand extends BaseCommand {

  use SignalAwareTrait;

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

  public function __construct(?string $name = NULL) {
    parent::__construct($name);
    $this->argv = $GLOBALS['argv'];
  }

  protected function configure() {
    parent::configure();

    $this->setName('exec');
    $this->setAliases(['ex']);
    $this->setDescription('Execute a command on multiple Drupal sites.');
    $this->addUsage('drush core:status');
    $this->addUsage('./vendor/bin/drush core:status');
    $this->addUsage('--group=GROUP -- drush core:status');
    $this->addUsage('--filter=FILTER -- drush core:status');
    $this->addUsage('--workers=4 -- drush cache:rebuild');
    $this->addUsage('ls web/sites/@@dir/settings.php');
    $this->addUsage('\'echo "Working on @@site" && drush @@site.local core:status\'');

    $this->addArgument(
      'cmd',
      InputArgument::REQUIRED | InputArgument::IS_ARRAY,
      'A shell command.'
    );

    $this->addOption(
      'workers',
      'w',
      InputOption::VALUE_OPTIONAL,
      'Number of commands to execute in parallel.',
      1,
    );

    $this->addOption(
      'dry-run',
      'X',
      InputOption::VALUE_NONE,
      'Do not execute commands, only display them.'
    );

    $this->addOption(
      'no-progress',
      NULL,
      InputOption::VALUE_NONE,
      'Do not show a progress bar.'
    );

    $this->ignoreValidationErrors();
  }

  protected function initialize(InputInterface $input, OutputInterface $output): void {
    if (!method_exists($input, 'getRawTokens')) {
      parent::initialize($input, $output);
      return;
    }

    // Parts of the command after "exec".
    $rawTokens = $input->getRawTokens(TRUE);

    // If obsolete --drall-* options are present, then abort.
    foreach ($rawTokens as $token) {
      if (str_starts_with($token, '--drall-')) {
        $output->writeln(<<<EOT
In Drall 4.x, all --drall-* options have been renamed.
See https://github.com/jigarius/drall/issues/99
EOT);
        throw new \RuntimeException('Obsolete options detected');
      }
    }

    // If options are present, an options separator (--) is required.
    if (!in_array('--', $rawTokens)) {
      foreach ($rawTokens as $token) {
        if (str_starts_with($token, '-')) {
          $output->writeln(<<<EOT
When using options, a "--" must be placed before the command to be executed.

<comment>Incorrect:</comment> drall exec --dry-run drush --field=site core:status
<comment>Correct:</comment>   drall exec --dry-run -- drush --field=site core:status

Notice the `--` between `--dry-run` and the word `drush`.
EOT);
          throw new \RuntimeException('Missing options separator');
        }
      }
    }

    parent::initialize($input, $output);
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->preExecute($input, $output);

    if (!$command = $this->getCommand($input, $output)) {
      return 1;
    }

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

    $workers = $this->getWorkerCount($input);

    // Display commands without executing them.
    if ($input->getOption('dry-run')) {
      foreach ($values as $value) {
        $pCommand = Placeholder::replace([$placeholder->value => $value], $command);
        $output->writeln("• $value: Preview");
        $output->writeln($pCommand, OutputInterface::VERBOSITY_QUIET);
      }

      return Command::SUCCESS;
    }

    $progressBar = new ProgressBar(
      $this->isProgressBarHidden($input) ? new NullOutput() : $output,
      count($values)
    );
    $exitCode = Command::SUCCESS;

    // Handle interruption signals to stop Drall gracefully.
    $isStopping = FALSE;
    $this->registerInterruptionListener(function () use (&$isStopping, $output) {
      $output->writeln('');

      // If a previous SIGINT was received, then stop immediately.
      if ($isStopping) {
        $this->logger->error('Interrupted by user.');
        exit(Command::FAILURE);
      }

      // Prepare to stop after the current item is processed.
      $this->logger->warning('Stopping after current item.');
      $isStopping = TRUE;
    });

    Loop::run(function () use (
      $values,
      $command,
      $placeholder,
      $output,
      $progressBar,
      $workers,
      &$exitCode,
      &$isStopping
    ) {
      yield ConcurrentIterator\each(
        Iterator\fromIterable($values),
        new LocalSemaphore($workers),
        function ($value) use ($command, $placeholder, $output, $progressBar, &$exitCode, &$isStopping) {
          if ($isStopping) {
            return;
          }

          $pCommand = Placeholder::replace([$placeholder->value => $value], $command);
          $process = new Process("($pCommand) 2>&1", getcwd());

          yield $process->start();
          $this->logger->debug('Running: {command}', ['command' => $pCommand]);

          // @todo Improve formatting of headings.
          $pOutput = yield ByteStream\buffer($process->getStdout());
          $pStatus = 'Done';
          $pIcon = '✔';
          if (Command::SUCCESS !== yield $process->join()) {
            $pStatus = 'Failed';
            $pIcon = '✖';
            $exitCode = Command::FAILURE;
          }

          $pMessage = "$pIcon $value: $pStatus";

          $progressBar->clear();
          // Always display command output, even in --quiet mode.
          $output->writeln($pMessage, OutputInterface::VERBOSITY_QUIET);
          $output->write($pOutput);

          $progressBar->advance();
          $progressBar->display();
        }
      );
    });

    if (!$isStopping) {
      $progressBar->finish();
    }

    $output->writeln('');

    if ($isStopping) {
      $this->logger->error('Interrupted by user.');
      return Command::FAILURE;
    }

    return $exitCode;
  }

  /**
   * Extracts the command to be executed by Drall.
   *
   * All drall-specific components are removed from the command.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Console input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Console output.
   *
   * @return string|null
   *   The command without Drall elements.
   *
   * @example
   * Input: /path/to/drall exec --verbose -- drush st --fields=site
   * Output: drush st --fields=site
   */
  private function getCommand(InputInterface $input, OutputInterface $output): ?string {
    // Everything after the first "--" is treated as an argument. All such
    // arguments are treated as parts of the command to be executed.
    $command = implode(' ', $input->getArguments()['cmd']);
    $this->logger->debug("Command received: {command}", ['command' => $command]);

    if (
      str_contains($command, 'drush') &&
      !Placeholder::search($command)
    ) {
      // Inject --uri=@@dir for Drush commands without placeholders.
      $command = preg_replace('/\b(drush) /', 'drush --uri=@@dir ', $command, -1);
      $this->logger->debug('Injected --uri parameter for Drush command.');
      $this->logger->notice("Command modified: {command}", ['command' => $command]);
    }

    return $command;
  }

  /**
   * Gets the number of workers that should be used.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   *
   * @return int
   *   Number of workers to be used.
   */
  protected function getWorkerCount(InputInterface $input): int {
    $result = $input->getOption('workers');

    if ($result > self::WORKER_LIMIT) {
      $this->logger->warning('Limiting workers to {count}, which is the maximum.', ['count' => self::WORKER_LIMIT]);
      $result = self::WORKER_LIMIT;
    }

    if ($result > 1) {
      $this->logger->notice("Using {count} workers.", ['count' => $result]);
    }

    return $result;
  }

  /**
   * Get unique placeholder from a command.
   */
  private function getUniquePlaceholder(string $command): ?Placeholder {
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
      $input->getOption('no-progress')
    ) {
      return TRUE;
    }

    return FALSE;
  }

}
