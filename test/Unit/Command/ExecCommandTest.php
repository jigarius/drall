<?php

use Consolidation\SiteAlias\SiteAliasManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use DrupalFinder\DrupalFinder;
use Drall\Drall;
use Drall\Service\SiteDetector;
use Drall\TestCase;

/**
 * @covers \Drall\Command\ExecCommand
 */
class ExecCommandTest extends TestCase {

  public function testNoSitesFound() {
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

    $app = new Drall($siteDetectorMock);
    $input = ['cmd' => 'cat @@dir'];
    $command = $app->find('exec')
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);
    $tester->execute($input);

    $tester->assertCommandIsSuccessful();
    $this->assertEquals(
      '[warning] No Drupal sites found.' . PHP_EOL,
      $tester->getDisplay(),
    );
  }

  public function testNonZeroExitCode() {
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
    $input = ['cmd' => 'drush @@site.dev core:rebuild'];
    /** @var ExecCommand $command */
    $command = $app->find('exec')
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);

    $this->assertEquals(1, $tester->execute($input));
  }

  public function testWithNoPlaceholders() {
    $app = new Drall();
    $input = ['cmd' => 'ls'];
    /** @var ExecCommand $command */
    $command = $app->find('exec')
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);

    $this->assertEquals(1, $tester->execute($input));
    $this->assertEquals(
      '[error] The command contains no placeholders. Please run it directly without Drall.' . PHP_EOL,
      $tester->getDisplay()
    );
  }

  public function testWithMixedPlaceholders() {
    $app = new Drall();
    $input = ['cmd' => 'drush @@site.local core:status && drush --uri=@@dir core:status'];
    /** @var ExecCommand $command */
    $command = $app->find('exec')
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);

    $this->assertEquals(1, $tester->execute($input));
    $this->assertEquals(
      '[error] The command contains: @@site, @@dir. Please use only one.' . PHP_EOL,
      $tester->getDisplay()
    );
  }

  /**
   * Drall caps the maximum number of workers.
   */
  public function testWorkerLimit() {
    $input = [
      'cmd' => 'drush --uri=@@dir core:status --fields=site',
      '--root' => $this->drupalDir(),
      '--drall-workers' => 17,
      '--drall-verbose' => TRUE,
    ];

    $app = new Drall(NULL, new ArrayInput($input));
    /** @var \Drall\Command\ExecCommand $command */
    $command = $app->find('exec');
    $command->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);
    $tester->execute($input);

    $this->assertNotEmpty($tester->getDisplay());
    $this->assertStringStartsWith(
      '[warning] Limiting workers to 16, which is the maximum.' . PHP_EOL,
      $tester->getDisplay()
    );
  }

  public function testWithWorkers() {
    $input = [
      'cmd' => 'drush --uri=@@dir core:status --fields=site',
      '--root' => $this->drupalDir(),
      '--drall-workers' => 2,
      '--drall-verbose' => TRUE,
    ];

    $app = new Drall(NULL, new ArrayInput($input));
    /** @var \Drall\Command\ExecCommand $command */
    $command = $app->find('exec');
    $command->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);
    $tester->execute($input, [
      'verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE,
    ]);

    $this->assertStringStartsWith(
      '[notice] Using 2 workers.',
      $tester->getDisplay()
    );
  }

  /**
   * Converts an array of input into an $argv like array.
   *
   * @param array $input
   *   Array of input as expected by CommandTester::execute().
   *
   * @return array
   *   Array resembling $argv.
   *
   * @see \Drall\Command\ExecCommand::setArgv()
   */
  private static function arrayInputAsArgv(array $input): array {
    array_unshift($input, '/opt/drall/bin/drall', 'exec');

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
