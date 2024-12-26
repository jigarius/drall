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
    $expected = <<<EOF
foo
bar
baz

EOF;
    // For some reason, drush's output has spaces before EOL.
    $actual = "foo \nbar \nbaz \n";

    $this->assertOutputEquals($expected, $actual);
  }

}
