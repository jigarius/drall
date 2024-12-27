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
    $process = Process::fromShellCommandline('drall st', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOT

  The command "st" was not understood. Did you mean one of the following?
  drall exec st
  drall exec drush st
  Alternatively, run "drall list" to see a list of all available commands.

EOT, $process->getErrorOutput());
  }

}
