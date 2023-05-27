<?php

use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Drall;
use Drall\Service\SiteDetector;
use Drall\TestCase;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Console\Tester\CommandTester;

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

    $app = new Drall();
    /** @var \Drall\Command\SiteAliasesCommand $command */
    $command = $app->find('site:aliases');
    $command->setSiteDetector($siteDetectorMock);
    $tester = new CommandTester($command);
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

    $app = new Drall();
    /** @var \Drall\Command\SiteAliasesCommand $command */
    $command = $app->find('site:aliases');
    $command->setSiteDetector($siteDetectorMock);
    $tester = new CommandTester($command);
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

    $app = new Drall();
    /** @var \Drall\Command\SiteAliasesCommand $command */
    $command = $app->find('site:aliases');
    $command->setSiteDetector($siteDetectorMock);
    $tester = new CommandTester($command);
    $tester->execute([]);

    $tester->assertCommandIsSuccessful();
    $this->assertEquals(
      '[warning] No site aliases found.' . PHP_EOL,
      $tester->getDisplay(TRUE)
    );
  }

}
