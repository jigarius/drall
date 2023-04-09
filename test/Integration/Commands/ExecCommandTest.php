<?php

namespace Drall\Test\Integration\Commands;

use Drall\IntegrationTestCase;

/**
 * @covers \Drall\Commands\ExecCommand
 */
class ExecCommandTest extends IntegrationTestCase {

  /**
   * Run a command in a directory with no Drupal.
   */
  public function testWithNoDrupal(): void {
    chdir('/tmp');
    $output = shell_exec('drall exec drush --uri=@@dir core:status');
    $this->assertOutputEquals('[warning] No Drupal sites found.' . PHP_EOL, $output);

    $output = shell_exec('drall exec drush @@site.local core:status');
    $this->assertOutputEquals('[warning] No Drupal sites found.' . PHP_EOL, $output);
  }

  /**
   * Run a command that has no placeholders.
   */
  public function testWithNoPlaceholders(): void {
    $output = shell_exec('drall exec foo 2>&1');
    $this->assertOutputEquals(
      '[error] The command contains no placeholders. Please run it directly without Drall.' . PHP_EOL,
      $output
    );
  }

  /**
   * Run drush command with @@dir.
   */
  public function testDrushWithUriPlaceholder(): void {
    $output = shell_exec('drall exec drush --uri=@@dir core:status --fields=site');
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
   * Run drush command with @@site.
   */
  public function testDrushWithSitePlaceholder(): void {
    $output = shell_exec('drall exec drush @@site.local core:status --fields=site');
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
   * Run drush command with no placeholders.
   */
  public function testDrushWithNoPlaceholders(): void {
    $output = shell_exec('drall exec drush core:status --fields=site');
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
   * Run multiple drush commands with no placeholders.
   */
  public function testMultipleDrushWithNoPlaceholders(): void {
    $output = shell_exec('drall exec "drush st --fields=site; drush st --fields=uri"');
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
   * Run drush with no placeholders, but "drush" is present in a path.
   *
   * Drall only appends --uri to "drush" have a whitespace right after.
   */
  public function testDrushInPath(): void {
    $output = shell_exec('drall exec ls ./vendor/drush/src 2>&1');
    $this->assertOutputEquals(
      '[error] The command contains no placeholders. Please run it directly without Drall.' . PHP_EOL,
      $output
    );
  }

  /**
   * Run command with Drush that's capitalized.
   *
   * If for some reason someone needs to write the word Drush, it won't be
   * appended with --uri if it is capitalized.
   */
  public function testDrushCapitalized(): void {
    $output = shell_exec('drall exec "echo \"Drush status\" && drush st --fields=site"');
    $this->assertOutputEquals(<<<EOF
Current site: default
Drush status
Site path : sites/default
Current site: donnie
Drush status
Site path : sites/donnie
Current site: leo
Drush status
Site path : sites/leo
Current site: mikey
Drush status
Site path : sites/mikey
Current site: ralph
Drush status
Site path : sites/ralph

EOF, $output);
  }

  /**
   * A command with mixed placeholders causes an error.
   */
  public function testWithMixedPlaceholders(): void {
    chdir('/tmp');
    $output = shell_exec('drall exec "drush --uri=@@dir core:status && drush @@site.local core:status" 2>&1');
    $this->assertOutputEquals(
      '[error] The command contains: @@site, @@dir. Please use only one.' . PHP_EOL,
      $output
    );
  }

  public function testWithUriPlaceholder(): void {
    $output = shell_exec('drall exec ls web/sites/@@dir/settings.php');
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
    $output = shell_exec('drall exec --drall-debug ls web/sites/@@dir/settings.php');
    $this->assertOutputEquals(<<<EOF
Current site: default
[debug] Running: ls web/sites/default/settings.php
web/sites/default/settings.php
Current site: donnie
[debug] Running: ls web/sites/donnie/settings.php
web/sites/donnie/settings.php
Current site: leo
[debug] Running: ls web/sites/leo/settings.php
web/sites/leo/settings.php
Current site: mikey
[debug] Running: ls web/sites/mikey/settings.php
web/sites/mikey/settings.php
Current site: ralph
[debug] Running: ls web/sites/ralph/settings.php
web/sites/ralph/settings.php

EOF, $output);
  }

  public function testWithUriPlaceholderAndGroup(): void {
    $output = shell_exec('drall exec --drall-group=bluish ls web/sites/@@dir/settings.php');
    $this->assertOutputEquals(<<<EOF
Current site: donnie
web/sites/donnie/settings.php
Current site: leo
web/sites/leo/settings.php

EOF, $output);
  }

  public function testWithSitePlaceholder(): void {
    $output = shell_exec('drall exec drush @@site.local core:status --fields=site');
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
    $output = shell_exec('drall exec --drall-debug drush @@site.local st --fields=site');
    $this->assertOutputEquals(<<<EOF
Current site: @donnie
[debug] Running: drush @donnie.local st --fields=site
Site path : sites/donnie
Current site: @leo
[debug] Running: drush @leo.local st --fields=site
Site path : sites/leo
Current site: @mikey
[debug] Running: drush @mikey.local st --fields=site
Site path : sites/mikey
Current site: @ralph
[debug] Running: drush @ralph.local st --fields=site
Site path : sites/ralph
Current site: @tmnt
[debug] Running: drush @tmnt.local st --fields=site
Site path : sites/default

EOF, $output);
  }

  public function testWithSitePlaceholderAndGroup(): void {
    $output = shell_exec('drall exec drush --drall-group=bluish @@site.local st --fields=site');
    $this->assertOutputEquals(<<<EOF
Current site: @donnie
Site path : sites/donnie
Current site: @leo
Site path : sites/leo

EOF, $output);
  }

}
