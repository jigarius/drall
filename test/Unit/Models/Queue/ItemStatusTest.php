<?php

namespace Drall\Models\Queue;

use Drall\TestCase;

/**
 * @covers \Drall\Models\Queue\ItemStatus
 */
class ItemStatusTest extends TestCase {

  protected ?Item $subject;

  public function testAll() {
    $this->assertEquals(
      [ItemStatus::PENDING, ItemStatus::RUNNING, ItemStatus::DONE],
      ItemStatus::all()
    );
  }

}
