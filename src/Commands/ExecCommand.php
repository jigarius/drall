<?php

namespace Drall\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drall\Models\SitesFile;

/**
 * A command to execute a drush command on multiple sites.
 */
class ExecCommand extends BaseCommand {

  protected function configure() {
    $this->setName('exec');
    $this->setAliases(['ex']);
    $this->setDescription('Execute a drush command.');
    $this->addArgument(
      'drush-command',
      InputArgument::REQUIRED | InputArgument::IS_ARRAY,
      'A drush command.'
    );
    $this->addUsage('core:status');
    $this->ignoreValidationErrors();
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $dirNames = $this->siteDetector()->getSiteDirNames();

    if (count($dirNames) === 0) {
      return 0;
    }

    // Symfony Console only recognizes options that are defined in the
    // ::configure() method. Since our goal is to catch all arguments and
    // options and send them to drush, we do exactly that.
    //
    // Example: drall drush arg1 arg2 --opt1 --opt2
    //
    // We simply ignore the first to items and send the rest to drush.
    global $argv;
    $drushCommand = join(' ', array_slice($argv, 2));

    foreach ($dirNames as $dirName) {
      $thisCommand = "drush --uri=$dirName $drushCommand";
      $output->writeln("Running: $thisCommand");
      passthru($thisCommand);
      // @todo Collect exit codes and display summary.
    }

    return 0;
  }

}
