<?php

namespace Drall\Services;

use Consolidation\Filter\FilterOutputData;
use Consolidation\Filter\LogicalOpFactory;
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
   * @param string|null $filter
   *   A filter expression.
   *
   * @return array
   *   Site directory names.
   */
  public function getSiteDirNames(
    string $group = NULL,
    ?string $filter = NULL,
  ): array {
    if (!$sitesFile = $this->getSitesFile($group)) {
      return [];
    }

    $result = $sitesFile->getDirNames();
    if ($filter) {
      $result = $this->filter($result, $filter);
    }

    return $result;
  }

  /**
   * Get a list of site URIs.
   *
   * @param string|null $group
   *   A site group, if any.
   * @param string|null $filter
   *   A filter expression.
   * @param bool $unique
   *   Whether to return unique keys only.
   *
   * @return array
   *   Keys from the $sites array.
   */
  public function getSiteKeys(
    string $group = NULL,
    ?string $filter = NULL,
    bool $unique = FALSE,
  ): array {
    if (!$sitesFile = $this->getSitesFile($group)) {
      return [];
    }

    $result = $sitesFile->getKeys($unique);
    if ($filter) {
      $result = $this->filter($result, $filter);
    }

    return $result;
  }

  /**
   * Get site aliases.
   *
   * @param string|null $group
   *   A site group, if any.
   * @param string|null $filter
   *   A filter expression.
   *
   * @return string[]
   *   Site aliases.
   */
  public function getSiteAliases(
    ?string $group = NULL,
    ?string $filter = NULL,
  ): array {
    $result = array_values($this->siteAliasManager()->getMultiple());

    if ($group) {
      $result = array_filter($result, function ($alias) use ($group) {
        return in_array($group, $alias->get('drall.groups') ?? []);
      });
    }

    $result = array_map(fn($a) => $a->name(), $result);
    if ($filter) {
      $result = $this->filter($result, $filter);
    }

    return $result;
  }

  /**
   * Get site names derived from aliases.
   *
   * If there are aliases like @foo.dev and @foo.prod, then @foo part is
   * considered the site name.
   *
   * @param string|null $group
   *   A site group, if any.
   * @param string|null $filter
   *   A filter expression.
   *
   * @return array
   *   An array of site alias names with the @ prefix.
   */
  public function getSiteAliasNames(
    ?string $group = NULL,
    ?string $filter = NULL,
  ): array {
    $result = array_map(function ($siteAlias) {
      return explode('.', $siteAlias)[0];
    }, $this->getSiteAliases($group));

    $result = array_unique(array_values($result));
    if ($filter) {
      $result = $this->filter($result, $filter);
    }

    return $result;
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

  /**
   * Filter data by expressions.
   *
   * @param array $data
   *   The data.
   * @param string $expression
   *   A filter expression.
   * @param string $default_filter_field
   *   The default field by which to filter.
   *
   * @return array
   *   Filtered data.
   *
   * @see https://packagist.org/packages/consolidation/filter-via-dot-access-data
   */
  private function filter(
    array $data,
    string $expression,
    string $default_filter_field = 'value'
  ): array {
    if (empty($data)) {
      return $data;
    }

    if ($is_flat = !is_array(reset($data))) {
      $data = array_map(fn($r) => [$default_filter_field => $r], $data);
    }

    $factory = LogicalOpFactory::get();
    $op = $factory->evaluate($expression, $default_filter_field);
    $expression = new FilterOutputData();

    $result = $expression->filter($data, $op);

    if ($is_flat) {
      $result = array_column($result, $default_filter_field);
    }

    return $result;
  }

}
