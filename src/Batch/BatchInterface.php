<?php

namespace Drall\Batch;

interface BatchInterface {

  public function reset(): void;

  public function addItems(array $ids): void;

  public function startItem(BatchItem $item): void;

  public function finishItem(BatchItem $item): void;

  /**
   * Get all started (in-progress) items.
   *
   * @return \Drall\Batch\BatchItem[]
   *   Batch items keyed by ID.
   */
  public function getStartedItems(): array;

  /**
   * Get all items.
   *
   * @return \Drall\Batch\BatchItem[]
   *   Batch items.
   */
  public function getItems(): array;

  /**
   * Get all queued items.
   *
   * @return \Drall\Batch\BatchItem[]
   *   Batch items.
   */
  public function getQueuedItems(): array;

  /**
   * Get all finished items.
   *
   * @return \Drall\Batch\BatchItem[]
   *   Batch items.
   */
  public function getFinishedItems(): array;

  public function getFinishedPercentage(): string;

  public function isComplete(): bool;

  public function getStartedAt(): \DateTimeImmutable;

  public function getUpdatedAt(): \DateTimeImmutable;

}
