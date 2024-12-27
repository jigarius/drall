<?php

namespace Drall;

use DrupalFinder\DrupalFinderComposerRuntime;
use PHPUnit\Framework\TestCase as TestCaseBase;

/**
 * Drall Test Case.
 */
abstract class TestCase extends TestCaseBase {

  const PATH_PROJECT = '/opt/drall';

  const PATH_FIXTURES = '/opt/drall/test/fixtures';

  const PATH_DRUPAL = '/opt/drupal';

  const PATH_NO_DRUPAL = '/opt/no-drupal';

  const PATH_EMPTY_DRUPAL = '/opt/empty-drupal';

  /**
   * Normalizes console output by stripping unimportant spaces.
   *
   * @param string $output
   *   Raw output.
   *
   * @return string
   *   Normalized output.
   */
  private static function normalizeString(string $output): string {
    return preg_replace('@(\s+)\n@', "\n", $output);
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

  protected function createDrupalFinderStub(?string $root = NULL): DrupalFinderComposerRuntime {
    $root ??= static::PATH_DRUPAL;
    $drupalFinder = $this->createStub(DrupalFinderComposerRuntime::class);
    $drupalFinder->method('getComposerRoot')->willReturn($root);
    $drupalFinder->method('getDrupalRoot')->willReturn("$root/web");
    $drupalFinder->method('getVendorDir')->willReturn("$root/vendor");
    return $drupalFinder;
  }

  protected function assertOutputEquals(string $expected, mixed $actual, string $message = ''): void {
    $this->assertEquals($expected, self::normalizeString($actual ?? ''), $message);
  }

  protected function assertOutputStartsWith(string $expected, mixed $actual, string $message = ''): void {
    $this->assertStringStartsWith($expected, self::normalizeString($actual ?? ''), $message);
  }

  protected function assertOutputContainsString(string $expected, mixed $actual, string $message = ''): void {
    $this->assertStringContainsString($expected, self::normalizeString($actual ?? ''), $message);
  }

}
