<?php

use Consolidation\SiteAlias\SiteAliasManager;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use DrupalFinder\DrupalFinder;
use Drall\Drall;
use Drall\Service\SiteDetector;
use Drall\TestCase;

/**
 * @covers \Drall\Command\BaseCommand
 * @covers \Drall\Command\SiteAliasesCommand
 */
class SiteAliasesCommandTest extends TestCase {

  public function testExecute() {
    $drupalFinder = new DrupalFinder();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteAliases'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteAliases')
      ->willReturn(['@leo.local', '@ralph.local']);

    $app = new Drall($siteDetectorMock);
    $tester = new CommandTester($app->find('site:aliases'));
    $tester->execute([]);

    $tester->assertCommandIsSuccessful();

    $this->assertEquals(
      <<<EOF
@leo.local
@ralph.local

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
      ->onlyMethods(['getSiteAliases'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteAliases')
      ->with('bluish')
      ->willReturn(['@tmnt.local']);

    $app = new Drall($siteDetectorMock);
    $tester = new CommandTester($app->find('site:aliases'));
    $tester->execute(['--drall-group' => 'bluish']);

    $tester->assertCommandIsSuccessful();
  }

  public function testExecuteWithNoSiteAliases() {
    $drupalFinder = new DrupalFinder();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteAliases'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteAliases')
      ->willReturn([]);

    $output = new BufferedOutput();

    $app = new Drall($siteDetectorMock);
    $tester = new CommandTester($app->find('site:aliases'));
    $tester->execute([]);

    $tester->assertCommandIsSuccessful();
    $this->assertEquals(
      '[warning] No site aliases found.' . PHP_EOL,
      $tester->getDisplay(TRUE)
    );
  }

}
