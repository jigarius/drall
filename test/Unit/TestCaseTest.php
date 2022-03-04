<?php

namespace Unit;

use Drall\TestCase;

/**
 * @covers Drall\TestCase
 */
class TestCaseTest extends TestCase {

  public function testDrupalDir() {
    $this->assertEquals('/opt/drupal', $this->drupalDir());
  }

  public function testCreateTempFile() {
    $path = static::createTempFile('Bunny Wabbit');
    $this->assertFileExists($path);
    $this->assertEquals('Bunny Wabbit', file_get_contents($path));
  }

  public function testFixturesDir() {
    $this->assertEquals(
      dirname(__DIR__) . '/fixtures',
      $this->fixturesDir()
    );
  }

  public function testProjectDir() {
    $this->assertEquals(
      dirname(dirname(__DIR__)),
      $this->projectDir()
    );
  }

}
