<?php

namespace Drall\Models\Queue;

/**
 * A queue item.
 */
class Item {

  /**
   * Item ID.
   *
   * @var string
   */
  protected string $id;

  /**
   * Item status.
   *
   * One of ItemStatus::* constants.
   *
   * @var string
   */
  protected string $status;

  public function __construct(string $id, string $status = ItemStatus::PENDING) {
    $this->id = $id;
    $this->status = $status;
  }

  /**
   * An array representation of the item.
   *
   * @return array
   *   Item data.
   */
  public function asArray(): array {
    return [
      'id' => $this->id,
      'status' => $this->status,
    ];
  }

  public function getId(): string {
    return $this->id;
  }

  public function getStatus(): string {
    return $this->status;
  }

  public function setStatus(string $status) {
    if (!in_array($status, ItemStatus::all())) {
      throw new \InvalidArgumentException("Invalid status: $status");
    }

    $this->status = $status;
  }

  /**
   * Creates an item from an array.
   *
   * @param array $item
   *   Item data.
   *
   * @return static
   *   An item.
   */
  public static function fromArray(array $item): self {
    return new self($item['id'], $item['status']);
  }

}
