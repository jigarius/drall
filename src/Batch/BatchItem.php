<?php

namespace Drall\Batch;

final class BatchItem {

  public function __construct(
    public readonly string $id,
    public ?\DateTimeImmutable $startedAt = NULL,
    public ?\DateTimeImmutable $finishedAt = NULL,
    private ?BatchItemStatus $status = NULL,
  ) {
    if ($this->status === NULL) {
      $this->status = BatchItemStatus::Queued;
    }
  }

  public function __toString(): string {
    return $this->id;
  }

  public function start(): void {
    $this->startedAt = new \DateTimeImmutable();
    $this->status = BatchItemStatus::Processing;
  }

  public function finish(): void {
    $this->finishedAt = new \DateTimeImmutable();
    $this->status = BatchItemStatus::Finished;
  }

  public function getDuration(): ?\DateInterval {
    if ($this->startedAt === NULL || $this->finishedAt === NULL) {
      return NULL;
    }
    return $this->startedAt->diff($this->finishedAt);
  }

  public function getStatus(): BatchItemStatus {
    return $this->status;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'startedAt' => $this->startedAt?->format('c'),
      'finishedAt' => $this->finishedAt?->format('c'),
      'status' => $this->status->value,
    ];
  }

  public static function fromArray(array $data): self {
    if (!isset($data['id'])) {
      throw new \InvalidArgumentException('Missing key: id');
    }

    return new self(
      id: $data['id'],
      startedAt: isset($data['startedAt']) ? new \DateTimeImmutable($data['startedAt']) : NULL,
      finishedAt: isset($data['finishedAt']) ? new \DateTimeImmutable($data['finishedAt']) : NULL,
      status: BatchItemStatus::tryFrom($data['status']) ?? BatchItemStatus::Queued,
    );
  }

}
