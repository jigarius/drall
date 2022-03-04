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

}
