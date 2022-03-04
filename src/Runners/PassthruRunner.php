<?php

namespace Drall\Runners;

class PassthruRunner implements RunnerInterface {

  public function execute(string $command): int {
    passthru($command, $exitCode);
    return $exitCode;
  }

}
