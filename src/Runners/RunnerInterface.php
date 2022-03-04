<?php

namespace Drall\Runners;

interface RunnerInterface {

  /**
   * Executes a command and returns the exit code.
   *
   * @param string $command
   *   A command.
   *
   * @return int
   *   Exit code issued by the command.
   */
  public function execute(string $command): int;

}
