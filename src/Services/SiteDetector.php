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

  /**
   * Get site aliases.
   *
   * @return Consolidation\SiteAlias\SiteAliasInterface[]
   *   Site aliases.
   */
  public function getSiteAliases(): array {
    return $this->siteAliasManager()->getMultiple();
  }

  /**
   * Get site names derived from aliases.
   *
   * If there are aliases like @foo.dev and @foo.prod, then @foo part is
   * considered the site name.
   *
   * @return array
   *   An array of site alias names with the @ prefix.
   */
  public function getSiteAliasNames(): array {
    $result = array_map(function ($alias) {
      return explode('.', $alias->name())[0];
    }, $this->siteAliasManager()->getMultiple());

    return array_unique(array_values($result));
  }

}
