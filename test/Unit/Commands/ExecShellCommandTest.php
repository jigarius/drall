<?php

use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Runners\FakeRunner;
use Symfony\Component\Console\Tester\CommandTester;
use DrupalFinder\DrupalFinder;
use Drall\Drall;
use Drall\Services\SiteDetector;
use Drall\Commands\BaseExecCommand;
use Drall\Commands\ExecShellCommand;
use Drall\TestCase;

/**
 * @covers \Drall\Commands\ExecShellCommand
 * @covers \Drall\Commands\BaseExecCommand
 */
class ExecShellCommandTest extends TestCase {

  public function testExtendsBaseCommand() {
    $this->assertTrue(is_subclass_of(ExecShellCommand::class, BaseExecCommand::class));
  }

  public function testExecuteWithSiteUris() {
    $drupalFinder = new DrupalFinder();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteDirNames', 'getDrushPath'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteDirNames')
      ->willReturn(['default', 'april', 'kacey']);

    $app = new Drall($siteDetectorMock);
    $input = ['cmd' => 'cat web/sites/@@uri/settings.php'];
    $runner = new FakeRunner();
    /** @var \Drall\Commands\ExecShellCommand $command */
    $command = $app->find('exec:shell')
      ->setRunner($runner)
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);
    $tester->execute($input);

    $tester->assertCommandIsSuccessful();
    $this->assertEquals(
      [
        'cat web/sites/default/settings.php',
        'cat web/sites/april/settings.php',
        'cat web/sites/kacey/settings.php',
      ],
      $runner->commandHistory(),
    );
  }

  public function testExecuteWithSiteAliases() {
    $drupalFinder = new DrupalFinder();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteAliasNames', 'getDrushPath'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteAliasNames')
      ->willReturn(['@splinter', '@shredder']);

    $app = new Drall($siteDetectorMock);
    $input = ['cmd' => 'drush @@site.dev core:rebuild && drush @@site.dev core:status'];
    $runner = new FakeRunner();
    /** @var \Drall\Commands\ExecShellCommand $command */
    $command = $app->find('exec:shell')
      ->setRunner($runner)
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);
    $tester->execute($input);

    $tester->assertCommandIsSuccessful();
    $this->assertEquals(
      [
        'drush @splinter.dev core:rebuild && drush @splinter.dev core:status',
        'drush @shredder.dev core:rebuild && drush @shredder.dev core:status',
      ],
      $runner->commandHistory(),
    );
  }

  /**
   * Converts an array of input into a $argv like array.
   *
   * @param array $input
   *   Array of input as expected by CommandTester::execute().
   *
   * @return array
   *   Array resembling $argv.
   *
   * @see \Drall\Commands\ExecDrushCommand::setArgv()
   */
  private static function arrayInputAsArgv(array $input): array {
    array_unshift($input, '/path/to/drall', 'exec');

    $argv = [];
    foreach ($input as $key => $value) {
      if (is_numeric($key) || $key === 'cmd') {
        $argv[] = $value;
        continue;
      }

      $argv[] = "$key=$value";
    }

    return $argv;
  }

}
