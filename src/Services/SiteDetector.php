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

  /**
   * Get a list of site directory names for a site group.
   *
   * @param string|null $group
   *   A site group, if any.
   *
   * @return array
   *   Contents of the $sites variable.
   */
  public function getSiteDirNames(string $group = NULL): array {
    if (!$sitesFile = $this->getSitesFile($group)) {
      return [];
    }

    return $sitesFile->getDirNames();
  }

  /**
   * Get site aliases.
   *
   * @param string|null $group
   *   A site group, if any.
   *
   * @return string[]
   *   Site aliases.
   */
  public function getSiteAliases(string $group = NULL): array {
    $result = $this->siteAliasManager()->getMultiple();

    if ($group) {
      $result = array_filter($result, function ($alias) use ($group) {
        return in_array($group, $alias->get('drall.groups') ?? []);
      });
    }

    return array_map(fn($a) => $a->name(), $result);
  }

  /**
   * Get site names derived from aliases.
   *
   * If there are aliases like @foo.dev and @foo.prod, then @foo part is
   * considered the site name.
   *
   * @param string|null $group
   *   A site group, if any.
   *
   * @return array
   *   An array of site alias names with the @ prefix.
   */
  public function getSiteAliasNames(string $group = NULL): array {
    $result = array_map(function ($siteAlias) {
      return explode('.', $siteAlias)[0];
    }, $this->getSiteAliases($group));
    return array_unique(array_values($result));
  }

  /**
   * Gets the path to the applicable drush binary.
   *
   * @return string
   *   Path/to/drush.
   */
  public function getDrushPath(): string {
    if (!$vendorDir = $this->drupalFinder->getVendorDir()) {
      // This should only happen when drall is installed globally and not in a
      // specific Drupal project.
      return 'drush';
    }

    return "$vendorDir/bin/drush";
  }

  private function getSitesFile($group = NULL): ?SitesFile {
    if (!$drupalRoot = $this->drupalFinder->getDrupalRoot()) {
      return NULL;
    }

    $basename = 'sites.php';
    if ($group) {
      $basename = "sites.$group.php";
    }

    return new SitesFile("$drupalRoot/sites/$basename");
  }

}
