<?php

namespace Drall\Models\Queue;

use Drall\TestCase;

/**
 * @covers \Drall\Models\Queue\Item
 */
class ItemTest extends TestCase {

  protected ?Item $subject;

  protected function setUp(): void {
    parent::setUp();

    $this->subject = new Item('april', ItemStatus::PENDING);
  }

  public function testFromArray() {
    $data = ['id' => 'april', 'status' => ItemStatus::PENDING];
    $item = Item::fromArray($data);
    $this->assertEquals($data, $item->asArray());
  }

  public function testAsArray() {
    $this->assertEquals(
      ['id' => 'april', 'status' => ItemStatus::PENDING],
      $this->subject->asArray()
    );
  }

  public function testGetId() {
    $this->assertEquals('april', $this->subject->getId());
  }

  public function testGetStatus() {
    $this->assertEquals(ItemStatus::PENDING, $this->subject->getStatus());
  }

  public function testSetStatus() {
    $item = new Item('kasey');
    $this->assertEquals(ItemStatus::PENDING, $item->getStatus());
    $item->setStatus(ItemStatus::DONE);
    $this->assertEquals(ItemStatus::DONE, $item->getStatus());
  }

  /**
   * An invalid status cannot be assigned to an item.
   */
  public function testSetStatusWithInvalidStatus() {
    $item = new Item('april');
    $this->expectException(\InvalidArgumentException::class);
    $item->setStatus('z');
  }

}
