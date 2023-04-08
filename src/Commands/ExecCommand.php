<?php

namespace Drall\Commands;

use Amp\ByteStream;
use Amp\Iterator;
use Amp\Loop;
use Amp\Process\Process;
use Amp\Sync\ConcurrentIterator;
use Amp\Sync\LocalSemaphore;
use Drall\Models\Placeholder;
use Drall\Models\RawCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    $this->addUsage('./vendor/bin/drush core:status');
    $this->addUsage('ls web/sites/@@uri/settings.php');
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
    $siteGroup = $this->getDrallGroup($input);

    if (!$placeholder = $this->getUniquePlaceholder($command)) {
      return 1;
    }

    // Get all possible values for the placeholder.
    $values = match ($placeholder) {
      Placeholder::Directory => $this->siteDetector()->getSiteDirNames($siteGroup),
      Placeholder::Site => $this->siteDetector()->getSiteAliasNames($siteGroup),
      default => throw new \RuntimeException('Unrecognized placeholder: ' . $placeholder->value),
    };

    if (empty($values)) {
      $this->logger->warning('No Drupal sites found.');
      return 0;
    }

    // Determine number of workers.
    $w = $input->getOption('drall-workers');

    if ($w > self::WORKER_LIMIT) {
      $this->logger->warning(sprintf('Limiting workers to %d, which is the maximum.', self::WORKER_LIMIT));
      $w = self::WORKER_LIMIT;
    }

    if ($w > 1) {
      $this->logger->info("Executing with $w workers.");
    }

    $logger = $this->logger;
    $hasErrors = FALSE;
    Loop::run(function () use ($command, $placeholder, $values, $w, $output, $logger, &$hasErrors) {
      // Removing the following line results in a segmentation fault.
      $logger;

      yield ConcurrentIterator\each(
        Iterator\fromIterable($values),
        new LocalSemaphore($w),
        function ($value) use ($command, $placeholder, $output, $logger, &$hasErrors) {
          $sCommand = Placeholder::replace([$placeholder->value => $value], $command);
          $process = new Process($sCommand);

          $output->writeln("Current site: $value");

          yield $process->start();
          $logger->debug("Running: $sCommand");

          $sOutput = yield ByteStream\buffer($process->getStdout());
          $exitCode = yield $process->join();

          if ($exitCode !== 0) {
            $hasErrors = TRUE;
          }

          $output->write($sOutput);
        }
      );
    });

    $output->writeln('');
    return $hasErrors ? 1 : 0;
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

    // Inject --uri=@@uri for Drush commands without placeholders.
    if (!Placeholder::search($command)) {
      $sCommand = preg_replace('/\b(drush) /', 'drush --uri=@@uri ', $command, -1, $count);
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

}
