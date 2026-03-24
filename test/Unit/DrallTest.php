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

  /**
   * @testdox Default input options are defined.
   */
  public function testDefaultInputOptions() {
    $app = new Drall();
    $options = $app->getDefinition()->getOptions();

    $this->assertEquals([
      'help',
      'quiet',
      'verbose',
      'version',
      'ansi',
      'no-interaction',
      'debug',
    ], array_keys($options));
  }

  /**
   * @testdox Verbosity quiet.
   */
  public function testVerbosityQuiet() {
    $tester = new ApplicationTester(new Drall());

    $tester->run(['command' => 'version', '--quiet' => TRUE]);
    $this->assertEquals(OutputInterface::VERBOSITY_QUIET, $tester->getOutput()->getVerbosity());

    $tester->run(['command' => 'version', '-q' => TRUE]);
    $this->assertEquals(OutputInterface::VERBOSITY_QUIET, $tester->getOutput()->getVerbosity());
  }

  /**
   * @testdox Verbosity level 0.
   */
  public function testVerbosityNormal() {
    $tester = new ApplicationTester(new Drall());
    $tester->run(['command' => 'version']);
    $this->assertEquals(OutputInterface::VERBOSITY_NORMAL, $tester->getOutput()->getVerbosity());
  }

  /**
   * @testdox Verbosity level 1.
   */
  public function testVerbosityVerbose() {
    $tester = new ApplicationTester(new Drall());

    $tester->run(['command' => 'version', '--verbose' => TRUE]);
    $this->assertEquals(OutputInterface::VERBOSITY_VERBOSE, $tester->getOutput()->getVerbosity());

    $tester->run(['command' => 'version', '-v' => TRUE]);
    $this->assertEquals(OutputInterface::VERBOSITY_VERBOSE, $tester->getOutput()->getVerbosity());
  }

  /**
   * @testdox Verbosity level 2.
   */
  public function testVerbosityVeryVerbose() {
    $tester = new ApplicationTester(new Drall());

    $tester->run(['command' => 'version', '--verbose' => 2]);
    $this->assertEquals(OutputInterface::VERBOSITY_VERY_VERBOSE, $tester->getOutput()->getVerbosity());

    $tester->run(['command' => 'version', '-vv' => TRUE]);
    $this->assertEquals(OutputInterface::VERBOSITY_VERY_VERBOSE, $tester->getOutput()->getVerbosity());
  }

  /**
   * @testdox Verbosity level 3.
   */
  public function testVerbosityDebug() {
    $tester = new ApplicationTester(new Drall());

    $tester->run(['command' => 'version', '--verbose' => 3]);
    $this->assertEquals(OutputInterface::VERBOSITY_DEBUG, $tester->getOutput()->getVerbosity());

    $tester->run(['command' => 'version', '-vvv' => TRUE]);
    $this->assertEquals(OutputInterface::VERBOSITY_DEBUG, $tester->getOutput()->getVerbosity());

    $tester->run(['command' => 'version', '-d']);
    $this->assertEquals(OutputInterface::VERBOSITY_DEBUG, $tester->getOutput()->getVerbosity());

    $tester->run(['command' => 'version', '--debug' => TRUE]);
    $this->assertEquals(OutputInterface::VERBOSITY_DEBUG, $tester->getOutput()->getVerbosity());
  }

}
