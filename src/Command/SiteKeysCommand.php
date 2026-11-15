<?php

namespace Drall\Command;

use Drall\Model\SiteDetectorOptions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
  name: 'site:keys',
  description: 'List the keys of the $sites array.',
  aliases: ['sk'],
  usages: [
    'site:keys',
    '--group=GROUP site:keys',
    '--filter=FILTER site:keys',
  ],
)]
class SiteKeysCommand extends BaseCommand {

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->preExecute($input, $output);

    $sdOptions = SiteDetectorOptions::fromInput($input);
    $keys = $this->siteDetector()
      ->getSiteKeys($sdOptions);

    if (count($keys) === 0) {
      $this->logger->warning('No Drupal sites found.');
      return 0;
    }

    foreach ($keys as $key) {
      $output->writeln($key);
    }

    return 0;
  }

}
