<?php

namespace Drall\Commands;

use Amp\ByteStream;
use Amp\Iterator;
use Amp\Loop;
use Amp\Process\Process;
use Amp\Sync\ConcurrentIterator;
use Amp\Sync\LocalSemaphore;
use Drall\Models\RawCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to execute a command on multiple sites.
 */
abstract class BaseExecCommand extends BaseCommand {

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
      'drall-workers',
      NULL,
      InputOption::VALUE_OPTIONAL,
      'Number of commands to execute in parallel.',
      1,
    );

    $this->ignoreValidationErrors();
  }

  protected function doExecute(
    RawCommand $command,
    InputInterface $input,
    OutputInterface $output
  ): int {
    $siteGroup = $this->getDrallGroup($input);
    $placeholder = $this->getPlaceholderName($command);

    // Get all possible values for the placeholder.
    switch ($placeholder) {
      case 'uri':
        // @todo Should the keys of the $sites array be used instead?
        // @todo Can sites exist with sites/GROUP/SITE/settings.php?
        //   If yes, then does --uri=GROUP/SITE work correctly?
        $values = $this->siteDetector()->getSiteDirNames($siteGroup);
        break;

      case 'site':
        $values = $this->siteDetector()->getSiteAliasNames($siteGroup);
        break;

      default:
        // @todo Log error message.
        return 1;
    }

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
          $sCommand = $command->with([$placeholder => $value]);
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

  private function getPlaceholderName(RawCommand $command): ?string {
    $hasUri = $command->hasPlaceholder('uri');
    $hasSite = $command->hasPlaceholder('site');

    if ($hasUri && $hasSite) {
      $this->logger->error('The command cannot contain both @@uri and @@site placeholders.');
      return NULL;
    }

    if (!$hasUri && !$hasSite) {
      $this->logger->error('The command has no placeholders and it can be run without Drall.');
      return NULL;
    }

    if ($hasUri) {
      return 'uri';
    }

    return 'site';
  }

}
