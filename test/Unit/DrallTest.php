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

  public function testVersion() {
    $app = new Drall();
    $this->assertMatchesRegularExpression(
      '/^\d+\.\d+.\d+(-(alpha|beta|rc)\d+)?$/',
      $app->getVersion()
    );

    $json_path = $this->projectDir() . DIRECTORY_SEPARATOR . 'composer.json';
    $json_data = json_decode(file_get_contents($json_path));

    $this->assertNotEmpty($json_data->version);
    $this->assertEquals($json_data->version, $app->getVersion());
  }

  public function testDefaultInputOptions() {
    $app = new Drall();
    $options = $app->getDefinition()->getOptions();

    $this->assertArrayNotHasKey('quiet', $options);
    $this->assertArrayNotHasKey('verbose', $options);
    $this->assertArrayHasKey('drall-verbose', $options);
    $this->assertArrayHasKey('drall-debug', $options);
  }

  public function testOptionVerbosityNormal() {
    $tester = new ApplicationTester(new Drall());
    $tester->run(['command' => 'version']);
    $this->assertEquals(OutputInterface::VERBOSITY_NORMAL, $tester->getOutput()->getVerbosity());
  }

  public function testOptionVerbosityVerbose() {
    $tester = new ApplicationTester(new Drall());
    $tester->run(['command' => 'version', '--drall-verbose' => TRUE]);
    $this->assertEquals(OutputInterface::VERBOSITY_VERY_VERBOSE, $tester->getOutput()->getVerbosity());
  }

  public function testOptionVerbosityDebug() {
    $tester = new ApplicationTester(new Drall());
    $tester->run(['command' => 'version', '--drall-debug' => TRUE]);
    $this->assertEquals(OutputInterface::VERBOSITY_DEBUG, $tester->getOutput()->getVerbosity());
  }

}
