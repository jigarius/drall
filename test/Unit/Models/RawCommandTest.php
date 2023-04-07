<?php

namespace Unit\Models;

use Drall\Models\RawCommand;
use Drall\TestCase;

/**
 * @covers \Drall\Models\RawCommand
 */
class RawCommandTest extends TestCase {

  protected SitesFile $subject;

  public function testToString() {
    $command = new RawCommand('hello world');
    $this->assertEquals('hello world', (string) $command);
  }

  public function testWith() {
    $command = new RawCommand('Hello @@human.dev! Good @@time.');
    $this->assertEquals(
      'Hello Jerry.dev! Good afternoon.',
      $command->with(['human' => 'Jerry', 'time' => 'afternoon'])
    );

    $command = new RawCommand('Hello @@humanoid!');
    $this->assertEquals(
      'Hello @@humanoid!',
      $command->with(['human' => 'Jerry'])
    );
  }

  public function testFromArgv() {
    $command = RawCommand::fromArgv(['/path/to/drall', 'exs', 'ls', '-alh']);
    $this->assertEquals("ls -alh", (string) $command);
  }

}
