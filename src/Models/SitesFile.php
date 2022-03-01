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
  private function getEntries() {
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
   * Get an array of site directories.
   *
   * @return array
   *   Site directory names.
   */
  public function getDirNames(): array {
    return array_unique(array_values($this->entries));
  }

}
