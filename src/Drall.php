<?php

namespace Drall;

use Consolidation\SiteAlias\SiteAliasManagerInterface;
use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Commands\ExecCommand;
use Drall\Services\SiteDetector;
use Drush\SiteAlias\SiteAliasFileLoader;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Drall\Commands\SiteDirectoriesCommand;
use Drall\Commands\SiteAliasesCommand;

final class Drall extends Application {

  const NAME = 'Drall';

  /**
   * Creates a Phpake Application instance.
   */
  public function __construct() {
    parent::__construct();

    $this->setName(self::NAME);
    $this->setVersion('0.0.0');

    $this->input = new ArgvInput();
    $this->output = new ConsoleOutput();
    $this->configureIO($this->input, $this->output);

    // @todo Use dependency injection.
    $siteDetector = new SiteDetector(
      $this->getDrupalFinder(),
      $this->getSiteAliasManager()
    );

    $cmd = new SiteDirectoriesCommand();
    $cmd->setSiteDetector($siteDetector);
    $this->add($cmd);

    $cmd = new SiteAliasesCommand();
    $cmd->setSiteDetector($siteDetector);
    $this->add($cmd);

    $cmd = new ExecCommand();
    $cmd->setSiteDetector($siteDetector);
    $this->add($cmd);
    $this->setDefaultCommand($cmd);
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
