<?php

namespace Drall\Test\Integration\Command;

use Drall\TestCase;
use Symfony\Component\Process\Process;

/**
 * @testdox site:aliases command
 * @covers \Drall\Command\SiteDirectoriesCommand
 */
class SiteAliasesCommandTest extends TestCase {

  /**
   * @testdox with no Drupal installation.
   */
  public function testWithNoDrupal(): void {
    $process = Process::fromShellCommandline('drall site:aliases', static::PATH_NO_DRUPAL);
    $process->run();
    $this->assertStringContainsString('Package "drupal/core" is not installed', $process->getErrorOutput());
  }

  /**
   * @testdox with an empty Drupal installation.
   */
  public function testWithEmptyDrupal(): void {
    $process = Process::fromShellCommandline('drall site:aliases', static::PATH_EMPTY_DRUPAL);
    $process->run();
    $this->assertStringContainsString('[warning] No site aliases found.', $process->getOutput());
  }

  /**
   * @testdox with a valid Drupal installation.
   */
  public function testExecute(): void {
    $process = Process::fromShellCommandline('drall site:aliases', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOF
@donnie.local
@leo.local
@mikey.local
@ralph.local
@tmnt.local

EOF, $process->getOutput());
  }

  /**
   * @testdox with --filter.
   */
  public function testWithFilter(): void {
    $process = Process::fromShellCommandline(
      'drall site:aliases --filter="leo||ralph"',
      static::PATH_DRUPAL,
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
@leo.local
@ralph.local

EOF, $process->getOutput());
  }

  /**
   * @testdox with --group.
   */
  public function testWithGroup(): void {
    $process = Process::fromShellCommandline(
      'drall site:aliases --group=reddish',
      static::PATH_DRUPAL
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
@mikey.local
@ralph.local

EOF, $process->getOutput());
  }

  /**
   * @testdox with --limit and --offset.
   */
  public function testWithRange(): void {
    $process = Process::fromShellCommandline('drall site:aliases --offset=2 --limit=2', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOF
@mikey.local
@ralph.local

EOF, $process->getOutput());
  }

}
