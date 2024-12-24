<?php

namespace Drall;

class IntegrationTestCase extends TestCase {

  /**
   * Asserts whether shell output equality.
   *
   * Ignores empty spaces at the end of lines.
   *
   * @param string $expected
   *   Expected output.
   * @param mixed $actual
   *   Actual output.
   * @param string $message
   *   Error message.
   */
  protected function assertOutputEquals(string $expected, mixed $actual, string $message = ''): void {
    $actual = preg_replace('@(\s+)\n@', "\n", $actual);
    $this->assertEquals($expected, $actual, $message);
  }

}
