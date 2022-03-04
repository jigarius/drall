<?php

namespace Drall\Test\Integration\Commands;

use Drall\IntegrationTestCase;

/**
 * @covers \Drall\Commands\SiteDirectoriesCommand
 */
class SiteDirectoriesCommandTest extends IntegrationTestCase {

  /**
   * Run site:directories with no Drupal installation.
   */
  public function testWithNoDrupal(): void {
    chdir('/tmp');
    $output = shell_exec('drall site:directories');
    $this->assertOutputEquals("[warning] No Drupal sites found." . PHP_EOL, $output);
  }

  /**
   * Run site:directories with a Drupal installation.
   */
  public function testExecute(): void {
    $output = shell_exec('drall site:directories');
    $this->assertOutputEquals(<<<EOF
default
donnie
leo
mikey
ralph

EOF, $output);
  }

  /**
   * Run site:directories with --drall-group.
   */
  public function testWithGroup(): void {
    $output = shell_exec('drall site:directories --drall-group=bluish');
    $this->assertOutputEquals(<<<EOF
donnie
leo

EOF, $output);
  }

}
