<?php

namespace Unit\Traits;

use Drall\Runners\FakeRunner;
use Drall\TestCase;
use Drall\Traits\RunnerAwareTrait;

/**
 * @covers \Drall\Traits\RunnerAwareTrait
 */
class RunnerAwareTraitTest extends TestCase {

  public function testRunner() {
    $subject = $this->getMockForTrait(RunnerAwareTrait::class);
    $runner = new FakeRunner();
    $subject->setRunner($runner);

    $this->assertSame($runner, $subject->runner());
  }

  public function testRunnerNotSet() {
    $this->expectException(\BadMethodCallException::class);
    $subject = $this->getMockForTrait(RunnerAwareTrait::class);
    $subject->runner();
  }

}
