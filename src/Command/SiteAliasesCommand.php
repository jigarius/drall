<?php

namespace Drall\Command;

use Drall\Model\SiteDetectorOptions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
  name: 'site:aliases',
  description: 'List all Drush site aliases.',
  aliases: ['sa']
)]
class SiteAliasesCommand extends BaseCommand {

  protected function configure() {
    parent::configure();
    $this->addUsage('site:aliases');
    $this->addUsage('--group=GROUP site:aliases');
    $this->addUsage('--filter=FILTER site:aliases');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->preExecute($input, $output);

    $sdOptions = SiteDetectorOptions::fromInput($input);
    $aliases = $this->siteDetector()
      ->getSiteAliases($sdOptions);

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
