<?php

namespace Drall\Command;

use Drall\Model\SiteDetectorOptions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
  name: 'site:directories',
  description: 'List the values of the $sites array.',
  aliases: ['sd'],
)]
class SiteDirectoriesCommand extends BaseCommand {

  protected function configure() {
    parent::configure();
    $this->addUsage('site:directories');
    $this->addUsage('--group=GROUP site:directories');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->preExecute($input, $output);

    $sdOptions = SiteDetectorOptions::fromInput($input);
    $dirNames = $this->siteDetector()
      ->getSiteDirNames($sdOptions);

    if (count($dirNames) === 0) {
      $this->logger->warning('No Drupal sites found.');
      return 0;
    }

    foreach ($dirNames as $dirName) {
      $output->writeln($dirName);
    }

    return 0;
  }

}
