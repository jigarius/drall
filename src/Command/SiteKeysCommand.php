<?php

namespace Drall\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to get a list of keys in the $sites array.
 */
class SiteKeysCommand extends BaseCommand {

  protected function configure() {
    parent::configure();

    $this->setName('site:keys');
    $this->setAliases(['sk']);
    $this->setDescription('List the keys of the $sites array.');
    $this->addUsage('site:keys');
    $this->addUsage('--drall-group=GROUP site:keys');
    $this->addUsage('--drall-filter=FILTER site:keys');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
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
