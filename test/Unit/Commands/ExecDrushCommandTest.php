<?php

use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Runners\FakeRunner;
use Symfony\Component\Console\Tester\CommandTester;
use DrupalFinder\DrupalFinder;
use Drall\Drall;
use Drall\Services\SiteDetector;
use Drall\Commands\BaseExecCommand;
use Drall\Commands\ExecDrushCommand;
use Drall\TestCase;

/**
 * @covers \Drall\Commands\ExecDrushCommand
 * @covers \Drall\Commands\BaseExecCommand
 */
class ExecDrushCommandTest extends TestCase {

  public function testExtendsBaseCommand() {
    $this->assertTrue(is_subclass_of(ExecDrushCommand::class, BaseExecCommand::class));
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
    $siteDetectorMock
      ->expects($this->once())
      ->method('getDrushPath')
      ->willReturn('/foo/drush');

    $app = new Drall($siteDetectorMock);
    $input = ['cmd' => 'core:status', '--fields' => 'site', '--uri' => '@@uri'];
    $runner = new FakeRunner();
    /** @var ExecCommand $command */
    $command = $app->find('exec:drush')
      ->setRunner($runner)
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);
    $tester->execute($input);

    $tester->assertCommandIsSuccessful();
    $this->assertEquals(
      [
        '/foo/drush core:status --fields=site --uri=default',
        '/foo/drush core:status --fields=site --uri=april',
        '/foo/drush core:status --fields=site --uri=kacey',
      ],
      $runner->commandHistory(),
    );
  }

  /**
   * If the command doesn't contain @@uri it is added automatically.
   */
  public function testExecuteWithImplicitSiteUris() {
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
    $siteDetectorMock
      ->expects($this->once())
      ->method('getDrushPath')
      ->willReturn('/foo/drush');

    $app = new Drall($siteDetectorMock);
    $input = ['cmd' => 'core:status', '--fields' => 'site'];
    $runner = new FakeRunner();
    /** @var ExecCommand $command */
    $command = $app->find('exec:drush')
      ->setRunner($runner)
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);
    $tester->execute($input);

    $tester->assertCommandIsSuccessful();
    $this->assertEquals(
      [
        '/foo/drush --uri=default core:status --fields=site',
        '/foo/drush --uri=april core:status --fields=site',
        '/foo/drush --uri=kacey core:status --fields=site',
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
    $siteDetectorMock
      ->expects($this->once())
      ->method('getDrushPath')
      ->willReturn('/foo/drush');

    $app = new Drall($siteDetectorMock);
    $input = ['cmd' => '@@site.dev core:status', '--fields' => 'site'];
    $runner = new FakeRunner();
    /** @var \Drall\Commands\ExecDrushCommand $command */
    $command = $app->find('exec:drush')
      ->setRunner($runner)
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);
    $tester->execute($input);

    $tester->assertCommandIsSuccessful();
    $this->assertEquals(
      [
        '/foo/drush @splinter.dev core:status --fields=site',
        '/foo/drush @shredder.dev core:status --fields=site',
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
