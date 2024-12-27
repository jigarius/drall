<?php

namespace Drall\Test\Integration\Command;

use Drall\TestCase;
use Symfony\Component\Process\Process;

/**
 * @testdox site:keys command.
 * @covers \Drall\Command\SiteDirectoriesCommand
 */
class SiteKeysCommandTest extends TestCase {

  /**
   * @testdox with no Drupal installation.
   */
  public function testWithNoDrupal(): void {
    $process = Process::fromShellCommandline('drall site:keys', static::PATH_NO_DRUPAL);
    $process->run();
    $this->assertStringContainsString('Package "drupal/core" is not installed', $process->getErrorOutput());
  }

  /**
   * @testdox with an empty Drupal installation.
   */
  public function testWithEmptyDrupal(): void {
    $process = Process::fromShellCommandline('drall site:keys', static::PATH_EMPTY_DRUPAL);
    $process->run();
    $this->assertStringContainsString('[warning] No Drupal sites found.', $process->getOutput());
  }

  /**
   * @testdox with a Drupal installation.
   */
  public function testExecute(): void {
    $process = Process::fromShellCommandline('drall site:keys', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOF
tmnt.com
cowabunga.com
tmnt.drall.local
donatello.com
8080.donatello.com
donnie.drall.local
leonardo.com
leo.drall.local
michelangelo.com
mikey.drall.local
raphael.com
ralph.drall.local

EOF, $process->getOutput());
  }

  /**
   * @testdox with --filter.
   */
  public function testExecuteWithFilter(): void {
    $process = Process::fromShellCommandline('drall site:keys --filter="value~=@.local\$@"', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOF
tmnt.drall.local
donnie.drall.local
leo.drall.local
mikey.drall.local
ralph.drall.local

EOF, $process->getOutput());
  }

  /**
   * @testdox with --group.
   */
  public function testWithGroup(): void {
    $process = Process::fromShellCommandline('drall site:keys --group=bluish', static::PATH_DRUPAL);
    $process->run();
    $this->assertOutputEquals(<<<EOF
donatello.com
8080.donatello.com
donnie.drall.local
leonardo.com
leo.drall.local

EOF, $process->getOutput());
  }

  /**
   * @testdox with DRALL_GROUP env var.
   */
  public function testWithGroupEnvVar(): void {
    $process = Process::fromShellCommandline(
      'drall site:keys',
      static::PATH_DRUPAL,
      ['DRALL_GROUP' => 'bluish'],
    );
    $process->run();
    $this->assertOutputEquals(<<<EOF
donatello.com
8080.donatello.com
donnie.drall.local
leonardo.com
leo.drall.local

EOF, $process->getOutput());
  }

}
