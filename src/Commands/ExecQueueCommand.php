<?php

namespace Drall\Commands;

use Drall\Models\Lock;
use Drall\Models\Queue\File as QueueFile;
use Drall\Runners\ExecRunner;
use Drall\Traits\RunnerAwareTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to execute a drush command on multiple sites.
 */
class ExecQueueCommand extends BaseCommand {

  use RunnerAwareTrait;

  public function __construct(string $name = NULL) {
    parent::__construct($name);
    $this->argv = $GLOBALS['argv'];
    $this->setRunner(new ExecRunner());
  }

  protected function configure() {
    parent::configure();

    $this->setName('exec:queue');
    $this->setDescription('Start a queue worker.');
    $this->setAliases(['exq']);
    $this->addArgument('drallq-file', InputArgument::REQUIRED);
    $this->addUsage('/path/to/drallq.json');
    $this->addOption('drall-worker-id', NULL, InputOption::VALUE_OPTIONAL, 'The ID to assign to the worker.');
    $this->setHidden(TRUE);
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->preExecute($input, $output);

    $workerId = $input->getOption('drall-worker-id') ?: getmypid();
    $prefix = '[drall:' . $workerId . ']';
    $qPath = $input->getArgument('drallq-file');

    if (!is_file($qPath)) {
      $this->logger->error("Queue file not found: $qPath");
      return 1;
    }

    $qPath = realpath($qPath);
    $qFile = new QueueFile($qPath);
    $qLock = new Lock("$qPath.rw.lock");
    $outputLock = new Lock("$qPath.output.lock");

    // Run until we run out of items to process.
    while (TRUE) {
      $qLock->acquire(TRUE);
      $qData = $qFile->read();
      $item = $qData->next();
      // This way, other workers know that an item has been taken by this worker.
      $qFile->write($qData);
      $qLock->release();

      // No more items left? Then we're done.
      if (!$item) {
        $outputLock->acquire(TRUE);
        $this->logger->debug("$prefix Nothing left to do. Terminating.");
        $outputLock->release();
        break;
      }

      // Process the item, i.e. run the command.
      $rawCommand = $qData->getCommand();
      $placeholder = $qData->getPlaceholder();
      $shellCommand = $rawCommand->with([$placeholder => $item->getId()]);

      $outputLock->acquire(TRUE);
      $this->logger->info("$prefix Started: $shellCommand");
      // @todo Store start time for each item.
      $outputLock->release();

      // @todo Store exit codes for each item.
      // @todo Store finish time for each item.
      $this->runner()->execute($shellCommand);

      // Mark the item as done.
      $qLock->acquire(TRUE);
      $qData = $qFile->read();
      $qData->markAsDone($item);
      $qFile->write($qData);
      $qLock->release();

      $outputLock->acquire(TRUE);
      // @todo Instead of writing output from each worker, maybe we can have
      //   the main drall command write the output? That way, users will be
      //   able to redirect the output to a log file, if required. Or maybe
      //   add support for a --drall-log-file option which will make all the
      //   workers write to the same log file.
      $this->logger->info("$prefix Finished: $shellCommand");
      $output->write($this->runner->getOutput());
      $outputLock->release();
    }

    return 0;
  }

}
