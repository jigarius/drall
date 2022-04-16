<?php

use Drall\Runners\ExecRunner;
use Drall\TestCase;

/**
 * @covers \Drall\Runners\ExecRunner
 */
class ExecRunnerTest extends TestCase {

  public function testExecute() {
    $runner = new ExecRunner();
    $this->assertEquals(0, $runner->execute('echo "Cowabunga!"'));
  }

  public function testGetOutput() {
    $runner = new ExecRunner();
    $runner->execute('echo "Cowabunga!"');
    $this->assertEquals(
      "Cowabunga!\n",
      $runner->getOutput()
    );
  }

}
