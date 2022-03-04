<?php

namespace Unit;

use Drall\Drall;
use Drall\TestCase;

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

}
