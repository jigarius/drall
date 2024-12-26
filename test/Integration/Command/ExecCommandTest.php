<?php

namespace Drall\Test\Integration\Command;

use Drall\TestCase;
use Symfony\Component\Process\Process;

/**
 * @testdox exec command
 * @covers \Drall\Command\ExecCommand
 */
class ExecCommandTest extends TestCase {

  /**
   * @testdox With no Drupal installation.
   */
  public function testWithNoDrupal(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush --uri=@@dir core:status',
      static::PATH_NO_DRUPAL,
    );
    $process->run();
    $this->assertStringContainsString('Package "drupal/core" is not installed', $process->getErrorOutput());

    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush @@site.local core:status',
      static::PATH_NO_DRUPAL,
    );
    $process->run();
    $this->assertStringContainsString('Package "drupal/core" is not installed', $process->getErrorOutput());
  }

  /**
   * @testdox With an empty Drupal installation.
   */
  public function testWithEmptyDrupal(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush --uri=@@dir core:status',
      static::PATH_EMPTY_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals('[warning] No Drupal sites found.' . PHP_EOL, $process->getOutput());

    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush @@site.local core:status',
      static::PATH_EMPTY_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals('[warning] No Drupal sites found.' . PHP_EOL, $process->getOutput());
  }

  /**
   * @testdox With no placeholders.
   */
  public function testWithNoPlaceholders(): void {
    $process = Process::fromShellCommandline('drall exec foo', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(
      '[error] The command contains no placeholders. Please run it directly without Drall.' . PHP_EOL,
      $process->getErrorOutput(),
    );
  }

  /**
   * @testdox Working directory.
   */
  public function testWorkingDirectory(): void {
    $process = Process::fromShellCommandline(
      'drall exec --drall-filter=tmnt "echo \"Site: @@site\" && pwd"',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
Finished: @tmnt
Site: @tmnt
/opt/drupal

EOT, $process->getOutput());
  }

  /**
   * @testdox With @@dir.
   */
  public function testDrushWithUriPlaceholder(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush --uri=@@dir core:status --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
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

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@site.
   */
  public function testDrushWithSitePlaceholder(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush @@site.local core:status --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
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

EOF, $process->getOutput());
  }

  /**
   * @testdox With no placeholders.
   */
  public function testDrushWithNoPlaceholders(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush core:status --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
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

EOF, $process->getOutput());
  }

  /**
   * @testdox With multiple drush commands and no placeholders.
   */
  public function testMultipleDrushWithNoPlaceholders(): void {
    $process = Process::fromShellCommandline(
      'drall exec "./vendor/bin/drush st --fields=site; ./vendor/bin/drush st --fields=uri"',
    static::PATH_DRUPAL,
    );
    $process->run();
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

EOF, $process->getOutput());
  }

  /**
   * @testdox With no placeholders and "drush" present in a path.
   */
  public function testDrushInPath(): void {
    $process = Process::fromShellCommandline(
      'drall exec ls ./vendor/drush/src',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(
      '[error] The command contains no placeholders. Please run it directly without Drall.' . PHP_EOL,
      $process->getErrorOutput(),
    );
  }

  /**
   * @testdox With Drush capitalized.
   *
   * If for some reason someone needs to write the word Drush, it won't be
   * appended with --uri if it is capitalized.
   */
  public function testDrushCapitalized(): void {
    $process = Process::fromShellCommandline(
      'drall exec "echo \"Drush status\" && ./vendor/bin/drush st --fields=site"',
      static::PATH_DRUPAL,
    );
    $process->run();
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

EOF, $process->getOutput());
  }

  /**
   * @testdox With mixed placeholders.
   */
  public function testWithMixedPlaceholders(): void {
    $process = Process::fromShellCommandline(
      'drall exec "./vendor/bin/drush --uri=@@dir st && ./vendor/bin/drush @@site.local st"',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(
      '[error] The command contains: @@site, @@dir. Please use only one.' . PHP_EOL,
      $process->getErrorOutput(),
    );
  }

  /**
   * @testdox With @@dir placeholder.
   */
  public function testWithDirPlaceholder(): void {
    $process = Process::fromShellCommandline(
      'drall exec ls web/sites/@@dir/settings.php',
    static::PATH_DRUPAL,
    );
    $process->run();
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

EOF, $process->getOutput());
  }

  /**
   * @testdox With --drall-filter.
   */
  public function testWithFilter(): void {
    $process = Process::fromShellCommandline(
      'drall exec --drall-filter=leo ./vendor/bin/drush st --field=site',
    static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
Finished: leo
sites/leo

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@dir placeholder and --drall-debug.
   */
  public function testWithDirPlaceholderAndDebug(): void {
    $process = Process::fromShellCommandline(
      'drall exec --drall-debug ls web/sites/@@dir/settings.php',
      static::PATH_DRUPAL,
    );
    $process->run();
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

EOF, $process->getOutput());
  }

  /**
   * @testdox With --drall-group.
   */
  public function testWithGroup(): void {
    $process = Process::fromShellCommandline(
      'drall exec --drall-group=bluish ./vendor/bin/drush st --field=site',
    static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
Finished: donnie
sites/donnie
Finished: leo
sites/leo

EOF, $process->getOutput());
  }

  /**
   * @testdox with DRALL_GROUP env var.
   */
  public function testWithGroupEnvVar(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush st --field=site',
      static::PATH_DRUPAL,
      ['DRALL_GROUP' => 'bluish'],
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
Finished: donnie
sites/donnie
Finished: leo
sites/leo

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@site placeholder.
   */
  public function testWithSitePlaceholder(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush @@site.local core:status --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
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

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@site placeholder and --drall-debug.
   */
  public function testWithSitePlaceholderDebug(): void {
    $process = Process::fromShellCommandline(
      'drall exec --drall-debug ./vendor/bin/drush @@site.local st --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
[debug] Running: ./vendor/bin/drush @donnie.local st --fields=site
Finished: @donnie
Site path : sites/donnie
[debug] Running: ./vendor/bin/drush @leo.local st --fields=site
Finished: @leo
Site path : sites/leo
[debug] Running: ./vendor/bin/drush @mikey.local st --fields=site
Finished: @mikey
Site path : sites/mikey
[debug] Running: ./vendor/bin/drush @ralph.local st --fields=site
Finished: @ralph
Site path : sites/ralph
[debug] Running: ./vendor/bin/drush @tmnt.local st --fields=site
Finished: @tmnt
Site path : sites/default

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@site placeholder and --drall-group.
   */
  public function testWithSitePlaceholderAndGroup(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush --drall-group=bluish @@site.local st --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
Finished: @donnie
Site path : sites/donnie
Finished: @leo
Site path : sites/leo

EOF, $process->getOutput());
  }

  /**
   * @testdox Catch STDERR output.
   */
  public function testCatchStdErrOutput(): void {
    $process = Process::fromShellCommandline(
      'drall exec --drall-filter=default ./vendor/bin/drush --verbose version',
      static::PATH_DRUPAL,
    );
    $process->run();

    // Ignore the Drush Version.
    $output = preg_replace('@(Drush version :) ([\d|\.|-]+)@', '$1 x.y.z', $process->getOutput());

    $this->assertOutputEquals(<<<EOF
Finished: default
 [info] Starting bootstrap to none
 [info] Drush bootstrap phase 0
 [info] Try to validate bootstrap phase 0
Drush version : x.y.z

EOF, $output);
  }

  /**
   * @testdox With progress bar.
   */
  public function testWithProgressBarVisible(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush st --field=site 2>&1',
      static::PATH_DRUPAL,
      ['DRALL_ENVIRONMENT' => 'unknown'],
    );
    $process->run();
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

EOF, $process->getOutput());
  }

  /**
   * @testdox With --drall-no-progress.
   */
  public function testWithProgressBarHidden(): void {
    $process = Process::fromShellCommandline(
      'drall exec --drall-no-progress ./vendor/bin/drush st --field=site 2>&1',
      static::PATH_DRUPAL,
      // The progress bar is always hidden in the "test" environment to avoid
      // repeating --no-progress in all commands. Thus, for this test,
      // an "unknown" environment is used to check whether --no-progress
      // actually works.
      ['DRALL_ENVIRONMENT' => 'unknown'],
    );
    $process->run();
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

EOF, $process->getOutput());
  }

  /**
   * @testdox With --drall-no-execute.
   */
  public function testWithNoExecute(): void {
    $process = Process::fromShellCommandline(
      'drall exec --drall-no-execute ./vendor/bin/drush core:status',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
./vendor/bin/drush --uri=default core:status
./vendor/bin/drush --uri=donnie core:status
./vendor/bin/drush --uri=leo core:status
./vendor/bin/drush --uri=mikey core:status
./vendor/bin/drush --uri=ralph core:status

EOF, $process->getOutput());
  }

  /**
   * @testdox With --drall-no-execute --drall-verbose.
   */
  public function testWithNoExecuteVerbose(): void {
    $process = Process::fromShellCommandline(
      'drall exec --drall-no-execute --drall-verbose drush core:status',
      static::PATH_DRUPAL,
    );
    $process->run();
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

EOF, $process->getOutput());
  }

  /**
   * @testdox With --drall-workers=2.
   */
  public function testWithWorkers(): void {
    $process = Process::fromShellCommandline(
      'drall ex --drall-workers=2 --drall-verbose drush --uri=@@dir core:status --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();

    $this->assertStringStartsWith(
      '[notice] Using 2 workers.',
      $process->getOutput(),
    );
  }

  /**
   * @testdox With --drall-workers=17.
   *
   * Drall caps the maximum workers to a pre-determined limit.
   */
  public function testWorkerLimit(): void {
    $process = Process::fromShellCommandline(
      'drall ex --drall-workers=17 --drall-verbose drush --uri=@@dir st --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertStringStartsWith(
      '[warning] Limiting workers to 16, which is the maximum.' . PHP_EOL,
      $process->getOutput(),
    );
  }

  /**
   * @testdox Non-zero exit code.
   */
  public function testNonZeroExitCode(): void {
    $process = Process::fromShellCommandline(
      'drall exec --drall-group=bad ./vendor/bin/drush st --field=site',
    );
    $process->run();
    $this->assertEquals(1, $process->getExitCode());
  }

}
