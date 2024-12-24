<?php

use Drall\IntegrationTestCase;

/**
 * @covers \Drall\IntegrationTestCase
 */
class IntegrationTestCaseTest extends IntegrationTestCase {

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
