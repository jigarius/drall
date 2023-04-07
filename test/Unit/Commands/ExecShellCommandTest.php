<?php

use Drall\Commands\BaseExecCommand;
use Drall\Commands\ExecShellCommand;
use Drall\TestCase;

/**
 * @covers \Drall\Commands\ExecShellCommand
 * @covers \Drall\Commands\BaseExecCommand
 */
class ExecShellCommandTest extends TestCase {

  public function testExtendsBaseCommand() {
    $this->assertTrue(is_subclass_of(ExecShellCommand::class, BaseExecCommand::class));
  }

}
