<?php

namespace Drall\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to get a list of site aliases in the Drupal installation.
 */
class SiteAliasesCommand extends BaseCommand {

  protected function configure() {
    parent::configure();

    $this->setName('site:aliases');
    $this->setAliases(['sa']);
    $this->setDescription('Get a list of site aliases.');
    $this->addUsage('site:aliases');
    $this->addUsage('--drall-group=GROUP site:aliases');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->preExecute($input, $output);

    $aliases = $this->siteDetector()
      ->getSiteAliases(
        $this->getDrallGroup($input),
        $this->getDrallFilter($input),
      );

    if (count($aliases) === 0) {
      $this->logger->warning('No site aliases found.');
      return 0;
    }

    foreach ($aliases as $alias) {
      $output->writeln($alias);
    }

    return 0;
  }

}
