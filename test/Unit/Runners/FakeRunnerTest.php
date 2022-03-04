<?php

use Drall\Runners\FakeRunner;
use Drall\TestCase;

/**
 * @covers \Drall\Runners\FakeRunner
 */
class FakeRunnerTest extends TestCase {

  public function testSetExitCode() {
    $runner = new FakeRunner();

    $this->assertEquals(0, $runner->execute('foo'));
    $this->assertSame($runner, $runner->setExitCode(3));
    $this->assertEquals(3, $runner->execute('foo'));
    $this->assertSame($runner, $runner->setExitCode(19));
    $this->assertEquals(19, $runner->execute('foo'));
  }

  public function testCommandHistory() {
    $runner = new FakeRunner();
    $runner->execute('foo');
    $runner->execute('bar');
    $runner->execute('baz');

    $this->assertEquals(['foo', 'bar', 'baz'], $runner->commandHistory());
  }

}
