<?php

use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Runners\FakeRunner;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use DrupalFinder\DrupalFinder;
use Drall\Drall;
use Drall\Services\SiteDetector;
use Drall\TestCase;

/**
 * @covers \Drall\Commands\BaseCommand
 * @covers \Drall\Commands\BaseExecCommand
 */
class BaseExecCommandTest extends TestCase {

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

    $app = new Drall($siteDetectorMock);
    $input = ['cmd' => 'cat @@uri'];
    $command = $app->find('exec:shell')
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);
    $tester->execute($input);

    $tester->assertCommandIsSuccessful();
    $this->assertEquals(
      '[warning] No Drupal sites found.' . PHP_EOL,
      $tester->getDisplay(),
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

    $app = new Drall($siteDetectorMock);
    $input = ['cmd' => 'drush @@site.dev core:rebuild'];
    $runner = new FakeRunner();
    $runner->setExitCode(5);
    /** @var ExecCommand $command */
    $command = $app->find('exec:shell')
      ->setRunner($runner)
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);

    $this->assertEquals(1, $tester->execute($input));
  }

  public function testExecuteWithoutPlaceholders() {
    $app = new Drall();
    $input = ['cmd' => 'drush core:status'];
    /** @var ExecCommand $command */
    $command = $app->find('exec:shell')
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);

    $this->assertEquals(1, $tester->execute($input));
    $this->assertEquals(
      '[error] The command has no placeholders and it can be run without Drall.' . PHP_EOL,
      $tester->getDisplay()
    );
  }

  public function testExecuteWithMixedPlaceholders() {
    $app = new Drall();
    $input = ['cmd' => 'drush @@site.local core:status && drush --uri=@@uri core:status'];
    /** @var ExecCommand $command */
    $command = $app->find('exec:shell')
      ->setArgv(self::arrayInputAsArgv($input));
    $tester = new CommandTester($command);

    $this->assertEquals(1, $tester->execute($input));
    $this->assertEquals(
      '[error] The command cannot contain both @@uri and @@site placeholders.' . PHP_EOL,
      $tester->getDisplay()
    );
  }

  /**
   * Drall caps the maximum number of workers.
   */
  public function testExecuteWithWorkerLimit() {
    $input = [
      'cmd' => 'drush --uri=@@uri core:status --fields=site',
      '--root' => $this->drupalDir(),
      '--drall-workers' => 17,
      '--drall-verbose' => TRUE,
    ];

    $app = new Drall(NULL, new ArrayInput($input));
    /** @var \Drall\Commands\ExecShellCommand $command */
    $command = $app->find('exec:shell');
    $command->setArgv(self::arrayInputAsArgv($input))
      ->setRunner(new FakeRunner());
    $tester = new CommandTester($command);
    $tester->execute($input);

    $this->assertNotEmpty($tester->getDisplay());
    $this->assertStringStartsWith(
      '[warning] Limiting workers to 16, which is the maximum.' . PHP_EOL,
      $tester->getDisplay()
    );
  }

  public function testExecuteWithWorkers() {
    $input = [
      'cmd' => 'drush --uri=@@uri core:status --fields=site',
      '--root' => $this->drupalDir(),
      '--drall-workers' => 2,
      '--drall-verbose' => TRUE,
    ];

    $app = new Drall(NULL, new ArrayInput($input));
    /** @var \Drall\Commands\ExecShellCommand $command */
    $command = $app->find('exec:shell');
    $command->setArgv(self::arrayInputAsArgv($input))
      ->setRunner(new FakeRunner());
    $tester = new CommandTester($command);
    $tester->execute($input, [
      'verbosity' => OutputInterface::VERBOSITY_VERY_VERBOSE,
    ]);

    $this->assertStringStartsWith(
      '[info] Executing with 2 workers.',
      $tester->getDisplay()
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
