<?php

namespace Drall\Test\Integration\Command;

use Drall\Command\ExecCommand;
use Drall\TestCase;
use Symfony\Component\Process\Process;

/**
 * Test the `drall exec` command.
 *
 * Most of the tests disable the progress bar with the -P option.
 *
 * @testdox exec command
 * @covers \Drall\Command\ExecCommand
 */
class ExecCommandTest extends TestCase {

  /**
   * @testdox Shows error when -- is not used.
   */
  public function testMissingOptionsSeparator(): void {
    $process = Process::fromShellCommandline(
      'drall exec -P --dry-run drush st',
    static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
A double-dash `--` must be placed before the command to be executed.
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
    $process = Process::fromShellCommandline('./vendor/bin/drall exec -P --drall-foo drush st', static::PATH_DRUPAL);
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
      'drall exec -P -- ./vendor/bin/drush --uri=@@dir core:status',
      static::PATH_NO_DRUPAL,
    );
    $process->run();
    $this->assertStringContainsString('Package "drupal/core" is not installed', $process->getErrorOutput());

    $process = Process::fromShellCommandline(
      'drall exec --no-progress -- ./vendor/bin/drush @@site.local core:status',
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
      'drall exec --no-progress -- ./vendor/bin/drush --uri=@@dir core:status',
      static::PATH_EMPTY_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals('[warning] No Drupal sites found.' . PHP_EOL, $process->getOutput());

    $process = Process::fromShellCommandline(
      'drall exec --no-progress -- ./vendor/bin/drush @@site.local core:status',
      static::PATH_EMPTY_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals('[warning] No Drupal sites found.' . PHP_EOL, $process->getOutput());
  }

  /**
   * @testdox Raises error for non-Drush command with no placeholders.
   */
  public function testNonDrushWithNoPlaceholders(): void {
    $process = Process::fromShellCommandline('drall exec --no-progress -- foo', static::PATH_DRUPAL);
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
      'drall exec --no-progress --filter=tmnt -- "echo \"Site: @@site\" && pwd"',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
Site: @tmnt
/opt/drupal
✔ @tmnt: Done

EOT, $process->getOutput());
  }

  /**
   * @testdox With @@dir.
   */
  public function testDrushWithUriPlaceholder(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress -- ./vendor/bin/drush --uri=@@dir core:status --field=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
sites/default
✔ default: Done
sites/donnie
✔ donnie: Done
sites/leo
✔ leo: Done
sites/mikey
✔ mikey: Done
sites/ralph
✔ ralph: Done

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@site.
   */
  public function testDrushWithSitePlaceholder(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress -- ./vendor/bin/drush @@site.local core:status --field=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
sites/donnie
✔ @donnie: Done
sites/leo
✔ @leo: Done
sites/mikey
✔ @mikey: Done
sites/ralph
✔ @ralph: Done
sites/default
✔ @tmnt: Done

EOF, $process->getOutput());
  }

  /**
   * @testdox Injects --uri for Drush command with no placeholders.
   */
  public function testDrushWithNoPlaceholders(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress -- ./vendor/bin/drush core:status --field=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
sites/default
✔ default: Done
sites/donnie
✔ donnie: Done
sites/leo
✔ leo: Done
sites/mikey
✔ mikey: Done
sites/ralph
✔ ralph: Done

EOF, $process->getOutput());
  }

  /**
   * @testdox With multiple drush commands and no placeholders.
   */
  public function testMultipleDrushWithNoPlaceholders(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress -- "./vendor/bin/drush st --fields=site; ./vendor/bin/drush st --fields=uri"',
    static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
Site path : sites/default
Site URI : http://default
✔ default: Done
Site path : sites/donnie
Site URI : http://donnie
✔ donnie: Done
Site path : sites/leo
Site URI : http://leo
✔ leo: Done
Site path : sites/mikey
Site URI : http://mikey
✔ mikey: Done
Site path : sites/ralph
Site URI : http://ralph
✔ ralph: Done

EOF, $process->getOutput());
  }

  /**
   * @testdox With no placeholders and "drush" present in a path.
   */
  public function testDrushInPath(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress -- ls ./vendor/drush/src',
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
      'drall exec --no-progress -- "echo \"Drush status\" && ./vendor/bin/drush st --fields=site"',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
Drush status
Site path : sites/default
✔ default: Done
Drush status
Site path : sites/donnie
✔ donnie: Done
Drush status
Site path : sites/leo
✔ leo: Done
Drush status
Site path : sites/mikey
✔ mikey: Done
Drush status
Site path : sites/ralph
✔ ralph: Done

EOF, $process->getOutput());
  }

  /**
   * @testdox With mixed placeholders.
   */
  public function testWithMixedPlaceholders(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress -- "./vendor/bin/drush --uri=@@dir st && ./vendor/bin/drush @@site.local st"',
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
      'drall exec --no-progress -- ls web/sites/@@dir/settings.php',
    static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
web/sites/default/settings.php
✔ default: Done
web/sites/donnie/settings.php
✔ donnie: Done
web/sites/leo/settings.php
✔ leo: Done
web/sites/mikey/settings.php
✔ mikey: Done
web/sites/ralph/settings.php
✔ ralph: Done

EOF, $process->getOutput());
  }

  /**
   * @testdox With --filter.
   */
  public function testWithFilter(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --no-progress --filter=leo -- ./vendor/bin/drush st --field=site',
    static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertOutputEquals(<<<EOF
sites/leo
✔ leo: Done

EOF, $process1->getOutput());

    // Short form.
    $process2 = Process::fromShellCommandline(
      'drall exec --no-progress -f leo -- ./vendor/bin/drush st --field=site',
    static::PATH_DRUPAL,
    );
    $process2->run();
    $this->assertOutputEquals(<<<EOF
sites/leo
✔ leo: Done

EOF, $process2->getOutput());
  }

  /**
   * @testdox With --group.
   */
  public function testWithGroup(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --no-progress --group=bluish -- ./vendor/bin/drush st --field=site',
    static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertOutputEquals(<<<EOF
sites/donnie
✔ donnie: Done
sites/leo
✔ leo: Done

EOF, $process1->getOutput());

    // Short form.
    $process2 = Process::fromShellCommandline(
      'drall exec --no-progress -g bluish -- ./vendor/bin/drush st --field=site',
    static::PATH_DRUPAL,
    );
    $process2->run();
    $this->assertOutputEquals(<<<EOF
sites/donnie
✔ donnie: Done
sites/leo
✔ leo: Done

EOF, $process2->getOutput());
  }

  /**
   * @testdox With --offset.
   */
  public function testWithOffset(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress --offset=3 -- ./vendor/bin/drush st --field=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
sites/mikey
✔ mikey: Done
sites/ralph
✔ ralph: Done

EOF, $process->getOutput());

    // Negative offsets like -2 select the last 2 sites.
    $process = Process::fromShellCommandline(
      'drall exec --no-progress --offset=-2 -- ./vendor/bin/drush st --field=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
sites/mikey
✔ mikey: Done
sites/ralph
✔ ralph: Done

EOF, $process->getOutput());
  }

  /**
   * @testdox With --limit.
   */
  public function testWithLimit(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --no-progress --limit=1 -- ./vendor/bin/drush st --field=site',
      static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertOutputEquals(<<<EOF
sites/default
✔ default: Done

EOF, $process1->getOutput());
  }

  /**
   * @testdox With --offset and --limit.
   */
  public function testWithRange(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --no-progress --offset=2 --limit=2 -- ./vendor/bin/drush st --field=site',
      static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertOutputEquals(<<<EOF
sites/leo
✔ leo: Done
sites/mikey
✔ mikey: Done

EOF, $process1->getOutput());
  }

  /**
   * @testdox with DRALL_GROUP env var.
   */
  public function testWithGroupEnvVar(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress -- ./vendor/bin/drush st --field=site',
      static::PATH_DRUPAL,
      ['DRALL_GROUP' => 'bluish'],
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
sites/donnie
✔ donnie: Done
sites/leo
✔ leo: Done

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@site placeholder.
   */
  public function testWithSitePlaceholder(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress ./vendor/bin/drush -- @@site.local core:status --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
Site path : sites/donnie
✔ @donnie: Done
Site path : sites/leo
✔ @leo: Done
Site path : sites/mikey
✔ @mikey: Done
Site path : sites/ralph
✔ @ralph: Done
Site path : sites/default
✔ @tmnt: Done

EOF, $process->getOutput());
  }

  /**
   * @testdox With @@site placeholder and --group.
   */
  public function testWithSitePlaceholderAndGroup(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress --group=bluish -- ./vendor/bin/drush @@site.local st --field=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
sites/donnie
✔ @donnie: Done
sites/leo
✔ @leo: Done

EOF, $process->getOutput());
  }

  /**
   * @testdox Catch STDERR output.
   */
  public function testCatchStdErrOutput(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress --filter=default -- ./vendor/bin/drush --verbose version',
      static::PATH_DRUPAL,
    );
    $process->run();

    // Ignore the Drush Version.
    $output = preg_replace('@(Drush version :) ([\d|\.|-]+)@', '$1 x.y.z', $process->getOutput());

    $this->assertOutputContainsString(<<<EOF
[info] Drush bootstrap phase 0
EOF, $output);

    $this->assertOutputContainsString(<<<EOF
Drush version : x.y.z
✔ default: Done

EOF, $output);
  }

  /**
   * @testdox Progress bar.
   */
  public function testWithProgressBar(): void {
    $process = Process::fromShellCommandline(
      'drall exec -- ./vendor/bin/drush st --field=site 2>&1',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
 0/5 [>---------------------------]   0%sites/default
✔ default: Done
 1/5 [=====>----------------------]  20%sites/donnie
✔ donnie: Done
 2/5 [===========>----------------]  40%sites/leo
✔ leo: Done
 3/5 [================>-----------]  60%sites/mikey
✔ mikey: Done
 4/5 [======================>-----]  80%sites/ralph
✔ ralph: Done
 5/5 [============================] 100%
EOF, $process->getOutput());
  }

  /**
   * @testdox With no buffer.
   */
  public function testWithNoBuffer(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress --no-buffer --verbose -- ./vendor/bin/drush st --field=site 2>&1',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertStringStartsWith(
      '[notice] Using no output buffering.' . PHP_EOL,
      $process->getOutput(),
    );
  }

  /**
   * @testdox With verbosity quiet.
   */
  public function testWithVerbosityQuiet(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --no-progress --quiet -- ./vendor/bin/drush st --field=site',
      static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertEquals(<<<EOT
sites/default
sites/donnie
sites/leo
sites/mikey
sites/ralph

EOT, $process1->getOutput());

    // Short form.
    $process2 = Process::fromShellCommandline(
      'drall exec --no-progress -q -- ./vendor/bin/drush st --field=site',
      static::PATH_DRUPAL,
    );
    $process2->run();
    $this->assertEquals(<<<EOT
sites/default
sites/donnie
sites/leo
sites/mikey
sites/ralph

EOT, $process2->getOutput());
  }

  /**
   * @testdox With --dry-run.
   */
  public function testWithDryRun(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --no-progress --dry-run -- ./vendor/bin/drush st',
      static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertOutputEquals(<<<EOF
./vendor/bin/drush --uri=default st
./vendor/bin/drush --uri=donnie st
./vendor/bin/drush --uri=leo st
./vendor/bin/drush --uri=mikey st
./vendor/bin/drush --uri=ralph st

EOF, $process1->getOutput());

    // Short form.
    $process2 = Process::fromShellCommandline(
      'drall exec --no-progress -X -- ./vendor/bin/drush st',
      static::PATH_DRUPAL,
    );
    $process2->run();
    $this->assertOutputEquals(<<<EOF
./vendor/bin/drush --uri=default st
./vendor/bin/drush --uri=donnie st
./vendor/bin/drush --uri=leo st
./vendor/bin/drush --uri=mikey st
./vendor/bin/drush --uri=ralph st

EOF, $process2->getOutput());
  }

  /**
   * @testdox Shows error when --interval is negative.
   */
  public function testNegativeInterval(): void {
    $process = Process::fromShellCommandline(
      'drall ex --interval=-3 -- drush st --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
The value for --interval must be a positive integer.

EOT, $process->getOutput());
    $this->assertOutputContainsString('Invalid options detected', $process->getErrorOutput());
    $this->assertEquals(1, $process->getExitCode());
  }

  /**
   * @testdox With --interval.
   */
  public function testWithInterval(): void {
    $process = Process::fromShellCommandline(
      'drall ex --interval=2 --verbose -- ./vendor/bin/drush st --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputContainsString(
      '[notice] Using a 2-second interval between commands.' . PHP_EOL,
      $process->getOutput(),
    );

    // The command must take 2 * count($sites) seconds.
    // This confirms that the sleep(2) command is actually executed.
    $timeTaken = microtime(TRUE) - $process->getStartTime();
    $this->assertGreaterThan(10, $timeTaken);
  }

  /**
   * @testdox With --workers=2.
   */
  public function testWithWorkers(): void {
    // The "default" item, takes 3+ seconds to execute during which the second
    // worker processes all other items. Finally, "default" finishes last.
    $process1 = Process::fromShellCommandline(
      "drall ex --no-progress --workers=2 --verbose -- \"if [ 'default' = '@@dir' ]; then sleep 3; fi; echo 'Hello @@dir.';\"",
      static::PATH_DRUPAL,
    );
    $process1->run();

    $this->assertOutputEquals(<<<EOT
[notice] Using 2 workers.
Hello donnie.
✔ donnie: Done
Hello leo.
✔ leo: Done
Hello mikey.
✔ mikey: Done
Hello ralph.
✔ ralph: Done
Hello default.
✔ default: Done

EOT, $process1->getOutput());

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
   * @testdox Shows error when --worker limit exceeds the maximum.
   */
  public function testWorkerLimit(): void {
    $process = Process::fromShellCommandline(
      'drall ex --workers=17 -- drush st --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
The value for --workers must be between 1 and 16.

EOT, $process->getOutput());
    $this->assertOutputContainsString('Invalid options detected', $process->getErrorOutput());
    $this->assertEquals(1, $process->getExitCode());
  }

  /**
   * @testdox Shows error when --workers and --interval are used together.
   */
  public function testIntervalWithWorkers(): void {
    $process = Process::fromShellCommandline(
      'drall ex --workers=2 --interval=2 -- drush st --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
The options --interval and --workers cannot be used together.

EOT, $process->getOutput());
    $this->assertOutputContainsString('Incompatible options detected', $process->getErrorOutput());
    $this->assertEquals(1, $process->getExitCode());
  }

  /**
   * @testdox Without --continue-on-failure, stops on first failure.
   */
  public function test_stops_on_first_failure(): void {
    $process = Process::fromShellCommandline(
      "drall exec -P -- \"if [ 'default' = '@@dir' ]; then exit 1; fi; echo 'Hello @@dir.';\"",
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
✖ default: Failed

EOT, $process->getOutput());
    $this->assertOutputContainsString(
      'One or more items have failed. Stopping.',
      $process->getErrorOutput(),
    );
    $this->assertEquals(1, $process->getExitCode());
  }

  /**
   * @testdox With --continue-on-failure, continues despite failures.
   */
  public function test_continue_on_failure(): void {
    $process = Process::fromShellCommandline(
      "drall exec -P --continue-on-failure -- \"if [ 'default' = '@@dir' ]; then exit 1; fi; echo 'Hello @@dir.';\"",
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
✖ default: Failed
Hello donnie.
✔ donnie: Done
Hello leo.
✔ leo: Done
Hello mikey.
✔ mikey: Done
Hello ralph.
✔ ralph: Done

EOT, $process->getOutput());
    $this->assertEquals(1, $process->getExitCode());
  }

  /**
   * @testdox One SIGINT gives a graceful exit.
   */
  public function testSigInt1(): void {
    // Start a long-running exec command.
    $process = Process::fromShellCommandline(
      'exec ./vendor/bin/drall exec --no-progress -- "./vendor/bin/drush st --field=site && sleep 2" 2>&1',
      static::PATH_DRUPAL,
    );
    $process->start();

    // Wait till the first two sites are processed.
    sleep(4);

    $process->signal(SIGINT);
    $process->wait();

    $this->assertOutputEquals(<<<EOF
sites/default
✔ default: Done
sites/donnie
✔ donnie: Done

EOF, $process->getOutput());

    $this->assertEquals(ExecCommand::INTERRUPTED, $process->getExitCode());
  }

  /**
   * @testdox Shows error when --batch-file has a non-JSON extension.
   */
  public function testBatchFileInvalidExtension(): void {
    $process = Process::fromShellCommandline(
      'drall exec --no-progress --batch-file=/tmp/batch.txt -- drush st --fields=site',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOT
The value for --batch-file must be a path to a file with the .json extension.

EOT, $process->getOutput());
    $this->assertOutputContainsString('Invalid options detected', $process->getErrorOutput());
    $this->assertEquals(1, $process->getExitCode());
  }

  /**
   * @testdox With --batch-file creates a batch file.
   */
  public function testBatchFileCreatesFile(): void {
    $batchFile = tempnam(sys_get_temp_dir(), 'Drall.') . '.json';
    $process = Process::fromShellCommandline(
      "drall exec --no-progress --batch-file=$batchFile -- ./vendor/bin/drush st --field=site",
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
sites/default
✔ default: Done
sites/donnie
✔ donnie: Done
sites/leo
✔ leo: Done
sites/mikey
✔ mikey: Done
sites/ralph
✔ ralph: Done

EOF, $process->getOutput());
    $this->assertEquals(0, $process->getExitCode());

    // Verify the batch file was created.
    $this->assertFileExists($batchFile);
    $data = json_decode(file_get_contents($batchFile), TRUE);
    $this->assertEquals('1.0', $data['version']);
    $this->assertCount(5, $data['finished']);
    $this->assertCount(0, $data['queued']);
    $this->assertCount(0, $data['started']);

    @unlink($batchFile);
  }

  /**
   * @testdox With --batch-file on a complete batch, default is no restart.
   */
  public function testBatchFileCompleteNoRestart(): void {
    $batchFile = tempnam(sys_get_temp_dir(), 'Drall.') . '.json';

    // Complete a batch.
    $process1 = Process::fromShellCommandline(
      "drall exec --no-progress --batch-file=$batchFile -- ./vendor/bin/drush st --field=site",
      static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertEquals(0, $process1->getExitCode());

    // Running again in non-interactive mode uses the default (N for restart).
    // The command should exit cleanly without re-executing.
    $process2 = Process::fromShellCommandline(
      "drall exec --no-progress --no-interaction --batch-file=$batchFile -- ./vendor/bin/drush st --field=site",
      static::PATH_DRUPAL,
    );
    $process2->run();
    $this->assertStringNotContainsString('Done', $process2->getOutput());
    $this->assertEquals(0, $process2->getExitCode());

    @unlink($batchFile);
  }

  /**
   * @testdox With --batch-file resumes an interrupted batch.
   */
  public function testBatchFileResumeInterrupted(): void {
    $batchFile = tempnam(sys_get_temp_dir(), 'Drall.') . '.json';

    // Start a long-running command and interrupt it.
    $process1 = Process::fromShellCommandline(
      "exec ./vendor/bin/drall exec --no-progress --batch-file=$batchFile -- \"./vendor/bin/drush st --field=site && sleep 2\" 2>&1",
      static::PATH_DRUPAL,
    );
    $process1->start();

    // Wait for the first two sites to finish.
    sleep(4);
    $process1->signal(SIGINT);
    $process1->wait();
    $this->assertEquals(ExecCommand::INTERRUPTED, $process1->getExitCode());

    // Verify the batch file has some finished and some queued items.
    $data = json_decode(file_get_contents($batchFile), TRUE);
    $finishedCount = count($data['finished']);
    $this->assertGreaterThan(0, $finishedCount);
    $this->assertLessThan(5, $finishedCount);

    // Resume the batch. In non-interactive mode, the default for
    // "Resume? [Y/n]" is Y, so it resumes automatically.
    $process2 = Process::fromShellCommandline(
      "drall exec --no-progress --no-interaction --batch-file=$batchFile -- ./vendor/bin/drush st --field=site",
      static::PATH_DRUPAL,
    );
    $process2->run();
    $this->assertEquals(0, $process2->getExitCode());

    // After resume, all items should be finished.
    $data = json_decode(file_get_contents($batchFile), TRUE);
    $this->assertCount(5, $data['finished']);
    $this->assertCount(0, $data['queued']);
    $this->assertCount(0, $data['started']);

    @unlink($batchFile);
  }

  /**
   * @testdox Two SIGINT gives a forceful exit.
   */
  public function testSigInt2(): void {
    // Use a slow command so the graceful exit (from the first SIGINT) is
    // still waiting for the current site when the second SIGINT arrives.
    $process = Process::fromShellCommandline(
      'exec ./vendor/bin/drall exec --no-progress -- "./vendor/bin/drush st --field=site && sleep 2" 2>&1',
      static::PATH_DRUPAL,
    );
    $process->start();

    // Wait till the first site is processed and the second begins.
    sleep(3);

    // Send two SIGINTs to force an immediate exit.
    $process->signal(SIGINT);
    usleep(300000);
    $process->signal(SIGINT);
    $process->wait();

    // Only the first site's output is present. The second site was
    // interrupted mid-execution, so its output was never flushed.
    $this->assertOutputEquals(<<<EOF
sites/default
✔ default: Done

EOF, $process->getOutput());

    $this->assertEquals(ExecCommand::INTERRUPTED, $process->getExitCode());
  }

}
