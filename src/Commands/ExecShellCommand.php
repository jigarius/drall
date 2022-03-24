<?php

namespace Drall\Commands;

use Drall\Models\RawCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to execute a drush command on multiple sites.
 */
class ExecShellCommand extends BaseExecCommand {

  protected function configure() {
    parent::configure();

    $this->setName('exec:shell');
    $this->setAliases(['exs']);
    $this->setDescription('Execute a shell command.');
    $this->addUsage('ls web/sites/@@uri/settings.php');
    $this->addUsage('echo "Working on @@site" && drush @@site core:status');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    // Symfony Console only recognizes options that are defined in the
    // ::configure() method. Since our goal is to catch all arguments and
    // options and send them to drush, we do it ourselves using $argv.
    //
    // @todo Is there a way to catch all options from $input?
    return $this->doExecute(RawCommand::fromArgv($this->argv), $input, $output);
  }

}
