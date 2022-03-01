<?php

namespace Drall\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    $this->addOption(
      'drall-group',
      NULL,
      InputOption::VALUE_REQUIRED,
      'Site group identifier.'
    );

    $this->addUsage('core:status');
    $this->addUsage('--uri=@@uri core:status');
    $this->addUsage('@@site.ENV core:status');
    $this->addUsage('--drall-group=GROUP core:status');

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
    $command = $this->getDrushCommandFromArgv($GLOBALS['argv']);
    $siteGroup = $input->getOption('drall-group');

    // Prepare all drush commands.
    if ($this->isCommandWithAlias($command)) {
      $drushCommands = $this->generateCommandsWithAlias($command, $siteGroup);
    }
    else {
      $drushCommands = $this->generateCommandsWithUri($command, $siteGroup);
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
   * Gets drush command from $argv, ignoring parts that are only for Drall.
   *
   * @param array $argv
   *   An $argv array.
   *
   * @return string
   *   A drush command.
   */
  private function getDrushCommandFromArgv(array $argv = []) {
    // Ignore the scriptname and the word "exec".
    $parts = array_slice($argv, 2);

    // Ignore options with --drall namespace.
    $parts = array_filter($parts, fn($w) => !str_starts_with($w, '--drall-'));

    return implode(' ', $parts);
  }

  /**
   * Prepares a list of drush commands with various site URIs.
   *
   * Results are keyed by unique site URIs.
   *
   * @param string $command
   *   The command to send to Drush. Example: core:status.
   * @param string|null $siteGroup
   *   A site group, if any.
   *
   * @return array
   *   Commands with various site URIs.
   */
  private function generateCommandsWithUri(string $command, ?string $siteGroup = NULL): array {
    if (!$this->isCommandWithUri($command)) {
      $command = "--uri=@@uri $command";
    }

    $commands = [];
    foreach ($this->siteDetector()->getSiteDirNames($siteGroup) as $dirName) {
      // @todo Should the keys of the $sites array be used instead?
      // @todo Can sites exist with sites/GROUP/SITE/settings.php?
      //   If yes, then does --uri=GROUP/SITE work correctly?
      $commands[] = 'drush ' . str_replace('@@uri', $dirName, $command);
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
   * @param string|null $siteGroup
   *   A site group, if any.
   *
   * @return array
   *   Commands with various site aliases.
   */
  private function generateCommandsWithAlias(string $command, ?string $siteGroup = NULL): array {
    $commands = [];
    foreach ($this->siteDetector()->getSiteAliasNames($siteGroup) as $siteName) {
      $commands[] = 'drush ' . str_replace('@@site', $siteName, $command);
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
