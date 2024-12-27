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
   * @testdox Works when -- is absent and options are not used.
   */
  public function testMissingOptionsSeparatorWithNoOptions(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush st',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertEquals(0, $process->getExitCode());
  }

  /**
   * @testdox Shows error when -- is absent but options are used.
   */
  public function testMissingOptionsSeparatorWithOptions(): void {
    $process = Process::fromShellCommandline(
      'drall exec --dry-run drush st',
    static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
When using options, a "--" must be placed before the command to be executed.
Incorrect: drall exec --dry-run drush --field=site core:status
Correct:   drall exec --dry-run -- drush --field=site core:status
Notice the `--` between `--dry-run` and the word `drush`.

EOT, $process->getOutput());
    $this->assertOutputContainsString('Missing options separator', $process->getErrorOutput());
    $this->assertEquals(1, $process->getExitCode());
  }

  /**
   * @testdox Shows error when --drall-* options are detected.
   */
  public function testShowErrorForObsoleteOptions(): void {
    $process = Process::fromShellCommandline('./vendor/bin/drall exec --drall-foo drush st', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOT
In Drall 4.x, all --drall-* options have been renamed.
See https://github.com/jigarius/drall/issues/99

EOT, $process->getOutput());
    $this->assertOutputContainsString('Obsolete options detected', $process->getErrorOutput());
    $this->assertEquals(1, $process->getExitCode());
  }

  /**
   * @testdox With no Drupal installation.
   */
  public function testWithNoDrupal(): void {
    $process = Process::fromShellCommandline(
      'drall exec -- ./vendor/bin/drush --uri=@@dir core:status',
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
      'drall exec -- ./vendor/bin/drush --uri=@@dir core:status',
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
   * @testdox Raises error for non-Drush command with no placeholders.
   */
  public function testNonDrushWithNoPlaceholders(): void {
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
      'drall exec --filter=tmnt -- "echo \"Site: @@site\" && pwd"',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
✔ @tmnt: Done
Site: @tmnt
/opt/drupal

EOT, $process->getOutput());
  }

  /**
   * @testdox With @@dir.
   */
  public function testDrushWithUriPlaceholder(): void {
    $process = Process::fromShellCommandline(
      'drall exec -- ./vendor/bin/drush --uri=@@dir core:status --field=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
✔ default: Done
sites/default
✔ donnie: Done
sites/donnie
✔ leo: Done
sites/leo
✔ mikey: Done
sites/mikey
✔ ralph: Done
sites/ralph

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@site.
   */
  public function testDrushWithSitePlaceholder(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush -- @@site.local core:status --field=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
✔ @donnie: Done
sites/donnie
✔ @leo: Done
sites/leo
✔ @mikey: Done
sites/mikey
✔ @ralph: Done
sites/ralph
✔ @tmnt: Done
sites/default

EOF, $process->getOutput());
  }

  /**
   * @testdox Injects --uri for Drush command with no placeholders.
   */
  public function testDrushWithNoPlaceholders(): void {
    $process = Process::fromShellCommandline(
      'drall exec -- ./vendor/bin/drush core:status --field=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
✔ default: Done
sites/default
✔ donnie: Done
sites/donnie
✔ leo: Done
sites/leo
✔ mikey: Done
sites/mikey
✔ ralph: Done
sites/ralph

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
✔ default: Done
Site path : sites/default
Site URI : http://default
✔ donnie: Done
Site path : sites/donnie
Site URI : http://donnie
✔ leo: Done
Site path : sites/leo
Site URI : http://leo
✔ mikey: Done
Site path : sites/mikey
Site URI : http://mikey
✔ ralph: Done
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
✔ default: Done
Drush status
Site path : sites/default
✔ donnie: Done
Drush status
Site path : sites/donnie
✔ leo: Done
Drush status
Site path : sites/leo
✔ mikey: Done
Drush status
Site path : sites/mikey
✔ ralph: Done
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
✔ default: Done
web/sites/default/settings.php
✔ donnie: Done
web/sites/donnie/settings.php
✔ leo: Done
web/sites/leo/settings.php
✔ mikey: Done
web/sites/mikey/settings.php
✔ ralph: Done
web/sites/ralph/settings.php

EOF, $process->getOutput());
  }

  /**
   * @testdox With --filter.
   */
  public function testWithFilter(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --filter=leo -- ./vendor/bin/drush st --field=site',
    static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertOutputEquals(<<<EOF
✔ leo: Done
sites/leo

EOF, $process1->getOutput());

    // Short form.
    $process2 = Process::fromShellCommandline(
      'drall exec -f leo -- ./vendor/bin/drush st --field=site',
    static::PATH_DRUPAL,
    );
    $process2->run();
    $this->assertOutputEquals(<<<EOF
✔ leo: Done
sites/leo

EOF, $process2->getOutput());
  }

  /**
   * @testdox With @@dir placeholder and --debug.
   */
  public function testWithDirPlaceholderAndDebug(): void {
    $process = Process::fromShellCommandline(
      'drall exec --debug -- ls web/sites/@@dir/settings.php',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
[debug] Command received: ls web/sites/@@dir/settings.php
[debug] Running: ls web/sites/default/settings.php
✔ default: Done
web/sites/default/settings.php
[debug] Running: ls web/sites/donnie/settings.php
✔ donnie: Done
web/sites/donnie/settings.php
[debug] Running: ls web/sites/leo/settings.php
✔ leo: Done
web/sites/leo/settings.php
[debug] Running: ls web/sites/mikey/settings.php
✔ mikey: Done
web/sites/mikey/settings.php
[debug] Running: ls web/sites/ralph/settings.php
✔ ralph: Done
web/sites/ralph/settings.php

EOF, $process->getOutput());
  }

  /**
   * @testdox With --group.
   */
  public function testWithGroup(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --group=bluish -- ./vendor/bin/drush st --field=site',
    static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertOutputEquals(<<<EOF
✔ donnie: Done
sites/donnie
✔ leo: Done
sites/leo

EOF, $process1->getOutput());

    // Short form.
    $process2 = Process::fromShellCommandline(
      'drall exec -g bluish -- ./vendor/bin/drush st --field=site',
    static::PATH_DRUPAL,
    );
    $process2->run();
    $this->assertOutputEquals(<<<EOF
✔ donnie: Done
sites/donnie
✔ leo: Done
sites/leo

EOF, $process2->getOutput());
  }

  /**
   * @testdox with DRALL_GROUP env var.
   */
  public function testWithGroupEnvVar(): void {
    $process = Process::fromShellCommandline(
      'drall exec -- ./vendor/bin/drush st --field=site',
      static::PATH_DRUPAL,
      ['DRALL_GROUP' => 'bluish'],
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
✔ donnie: Done
sites/donnie
✔ leo: Done
sites/leo

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@site placeholder.
   */
  public function testWithSitePlaceholder(): void {
    $process = Process::fromShellCommandline(
      'drall exec ./vendor/bin/drush -- @@site.local core:status --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
✔ @donnie: Done
Site path : sites/donnie
✔ @leo: Done
Site path : sites/leo
✔ @mikey: Done
Site path : sites/mikey
✔ @ralph: Done
Site path : sites/ralph
✔ @tmnt: Done
Site path : sites/default

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@site placeholder and --debug.
   */
  public function testWithSitePlaceholderDebug(): void {
    $process = Process::fromShellCommandline(
      'drall exec --debug -- ./vendor/bin/drush @@site.local st --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
[debug] Command received: ./vendor/bin/drush @@site.local st --fields=site
[debug] Running: ./vendor/bin/drush @donnie.local st --fields=site
✔ @donnie: Done
Site path : sites/donnie
[debug] Running: ./vendor/bin/drush @leo.local st --fields=site
✔ @leo: Done
Site path : sites/leo
[debug] Running: ./vendor/bin/drush @mikey.local st --fields=site
✔ @mikey: Done
Site path : sites/mikey
[debug] Running: ./vendor/bin/drush @ralph.local st --fields=site
✔ @ralph: Done
Site path : sites/ralph
[debug] Running: ./vendor/bin/drush @tmnt.local st --fields=site
✔ @tmnt: Done
Site path : sites/default

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@site placeholder and --group.
   */
  public function testWithSitePlaceholderAndGroup(): void {
    $process = Process::fromShellCommandline(
      'drall exec --group=bluish -- ./vendor/bin/drush @@site.local st --field=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
✔ @donnie: Done
sites/donnie
✔ @leo: Done
sites/leo

EOF, $process->getOutput());
  }

  /**
   * @testdox Catch STDERR output.
   */
  public function testCatchStdErrOutput(): void {
    $process = Process::fromShellCommandline(
      'drall exec --filter=default -- ./vendor/bin/drush --verbose version',
      static::PATH_DRUPAL,
    );
    $process->run();

    // Ignore the Drush Version.
    $output = preg_replace('@(Drush version :) ([\d|\.|-]+)@', '$1 x.y.z', $process->getOutput());

    $this->assertOutputEquals(<<<EOF
✔ default: Done
 [info] Starting bootstrap to none
 [info] Drush bootstrap phase 0
 [info] Try to validate bootstrap phase 0
Drush version : x.y.z

EOF, $output);
  }

  /**
   * @testdox Progress bar.
   */
  public function testWithProgressBar(): void {
    $process = Process::fromShellCommandline(
      'drall exec -- ./vendor/bin/drush st --field=site 2>&1',
      static::PATH_DRUPAL,
      ['DRALL_ENVIRONMENT' => 'unknown'],
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
✔ default: Done
sites/default
 1/5 [=====>----------------------]  20%✔ donnie: Done
sites/donnie
 2/5 [===========>----------------]  40%✔ leo: Done
sites/leo
 3/5 [================>-----------]  60%✔ mikey: Done
sites/mikey
 4/5 [======================>-----]  80%✔ ralph: Done
sites/ralph
 5/5 [============================] 100%

EOF, $process->getOutput());
  }

  /**
   * @testdox With --no-progress.
   */
  public function testWithNoProgressBar(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress -- ./vendor/bin/drush st --field=site 2>&1',
      static::PATH_DRUPAL,
      // The progress bar is always hidden in the "test" environment to avoid
      // repeating --no-progress in all commands. Thus, for this test,
      // an "unknown" environment is used to check whether --no-progress
      // actually works.
      ['DRALL_ENVIRONMENT' => 'unknown'],
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
✔ default: Done
sites/default
✔ donnie: Done
sites/donnie
✔ leo: Done
sites/leo
✔ mikey: Done
sites/mikey
✔ ralph: Done
sites/ralph

EOF, $process->getOutput());
  }

  /**
   * @testdox With verbosity quiet.
   */
  public function testWithVerbosityQuiet(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --quiet -- ./vendor/bin/drush st --field=site',
      static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertEquals(<<<EOT
✔ default: Done
✔ donnie: Done
✔ leo: Done
✔ mikey: Done
✔ ralph: Done

EOT, $process1->getOutput());

    // Short form.
    $process2 = Process::fromShellCommandline(
      'drall exec -q -- ./vendor/bin/drush st --field=site',
      static::PATH_DRUPAL,
    );
    $process2->run();
    $this->assertEquals(<<<EOT
✔ default: Done
✔ donnie: Done
✔ leo: Done
✔ mikey: Done
✔ ralph: Done

EOT, $process2->getOutput());
  }

  /**
   * @testdox With --dry-run.
   */
  public function testWithDryRun(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --dry-run -- ./vendor/bin/drush st',
      static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertOutputEquals(<<<EOF
• default: Preview
./vendor/bin/drush --uri=default st
• donnie: Preview
./vendor/bin/drush --uri=donnie st
• leo: Preview
./vendor/bin/drush --uri=leo st
• mikey: Preview
./vendor/bin/drush --uri=mikey st
• ralph: Preview
./vendor/bin/drush --uri=ralph st

EOF, $process1->getOutput());

    // Short form.
    $process2 = Process::fromShellCommandline(
      'drall exec -X -- ./vendor/bin/drush st',
      static::PATH_DRUPAL,
    );
    $process2->run();
    $this->assertOutputEquals(<<<EOF
• default: Preview
./vendor/bin/drush --uri=default st
• donnie: Preview
./vendor/bin/drush --uri=donnie st
• leo: Preview
./vendor/bin/drush --uri=leo st
• mikey: Preview
./vendor/bin/drush --uri=mikey st
• ralph: Preview
./vendor/bin/drush --uri=ralph st

EOF, $process2->getOutput());
  }

  /**
   * @testdox With --dry-run --quiet.
   */
  public function testWithDryRunQuiet(): void {
    $process = Process::fromShellCommandline(
      'drall exec --dry-run --quiet -- ./vendor/bin/drush st',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
./vendor/bin/drush --uri=default st
./vendor/bin/drush --uri=donnie st
./vendor/bin/drush --uri=leo st
./vendor/bin/drush --uri=mikey st
./vendor/bin/drush --uri=ralph st

EOF, $process->getOutput());
  }

  /**
   * @testdox With --workers=2.
   */
  public function testWithWorkers(): void {
    $process1 = Process::fromShellCommandline(
      'drall ex --workers=2 --verbose -- drush --uri=@@dir core:status --fields=site',
      static::PATH_DRUPAL,
    );
    $process1->run();

    $this->assertStringStartsWith(
      '[notice] Using 2 workers.',
      $process1->getOutput(),
    );

    // Short form.
    $process2 = Process::fromShellCommandline(
      'drall ex -w2 --verbose -- drush --uri=@@dir core:status --fields=site',
      static::PATH_DRUPAL,
    );
    $process2->run();

    $this->assertStringStartsWith(
      '[notice] Using 2 workers.',
      $process2->getOutput(),
    );
  }

  /**
   * @testdox With --workers=17.
   *
   * Drall caps the maximum workers to a pre-determined limit.
   */
  public function testWorkerLimit(): void {
    $process = Process::fromShellCommandline(
      'drall ex --workers=17 --verbose -- drush --uri=@@dir st --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertStringStartsWith(
      '[warning] Limiting workers to 16, which is the maximum.' . PHP_EOL,
      $process->getOutput(),
    );
  }

  /**
   * @testdox Exits with non-zero code if any command fails.
   */
  public function testNonZeroExitCode(): void {
    $process = Process::fromShellCommandline(
      "drall ex -- \"if [ 'default' = '@@dir' ]; then exit 1; fi; echo 'Hello @@dir!';\"",
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
✖ default: Failed
✔ donnie: Done
Hello donnie!
✔ leo: Done
Hello leo!
✔ mikey: Done
Hello mikey!
✔ ralph: Done
Hello ralph!

EOT, $process->getOutput());
    $this->assertEquals(1, $process->getExitCode());
  }

}
