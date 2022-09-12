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

  /**
   * Converts an array of input into a $argv like array.
   *
   * @param array $input
   *   Array of input as expected by CommandTester::execute().
   *
   * @return array
   *   Array resembling $argv.
   *
   * @see \Drall\Commands\ExecDrushCommand::setArgv()
   */
  private static function arrayInputAsArgv(array $input): array {
    array_unshift($input, '/path/to/drall', 'exec');

    $argv = [];
    foreach ($input as $key => $value) {
      if (is_numeric($key) || $key === 'cmd') {
        $argv[] = $value;
        continue;
      }

      $argv[] = "$key=$value";
    }

    return $argv;
  }

}
