<?php

namespace Drall\Service;

use Consolidation\Filter\FilterOutputData;
use Consolidation\Filter\LogicalOpFactory;
use Consolidation\SiteAlias\SiteAliasManager;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Consolidation\SiteAlias\SiteAliasManagerInterface;
use Drall\Model\SiteDetectorOptions;
use Drall\Model\SitesFile;
use Drall\Trait\DrupalFinderAwareTrait;
use DrupalFinder\DrupalFinderComposerRuntime;
use Drush\SiteAlias\SiteAliasFileLoader;

class SiteDetector {

  use SiteAliasManagerAwareTrait;
  use DrupalFinderAwareTrait;

  public function __construct(
    ?DrupalFinderComposerRuntime $drupalFinder = NULL,
    ?SiteAliasManagerInterface $siteAliasManager = NULL,
  ) {
    if (!$drupalFinder) {
      $drupalFinder = new DrupalFinderComposerRuntime();
    }
    $this->setDrupalFinder($drupalFinder);

    if (!$siteAliasManager) {
      $siteAliasManager = new SiteAliasManager(new SiteAliasFileLoader());
      $siteAliasManager->addSearchLocation($drupalFinder->getComposerRoot() . '/drush/sites');
    }
    $this->setSiteAliasManager($siteAliasManager);
  }

  /**
   * Get a list of site directory names for a site group.
   *
   * @param \Drall\Model\SiteDetectorOptions|null $options
   *   Options.
   *
   * @return array
   *   Site directory names.
   */
  public function getSiteDirNames(?SiteDetectorOptions $options = NULL): array {
    $options = $options ?? new SiteDetectorOptions();

    if (!$sitesFile = $this->getSitesFile($options->getGroup())) {
      return [];
    }

    $result = $sitesFile->getDirNames();
    $result = $this->applyFilter($result, $options->getFilter() ?? '');
    return $this->applyRange($result, $options->getOffset(), $options->getLimit());
  }

  /**
   * Get a list of site URIs.
   *
   * @param \Drall\Model\SiteDetectorOptions|null $options
   *   Options.
   * @param bool $unique
   *   Whether to return unique keys only.
   *
   * @return array
   *   Keys from the $sites array.
   */
  public function getSiteKeys(?SiteDetectorOptions $options = NULL, bool $unique = FALSE): array {
    $options = $options ?? new SiteDetectorOptions();
    if (!$sitesFile = $this->getSitesFile($options->getGroup())) {
      return [];
    }

    $result = $sitesFile->getKeys($unique);
    $result = $this->applyFilter($result, $options->getFilter() ?? '');
    return $this->applyRange($result, $options->getOffset(), $options->getLimit());
  }

  /**
   * Get site aliases.
   *
   * @param \Drall\Model\SiteDetectorOptions|null $options
   *   Options.
   *
   * @return string[]
   *   Site aliases.
   */
  public function getSiteAliases(?SiteDetectorOptions $options = NULL): array {
    $options = $options ?? new SiteDetectorOptions();
    // Use Drupal Finder to ensure that the Drupal is installed. This ensures
    // consistency in errors raised by methods that depend on sites.*.php.
    $this->drupalFinder()->getDrupalRoot();

    $result = array_values($this->siteAliasManager()->getMultiple());

    if ($group = $options->getGroup()) {
      $result = array_filter($result, function ($alias) use ($group) {
        return in_array($group, $alias->get('drall.groups') ?? []);
      });
    }

    $result = array_map(fn($a) => $a->name(), $result);
    $result = $this->applyFilter($result, $options->getFilter() ?? '');
    return $this->applyRange($result, $options->getOffset(), $options->getLimit());
  }

  /**
   * Get site names derived from aliases.
   *
   * If there are aliases like @foo.dev and @foo.prod, then @foo part is
   * considered the site name.
   *
   * @param \Drall\Model\SiteDetectorOptions|null $options
   *   Options.
   *
   * @return array
   *   An array of site alias names with the @ prefix.
   */
  public function getSiteAliasNames(?SiteDetectorOptions $options = NULL): array {
    $options = $options ?? new SiteDetectorOptions();

    // Certain options must be used only once in this method.
    // Thus, we do not forward them to ::getSiteAliases().
    $saOptions = new SiteDetectorOptions();
    $saOptions->setGroup($options->getGroup());

    $result = array_map(function ($siteAlias) {
      return explode('.', $siteAlias)[0];
    }, $this->getSiteAliases($saOptions));

    $result = array_unique(array_values($result));
    $result = $this->applyFilter($result, $options->getFilter() ?? '');
    return $this->applyRange($result, $options->getOffset(), $options->getLimit());
  }

  /**
   * Gets the path to the applicable drush binary.
   *
   * @return string
   *   Path/to/drush.
   */
  public function getDrushPath(): string {
    return $this->drupalFinder->getVendorDir() . "/bin/drush";
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
  private function applyFilter(
    array $data,
    string $expression,
    string $default_filter_field = 'value',
  ): array {
    if (empty($data) || empty($expression)) {
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

  /**
   * Get data after applying the given offset and limit.
   *
   * @param array $data
   *   The data.
   * @param int|null $offset
   *   An offset.
   * @param int|null $limit
   *   A limit.
   *
   * @return array
   *   The data after applying the range.
   */
  private function applyRange(array $data, ?int $offset, ?int $limit): array {
    if (is_null($offset) && is_null($limit)) {
      return $data;
    }

    return array_splice($data, $offset ?? 0, $limit);
  }

  /**
   * Detects sites in a "sites" directory.
   *
   * Builds a $sites array based on the contents of a given "sites" directory.
   * The detection is based on the presence of settings.php.
   *
   * @param string $path
   *   Path to a DRUPAL/sites directory.
   *
   * @return array<string, string>
   *   An associative array for use as $sites.
   */
  public static function detectFromDirectory(string $path): array {
    $pattern = implode(DIRECTORY_SEPARATOR, [$path, '*', 'settings.php']);

    $result = [];
    foreach (glob($pattern) as $item) {
      if (!is_file($item)) {
        continue;
      }

      $dirname = basename(dirname($item));
      $result[$dirname] = $dirname;
    }

    return $result;
  }

}
