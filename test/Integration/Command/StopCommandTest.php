<?php

namespace Integration\Command;

use Drall\TestCase;
use Symfony\Component\Process\Process;

/**
 * @testdox Stop Command
 * @covers \Drall\Command\StopCommand
 */
class StopCommandTest extends TestCase {

  /**
   * @testdox Stops all exec commands that started before "stop" was triggered.
   */
  public function testStop(): void {
    // Start a long-running exec command 1.
    $process1 = Process::fromShellCommandline(
      'drall exec --no-progress --interval=2 -- ./vendor/bin/drush st --field=site 2>&1',
      static::PATH_DRUPAL,
    );
    $process1->start();

    // Start long-running exec command 2.
    $process2 = Process::fromShellCommandline(
      'drall exec --no-progress --interval=2 -- ./vendor/bin/drush st --field=site 2>&1',
      static::PATH_DRUPAL,
    );
    $process2->start();

    // Wait till the first two sites are processed.
    sleep(4);

    // Run the stop command.
    $stopProcess = Process::fromShellCommandline(
      'drall stop -d 2>&1',
      static::PATH_DRUPAL,
    );
    $stopProcess->run();

    // Wait for all processes to finish.
    $process1->wait();
    $process2->wait();

    // Start long-running exec command 3.
    // This command is run after "stop" so it should finish successfully.
    $process3 = Process::fromShellCommandline(
      'drall exec --no-progress --interval=2 -- ./vendor/bin/drush st --field=site 2>&1',
      static::PATH_DRUPAL,
    );
    $process3->run();

    // Read the stop date and time.
    $stopFilePath = sys_get_temp_dir() . '/drall.stop';
    $this->assertFileExists($stopFilePath);
    $dtStop = new \DateTime('@' . trim(file_get_contents($stopFilePath)));

    // If PCNTL is available, a warning must be displayed.
    $this->assertOutputEquals(<<<EOF
[warning] It is discommended to use the "stop" command when PCNTL is available.
[notice] A stop was requested at {$dtStop->format('j M, Y @ H:i:s')}.
[debug] Touched file: /tmp/drall.stop

EOF, $stopProcess->getOutput());

    // Process 1 should detect the stop command.
    $this->assertOutputEquals(<<<EOF
sites/default
✔ default: Done
sites/donnie
✔ donnie: Done
[warning] A stop was requested using 'drall stop'.

EOF, $process1->getOutput(), 'Process 1 did not respect the "stop" command.');

    // Process 2 should detect the stop command.
    $this->assertOutputEquals(<<<EOF
sites/default
✔ default: Done
sites/donnie
✔ donnie: Done
[warning] A stop was requested using 'drall stop'.

EOF, $process2->getOutput(), 'Process 2 did not respect the "stop" command.');

    // Assert that the process 3 finished normally because the "stop" command
    // was executed before process 3 started.
    $this->assertOutputEquals(<<<EOF
sites/default
✔ default: Done
sites/donnie
✔ donnie: Done
sites/leo
✔ leo: Done
sites/mikey
✔ mikey: Done
sites/ralph
✔ ralph: Done

EOF, $process3->getOutput(), 'Process 3 did not finish successfully.');
  }

}
