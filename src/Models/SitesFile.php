<?php

namespace Drall\Models;

/**
 * A sites.php file or equivalent.
 */
class SitesFile {

  protected string $path;

  protected array $entries;

  public function __construct(string $path) {
    $this->path = $path;
    $this->entries = $this->getEntries();
  }

  /**
   * Get the path to the sites file.
   *
   * @return string
   *   Path to the file.
   */
  public function getPath(): string {
    return $this->path;
  }

  /**
   * Get the value of the $sites variable.
   *
   * @return array
   *   Contents of the $sites array.
   */
  private function getEntries(): array {
    if (!is_file($this->path)) {
      throw new \RuntimeException("Cannot read sites file: $this->path");
    }

    require $this->path;

    if (!isset($sites) || !is_array($sites)) {
      throw new \RuntimeException("Site declarations not found in file: $this->path");
    }

    return $sites;
  }

  /**
   * Get an array of site directory names.
   *
   * @return array
   *   Site directory names.
   */
  public function getDirNames(): array {
    return array_values(array_unique($this->entries));
  }

  /**
   * Get keys of the $sites array.
   *
   * @param bool $unique
   *   If TRUE, only one key (the last one) will be returned for each site.
   *
   * @return array
   *   Site keys from $sites.
   */
  public function getKeys(bool $unique = FALSE): array {
    if (!$unique) {
      return array_keys($this->entries);
    }

    return array_keys(array_flip(array_flip($this->entries)));
  }

}
