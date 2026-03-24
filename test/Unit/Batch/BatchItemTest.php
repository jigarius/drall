<?php

namespace Unit\Batch;

use Drall\Batch\BatchItem;
use Drall\Batch\BatchItemStatus;
use Drall\TestCase;

/**
 * @covers \Drall\Batch\BatchItem
 */
class BatchItemTest extends TestCase {

  public function test_default_status_is_queued() {
    $item = new BatchItem('site1');
    $this->assertEquals(BatchItemStatus::Queued, $item->getStatus());
    $this->assertNull($item->startedAt);
    $this->assertNull($item->finishedAt);
  }

  public function test_constructor_with_explicit_status() {
    $item = new BatchItem('site1', status: BatchItemStatus::Processing);
    $this->assertEquals(BatchItemStatus::Processing, $item->getStatus());
  }

  public function test_to_string() {
    $item = new BatchItem('site1');
    $this->assertEquals('site1', (string) $item);
  }

  public function test_start() {
    $item = new BatchItem('site1');
    $item->start();
    $this->assertEquals(BatchItemStatus::Processing, $item->getStatus());
    $this->assertNotNull($item->startedAt);
    $this->assertNull($item->finishedAt);
  }

  public function test_finish() {
    $item = new BatchItem('site1');
    $item->start();
    $item->finish();
    $this->assertEquals(BatchItemStatus::Finished, $item->getStatus());
    $this->assertNotNull($item->startedAt);
    $this->assertNotNull($item->finishedAt);
  }

  public function test_get_duration_returns_null_when_not_started() {
    $item = new BatchItem('site1');
    $this->assertNull($item->getDuration());
  }

  public function test_get_duration_returns_null_when_not_finished() {
    $item = new BatchItem('site1');
    $item->start();
    $this->assertNull($item->getDuration());
  }

  public function test_get_duration_returns_interval() {
    $startedAt = new \DateTimeImmutable('2026-01-01 10:00:00');
    $finishedAt = new \DateTimeImmutable('2026-01-01 10:05:30');
    $item = new BatchItem('site1', $startedAt, $finishedAt, BatchItemStatus::Finished);
    $duration = $item->getDuration();
    $this->assertInstanceOf(\DateInterval::class, $duration);
    $this->assertEquals(5, $duration->i);
    $this->assertEquals(30, $duration->s);
  }

  public function test_to_array() {
    $item = new BatchItem('site1');
    $result = $item->toArray();
    $this->assertEquals('site1', $result['id']);
    $this->assertNull($result['startedAt']);
    $this->assertNull($result['finishedAt']);
    $this->assertEquals('q', $result['status']);
  }

  public function test_to_array_with_dates() {
    $startedAt = new \DateTimeImmutable('2026-01-01T10:00:00+00:00');
    $finishedAt = new \DateTimeImmutable('2026-01-01T10:05:00+00:00');
    $item = new BatchItem('site1', $startedAt, $finishedAt, BatchItemStatus::Finished);
    $result = $item->toArray();
    $this->assertEquals('site1', $result['id']);
    $this->assertEquals($startedAt->format('c'), $result['startedAt']);
    $this->assertEquals($finishedAt->format('c'), $result['finishedAt']);
    $this->assertEquals('f', $result['status']);
  }

  public function test_from_array() {
    $item = BatchItem::fromArray([
      'id' => 'site1',
      'startedAt' => '2026-01-01T10:00:00+00:00',
      'finishedAt' => '2026-01-01T10:05:00+00:00',
      'status' => 'f',
    ]);
    $this->assertEquals('site1', $item->id);
    $this->assertEquals(BatchItemStatus::Finished, $item->getStatus());
    $this->assertNotNull($item->startedAt);
    $this->assertNotNull($item->finishedAt);
  }

  public function test_from_array_minimal() {
    $item = BatchItem::fromArray(['id' => 'site1', 'status' => 'q']);
    $this->assertEquals('site1', $item->id);
    $this->assertEquals(BatchItemStatus::Queued, $item->getStatus());
    $this->assertNull($item->startedAt);
    $this->assertNull($item->finishedAt);
  }

  public function test_from_array_without_id_throws_exception() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Missing key: id');
    BatchItem::fromArray([]);
  }

  public function test_from_array_with_invalid_status_defaults_to_queued() {
    $item = BatchItem::fromArray([
      'id' => 'site1',
      'status' => 'invalid',
    ]);
    $this->assertEquals(BatchItemStatus::Queued, $item->getStatus());
  }

  public function test_roundtrip_to_array_from_array() {
    $startedAt = new \DateTimeImmutable('2026-01-01T10:00:00+00:00');
    $finishedAt = new \DateTimeImmutable('2026-01-01T10:05:00+00:00');
    $original = new BatchItem('site1', $startedAt, $finishedAt, BatchItemStatus::Finished);
    $restored = BatchItem::fromArray($original->toArray());
    $this->assertEquals($original->id, $restored->id);
    $this->assertEquals($original->getStatus(), $restored->getStatus());
    $this->assertEquals(
      $original->startedAt->format('c'),
      $restored->startedAt->format('c')
    );
    $this->assertEquals(
      $original->finishedAt->format('c'),
      $restored->finishedAt->format('c')
    );
  }

}
