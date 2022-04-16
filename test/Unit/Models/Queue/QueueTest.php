<?php

namespace Drall\Models\Queue;

use Drall\Models\RawCommand;
use Drall\TestCase;

/**
 * @covers \Drall\Models\Queue\Queue
 */
class QueueTest extends TestCase {

  protected Queue $subject;

  protected function setUp(): void {
    parent::setUp();

    $qPath = $this->fixturesDir() . '/drallq.json';
    $data = json_decode(file_get_contents($qPath), TRUE);
    $this->subject = Queue::fromArray($data);
  }

  public function testGetVersion() {
    $this->assertEquals('x.y.z', $this->subject->getVersion());
  }

  public function testGetProgress() {
    $this->assertEquals(0, $this->subject->getProgress());

    $item = $this->subject->next();
    $this->subject->markAsDone($item);

    $this->assertEquals(16, $this->subject->getProgress());
  }

  public function testGetProgressWithoutItems() {
    $subject = new Queue('x', new RawCommand('cat sites/@@uri/settings.php'), '@@uri');
    $this->assertEquals(0, $subject->getProgress());
  }

  public function testGetCommand() {
    $this->assertEquals(
      'drush --uri=@@uri core:status --fields=site',
      (string) $this->subject->getCommand()
    );
  }

  public function testPush() {
    $subject = new Queue('x', new RawCommand('cat sites/@@uri/settings.php'), '@@uri');
    $this->assertEmpty($subject->asArray()['items']);
    $subject->push(new Item('default'));
    $this->assertCount(1, $subject->asArray()['items']);
  }

  public function testPushDuplicateItem() {
    $subject = new Queue('x', new RawCommand('cat sites/@@uri/settings.php'), '@@uri');
    $this->expectException(\BadMethodCallException::class);
    $subject->push(new Item('default'));
    $subject->push(new Item('default'));
  }

  public function testNext() {
    $item = $this->subject->next();
    $this->assertEquals('default', $item->getId());
    $this->assertEquals(ItemStatus::RUNNING, $item->getStatus());

    // The item must've been moved to the end and marked as "running".
    $data = $this->subject->asArray();
    $this->assertEquals(
      ['id' => 'default', 'status' => ItemStatus::RUNNING],
      array_pop($data['items'])
    );
  }

  public function testNextAfterFinished() {
    for ($i = 0; $i < 6; $i++) {
      $this->subject->next();
    }

    $this->assertNull($this->subject->next());
    $this->assertNull($this->subject->next());
  }

  public function testMarkAsDone() {
    $item = $this->subject->next();
    $this->assertEquals('default', $item->getId());
    $this->assertEquals(ItemStatus::RUNNING, $item->getStatus());

    $this->subject->markAsDone($item);
    $this->assertEquals(ItemStatus::DONE, $item->getStatus());

    // The item must've been moved to the end and marked as "running".
    $data = $this->subject->asArray();
    $this->assertEquals(
      ['id' => 'default', 'status' => ItemStatus::DONE],
      array_pop($data['items'])
    );
  }

}
