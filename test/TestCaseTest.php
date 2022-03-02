<?php

use Drall\TestCase;

/**
 * @covers Drall\TestCase
 */
class TestCaseTest extends TestCase {

  public function testCreateTempFile() {
    $path = static::createTempFile('Bunny Wabbit');
    $this->assertFileExists($path);
    $this->assertEquals('Bunny Wabbit', file_get_contents($path));
  }

  public function testFixturesDir() {
    $this->assertEquals(
      dirname(__FILE__) . '/fixtures',
      $this->fixturesDir()
    );
  }

}
