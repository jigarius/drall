<?php

namespace Drall\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
    if (!$dirNames = $this->siteDetector()->getSiteDirNames()) {
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
    $drushCommand = implode(' ', array_slice($argv, 2));

    $errorCodes = [];
    foreach ($dirNames as $dirName) {
      $thisCommand = "drush --uri=$dirName $drushCommand";
      $output->writeln("Running: $thisCommand");
      passthru($thisCommand, $exitCode);

      if ($exitCode !== 0) {
        $errorCodes[$dirName] = $exitCode;
      }
    }

    // @todo Display summary of errors as per verbosity level.
    return empty($errorCodes) ? 0 : 1;
  }

}
