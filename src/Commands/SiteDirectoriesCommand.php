<?php

namespace Drall\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to get a list of site directories in the Drupal installation.
 */
class SiteDirectoriesCommand extends BaseCommand {

  protected function configure() {
    $this->setName('site:directories');
    $this->setAliases(['sd']);
    $this->setDescription('Get a list of site directories.');

    $this->addOption(
      'drall-group',
      NULL,
      InputOption::VALUE_REQUIRED,
      'Site group identifier.'
    );

    $this->addUsage('site:directories');
    $this->addUsage('--drall-group=GROUP site:directories');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $dirNames = $this->siteDetector()->getSiteDirNames(
      $input->getOption('drall-group')
    );

    if (count($dirNames) === 0) {
      $this->logger->warning('No site directories found.');
      return 0;
    }

    foreach ($dirNames as $dirName) {
      $output->writeln($dirName);
    }

    return 0;
  }

}
