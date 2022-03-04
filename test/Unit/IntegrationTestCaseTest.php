<?php

use Drall\IntegrationTestCase;

/**
 * @covers \Drall\IntegrationTestCase
 */
class IntegrationTestCaseTest extends IntegrationTestCase {

  public function testCwd() {
    chdir('/tmp');

    $this->assertEquals('/tmp', getcwd());

    // Takes us to the Drupal directory.
    $this->setUp();

    $this->assertEquals($this->drupalDir(), getcwd());

    // Takes us to where we were, i.e. /tmp.
    $this->tearDown();

    $this->assertEquals('/tmp', getcwd());
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
