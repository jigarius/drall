<?php

namespace Drall;

use Consolidation\SiteAlias\SiteAliasManagerInterface;
use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Commands\ExecCommand;
use Drall\Services\SiteDetector;
use DrupalCodeGenerator\Logger\ConsoleLogger;
use Drush\SiteAlias\SiteAliasFileLoader;
use DrupalFinder\DrupalFinder;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Drall\Commands\SiteDirectoriesCommand;
use Drall\Commands\SiteAliasesCommand;
use Symfony\Component\Console\Output\OutputInterface;

final class Drall extends Application {

  const NAME = 'Drall';

  const VERSION = '0.5.0';

  use LoggerAwareTrait;

  /**
   * Creates a Phpake Application instance.
   */
  public function __construct(
    ?InputInterface $input = NULL,
    ?OutputInterface $output = NULL
  ) {
    parent::__construct();

    $input = $input ?? new ArgvInput();
    $output = $output ?? new ConsoleOutput();

    $this->setName(self::NAME);
    $this->setVersion(self::VERSION);
    $this->configureIO($input, $output);

    // @todo Use dependency injection.
    $siteDetector = new SiteDetector(
      $this->getDrupalFinder(),
      $this->getSiteAliasManager()
    );

    $this->setLogger(new ConsoleLogger($output));

    $cmd = new SiteDirectoriesCommand();
    $cmd->setSiteDetector($siteDetector);
    $cmd->setLogger($this->logger);
    $this->add($cmd);

    $cmd = new SiteAliasesCommand();
    $cmd->setSiteDetector($siteDetector);
    $cmd->setLogger($this->logger);
    $this->add($cmd);

    $cmd = new ExecCommand();
    $cmd->setSiteDetector($siteDetector);
    $cmd->setLogger($this->logger);
    $this->add($cmd);
  }

  private function getDrupalFinder(): DrupalFinder {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    return $drupalFinder;
  }

  private function getSiteAliasManager(): SiteAliasManagerInterface {
    $aliasFileLoader = new SiteAliasFileLoader();
    $siteAliasManager = new SiteAliasManager($aliasFileLoader);
    $siteAliasManager->addSearchLocation('drush/sites');
    return $siteAliasManager;
  }

}
