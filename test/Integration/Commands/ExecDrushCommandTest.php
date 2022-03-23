<?php

namespace Drall\Test\Integration\Commands;

use Drall\IntegrationTestCase;

/**
 * @covers \Drall\Commands\ExecDrushCommand
 */
class ExecDrushCommandTest extends IntegrationTestCase {

  /**
   * Run drall exec:drush with implicit --uri=@@uri in a directory with no Drupal.
   */
  public function testWithImplicitSiteUriAndNoDrupal(): void {
    chdir('/tmp');
    $output = shell_exec('drall exec:drush st');
    $this->assertOutputEquals("[warning] No Drupal sites found." . PHP_EOL, $output);
  }

  /**
   * Run drall exec:drush with implicit --uri=@@uri.
   */
  public function testWithImplicitSiteUri(): void {
    $output = shell_exec('drall exec:drush st --fields=site');
    $this->assertOutputEquals(<<<EOF
Current site: default
 Site path : sites/default
Current site: donnie
 Site path : sites/donnie
Current site: leo
 Site path : sites/leo
Current site: mikey
 Site path : sites/mikey
Current site: ralph
 Site path : sites/ralph

EOF, $output);
  }

  /**
   * Run drall exec:drush with implicit --uri=@@uri and --drall-group.
   */
  public function testWithImplicitSiteUriAndGroup(): void {
    $output = shell_exec('drall exec:drush --drall-group=bluish st --fields=site');
    $this->assertOutputEquals(<<<EOF
Current site: donnie
 Site path : sites/donnie
Current site: leo
 Site path : sites/leo

EOF, $output);
  }

  /**
   * Run drall exec:drush with explicit --uri=@@uri.
   */
  public function testWithExplicitSiteUri(): void {
    $output = shell_exec('drall exec:drush st --uri=@@uri --fields=site');
    $this->assertOutputEquals(<<<EOF
Current site: default
 Site path : sites/default
Current site: donnie
 Site path : sites/donnie
Current site: leo
 Site path : sites/leo
Current site: mikey
 Site path : sites/mikey
Current site: ralph
 Site path : sites/ralph

EOF, $output);
  }

  /**
   * Run drall exec:drush with @@site in a directory with no Drupal.
   */
  public function testWithImplicitSiteAliasAndNoDrupal(): void {
    chdir('/tmp');
    $output = shell_exec('drall exec:drush @@site.local st');
    $this->assertOutputEquals("[warning] No Drupal sites found." . PHP_EOL, $output);
  }

  /**
   * Run drall exec:drush with @@site.
   */
  public function testWithSiteAlias(): void {
    $output = shell_exec('drall exec:drush @@site.local st --fields=site');
    $this->assertOutputEquals(<<<EOF
Current site: @donnie
 Site path : sites/donnie
Current site: @leo
 Site path : sites/leo
Current site: @mikey
 Site path : sites/mikey
Current site: @ralph
 Site path : sites/ralph
Current site: @tmnt
 Site path : sites/default

EOF, $output);
  }

  /**
   * Run drall exec:drush with @@site and --drall-group.
   */
  public function testWithSiteAliasAndGroup(): void {
    $output = shell_exec('drall exec:drush --drall-group=bluish @@site.local st --fields=site');
    $this->assertOutputEquals(<<<EOF
Current site: @donnie
 Site path : sites/donnie
Current site: @leo
 Site path : sites/leo

EOF, $output);
  }

}
