<?php

namespace Drall;

use PHPUnit\Framework\TestCase as TestCaseBase;

/**
 * Drall Test Case.
 */
abstract class TestCase extends TestCaseBase {

  /**
   * Get the path to the Drupal project root.
   *
   * @return string
   *   /path/to/drupal.
   */
  protected function drupalDir(): string {
    return getenv('DRUPAL_PATH');
  }

  /**
   * Get the path to the project's root directory.
   *
   * @return string
   *   /path/to/root.
   */
  protected function projectDir(): string {
    return dirname(__DIR__);
  }

  /**
   * Get the path to the fixtures directory.
   *
   * @return string
   *   /path/to/fixtures.
   */
  protected function fixturesDir(): string {
    return dirname(__DIR__) . '/test/fixtures';
  }

  /**
   * Creates a temporary file path.
   *
   * @example
   * /path/to/tmp/Drall.random
   *
   * @return string
   *   A path.
   */
  protected function createTempFilePath(): string {
    return tempnam(sys_get_temp_dir(), 'Drall.');
  }

  /**
   * Creates a temporary file with the given contents.
   *
   * @param string $data
   *   Contents to write to the file.
   *
   * @return string
   *   Path to the file.
   */
  protected function createTempFile(string $data): string {
    $path = $this->createTempFilePath();
    file_put_contents($path, $data);
    return $path;
  }

}
