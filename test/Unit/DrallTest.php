<?php

namespace Unit;

use Drall\Drall;
use Drall\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * @covers \Drall\Drall
 */
class DrallTest extends TestCase {

  public function testName() {
    $app = new Drall();
    $this->assertSame(Drall::NAME, $app->getName());
  }

  public function testDefaultInputOptions() {
    $app = new Drall();
    $options = $app->getDefinition()->getOptions();

    $this->assertEquals([
      'help',
      'verbose',
      'version',
      'ansi',
      'debug',
    ], array_keys($options));
  }

  public function testOptionVerbosityNormal() {
    $tester = new ApplicationTester(new Drall());
    $tester->run(['command' => 'version']);
    $this->assertEquals(OutputInterface::VERBOSITY_NORMAL, $tester->getOutput()->getVerbosity());
  }

  public function testOptionVerbosityVerbose() {
    $tester = new ApplicationTester(new Drall());
    $tester->run(['command' => 'version', '--verbose' => TRUE]);
    $this->assertEquals(OutputInterface::VERBOSITY_VERY_VERBOSE, $tester->getOutput()->getVerbosity());
  }

  public function testOptionVerbosityDebug() {
    $tester = new ApplicationTester(new Drall());
    $tester->run(['command' => 'version', '--debug' => TRUE]);
    $this->assertEquals(OutputInterface::VERBOSITY_DEBUG, $tester->getOutput()->getVerbosity());
  }

}
