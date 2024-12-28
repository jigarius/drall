<?php

namespace Unit;

use Drall\TestCase;

/**
 * @covers \Drall\TestCase
 */
class TestCaseTest extends TestCase {

  public function testCreateTempFilePath() {
    $path = static::createTempFilePath();
    $this->assertEquals(sys_get_temp_dir(), dirname($path));
    $this->assertStringStartsWith('Drall.', basename($path));
  }

  public function testCreateTempFile() {
    $path = static::createTempFile('Bunny Wabbit');
    $this->assertFileExists($path);
    $this->assertEquals('Bunny Wabbit', file_get_contents($path));
  }

  public function testAssertOutputEquals() {
    // For some reason, drush's output ($actual) has spaces before EOL.
    // This assertion respects leading spaces and ignores trailing spaces.
    $this->assertOutputEquals(<<<EOT
[notice] Hakuna matata.
  - bunny
  - wabbit

EOT, "[notice] Hakuna matata. \n  - bunny \n  - wabbit \n");
  }

  public function testAssertOutputStartsWith() {
    // For some reason, drush's output ($actual) has spaces before EOL.
    // This assertion respects leading spaces and ignores trailing spaces.
    $this->assertOutputStartsWith(
      '[notice] Hakuna matata.' . PHP_EOL,
      "[notice] Hakuna matata. \n  - bunny \n  - wabbit \n",
    );
  }

  public function testAssertOutputContainsString() {
    // For some reason, drush's output ($actual) has spaces before EOL.
    // This assertion respects leading spaces and ignores trailing spaces.
    $this->assertOutputContainsString(
      '  - bunny' . PHP_EOL,
      "[notice] Hakuna matata. \n  - bunny \n  - wabbit \n",
    );
  }

}
