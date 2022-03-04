<?php

namespace Drall\Test\Integration\Commands;

use Drall\IntegrationTestCase;

/**
 * @covers \Drall\Commands\ExecCommand
 */
class ExecCommandTest extends IntegrationTestCase {

  /**
   * Run drall exec with implicit --uri=@@uri in a directory with no Drupal.
   */
  public function testWithImplicitSiteUriAndNoDrupal(): void {
    chdir('/tmp');
    $output = shell_exec('drall exec st');
    $this->assertOutputEquals("[warning] No Drupal sites found." . PHP_EOL, $output);
  }

  /**
   * Run drall exec with implicit --uri=@@uri.
   */
  public function testWithImplicitSiteUri(): void {
    $output = shell_exec('drall exec st --fields=site');
    $this->assertOutputEquals(<<<EOF
Running: drush --uri=default st --fields=site
 Site path : sites/default
Running: drush --uri=donnie st --fields=site
 Site path : sites/donnie
Running: drush --uri=leo st --fields=site
 Site path : sites/leo
Running: drush --uri=mikey st --fields=site
 Site path : sites/mikey
Running: drush --uri=ralph st --fields=site
 Site path : sites/ralph

EOF, $output);
  }

  /**
   * Run drall exec with implicit --uri=@@uri and --drall-group.
   */
  public function testWithImplicitSiteUriAndGroup(): void {
    $output = shell_exec('drall exec --drall-group=bluish st --fields=site');
    $this->assertOutputEquals(<<<EOF
Running: drush --uri=donnie st --fields=site
 Site path : sites/donnie
Running: drush --uri=leo st --fields=site
 Site path : sites/leo

EOF, $output);
  }

  /**
   * Run drall exec with explicit --uri=@@uri.
   */
  public function testWithExplicitSiteUri(): void {
    $output = shell_exec('drall exec st --uri=@@uri --fields=site');
    $this->assertOutputEquals(<<<EOF
Running: drush st --uri=default --fields=site
 Site path : sites/default
Running: drush st --uri=donnie --fields=site
 Site path : sites/donnie
Running: drush st --uri=leo --fields=site
 Site path : sites/leo
Running: drush st --uri=mikey --fields=site
 Site path : sites/mikey
Running: drush st --uri=ralph --fields=site
 Site path : sites/ralph

EOF, $output);
  }

  /**
   * Run drall exec with @@site in a directory with no Drupal.
   */
  public function testWithImplicitSiteAliasAndNoDrupal(): void {
    chdir('/tmp');
    $output = shell_exec('drall exec @@site.local st');
    $this->assertOutputEquals("[warning] No Drupal sites found." . PHP_EOL, $output);
  }

  /**
   * Run drall exec with @@site.
   */
  public function testWithSiteAlias(): void {
    $output = shell_exec('drall exec @@site.local st --fields=site');
    $this->assertOutputEquals(<<<EOF
Running: drush @donnie.local st --fields=site
 Site path : sites/donnie
Running: drush @leo.local st --fields=site
 Site path : sites/leo
Running: drush @mikey.local st --fields=site
 Site path : sites/mikey
Running: drush @ralph.local st --fields=site
 Site path : sites/ralph
Running: drush @tmnt.local st --fields=site
 Site path : sites/default

EOF, $output);
  }

  /**
   * Run drall exec with @@site and --drall-group.
   */
  public function testWithSiteAliasAndGroup(): void {
    $output = shell_exec('drall exec --drall-group=bluish @@site.local st --fields=site');
    $this->assertOutputEquals(<<<EOF
Running: drush @donnie.local st --fields=site
 Site path : sites/donnie
Running: drush @leo.local st --fields=site
 Site path : sites/leo

EOF, $output);
  }

}
