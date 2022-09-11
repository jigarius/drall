<?php

namespace Drall\Commands;

use Amp\Iterator;
use Amp\Loop;
use Amp\Process\Process;
use Amp\Sync\ConcurrentIterator;
use Amp\Sync\LocalSemaphore;
use Drall\Models\RawCommand;
use Drall\Traits\RunnerAwareTrait;
use Drall\Runners\PassthruRunner;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to execute a drush command on multiple sites.
 */
abstract class BaseExecCommand extends BaseCommand {

  use RunnerAwareTrait;

  /**
   * Maximum number of Drall workers.
   *
   * @var int
   */
  const WORKER_LIMIT = 10;

  /**
   * To be treated as the $argv array.
   *
   * @var array
   */
  protected array $argv;

  public function __construct(string $name = NULL) {
    parent::__construct($name);
    $this->argv = $GLOBALS['argv'];
    $this->setRunner(new PassthruRunner());
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

    // Get all values for the placeholder.
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
        return 1;
    }

    if (empty($values)) {
      $this->logger->warning('No Drupal sites found.');
      return 0;
    }

    // If multiple workers are required.
    $w = $input->getOption('drall-workers');

    if ($w > self::WORKER_LIMIT) {
      $this->logger->warning('Limiting workers to 10, which is the maximum.');
      $w = 10;
    }

    if ($w > 1) {
      $this->logger->info("Executing with $w workers.");
    }

    Loop::run(function () use ($command, $w, $placeholder, $values, $output) {
      yield ConcurrentIterator\each(
        Iterator\fromIterable($values),
        new LocalSemaphore($w),
        function ($value) use ($command, $placeholder, $output) {
          $process = new Process($command->with([$placeholder => $value]));
          yield $process->start();
          $promise = $process->join();
          $promise->onResolve(function ($error, $result) use ($output) {
            if ($error) {
              $output->write('F');
              return;
            }

            $output->write('.');
          });
          yield $promise;
        }
      );
    });

    echo PHP_EOL;
    return 0;
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
