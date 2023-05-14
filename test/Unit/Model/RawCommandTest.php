<?php

use Drall\Model\RawCommand;
use Drall\TestCase;

/**
 * @covers \Drall\Model\RawCommand
 */
class RawCommandTest extends TestCase {

  public function testToString() {
    $command = new RawCommand('hello world');
    $this->assertEquals('hello world', (string) $command);
  }

  public function testFromArgv() {
    $command = RawCommand::fromArgv(['/path/to/drall', 'exs', 'ls', '-alh']);
    $this->assertEquals("ls -alh", (string) $command);
  }

}
