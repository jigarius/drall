<?php

use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Runners\FakeRunner;
use Symfony\Component\Console\Input\ArrayInput;
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
    $this->assertEquals(
      [
        'drush @splinter.dev core:rebuild',
        'drush @shredder.dev core:rebuild',
      ],
      $runner->commandHistory(),
    );
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
      '--drall-workers' => 15,
      '--drall-verbose' => TRUE,
    ];

    $app = new Drall(NULL, new ArrayInput($input));
    /** @var \Drall\Commands\ExecShellCommand $command */
    $command = $app->find('exec:shell');
    $command->setArgv(self::arrayInputAsArgv($input))
      ->setRunner($runner = new FakeRunner());
    $tester = new CommandTester($command);
    $tester->execute($input);

    $this->assertEquals(
      '[warning] Limiting workers to 10, which is the maximum.' . PHP_EOL,
      $tester->getDisplay()
    );
  }

  public function testExecuteWithWorkers() {
    $input = [
      'cmd' => 'drush --uri=@@uri core:status --fields=site',
      '--root' => $this->drupalDir(),
      '--drall-workers' => 2,
    ];

    $app = new Drall(NULL, new ArrayInput($input));
    /** @var \Drall\Commands\ExecShellCommand $command */
    $command = $app->find('exec:shell');
    $command->setArgv(self::arrayInputAsArgv($input))
      ->setRunner($runner = new FakeRunner());
    $tester = new CommandTester($command);
    $tester->execute($input);

    $this->assertCount(1, $runner->commandHistory());
    // Remove the random part of the basename in /path/to/62628d4ce25f7.drallq.json.
    $workerCommand = preg_replace("@([^/]+)(\.drallq\.json)@", 'RAND$2', $runner->commandHistory()[0]);

    $tmpDir = sys_get_temp_dir();
    $this->assertEquals(
      [
        "(/opt/drall/bin/drall exec:queue '$tmpDir/RAND.drallq.json' --drall-worker-id=1 &)",
        "(/opt/drall/bin/drall exec:queue '$tmpDir/RAND.drallq.json' --drall-worker-id=2 &)",
      ],
      explode(' && ', $workerCommand)
    );
  }

  /**
   * The --drall-verbose flag is passed to worker processes.
   */
  public function testExecuteWithWorkerVerbosity() {
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
      ->setRunner($runner = new FakeRunner());
    $tester = new CommandTester($command);
    $tester->execute($input);

    $this->assertCount(1, $runner->commandHistory());
    // Remove the random part of the basename in /path/to/62628d4ce25f7.drallq.json.
    $workerCommand = preg_replace("@([^/]+)(\.drallq\.json)@", 'RAND$2', $runner->commandHistory()[0]);

    $tmpDir = sys_get_temp_dir();
    $this->assertEquals(
      [
        "(/opt/drall/bin/drall exec:queue '$tmpDir/RAND.drallq.json' --drall-worker-id=1 --drall-verbose &)",
        "(/opt/drall/bin/drall exec:queue '$tmpDir/RAND.drallq.json' --drall-worker-id=2 --drall-verbose &)",
      ],
      explode(' && ', $workerCommand)
    );
  }

  /**
   * The --drall-debug flag is passed to worker processes.
   */
  public function testExecuteWithWorkerDebug() {
    $input = [
      'cmd' => 'drush --uri=@@uri core:status --fields=site',
      '--root' => $this->drupalDir(),
      '--drall-workers' => 2,
      '--drall-debug' => TRUE,
    ];

    $app = new Drall(NULL, new ArrayInput($input));
    /** @var \Drall\Commands\ExecShellCommand $command */
    $command = $app->find('exec:shell');
    $command->setArgv(self::arrayInputAsArgv($input))
      ->setRunner($runner = new FakeRunner());
    $tester = new CommandTester($command);
    $tester->execute($input);

    $this->assertCount(1, $runner->commandHistory());
    // Remove the random part of the basename in /path/to/62628d4ce25f7.drallq.json.
    $workerCommand = preg_replace("@([^/]+)(\.drallq\.json)@", 'RAND$2', $runner->commandHistory()[0]);

    $tmpDir = sys_get_temp_dir();
    $this->assertEquals(
      [
        "(/opt/drall/bin/drall exec:queue '$tmpDir/RAND.drallq.json' --drall-worker-id=1 --drall-debug &)",
        "(/opt/drall/bin/drall exec:queue '$tmpDir/RAND.drallq.json' --drall-worker-id=2 --drall-debug &)",
      ],
      explode(' && ', $workerCommand)
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
