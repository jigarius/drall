<?php

use Consolidation\SiteAlias\SiteAliasManager;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use DrupalFinder\DrupalFinder;
use Drall\Drall;
use Drall\Services\SiteDetector;
use Drall\TestCase;

/**
 * @covers \Drall\Commands\BaseCommand
 * @covers \Drall\Commands\SiteDirectoriesCommand
 */
class SiteDirectoriesCommandTest extends TestCase {

  public function testExecute() {
    $drupalFinder = new DrupalFinder();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteDirNames'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteDirNames')
      ->willReturn(['donnie', 'leo']);

    $app = new Drall($siteDetectorMock);
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
    $drupalFinder = new DrupalFinder();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteDirNames'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteDirNames')
      ->with('bluish')
      ->willReturn(['default']);

    $app = new Drall($siteDetectorMock);
    $tester = new CommandTester($app->find('site:directories'));
    $tester->execute(['--drall-group' => 'bluish']);

    $tester->assertCommandIsSuccessful();
  }

  public function testExecuteWithNoSiteDirectories() {
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
    $tester = new CommandTester($app->find('site:directories'));
    $tester->execute([]);

    $tester->assertCommandIsSuccessful();

    $this->assertEquals('', $tester->getDisplay());
    $this->assertEquals("[warning] No Drupal sites found.\n", $output->fetch());
  }

}
