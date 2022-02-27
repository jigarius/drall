<?php

namespace Drall\Commands;

use Consolidation\SiteAlias\SiteAliasManager;
use Drush\SiteAlias\SiteAliasFileLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to get a list of site aliases in the Drupal installation.
 */
class SiteAliasesCommand extends BaseCommand {

  protected function configure() {
    $this->setName('site:aliases');
    $this->setAliases(['sa']);
    $this->setDescription('Get a list of site aliases.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $aliases = $this->siteDetector()->getSiteAliases();

    if (count($aliases) === 0) {
      $output->writeln('No site aliases found.');
      return 0;
    }

    foreach ($aliases as $alias) {
      $output->writeln($alias->name());
    }

    return 0;
  }

}
