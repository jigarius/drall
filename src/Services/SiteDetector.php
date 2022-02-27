<?php

namespace Drall\Services;

use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Consolidation\SiteAlias\SiteAliasManagerInterface;
use Drall\Traits\DrupalFinderAwareTrait;
use DrupalFinder\DrupalFinder;
use Drall\Models\SitesFile;

class SiteDetector {

  use SiteAliasManagerAwareTrait;
  use DrupalFinderAwareTrait;

  public function __construct(
    DrupalFinder $drupalFinder,
    SiteAliasManagerInterface $siteAliasManager
  ) {
    $this->setDrupalFinder($drupalFinder);
    $this->setSiteAliasManager($siteAliasManager);
  }

  public function getDrupalRoot(): string {
    if (!$drupalRoot = $this->drupalFinder->getDrupalRoot()) {
      throw new \RuntimeException('Could not detect a Drupal installation.');
    }

    return $drupalRoot;
  }

  public function getSiteDirNames(): array {
    $drupalRoot = $this->getDrupalRoot();
    $sitesFile = new SitesFile("$drupalRoot/sites/sites.php");
    return $sitesFile->getDirNames();
  }

  public function getSiteAliases(): array {
    return $this->siteAliasManager()->getMultiple();
  }

}
