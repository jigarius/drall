<?php

use Drall\Commands\BaseExecCommand;
use Drall\Commands\ExecDrushCommand;
use Drall\TestCase;

/**
 * @covers \Drall\Commands\ExecDrushCommand
 * @covers \Drall\Commands\BaseExecCommand
 */
class ExecDrushCommandTest extends TestCase {

  public function testExtendsBaseCommand() {
    $this->assertTrue(is_subclass_of(ExecDrushCommand::class, BaseExecCommand::class));
  }

}
