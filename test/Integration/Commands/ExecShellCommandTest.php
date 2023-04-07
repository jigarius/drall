<?php

namespace Drall\Test\Integration\Commands;

use Drall\IntegrationTestCase;

/**
 * @covers \Drall\Commands\ExecDrushCommand
 */
class ExecShellCommandTest extends IntegrationTestCase {

  /**
   * Run a command in a directory with no Drupal.
   */
  public function testExecuteWithNoDrupal(): void {
    chdir('/tmp');
    $output = shell_exec('drall exec:shell drush --uri=@@uri core:status');
    $this->assertOutputEquals('[warning] No Drupal sites found.' . PHP_EOL, $output);

    $output = shell_exec('drall exec:shell drush @@site.local core:status');
    $this->assertOutputEquals('[warning] No Drupal sites found.' . PHP_EOL, $output);
  }

  /**
   * Run a non-drush command that has no placeholders.
   */
  public function testExecuteShellWithNoPlaceholders(): void {
    $output = shell_exec('drall exec:shell foo 2>&1');
    $this->assertOutputEquals(
      '[error] The command has no placeholders and it can be run without Drall.' . PHP_EOL,
      $output
    );
  }

  /**
   * Run drush command that has no placeholders.
   */
  public function testExecuteDrushWithNoPlaceholders(): void {
    $output = shell_exec('drall exec "drush core:status --fields=site && drush core:status --fields=uri"');
    $this->assertOutputEquals(<<<EOF
Current site: default
Site path : sites/default
Site URI : http://default
Current site: donnie
Site path : sites/donnie
Site URI : http://donnie
Current site: leo
Site path : sites/leo
Site URI : http://leo
Current site: mikey
Site path : sites/mikey
Site URI : http://mikey
Current site: ralph
Site path : sites/ralph
Site URI : http://ralph

EOF, $output);
  }

  /**
   * Run a drush with no placeholders, but "drush" is present in a path.
   *
   * Drall should not append --uri to this "drush".
   */
  public function testExecuteDrushWithNoPlaceholders2(): void {
    $output = shell_exec('drall exec:shell ls ./vendor/drush/src 2>&1');
    $this->assertOutputEquals(
      '[error] The command has no placeholders and it can be run without Drall.' . PHP_EOL,
      $output
    );
  }

  /**
   * A command with both @@uri and @@site placeholders results in an error.
   */
  public function testExecuteWithMixedPlaceholders(): void {
    chdir('/tmp');
    $output = shell_exec('drall exec:shell "drush --uri=@@uri core:status && drush @@site.local core:status" 2>&1');
    $this->assertOutputEquals(
      '[error] The command cannot contain both @@uri and @@site placeholders.' . PHP_EOL,
      $output
    );
  }

  public function testWithUriPlaceholder(): void {
    $output = shell_exec('drall exec:shell ls web/sites/@@uri/settings.php');
    $this->assertOutputEquals(<<<EOF
Current site: default
web/sites/default/settings.php
Current site: donnie
web/sites/donnie/settings.php
Current site: leo
web/sites/leo/settings.php
Current site: mikey
web/sites/mikey/settings.php
Current site: ralph
web/sites/ralph/settings.php

EOF, $output);
  }

  public function testWithUriPlaceholderVerbose(): void {
    $output = shell_exec('drall exs --drall-group=bluish --drall-debug ls web/sites/@@uri/settings.php');
    $this->assertOutputEquals(<<<EOF
Current site: donnie
[debug] Running: ls web/sites/donnie/settings.php
web/sites/donnie/settings.php
Current site: leo
[debug] Running: ls web/sites/leo/settings.php
web/sites/leo/settings.php

EOF, $output);
  }

  public function testWithUriPlaceholderAndGroup(): void {
    $output = shell_exec('drall exec:shell --drall-group=bluish ls web/sites/@@uri/settings.php');
    $this->assertOutputEquals(<<<EOF
Current site: donnie
web/sites/donnie/settings.php
Current site: leo
web/sites/leo/settings.php

EOF, $output);
  }

  public function testWithSitePlaceholder(): void {
    $output = shell_exec('drall exec:shell drush @@site.local core:status --fields=site');
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

  public function testWithSitePlaceholderVerbose(): void {
    $output = shell_exec('drall exs --drall-group=bluish --drall-debug drush @@site.local st --fields=site');
    $this->assertOutputEquals(<<<EOF
Current site: @donnie
[debug] Running: drush @donnie.local st --fields=site
Site path : sites/donnie
Current site: @leo
[debug] Running: drush @leo.local st --fields=site
Site path : sites/leo

EOF, $output);
  }

  public function testWithSitePlaceholderAndGroup(): void {
    $output = shell_exec('drall exec:shell drush --drall-group=bluish @@site.local st --fields=site');
    $this->assertOutputEquals(<<<EOF
Current site: @donnie
Site path : sites/donnie
Current site: @leo
Site path : sites/leo

EOF, $output);
  }

}
