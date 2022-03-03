<?php

namespace Drall;

use PHPUnit\Framework\TestCase as TestCaseBase;

/**
 * Drall Test Case.
 */
abstract class TestCase extends TestCaseBase {

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
   * Creates a temporary file with the given contents.
   *
   * @param string $data
   *   Contents to write to the file.
   *
   * @return string
   *   Path to the file.
   */
  protected static function createTempFile(string $data): string {
    $path = tempnam(sys_get_temp_dir(), 'Phpake.');
    file_put_contents($path, $data);
    return $path;
  }

}
