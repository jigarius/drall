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
      'cmd',
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

    // Prepare all drush commands.
    if ($this->isCommandWithAlias($command)) {
      $drushCommands = $this->generateCommandsWithAlias($command);
    }
    else {
      $drushCommands = $this->generateCommandsWithUri($command);
    }

    $errorCodes = [];
    foreach ($drushCommands as $key => $drushCommand) {
      $output->writeln("Running: $drushCommand");
      passthru($drushCommand, $exitCode);

      if ($exitCode !== 0) {
        $errorCodes[$key] = $exitCode;
      }
    }

    // @todo Display summary of errors as per verbosity level.
    return empty($errorCodes) ? 0 : 1;
  }

  /**
   * Prepares a list of drush commands with various site URIs.
   *
   * Results are keyed by unique site URIs.
   *
   * @param string $command
   *   The command to send to Drush. Example: core:status.
   * @return array
   *   Commands with various site URIs.
   */
  private function generateCommandsWithUri(string $command): array {
    if (!$this->isCommandWithUri($command)) {
      $command = "--uri=@@uri $command";
    }

    $commands = [];
    foreach ($this->siteDetector()->getSiteDirNames() as $dirName) {
      // @todo Should the keys of the $sites array be used instead?
      // @todo Can sites exist with sites/GROUP/SITE/settings.php?
      //   If yes, then does --uri=GROUP/SITE work correctly?
      $commands[]= 'drush ' . str_replace('@@uri', $dirName, $command);
    }
    return $commands;
  }

  /**
   * Prepares a list of drush commands with various site aliases.
   *
   * Results are keyed by unique site aliases.
   *
   * @param string $command
   *   The command to send to Drush. Example: core:status.
   * @return array
   *   Commands with various site aliases.
   */
  private function generateCommandsWithAlias(string $command): array {
    $commands = [];
    foreach ($this->siteDetector()->getSiteAliasNames() as $siteName) {
      $commands[]= 'drush ' . str_replace('@@site', $siteName, $command);
    }
    return $commands;
  }

  private function isCommandWithUri(string $command): bool {
    return preg_match('/\W?@@uri\W?/', $command);
  }

  private function isCommandWithAlias(string $command): bool {
    return preg_match('/\W?@@site\W?/', $command);
  }

}
