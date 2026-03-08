<?php

namespace Drall\Batch;

abstract class BatchBase implements BatchInterface {

  const VERSION = '1.0';

  protected array $data;

  public function __construct() {
    $this->reset();
  }

  public function reset(): void {
    $this->data = [
      'version' => static::VERSION,
      'startedAt' => new \DateTimeImmutable(),
      'updatedAt' => new \DateTimeImmutable(),
      'started' => [],
      'queued' => [],
      'finished' => [],
    ];
  }

  abstract protected function save(): void;

  public function addItems(array $ids): void {
    foreach ($ids as $id) {
      $item = new BatchItem($id);
      $this->data['queued'][$item->id] = $item;
    }
    $this->save();
  }

  public function claimItem(): ?BatchItem {
    foreach ($this->getQueuedItems() as $item) {
      unset($this->data['queued'][$item->id]);
      $this->data['started'][$item->id] = $item;
      $item->start();
      $this->save();
      return $item;
    }

    return NULL;
  }

  public function finishItem(BatchItem $item): void {
    if (!isset($this->data['started'][$item->id])) {
      throw new \RuntimeException("Only a started item can be finished: $item");
    }

    if (!$item->getStatus() === BatchItemStatus::Finished) {
      $item->finish();
    }

    unset($this->data['started'][$item->id]);
    $this->data['finished'][$item->id] = $item;
    $this->save();
  }

  /**
   * Get all started (in-progress) items.
   *
   * @return \Drall\Batch\BatchItem[]
   *   Batch items keyed by ID.
   */
  public function getStartedItems(): array {
    return $this->data['started'];
  }

  /**
   * Get all items.
   *
   * @return \Drall\Batch\BatchItem[]
   *   Batch items keyed by ID.
   */
  public function getItems(): array {
    return $this->data['started'] + $this->data['queued'] + $this->data['finished'];
  }

  /**
   * Get all queued items.
   *
   * @return \Drall\Batch\BatchItem[]
   *   Batch items keyed by ID.
   */
  public function getQueuedItems(): array {
    return $this->data['queued'];
  }

  /**
   * Get all finished items.
   *
   * @return \Drall\Batch\BatchItem[]
   *   Batch items keyed by ID.
   */
  public function getFinishedItems(): array {
    return $this->data['finished'];
  }

  public function getFinishedPercentage(): string {
    if (!$total = count($this->getItems())) {
      return '0.00';
    }

    return number_format(count($this->getFinishedItems()) / $total * 100, 2);
  }

  public function isComplete(): bool {
    return count($this->getQueuedItems()) === 0 && count($this->getStartedItems()) === 0;
  }

  public function getStartedAt(): \DateTimeImmutable {
    return $this->data['startedAt'];
  }

  public function getUpdatedAt(): \DateTimeImmutable {
    return $this->data['updatedAt'];
  }

  protected function toArray(): array {
    $data = $this->data;

    $data['startedAt'] = $data['startedAt']->format('c');
    $data['updatedAt'] = $data['updatedAt']->format('c');

    $data['started'] = array_map(fn($i) => $i->toArray(), $data['started']);
    $data['queued'] = array_map(fn($i) => $i->toArray(), $data['queued']);
    $data['finished'] = array_map(fn($i) => $i->toArray(), $data['finished']);

    return $data;
  }

}
