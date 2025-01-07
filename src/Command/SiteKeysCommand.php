<?php

namespace Drall\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
  name: 'site:keys',
  description: 'List the keys of the $sites array.',
  aliases: ['sk'],
)]
class SiteKeysCommand extends BaseCommand {

  protected function configure() {
    parent::configure();
    $this->addUsage('site:keys');
    $this->addUsage('--group=GROUP site:keys');
    $this->addUsage('--filter=FILTER site:keys');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->preExecute($input, $output);

    $keys = $this->siteDetector()
      ->getSiteKeys(
        $this->getDrallGroup($input),
        $this->getDrallFilter($input),
      );

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
