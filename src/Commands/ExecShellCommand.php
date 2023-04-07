<?php

namespace Drall\Commands;

use Drall\Models\RawCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to execute a shell command on multiple sites.
 *
 * @todo Rename to ExecCommand.
 * @todo Merge with BaseExecCommand.
 */
class ExecShellCommand extends BaseExecCommand {

  protected function configure() {
    parent::configure();

    $this->setName('exec');
    $this->setAliases(['ex', 'exec:shell', 'exs']);
    $this->setDescription('Execute a command.');
    $this->addUsage('drush core:status');
    $this->addUsage('./vendor/bin/drush core:status');
    $this->addUsage('ls web/sites/@@uri/settings.php');
    $this->addUsage('echo "Working on @@site" && drush @@site.local core:status');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->preExecute($input, $output);

    if (!in_array($input->getFirstArgument(), ['exec', 'ex'])) {
      $this->showDeprecationWarning();
    }

    return $this->doExecute($this->getCommand(), $input, $output);
  }

  protected function getCommand(): RawCommand {
    // Symfony Console only recognizes options that are defined in the
    // ::configure() method. Since our goal is to catch all arguments and
    // options and send them to drush, we do it ourselves using $argv.
    //
    // @todo Is there a way to catch all options from $input?
    $command = RawCommand::fromArgv($this->argv);

    if (!str_contains($command, 'drush')) {
      return $command;
    }

    // Inject --uri=@@uri for Drush commands without placeholders.
    if (!$command->hasPlaceholder('uri') && !$command->hasPlaceholder('site')) {
      $sCommand = preg_replace('/\b(drush) /', 'drush --uri=@@uri ', $command, -1, $count);
      $command = new RawCommand($sCommand);
      $this->logger->debug('Injected --uri parameter for Drush command.');
    }

    return $command;
  }

  public function showDeprecationWarning(): void {
    if (getenv('DRALL_ENVIRONMENT') === 'test') {
      // This is cheating, but it helps prevent accounting for this warning
      // message in all the tests. This is temporary anyway.
      return;
    }

    $this->logger->warning(<<<EOT
drall exec:shell has been deprecated and will be removed in Drall 3.
Please use the 'drall exec' command instead.
EOT);
  }

}
