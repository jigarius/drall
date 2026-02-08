<?php

namespace Drall\Command;

use Drall\Trait\StoppableCommandTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Stops an "exec" command when PCNTL is not available.
 *
 * This command is merely a workaround when PCNTL is not available. Ideally,
 * consider installing PCNTL so that commands can be stopped using Ctrl + C.
 * Also, this command might have undesirable results when multiple "exec"
 * commands are running at the same time.
 */
final class StopCommand extends BaseCommand {

  use StoppableCommandTrait;

  protected function configure() {
    parent::configure();

    $this->setName('stop');
    $this->setAliases(['st']);
    $this->setDescription('Stop all "exec" commands that are currently running.');

    if (self::isPcntlEnabled()) {
      $this->setHidden();
    }
  }

  protected function preExecute(InputInterface $input, OutputInterface $output): void {
    parent::preExecute($input, $output);

    if (self::isPcntlEnabled()) {
      $this->logger->warning('It is discommended to use the "stop" command when PCNTL is available.');
    }
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    /** @var \Symfony\Component\Console\Output\ConsoleOutput $output */
    $this->preExecute($input, $output);

    $this->createStopFile();
    return Command::SUCCESS;
  }

}
