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
  usages: [
    'site:directories',
    '--group=GROUP site:directories',
  ],
)]
class SiteDirectoriesCommand extends BaseCommand {

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
