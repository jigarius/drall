<?php

namespace Drall\Command;

use Amp\ByteStream;
use Amp\ByteStream\WritableResourceStream;
use Amp\Pipeline\Pipeline;
use Amp\Process\Process;
use Drall\Batch\BatchInterface;
use Drall\Batch\FileBatch;
use Drall\Batch\MemoryBatch;
use Drall\Model\Placeholder;
use Drall\Model\SiteDetectorOptions;
use Drall\Trait\StoppableCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * A command to execute a shell command on multiple sites.
 */
final class ExecCommand extends BaseCommand implements SignalableCommandInterface {

  use StoppableCommandTrait;

  /**
   * Exit code when stopping due to user interruption.
   */
  const INTERRUPTED = 130;

  /**
   * Maximum number of Drall workers.
   *
   * @var int
   */
  const WORKER_LIMIT = 16;

  /**
   * Whether execution is stopping.
   *
   * @var bool
   */
  private bool $isInterrupted = FALSE;

  protected function configure() {
    parent::configure();

    $this->setName('exec');
    $this->setAliases(['ex']);
    $this->setDescription('Execute a command on multiple Drupal sites.');
    $this->addUsage('drush core:status');
    $this->addUsage('./vendor/bin/drush core:status');
    $this->addUsage('--group=GROUP -- drush core:status');
    $this->addUsage('--filter=FILTER -- drush core:status');
    $this->addUsage('--offset=2 --limit=2 -- drush core:status');
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
      'interval',
      NULL,
      InputOption::VALUE_OPTIONAL,
      'Number of seconds to wait between commands.',
      0,
    );

    $this->addOption(
      'dry-run',
      'X',
      InputOption::VALUE_NONE,
      'Do not execute commands, only display them.'
    );

    $this->addOption(
      'no-buffer',
      'B',
      InputOption::VALUE_NONE,
      'Do not buffer output.'
    );

    $this->addOption(
      'no-progress',
      'P',
      InputOption::VALUE_NONE,
      'Do not show a progress bar.'
    );

    $this->addOption(
      'batch-file',
      NULL,
      InputOption::VALUE_OPTIONAL,
      'Path to a batch file for resumable execution.',
    );

    $this->ignoreValidationErrors();
  }

  protected function initialize(InputInterface $input, OutputInterface $output): void {
    $this->checkObsoleteOptions($input, $output);
    $this->checkOptionsSeparator($input, $output);
    $this->checkBatchFileOption($input, $output);
    $this->checkIntervalOption($input, $output);
    $this->checkWorkersOption($input, $output);
    $this->checkInterOptionCompatibility($input, $output);

    parent::initialize($input, $output);
  }

  private function checkOptionsSeparator(InputInterface $input, OutputInterface $output): void {
    // @todo Use ::getRawTokens() when Symfony Console 7.x becomes a compulsory requirement.
    $rawTokens = preg_split('/\s+/', (string) $input);

    // If options are present, an options separator (--) is required.
    if (in_array('--', $rawTokens)) {
      return;
    }

    $output->writeln(<<<EOT
A double-dash `--` must be placed before the command to be executed.

<comment>Incorrect:</comment> drall exec --dry-run drush --field=site core:status
<comment>Correct:</comment>   drall exec --dry-run -- drush --field=site core:status

Notice the `--` between `--dry-run` and the word `drush`.
EOT);
    throw new \RuntimeException('Missing options separator');
  }

  private function checkObsoleteOptions(InputInterface $input, OutputInterface $output): void {
    // @todo Use ::getRawTokens() when Symfony Console 7.x becomes a compulsory requirement.
    $rawTokens = preg_split('/\s+/', (string) $input);

    // If obsolete --drall-* options are present, then abort.
    foreach ($rawTokens as $token) {
      if (str_starts_with($token, '--drall-')) {
        $output->writeln(<<<EOT
In Drall 4.x, all <comment>--drall-*</comment> options have been renamed.
See https://github.com/jigarius/drall/issues/99
EOT);
        throw new \RuntimeException('Obsolete options detected');
      }
    }
  }

  private function checkBatchFileOption(InputInterface $input, OutputInterface $output): void {
    if (!$batchFile = $input->getOption('batch-file')) {
      return;
    }

    if (pathinfo($batchFile, PATHINFO_EXTENSION) !== 'json') {
      $output->writeln(<<<EOT
The value for <comment>--batch-file</comment> must be a path to a file with the <comment>.json</comment> extension.
EOT);
      throw new \RuntimeException('Invalid options detected');
    }
  }

  private function checkIntervalOption(InputInterface $input, OutputInterface $output): void {
    $interval = $input->getOption('interval');

    if ($interval < 0) {
      $output->writeln(<<<EOT
The value for <comment>--interval</comment> must be a positive integer.
EOT);
      throw new \RuntimeException('Invalid options detected');
    }
  }

  private function checkWorkersOption(InputInterface $input, OutputInterface $output): void {
    $workers = $input->getOption('workers');
    $limit = self::WORKER_LIMIT;

    if ($workers < 1 || $workers > $limit) {
      $output->writeln(<<<EOT
The value for <comment>--workers</comment> must be between 1 and $limit.
EOT);
      throw new \RuntimeException('Invalid options detected');
    }
  }

  private function checkInterOptionCompatibility(InputInterface $input, OutputInterface $output): void {
    if (
      $input->getOption('workers') > 1 &&
      $input->getOption('interval') > 0
    ) {
      $output->writeln(<<<EOT
The options <comment>--interval</comment> and <comment>--workers</comment> cannot be used together.
EOT);
      throw new \RuntimeException('Incompatible options detected');
    }
  }

  protected function preExecute(InputInterface $input, OutputInterface $output): void {
    parent::preExecute($input, $output);

    $workers = $input->getOption('workers');
    if ($workers > 1) {
      $this->logger->notice("Using {count} workers.", ['count' => $workers]);
    }

    if ($interval = $input->getOption('interval')) {
      $this->logger->notice("Using a $interval-second interval between commands.", ['interval' => $interval]);
    }

    if ($input->getOption('no-buffer')) {
      $this->logger->notice("Using no output buffering.");
    }
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    /** @var \Symfony\Component\Console\Output\ConsoleOutput $output */
    $this->preExecute($input, $output);

    if (!$command = $this->getCommand($input, $output)) {
      return 1;
    }

    if (!$placeholder = $this->getUniquePlaceholder($command)) {
      return 1;
    }

    // Get all possible values for the placeholder.
    $sdOptions = SiteDetectorOptions::fromInput($input);
    $values = match ($placeholder) {
      Placeholder::Directory => $this->siteDetector()->getSiteDirNames($sdOptions),
      Placeholder::Site => $this->siteDetector()->getSiteAliasNames($sdOptions),
      Placeholder::Key => $this->siteDetector()->getSiteKeys($sdOptions),
      Placeholder::UniqueKey => $this->siteDetector()->getSiteKeys($sdOptions, TRUE),
      default => throw new \RuntimeException("Unrecognized placeholder: $placeholder->value"),
    };

    $batch = $this->initBatch($input, $output, $values);
    if ($batch === NULL) {
      return 0;
    }

    if (!$batch->getQueuedItems() && !$batch->getStartedItems()) {
      $this->logger->warning('No Drupal sites found.');
      return 0;
    }

    // Display commands without executing them.
    if ($input->getOption('dry-run')) {
      foreach ($batch->getQueuedItems() as $item) {
        $pCommand = Placeholder::replace([$placeholder->value => $item->id], $command);
        $output->writeln($pCommand);
      }

      return Command::SUCCESS;
    }

    $textSection = $output->section();
    $progressBar = new ProgressBar(
      $input->getOption('no-progress') ? new NullOutput() : $output->section(),
      count($values)
    );
    $progressBar->setProgress(count($batch->getFinishedItems()));

    $exitCode = Command::SUCCESS;

    // Within the iteration, all output must go through the output sections.
    // This keeps the text at the top and the progress bar at the bottom.
    Pipeline::fromIterable($batch->getStartedItems() + $batch->getQueuedItems())
      ->concurrent($input->getOption('workers'))
      ->unordered()
      ->forEach((function ($item) use (
        $batch,
        $input,
        $output,
        $textSection,
        $command,
        $placeholder,
        $progressBar,
        &$exitCode,
      ) {
        if ($this->isInterrupted || $this->isStopped()) {
          return;
        }

        /** @var \Drall\Batch\BatchItem $item */
        $batch->startItem($item);
        $pCommand = Placeholder::replace([$placeholder->value => $item->id], $command);
        $process = Process::start("($pCommand) 2>&1");

        // Send process output directly to the output stream.
        if (
          $input->getOption('no-buffer') &&
          is_a($output, StreamOutput::class)
        ) {
          $wStream = new WritableResourceStream($output->getStream());
          ByteStream\pipe($process->getStdout(), $wStream);
        }
        // Buffer process output until it finishes.
        elseif ($pOutput = rtrim(ByteStream\buffer($process->getStdout()))) {
          // Always display command output, even in --quiet mode.
          $textSection->writeln($pOutput, OutputInterface::VERBOSITY_QUIET);
        }

        if (Command::SUCCESS === $process->join()) {
          $textSection->writeln("✔ $item: Done");
        }
        else {
          $textSection->writeln("✖ $item: Failed");
          $exitCode = Command::FAILURE;
        }

        $batch->finishItem($item);
        $progressBar->advance();

        // Wait between commands if --interval is specified.
        if ($interval = $input->getOption('interval')) {
          sleep($interval);
        }
      }));

    if ($this->isInterrupted || $this->isStopped()) {
      $output->writeln('');
      return self::INTERRUPTED;
    }

    $progressBar->finish();

    return $exitCode;
  }

  private function initBatch(InputInterface $input, OutputInterface $output, array $values): ?BatchInterface {
    if (!$batchFile = $input->getOption('batch-file')) {
      $batch = new MemoryBatch();
      $batch->addItems($values);
      return $batch;
    }

    $batch = new FileBatch($batchFile);

    // No existing batch data — start fresh.
    if (!$batch->getItems()) {
      $batch->addItems($values);
      return $batch;
    }

    /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
    $helper = $this->getHelper('question');

    if ($batch->isComplete()) {
      $question = new ConfirmationQuestion(
        'Batch is already complete. Restart? [y/N] ',
        FALSE,
      );

      if (!$helper->ask($input, $output, $question)) {
        return NULL;
      }
    }
    else {
      $question = new ConfirmationQuestion(
        'A batch file already exists. Resume? [Y/n] ',
        TRUE,
      );

      if ($helper->ask($input, $output, $question)) {
        return $batch;
      }
    }

    // Start a fresh batch.
    $batch->reset();
    $batch->addItems($values);
    return $batch;
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

  public function getSubscribedSignals(): array {
    return [SIGINT];
  }

  public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false {
    // If a SIGINT is received more than once, stop immediately.
    if ($this->isInterrupted) {
      return self::INTERRUPTED;
    }

    $this->isInterrupted = TRUE;
    return FALSE;
  }

}
