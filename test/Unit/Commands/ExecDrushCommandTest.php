<?php

use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Runners\FakeRunner;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use DrupalFinder\DrupalFinder;
use Drall\Drall;
use Drall\Services\SiteDetector;
use Drall\TestCase;

/**
 * @covers \Drall\Commands\ExecDrushCommand
 */
class ExecDrushCommandTest extends TestCase {

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

  public function testExecuteWithNoSitesFound() {
    $drupalFinder = new DrupalFinder();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteDirNames'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteDirNames')
      ->willReturn([]);

    $output = new BufferedOutput();
    $app = new Drall($siteDetectorMock, NULL, $output);
    $input = ['cmd' => 'core:status', '--fields' => 'site'];
    $command = $app->find('exec:drush');
    $tester = new CommandTester($command);
    $tester->execute($input);

    $tester->assertCommandIsSuccessful();
    $this->assertEquals(
      "[warning] No Drupal sites found.\n",
      $output->fetch(),
    );
  }

  public function testExecuteWithNonZeroExitCode() {
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
    $input = ['cmd' => '@@site.dev core:rebuild'];
    $runner = new FakeRunner();
    $runner->setExitCode(5);
    /** @var ExecCommand $command */
    $command = $app->find('exec:drush')
      ->setRunner($runner)
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);

    $this->assertEquals(1, $tester->execute($input));
    $this->assertEquals(
      [
        '/foo/drush @splinter.dev core:rebuild',
        '/foo/drush @shredder.dev core:rebuild',
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
