<?php

namespace Drall\Test\Integration\Command;

use Drall\TestCase;
use Symfony\Component\Process\Process;

/**
 * @testdox site:directories command
 * @covers \Drall\Command\SiteDirectoriesCommand
 */
class SiteDirectoriesCommandTest extends TestCase {

  /**
   * @testdox with no Drupal installation.
   */
  public function testWithNoDrupal(): void {
    $process = Process::fromShellCommandline('drall site:directories', static::PATH_NO_DRUPAL);
    $process->run();
    $this->assertStringContainsString('Package "drupal/core" is not installed', $process->getErrorOutput());
  }

  /**
   * @testdox with an empty Drupal installation.
   */
  public function testWithEmptyDrupal(): void {
    $process = Process::fromShellCommandline('drall site:directories', static::PATH_EMPTY_DRUPAL);
    $process->run();
    $this->assertStringContainsString('[warning] No Drupal sites found.', $process->getOutput());
  }

  /**
   * @testdox with a valid Drupal installation.
   */
  public function testExecute(): void {
    $process = Process::fromShellCommandline('drall site:directories', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOF
default
donnie
leo
mikey
ralph

EOF, $process->getOutput());
  }

  /**
   * @testdox with --filter.
   */
  public function testExecuteWithFilter(): void {
    $process = Process::fromShellCommandline('drall site:directories --filter="leo||ralph"', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOF
leo
ralph

EOF, $process->getOutput());
  }

  /**
   * @testdox with --group.
   */
  public function testWithGroup(): void {
    $process = Process::fromShellCommandline('drall site:directories --group=bluish', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOF
donnie
leo

EOF, $process->getOutput());
  }

  /**
   * @testdox with --limit and --offset.
   */
  public function testWithRange(): void {
    $process = Process::fromShellCommandline('drall site:directories --offset=2 --limit=2', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOF
leo
mikey

EOF, $process->getOutput());
  }

}
