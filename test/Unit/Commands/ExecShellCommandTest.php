<?php

use Drall\Commands\BaseExecCommand;
use Drall\Commands\ExecCommand;
use Drall\TestCase;

/**
 * @covers \Drall\Commands\ExecCommand
 * @covers \Drall\Commands\BaseExecCommand
 */
class ExecShellCommandTest extends TestCase {

  public function testExtendsBaseCommand() {
    $this->assertTrue(is_subclass_of(ExecCommand::class, BaseExecCommand::class));
  }

}
