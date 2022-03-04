<?php

namespace Drall\Runners;

/**
 * A fake runner that simply collects commands.
 */
class FakeRunner implements RunnerInterface {

  protected array $commands = [];

  protected int $exitCode = 0;

  public function execute(string $command): int {
    $this->commands[] = $command;
    return $this->exitCode;
  }

  /**
   * Set the exit code to return for the next call to ::execute().
   *
   * @param int $exitCode
   *   Exit code.
   *
   * @return $this
   *   The runner.
   */
  public function setExitCode(int $exitCode): self {
    $this->exitCode = $exitCode;
    return $this;
  }

  /**
   * List of commands received by the runner.
   *
   * @return array
   *   Command history.
   */
  public function commandHistory(): array {
    return $this->commands;
  }

}
