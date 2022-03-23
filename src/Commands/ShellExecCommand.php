<?php

namespace Drall\Commands;

/**
 * A command to execute a drush command on multiple sites.
 */
class ShellExecCommand extends BaseExecCommand {

  protected function configure() {
    parent::configure();

    $this->setName('exec:shell');
    $this->setAliases(['exs']);
    $this->setDescription('Execute a shell command.');
    $this->addUsage('ls web/sites/@@uri/settings.php');
    $this->addUsage('echo "Working on @@site" && drush @@site core:status');
  }

}
