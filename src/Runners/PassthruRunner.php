<?php

namespace Drall\Runners;

/**
 * A runner that uses PHP's passthru() to execute commands.
 */
class PassthruRunner implements RunnerInterface {

  public function execute(string $command): int {
    passthru($command, $exitCode);
    return $exitCode;
  }

  public function getOutput(): ?string {
    return NULL;
  }

}
