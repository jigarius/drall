<?php

namespace Drall\Command;

use Amp\ByteStream;
use Amp\Pipeline\Pipeline;
use Amp\Process\Process;
use Drall\Model\EnvironmentId;
use Drall\Model\Placeholder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to execute a shell command on multiple sites.
 */
final class ExecCommand extends BaseCommand implements SignalableCommandInterface {

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
   * Whether execution is stopping due to an interruption signal.
   *
   * @var bool
   */
  private bool $isStopping = FALSE;

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
      'no-progress',
      NULL,
      InputOption::VALUE_NONE,
      'Do not show a progress bar.'
    );

    $this->ignoreValidationErrors();
  }

  protected function initialize(InputInterface $input, OutputInterface $output): void {
    $this->checkObsoleteOptions($input, $output);
    $this->checkOptionsSeparator($input, $output);
    $this->checkIntervalOption($input, $output);
    $this->checkWorkersOption($input, $output);
    $this->checkInterOptionCompatibility($input, $output);

    parent::initialize($input, $output);
  }

  private function checkOptionsSeparator(InputInterface $input, OutputInterface $output): void {
    if (!method_exists($input, 'getRawTokens')) {
      return;
    }

    // If options are present, an options separator (--) is required.
    $rawTokens = $input->getRawTokens(TRUE);
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
  }

  private function checkObsoleteOptions(InputInterface $input, OutputInterface $output): void {
    if (!method_exists($input, 'getRawTokens')) {
      return;
    }

    // If obsolete --drall-* options are present, then abort.
    foreach ($input->getRawTokens(TRUE) as $token) {
      if (str_starts_with($token, '--drall-')) {
        $output->writeln(<<<EOT
In Drall 4.x, all <comment>--drall-*</comment> options have been renamed.
See https://github.com/jigarius/drall/issues/99
EOT);
        throw new \RuntimeException('Obsolete options detected');
      }
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
      ;
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

    // Display commands without executing them.
    if ($input->getOption('dry-run')) {
      foreach ($values as $value) {
        $pCommand = Placeholder::replace([$placeholder->value => $value], $command);
        $output->writeln("• $value: Preview");
        $output->writeln($pCommand, OutputInterface::VERBOSITY_QUIET);
      }

      return Command::SUCCESS;
    }

    // After this point, all output must go through the output sections.
    // This keeps the text at the top and the progress bar at the bottom.
    $textSection = $output->section();
    $progressBar = new ProgressBar(
      $this->isProgressBarHidden($input) ? new NullOutput() : $output->section(),
      count($values)
    );

    $exitCode = Command::SUCCESS;

    Pipeline::fromIterable($values)
      ->concurrent($input->getOption('workers'))
      ->unordered()
      ->forEach((function ($value) use (
        $input,
        $output,
        $textSection,
        $command,
        $placeholder,
        $progressBar,
        &$exitCode,
      ) {
        if ($this->isStopping) {
          return;
        }

        $pCommand = Placeholder::replace([$placeholder->value => $value], $command);
        $process = Process::start("($pCommand) 2>&1");

        $pOutput = rtrim(ByteStream\buffer($process->getStdout()));
        if ($pOutput) {
          // Always display command output, even in --quiet mode.
          $textSection->writeln($pOutput, OutputInterface::VERBOSITY_QUIET);
        }

        if (Command::SUCCESS === $process->join()) {
          $textSection->writeln("✔ $value: Done");
        }
        else {
          $textSection->writeln("✖ $value: Failed");
          $exitCode = Command::FAILURE;
        }

        $progressBar->advance();

        // Wait between commands if --interval is specified.
        if ($interval = $input->getOption('interval')) {
          sleep($interval);
        }
      }));

    if ($this->isStopping) {
      $output->writeln('');
      return self::INTERRUPTED;
    }

    $progressBar->finish();

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
      EnvironmentId::Test->isActive() ||
      $input->getOption('no-progress')
    ) {
      return TRUE;
    }

    return FALSE;
  }

  public function getSubscribedSignals(): array {
    return [SIGINT];
  }

  public function handleSignal(int $signal, int|false $previousExitCode = 0): int|false {
    // If a SIGINT is received more than once, stop immediately.
    if ($this->isStopping) {
      return self::INTERRUPTED;
    }

    $this->isStopping = TRUE;
    return FALSE;
  }

}
