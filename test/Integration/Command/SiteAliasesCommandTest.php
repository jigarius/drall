<?php

namespace Drall\Test\Integration\Commands;

use Drall\IntegrationTestCase;

/**
 * @covers \Drall\Command\SiteDirectoriesCommand
 */
class SiteAliasesCommandTest extends IntegrationTestCase {

  /**
   * Run site:aliases with no Drupal installation.
   */
  public function testWithNoDrupal(): void {
    $this->markTestSkipped('Needs work.');
    chdir('/tmp');
    $output = shell_exec('drall site:aliases');
    $this->assertOutputEquals("[warning] No site aliases found." . PHP_EOL, $output);
  }

  /**
   * Run site:aliases with a Drupal installation.
   */
  public function testExecute(): void {
    $output = shell_exec('drall site:aliases');
    $this->assertOutputEquals(<<<EOF
@donnie.local
@leo.local
@mikey.local
@ralph.local
@tmnt.local

EOF, $output);
  }

  /**
   * Run site:aliases with --drall-filter.
   */
  public function testExecuteWithFilter(): void {
    $output = shell_exec('drall site:aliases --drall-filter="leo||ralph"');
    $this->assertOutputEquals(<<<EOF
@leo.local
@ralph.local

EOF, $output);
  }

  /**
   * Run site:aliases with --drall-group.
   */
  public function testWithGroup(): void {
    $output = shell_exec('drall site:aliases --drall-group=reddish');
    $this->assertOutputEquals(<<<EOF
@mikey.local
@ralph.local

EOF, $output);
  }

  /**
   * Run site:aliases with DRALL_GROUP env var.
   */
  public function testWithGroupEnvVar(): void {
    $output = shell_exec('DRALL_GROUP=reddish drall site:aliases');
    $this->assertOutputEquals(<<<EOF
@mikey.local
@ralph.local

EOF, $output);
  }

}
