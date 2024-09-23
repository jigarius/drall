<?php

use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Drall;
use Drall\Service\SiteDetector;
use Drall\TestCase;
use DrupalFinder\DrupalFinderComposerRuntime;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Drall\Command\BaseCommand
 * @covers \Drall\Command\SiteDirectoriesCommand
 */
class SiteDirectoriesCommandTest extends TestCase {

  public function testExecute() {
    $drupalFinder = new DrupalFinderComposerRuntime();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteDirNames'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteDirNames')
      ->willReturn(['donnie', 'leo']);

    $app = new Drall();
    /** @var \Drall\Command\SiteDirectoriesCommand $command */
    $command = $app->find('site:directories');
    $command->setSiteDetector($siteDetectorMock);
    $tester = new CommandTester($app->find('site:directories'));
    $tester->execute([]);

    $tester->assertCommandIsSuccessful();

    $this->assertEquals(
      <<<EOF
donnie
leo

EOF
,
      $tester->getDisplay()
    );
  }

  public function testExecuteWithGroup() {
    $this->markTestSkipped('Replace with integration test.');
    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteDirNames'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteDirNames')
      ->with('bluish')
      ->willReturn(['default']);

    $app = new Drall();
    /** @var \Drall\Command\SiteDirectoriesCommand $command */
    $command = $app->find('site:directories');
    $command->setSiteDetector($siteDetectorMock);
    $tester = new CommandTester($command);
    $tester->execute(['--drall-group' => 'bluish']);

    $tester->assertCommandIsSuccessful();
  }

  public function testExecuteWithNoSiteDirectories() {
    $drupalFinder = new DrupalFinderComposerRuntime();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteDirNames'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteDirNames')
      ->willReturn([]);

    $app = new Drall();
    /** @var \Drall\Command\SiteDirectoriesCommand $command */
    $command = $app->find('site:directories');
    $command->setSiteDetector($siteDetectorMock);
    $tester = new CommandTester($command);
    $tester->execute([]);

    $tester->assertCommandIsSuccessful();

    $this->assertEquals(
      '[warning] No Drupal sites found.' . PHP_EOL,
      $tester->getDisplay()
    );
  }

}
