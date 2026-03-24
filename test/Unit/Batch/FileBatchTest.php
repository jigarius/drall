<?php

namespace Unit\Batch;

use Drall\Batch\BatchItemStatus;
use Drall\Batch\FileBatch;
use Drall\TestCase;

/**
 * @covers \Drall\Batch\FileBatch
 */
class FileBatchTest extends TestCase {

  public function test_creates_fresh_batch_when_file_does_not_exist() {
    $path = static::createTempFilePath();
    $batch = new FileBatch($path);
    $this->assertEmpty($batch->getItems());
    $this->assertTrue($batch->isComplete());
  }

  public function test_save_creates_file() {
    $path = static::createTempFilePath();
    $batch = new FileBatch($path);
    $batch->addItems(['site1']);
    $this->assertFileExists($path);
  }

  public function test_load_restores_state() {
    $path = static::createTempFilePath();

    // Create a batch and add items.
    $batch = new FileBatch($path);
    $batch->addItems(['site1', 'site2', 'site3']);
    $items = $batch->getQueuedItems();
    $batch->startItem($items['site1']);
    $batch->finishItem($items['site1']);
    $batch->startItem($items['site2']);

    // Load from the same file.
    $restored = new FileBatch($path);
    $this->assertCount(3, $restored->getItems());
    $this->assertCount(1, $restored->getQueuedItems());
    $this->assertCount(1, $restored->getStartedItems());
    $this->assertCount(1, $restored->getFinishedItems());
    $this->assertFalse($restored->isComplete());
  }

  public function test_load_preserves_item_statuses() {
    $path = static::createTempFilePath();

    $batch = new FileBatch($path);
    $batch->addItems(['site1', 'site2', 'site3']);
    $items = $batch->getQueuedItems();
    $batch->startItem($items['site1']);
    $batch->finishItem($items['site1']);
    $batch->startItem($items['site2']);

    $restored = new FileBatch($path);
    $allItems = $restored->getItems();
    $this->assertEquals(BatchItemStatus::Finished, $allItems['site1']->getStatus());
    $this->assertEquals(BatchItemStatus::Processing, $allItems['site2']->getStatus());
    $this->assertEquals(BatchItemStatus::Queued, $allItems['site3']->getStatus());
  }

  public function test_load_returns_false_for_missing_file() {
    $path = static::createTempFilePath();
    unlink($path);
    $batch = new FileBatch($path);
    $this->assertFalse($batch->load());
  }

  public function test_load_returns_false_for_invalid_version() {
    $path = static::createTempFile(json_encode([
      'version' => '0.0',
      'startedAt' => '2026-01-01T00:00:00+00:00',
      'updatedAt' => '2026-01-01T00:00:00+00:00',
      'started' => [],
      'queued' => [],
      'finished' => [],
    ]));
    $batch = new FileBatch($path);
    // The batch should have reset since the version is invalid.
    $this->assertEmpty($batch->getItems());
  }

  public function test_load_returns_false_for_invalid_json() {
    $path = static::createTempFile('Invalid JSON');
    $batch = new FileBatch($path);
    $this->assertFalse($batch->load());
    $this->assertEmpty($batch->getItems());
  }

  public function test_reset_clears_persisted_state() {
    $path = static::createTempFilePath();

    $batch = new FileBatch($path);
    $batch->addItems(['site1', 'site2']);
    $items = $batch->getQueuedItems();
    $batch->startItem($items['site1']);
    $batch->finishItem($items['site1']);

    $batch->reset();
    $this->assertEmpty($batch->getItems());
    $this->assertTrue($batch->isComplete());
  }

  public function test_get_finished_percentage_persists() {
    $path = static::createTempFilePath();

    $batch = new FileBatch($path);
    $batch->addItems(['site1', 'site2']);
    $items = $batch->getQueuedItems();
    $batch->startItem($items['site1']);
    $batch->finishItem($items['site1']);

    $restored = new FileBatch($path);
    $this->assertEquals('50.00', $restored->getFinishedPercentage());
  }

  public function test_started_at_persists() {
    $path = static::createTempFilePath();

    $batch = new FileBatch($path);
    $startedAt = $batch->getStartedAt();
    $batch->addItems(['site1']);

    $restored = new FileBatch($path);
    $this->assertEquals(
      $startedAt->format('c'),
      $restored->getStartedAt()->format('c')
    );
  }

  public function test_save_throws_exception_for_unwritable_path() {
    $path = '/dev/null/impossible';
    $batch = new FileBatch($path);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Could not write file: $path");
    @$batch->addItems(['site1']);
  }

  public function test_updated_at_changes_on_save() {
    $path = static::createTempFilePath();

    $batch = new FileBatch($path);
    $batch->addItems(['site1']);
    $updatedAt1 = $batch->getUpdatedAt();

    // Trigger another save.
    $batch->addItems(['site2']);
    $updatedAt2 = $batch->getUpdatedAt();

    $this->assertGreaterThanOrEqual($updatedAt1, $updatedAt2);
  }

}
