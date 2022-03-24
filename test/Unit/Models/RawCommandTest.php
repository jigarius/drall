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

  public function testHasPlaceholder() {
    $command = new RawCommand('drush --uri=@@uri st');
    $this->assertTrue($command->hasPlaceholder('uri'));

    $command = new RawCommand('@@site.dev st');
    $this->assertTrue($command->hasPlaceholder('site'));

    $command = new RawCommand('/path/to/@@uri/files');
    $this->assertTrue($command->hasPlaceholder('uri'));

    $command = new RawCommand('drush --uri=@@uri st');
    $this->assertFalse($command->hasPlaceholder('URI'));

    $command = new RawCommand('drush @@urinal');
    $this->assertFalse($command->hasPlaceholder('uri'));
  }

  public function testWith() {
    $command = new RawCommand('Hello @@human.dev! Good @@time.');
    $this->assertEquals(
      'Hello Jerry.dev! Good afternoon.',
      $command->with(['human' => 'Jerry', 'time' => 'afternoon'])
    );

    // @todo Fix this.
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
