<?php

namespace Drall\Test\Integration;

use Composer\InstalledVersions;
use Drall\Drall;
use Drall\TestCase;
use Symfony\Component\Process\Process;

/**
 * @covers \Drall\Drall
 */
class DrallTest extends TestCase {

  /**
   * @testdox Returns correct version string.
   */
  public function testVersion(): void {
    $process = Process::fromShellCommandline('drall --version', static::PATH_DRUPAL);
    $process->run();
    $version = InstalledVersions::getPrettyVersion('jigarius/drall');
    $this->assertStringContainsString(Drall::NAME . ' ' . $version, $process->getOutput());
  }

  /**
   * @testdox Suggests "drush" for unrecognized commands.
   */
  public function testUnrecognizedCommand(): void {
    $process = Process::fromShellCommandline('drall cron', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOT

  The command "cron" was not understood. Did you mean one of the following?
  drall exec cron
  drall exec drush cron
  Alternatively, run "drall list" to see a list of all available commands.

EOT, $process->getErrorOutput());
  }

}
