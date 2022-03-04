<?php

use Drall\Runners\PassthruRunner;
use Drall\TestCase;

/**
 * @covers \Drall\Runners\PassthruRunner
 */
class PassthruRunnerTest extends TestCase {

  public function testExecute() {
    $runner = new PassthruRunner();
    $this->assertEquals(0, $runner->execute('ls > /dev/null'));
  }

}
