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

  public function testWorkingDirectory(): void {
    $output = shell_exec('drall exec --drall-filter=tmnt "echo \"Site: @@site\" && pwd && which drush"');
    $this->assertOutputEquals(<<<EOT
Finished: @tmnt
Site: @tmnt
/opt/drupal
/opt/drupal/vendor/bin/drush

EOT, $output);
  }

  /**
   * Run drush command with @@dir.
   */
  public function testDrushWithUriPlaceholder(): void {
    $output = shell_exec('drall exec drush --uri=@@dir core:status --fields=site');
    $this->assertOutputEquals(<<<EOF
Finished: default
Site path : sites/default
Finished: donnie
Site path : sites/donnie
Finished: leo
Site path : sites/leo
Finished: mikey
Site path : sites/mikey
Finished: ralph
Site path : sites/ralph

EOF, $output);
  }

  /**
   * Run drush command with @@site.
   */
  public function testDrushWithSitePlaceholder(): void {
    $output = shell_exec('drall exec drush @@site.local core:status --fields=site');
    $this->assertOutputEquals(<<<EOF
Finished: @donnie
Site path : sites/donnie
Finished: @leo
Site path : sites/leo
Finished: @mikey
Site path : sites/mikey
Finished: @ralph
Site path : sites/ralph
Finished: @tmnt
Site path : sites/default

EOF, $output);
  }

  /**
   * Run drush command with no placeholders.
   */
  public function testDrushWithNoPlaceholders(): void {
    $output = shell_exec('drall exec drush core:status --fields=site');
    $this->assertOutputEquals(<<<EOF
Finished: default
Site path : sites/default
Finished: donnie
Site path : sites/donnie
Finished: leo
Site path : sites/leo
Finished: mikey
Site path : sites/mikey
Finished: ralph
Site path : sites/ralph

EOF, $output);
  }

  /**
   * Run multiple drush commands with no placeholders.
   */
  public function testMultipleDrushWithNoPlaceholders(): void {
    $output = shell_exec('drall exec "drush st --fields=site; drush st --fields=uri"');
    $this->assertOutputEquals(<<<EOF
Finished: default
Site path : sites/default
Site URI : http://default
Finished: donnie
Site path : sites/donnie
Site URI : http://donnie
Finished: leo
Site path : sites/leo
Site URI : http://leo
Finished: mikey
Site path : sites/mikey
Site URI : http://mikey
Finished: ralph
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
Finished: default
Drush status
Site path : sites/default
Finished: donnie
Drush status
Site path : sites/donnie
Finished: leo
Drush status
Site path : sites/leo
Finished: mikey
Drush status
Site path : sites/mikey
Finished: ralph
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
Finished: default
web/sites/default/settings.php
Finished: donnie
web/sites/donnie/settings.php
Finished: leo
web/sites/leo/settings.php
Finished: mikey
web/sites/mikey/settings.php
Finished: ralph
web/sites/ralph/settings.php

EOF, $output);
  }

  public function testWithFilter(): void {
    $output = shell_exec('drall exec --drall-filter=leo ls web/sites/@@dir/settings.php');
    $this->assertOutputEquals(<<<EOF
Finished: leo
web/sites/leo/settings.php

EOF, $output);
  }

  public function testWithUriPlaceholderVerbose(): void {
    $output = shell_exec('drall exec --drall-debug ls web/sites/@@dir/settings.php');
    $this->assertOutputEquals(<<<EOF
[debug] Running: ls web/sites/default/settings.php
Finished: default
web/sites/default/settings.php
[debug] Running: ls web/sites/donnie/settings.php
Finished: donnie
web/sites/donnie/settings.php
[debug] Running: ls web/sites/leo/settings.php
Finished: leo
web/sites/leo/settings.php
[debug] Running: ls web/sites/mikey/settings.php
Finished: mikey
web/sites/mikey/settings.php
[debug] Running: ls web/sites/ralph/settings.php
Finished: ralph
web/sites/ralph/settings.php

EOF, $output);
  }

  public function testWithUriPlaceholderAndGroup(): void {
    $output = shell_exec('drall exec --drall-group=bluish ls web/sites/@@dir/settings.php');
    $this->assertOutputEquals(<<<EOF
Finished: donnie
web/sites/donnie/settings.php
Finished: leo
web/sites/leo/settings.php

EOF, $output);
  }

  public function testWithSitePlaceholder(): void {
    $output = shell_exec('drall exec drush @@site.local core:status --fields=site');
    $this->assertOutputEquals(<<<EOF
Finished: @donnie
Site path : sites/donnie
Finished: @leo
Site path : sites/leo
Finished: @mikey
Site path : sites/mikey
Finished: @ralph
Site path : sites/ralph
Finished: @tmnt
Site path : sites/default

EOF, $output);
  }

  public function testWithSitePlaceholderVerbose(): void {
    $output = shell_exec('drall exec --drall-debug drush @@site.local st --fields=site');
    $this->assertOutputEquals(<<<EOF
[debug] Running: drush @donnie.local st --fields=site
Finished: @donnie
Site path : sites/donnie
[debug] Running: drush @leo.local st --fields=site
Finished: @leo
Site path : sites/leo
[debug] Running: drush @mikey.local st --fields=site
Finished: @mikey
Site path : sites/mikey
[debug] Running: drush @ralph.local st --fields=site
Finished: @ralph
Site path : sites/ralph
[debug] Running: drush @tmnt.local st --fields=site
Finished: @tmnt
Site path : sites/default

EOF, $output);
  }

  public function testWithSitePlaceholderAndGroup(): void {
    $output = shell_exec('drall exec drush --drall-group=bluish @@site.local st --fields=site');
    $this->assertOutputEquals(<<<EOF
Finished: @donnie
Site path : sites/donnie
Finished: @leo
Site path : sites/leo

EOF, $output);
  }

  public function testCatchStdErrOutput(): void {
    $output = shell_exec('drall exec --drall-filter=default drush version --verbose');

    // Ignore the Drush Version.
    $output = preg_replace('@(Drush version :) (\d+\.\d+\.\d+)@', '$1 x.y.z', $output);

    $this->assertOutputEquals(<<<EOF
Finished: default
 [info] Starting bootstrap to none
 [info] Drush bootstrap phase 0
 [info] Try to validate bootstrap phase 0
Drush version : x.y.z

EOF, $output);
  }

  public function testWithProgressBarVisible(): void {
    $output = shell_exec('DRALL_ENVIRONMENT=unknown drall exec drush st --field=site 2>&1');
    $this->assertOutputEquals(<<<EOF
Finished: default
sites/default
 1/5 [=====>----------------------]  20%Finished: donnie
sites/donnie
 2/5 [===========>----------------]  40%Finished: leo
sites/leo
 3/5 [================>-----------]  60%Finished: mikey
sites/mikey
 4/5 [======================>-----]  80%Finished: ralph
sites/ralph
 5/5 [============================] 100%

EOF, $output);
  }

  public function testWithProgressBarHidden(): void {
    $output = shell_exec('DRALL_ENVIRONMENT=foo drall exec --drall-no-progress drush st --field=site 2>&1');
    $this->assertOutputEquals(<<<EOF
Finished: default
sites/default
Finished: donnie
sites/donnie
Finished: leo
sites/leo
Finished: mikey
sites/mikey
Finished: ralph
sites/ralph

EOF, $output);
  }

  public function testWithNoExecute(): void {
    $output = shell_exec('drall exec --drall-no-execute drush core:status');
    $this->assertOutputEquals(<<<EOF
drush --uri=default core:status
drush --uri=donnie core:status
drush --uri=leo core:status
drush --uri=mikey core:status
drush --uri=ralph core:status

EOF, $output);
  }

  public function testWithNoExecuteVerbose(): void {
    $output = shell_exec('drall exec --drall-no-execute --drall-verbose drush core:status');
    $this->assertOutputEquals(<<<EOF
# Item: default
drush --uri=default core:status
# Item: donnie
drush --uri=donnie core:status
# Item: leo
drush --uri=leo core:status
# Item: mikey
drush --uri=mikey core:status
# Item: ralph
drush --uri=ralph core:status

EOF, $output);
  }

}
