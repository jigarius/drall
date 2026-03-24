<?php

namespace Unit\Batch;

use Drall\Batch\BatchItemStatus;
use Drall\Batch\MemoryBatch;
use Drall\TestCase;

/**
 * @covers \Drall\Batch\MemoryBatch
 * @covers \Drall\Batch\BatchBase
 */
class MemoryBatchTest extends TestCase {

  public function test_initial_state() {
    $batch = new MemoryBatch();
    $this->assertEmpty($batch->getItems());
    $this->assertEmpty($batch->getQueuedItems());
    $this->assertEmpty($batch->getStartedItems());
    $this->assertEmpty($batch->getFinishedItems());
    $this->assertTrue($batch->isComplete());
    $this->assertEquals('0.00', $batch->getFinishedPercentage());
  }

  public function test_started_at_and_updated_at() {
    $batch = new MemoryBatch();
    $this->assertInstanceOf(\DateTimeImmutable::class, $batch->getStartedAt());
    $this->assertInstanceOf(\DateTimeImmutable::class, $batch->getUpdatedAt());
  }

  public function test_add_items() {
    $batch = new MemoryBatch();
    $batch->addItems(['site1', 'site2', 'site3']);
    $this->assertCount(3, $batch->getItems());
    $this->assertCount(3, $batch->getQueuedItems());
    $this->assertCount(0, $batch->getStartedItems());
    $this->assertCount(0, $batch->getFinishedItems());
    $this->assertFalse($batch->isComplete());
  }

  public function test_start_item() {
    $batch = new MemoryBatch();
    $batch->addItems(['site1', 'site2']);
    $items = $batch->getQueuedItems();
    $batch->startItem($items['site1']);
    $this->assertCount(1, $batch->getQueuedItems());
    $this->assertCount(1, $batch->getStartedItems());
    $this->assertCount(0, $batch->getFinishedItems());
    $this->assertEquals(BatchItemStatus::Processing, $items['site1']->getStatus());
  }

  public function test_finish_item() {
    $batch = new MemoryBatch();
    $batch->addItems(['site1']);
    $items = $batch->getQueuedItems();
    $batch->startItem($items['site1']);
    $batch->finishItem($items['site1']);
    $this->assertCount(0, $batch->getQueuedItems());
    $this->assertCount(0, $batch->getStartedItems());
    $this->assertCount(1, $batch->getFinishedItems());
    $this->assertEquals(BatchItemStatus::Finished, $items['site1']->getStatus());
    $this->assertTrue($batch->isComplete());
  }

  public function test_start_item_that_is_not_queued_or_started() {
    $batch = new MemoryBatch();
    $batch->addItems(['site1']);
    $items = $batch->getQueuedItems();
    $batch->startItem($items['site1']);
    $batch->finishItem($items['site1']);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Only a queued or started item can be started: site1');
    $batch->startItem($items['site1']);
  }

  public function test_finish_item_that_is_not_started() {
    $batch = new MemoryBatch();
    $batch->addItems(['site1']);
    $items = $batch->getQueuedItems();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Only a started item can be finished: site1');
    $batch->finishItem($items['site1']);
  }

  public function test_restart_started_item() {
    $batch = new MemoryBatch();
    $batch->addItems(['site1']);
    $items = $batch->getQueuedItems();
    $batch->startItem($items['site1']);
    // Restarting an already started item should not throw.
    $batch->startItem($items['site1']);
    $this->assertCount(1, $batch->getStartedItems());
  }

  public function test_get_finished_percentage() {
    $batch = new MemoryBatch();
    $batch->addItems(['site1', 'site2', 'site3', 'site4']);

    $items = $batch->getQueuedItems();
    $this->assertEquals('0.00', $batch->getFinishedPercentage());

    $batch->startItem($items['site1']);
    $batch->finishItem($items['site1']);
    $this->assertEquals('25.00', $batch->getFinishedPercentage());

    $batch->startItem($items['site2']);
    $batch->finishItem($items['site2']);
    $this->assertEquals('50.00', $batch->getFinishedPercentage());

    $batch->startItem($items['site3']);
    $batch->finishItem($items['site3']);
    $this->assertEquals('75.00', $batch->getFinishedPercentage());

    $batch->startItem($items['site4']);
    $batch->finishItem($items['site4']);
    $this->assertEquals('100.00', $batch->getFinishedPercentage());
  }

  public function test_get_finished_percentage_with_no_items() {
    $batch = new MemoryBatch();
    $this->assertEquals('0.00', $batch->getFinishedPercentage());
  }

  public function test_reset() {
    $batch = new MemoryBatch();
    $batch->addItems(['site1', 'site2']);
    $items = $batch->getQueuedItems();
    $batch->startItem($items['site1']);
    $batch->finishItem($items['site1']);

    $batch->reset();
    $this->assertEmpty($batch->getItems());
    $this->assertTrue($batch->isComplete());
  }

  public function test_is_complete() {
    $batch = new MemoryBatch();
    $this->assertTrue($batch->isComplete());

    $batch->addItems(['site1']);
    $this->assertFalse($batch->isComplete());

    $items = $batch->getQueuedItems();
    $batch->startItem($items['site1']);
    $this->assertFalse($batch->isComplete());

    $batch->finishItem($items['site1']);
    $this->assertTrue($batch->isComplete());
  }

  public function test_get_items_returns_all() {
    $batch = new MemoryBatch();
    $batch->addItems(['site1', 'site2', 'site3']);
    $items = $batch->getQueuedItems();

    $batch->startItem($items['site1']);
    $batch->finishItem($items['site1']);
    $batch->startItem($items['site2']);

    // site1=finished, site2=started, site3=queued.
    $allItems = $batch->getItems();
    $this->assertCount(3, $allItems);
    $this->assertArrayHasKey('site1', $allItems);
    $this->assertArrayHasKey('site2', $allItems);
    $this->assertArrayHasKey('site3', $allItems);
  }

}
