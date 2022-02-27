<?php

namespace Drall\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drall\Models\SitesFile;

/**
 * A command to get a list of site directories in the Drupal installation.
 */
class SiteDirectoriesCommand extends BaseCommand {

  protected function configure() {
    $this->setName('site:directories');
    $this->setAliases(['sd']);
    $this->setDescription('Get a list of site directories.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $dirNames = $this->siteDetector()->getSiteDirNames();

    if (count($dirNames) === 0) {
      $output->writeln('No site directories found.');
      return 0;
    }

    foreach ($dirNames as $dirName) {
      $output->writeln($dirName);
    }

    return 0;
  }

}
