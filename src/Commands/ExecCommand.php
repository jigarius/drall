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
    // Symfony Console only recognizes options that are defined in the
    // ::configure() method. Since our goal is to catch all arguments and
    // options and send them to drush, we do exactly that.
    //
    // Example: drall drush arg1 arg2 --opt1 --opt2
    //
    // We simply ignore the first to items and send the rest to drush.
    global $argv;
    $command = implode(' ', array_slice($argv, 2));

    // @todo Generate commands in different methods, but execute them from
    //   one place. This will allow processing the exit codes in a uniform
    //   manner.
    if ($this->isCommandWithAlias($command)) {
      return $this->executeWithAlias($command, $input, $output);
    }

    return $this->executeWithUri($command, $input, $output);
  }

  private function executeWithUri(
    string $command,
    InputInterface $input,
    OutputInterface $output
  ): int {
    if (!$this->isCommandWithUri($command)) {
      $command = "--uri=@@uri $command";
    }

    $errorCodes = [];
    foreach ($this->siteDetector()->getSiteDirNames() as $dirName) {
      // @todo Should the keys of the $sites array be used instead?
      // @todo Can sites exist with sites/GROUP/SITE/settings.php?
      //   If yes, then does --uri=GROUP/SITE work correctly?
      $thisCommand = 'drush ' . str_replace('@@uri', $dirName, $command);
      $output->writeln("Running: $thisCommand");
      passthru($thisCommand, $exitCode);

      if ($exitCode !== 0) {
        $errorCodes[$dirName] = $exitCode;
      }
    }

    // @todo Display summary of errors as per verbosity level.
    return empty($errorCodes) ? 0 : 1;
  }

  private function executeWithAlias(
    string $command,
    InputInterface $input,
    OutputInterface $output
  ): int {
    $errorCodes = [];
    foreach ($this->siteDetector()->getSiteAliasNames() as $siteName) {
      $thisCommand = 'drush ' . str_replace('@@site', $siteName, $command);
      $output->writeln("Running: $thisCommand");
      passthru($thisCommand, $exitCode);

      if ($exitCode !== 0) {
        $errorCodes[$siteName] = $exitCode;
      }
    }

    // @todo Display summary of errors as per verbosity level.
    return 1;
  }

  private function isCommandWithUri(string $command): bool {
    return preg_match('/\W?@@uri\W?/', $command);
  }

  private function isCommandWithAlias(string $command): bool {
    return preg_match('/\W?@@site\W?/', $command);
  }

}
