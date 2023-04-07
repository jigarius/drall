<?php

namespace Drall\Commands;

use Drall\Models\RawCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to execute a drush command on multiple sites.
 *
 * @todo Remove in v3.
 */
class ExecDrushCommand extends BaseExecCommand {

  protected function configure() {
    parent::configure();

    $this->setName('exec:drush');
    $this->setAliases(['exd']);
    $this->setDescription('Execute a drush command.');
    $this->addUsage('core:status');
    $this->addUsage('--uri=@@uri core:status');
    $this->addUsage('@@site.ENV core:status');
    $this->addUsage('--drall-group=GROUP core:status');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->preExecute($input, $output);
    $this->showDeprecationWarning();

    // Symfony Console only recognizes options that are defined in the
    // ::configure() method. Since our goal is to catch all arguments and
    // options and send them to drush, we do it ourselves using $argv.
    //
    // @todo Is there a way to catch all options from $input?
    $command = RawCommand::fromArgv($this->argv);

    // If no placeholders are present, assume --uri=@@uri.
    if (
      !$command->hasPlaceholder('site') &&
      !$command->hasPlaceholder('uri')
    ) {
      $command = new RawCommand("--uri=@@uri $command");
    }

    $command = new RawCommand($this->siteDetector()->getDrushPath() . ' ' . $command);

    return $this->doExecute($command, $input, $output);
  }

  public function showDeprecationWarning(): void {
    if (getenv('DRALL_ENVIRONMENT') === 'test') {
      // This is cheating, but it helps prevent accounting for this warning
      // message in all the tests. This is temporary anyway.
      return;
    }

    $this->logger->warning(<<<EOT
drall exec:drush has been deprecated and will be removed in Drall 3.
Please use the 'drall exec' command instead.

Old usage: drall exec:drush core:status
New usage: drall exec drush core:status
EOT);
  }

}
