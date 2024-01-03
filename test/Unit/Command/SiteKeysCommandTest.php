<?php

namespace Unit\Command;

use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Drall;
use Drall\Service\SiteDetector;
use Drall\TestCase;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Drall\Command\BaseCommand
 * @covers \Drall\Command\SiteDirectoriesCommand
 */
class SiteKeysCommandTest extends TestCase {

  public function testExecute() {
    $drupalFinder = new DrupalFinder();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteKeys'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteKeys')
      ->willReturn(['donatello.com', 'leonardo.com']);

    $app = new Drall();
    /** @var \Drall\Command\SiteKeysCommand $command */
    $command = $app->find('site:keys');
    $command->setSiteDetector($siteDetectorMock);

    $tester = new CommandTester($command);
    $tester->execute([]);

    $tester->assertCommandIsSuccessful();

    $this->assertEquals(
      <<<EOF
donatello.com
leonardo.com

EOF,
      $tester->getDisplay()
    );
  }

  public function testExecuteWithGroup() {
    $drupalFinder = new DrupalFinder();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteKeys'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteKeys')
      ->with('none')
      ->willReturn(['tmnt.com']);

    $app = new Drall();

    /** @var \Drall\Command\SiteKeysCommand $command */
    $command = $app->find('site:keys');
    $command->setSiteDetector($siteDetectorMock);

    $tester = new CommandTester($command);
    $tester->execute(['--drall-group' => 'none']);

    $tester->assertCommandIsSuccessful();

    $this->assertEquals(
      <<<EOF
tmnt.com

EOF,
      $tester->getDisplay()
    );
  }

  public function testExecuteWithNoSiteDirectories() {
    $drupalFinder = new DrupalFinder();
    $siteAliasManager = new SiteAliasManager();

    $siteDetectorMock = $this->getMockBuilder(SiteDetector::class)
      ->setConstructorArgs([$drupalFinder, $siteAliasManager])
      ->onlyMethods(['getSiteKeys'])
      ->getMock();
    $siteDetectorMock
      ->expects($this->once())
      ->method('getSiteKeys')
      ->willReturn([]);

    $app = new Drall();
    /** @var \Drall\Command\SiteKeysCommand $command */
    $command = $app->find('site:keys');
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
