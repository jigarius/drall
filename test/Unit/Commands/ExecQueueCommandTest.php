<?php

use Symfony\Component\Console\Tester\ApplicationTester;
use Drall\Drall;
use Drall\Commands\BaseExecCommand;
use Drall\Commands\ExecDrushCommand;
use Drall\Models\Queue\File;
use Drall\Models\Queue\Item;
use Drall\Models\Queue\Queue;
use Drall\Models\RawCommand;
use Drall\TestCase;

/**
 * @covers \Drall\Commands\ExecQueueCommand
 * @covers \Drall\Commands\BaseExecCommand
 */
class ExecQueueCommandTest extends TestCase {

  public function testExtendsBaseCommand() {
    $this->assertTrue(is_subclass_of(ExecDrushCommand::class, BaseExecCommand::class));
  }

  public function testExecuteWithNonExistentQueueFile() {
    $qPath = $this->createTempFilePath();
    unlink($qPath);

    $tester = new ApplicationTester(new Drall());
    $tester->run([
      'command' => 'exec:queue',
      'drallq-file' => $qPath,
      '--drall-verbose' => TRUE,
    ]);

    $this->assertEquals(
      "[error] Queue file not found: $qPath" . PHP_EOL,
      $tester->getDisplay(),
    );
  }

  public function testExecute() {
    $qFile = new File($this->createTempFilePath());
    $qData = new Queue(
      'x.y.z',
      new RawCommand('echo "We at @@uri."'),
      'uri'
    );
    $qData->push(new Item('default'));
    $qFile->write($qData);

    $tester = new ApplicationTester(new Drall());
    $tester->run([
      'command' => 'exec:queue',
      'drallq-file' => $qFile->getPath(),
      '--drall-worker-id' => 'test',
      '--drall-verbose' => TRUE,
    ]);

    $this->assertEquals(
      <<<EOF
[info] [drall:test] Started: echo "We at default."
[info] [drall:test] Finished: echo "We at default."
We at default.
EOF,
      trim($tester->getDisplay(TRUE)),
    );
  }

}
