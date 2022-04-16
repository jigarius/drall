<?php

namespace Drall\Runners;

/**
 * A runner that uses PHP's exec() to execute commands.
 */
class ExecRunner implements RunnerInterface {

  protected ?array $output;

  public function __construct() {
    $this->output = NULL;
  }

  public function execute(string $command): int {
    $this->output = [];
    exec($command, $this->output, $exitCode);
    return $exitCode;
  }

  public function getOutput(): string {
    return implode(PHP_EOL, $this->output) . PHP_EOL;
  }

}
