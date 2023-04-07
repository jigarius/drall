<?php

namespace Drall\Test\Integration;

use Drall\Drall;
use Drall\IntegrationTestCase;

/**
 * @covers \Drall\Drall
 */
class DrallTest extends IntegrationTestCase {

  public function testVersion() {
    $output = shell_exec('drall --version');
    $this->assertEquals(Drall::NAME . ' ' . Drall::VERSION . PHP_EOL, $output);
  }

  /**
   * Run drall with a command it doesn't recognize.
   */
  public function testUnrecognizedCommand() {
    $output = shell_exec('drall st 2>&1');
    $this->assertOutputEquals(<<<EOT

  The command "st" was not understood. Did you mean one of the following?
  drall exec st
  drall exec drush st
  Alternatively, run "drall list" to see a list of all available commands.

EOT, $output);
  }

}
