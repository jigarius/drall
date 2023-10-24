<?php

namespace Drall\Test\Integration\Commands;

use Drall\IntegrationTestCase;

/**
 * @covers \Drall\Command\SiteDirectoriesCommand
 */
class SiteKeysCommandTest extends IntegrationTestCase {

  /**
   * Run site:keys with no Drupal installation.
   */
  public function testWithNoDrupal(): void {
    chdir('/tmp');
    $output = shell_exec('drall site:keys');
    $this->assertOutputEquals("[warning] No Drupal sites found." . PHP_EOL, $output);
  }

  /**
   * Run site:keys with a Drupal installation.
   */
  public function testExecute(): void {
    $output = shell_exec('drall site:keys');
    $this->assertOutputEquals(<<<EOF
tmnt.com
cowabunga.com
tmnt.drall.local
donatello.com
8080.donatello.com
donnie.drall.local
leonardo.com
leo.drall.local
michelangelo.com
mikey.drall.local
raphael.com
ralph.drall.local

EOF, $output);
  }

  /**
   * Run site:keys with --drall-filter.
   */
  public function testExecuteWithFilter(): void {
    $output = shell_exec('drall site:keys --drall-filter="value~=@.local\$@"');
    $this->assertOutputEquals(<<<EOF
tmnt.drall.local
donnie.drall.local
leo.drall.local
mikey.drall.local
ralph.drall.local

EOF, $output);
  }

  /**
   * Run site:keys with --drall-group.
   */
  public function testWithGroup(): void {
    $output = shell_exec('drall site:keys --drall-group=bluish');
    $this->assertOutputEquals(<<<EOF
donatello.com
8080.donatello.com
donnie.drall.local
leonardo.com
leo.drall.local

EOF, $output);
  }

  /**
   * Run site:keys with DRALL_GROUP env var.
   */
  public function testWithGroupEnvVar(): void {
    $output = shell_exec('DRALL_GROUP=bluish drall site:keys');
    $this->assertOutputEquals(<<<EOF
donatello.com
8080.donatello.com
donnie.drall.local
leonardo.com
leo.drall.local

EOF, $output);
  }

  public function testWithComposerRoot() {
    chdir('/');
    $output = shell_exec('drall --root=' . $this->drupalDir() . ' site:keys');
    $this->assertEquals(<<<EOF
tmnt.com
cowabunga.com
tmnt.drall.local
donatello.com
8080.donatello.com
donnie.drall.local
leonardo.com
leo.drall.local
michelangelo.com
mikey.drall.local
raphael.com
ralph.drall.local

EOF, $output);
  }

  public function testWithDrupalRoot() {
    chdir('/');
    $output = shell_exec('drall --root=' . $this->drupalDir() . '/web site:keys');
    $this->assertEquals(<<<EOF
tmnt.com
cowabunga.com
tmnt.drall.local
donatello.com
8080.donatello.com
donnie.drall.local
leonardo.com
leo.drall.local
michelangelo.com
mikey.drall.local
raphael.com
ralph.drall.local

EOF, $output);
  }

}
