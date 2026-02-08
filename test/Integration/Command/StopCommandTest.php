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
   * @testdox Stops exec command.
   */
  public function testStop(): void {
    // Start a long-running exec command.
    $process1 = Process::fromShellCommandline(
      'drall exec --no-progress --interval=2 -- ./vendor/bin/drush st --field=site 2>&1',
      static::PATH_DRUPAL,
    );
    $process1->start();

    // Wait till the first two sites are processed.
    sleep(4);

    // Run the stop command.
    $process2 = Process::fromShellCommandline(
      'drall stop -v 2>&1',
      static::PATH_DRUPAL,
    );
    $process2->run();

    // Wait for process1 to finish.
    $process1->wait();

    // Assert that the first process detected the stop file.
    $this->assertOutputEquals(<<<EOF
sites/default
✔ default: Done
sites/donnie
✔ donnie: Done
[warning] A drall.stop file was detected. Stopping.

EOF, $process1->getOutput());

    $this->assertOutputEquals(<<<EOF
[warning] It is discommended to use the "stop" command when PCNTL is available.
[notice] Created file: /tmp/drall.stop

EOF, $process2->getOutput());
  }

}
