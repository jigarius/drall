<?php

namespace Drall;

use DrupalFinder\DrupalFinderComposerRuntime;
use PHPUnit\Framework\TestCase as TestCaseBase;

/**
 * Drall Test Case.
 */
abstract class TestCase extends TestCaseBase {

  /**
   * Original current working directory.
   *
   * @var string
   */
  protected string $cwd = '/';

  protected function setUp(): void {
    $this->cwd = getcwd();
    chdir($this->drupalDir());
  }

  protected function tearDown(): void {
    chdir($this->cwd);
  }

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

  protected function createDrupalFinderStub(?string $root = NULL): DrupalFinderComposerRuntime {
    $root ??= $this->drupalDir();
    $drupalFinder = $this->createStub(DrupalFinderComposerRuntime::class);
    $drupalFinder->method('getComposerRoot')->willReturn($root);
    $drupalFinder->method('getDrupalRoot')->willReturn("$root/web");
    $drupalFinder->method('getVendorDir')->willReturn("$root/vendor");
    return $drupalFinder;
  }

}
