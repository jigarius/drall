<?php

namespace Drall\Traits;

use Drall\Runners\RunnerInterface;

trait RunnerAwareTrait {

  /**
   * The runner to use for executing commands.
   *
   * @var \Drall\Runners\RunnerInterface|null
   */
  protected ?RunnerInterface $runner;

  public function setRunner(RunnerInterface $runner): self {
    $this->runner = $runner;
    return $this;
  }

  public function runner(): RunnerInterface {
    if (!isset($this->runner)) {
      throw new \BadMethodCallException('Cannot call runner() before calling setRunner().');
    }

    return $this->runner;
  }

}
