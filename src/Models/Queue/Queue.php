<?php

namespace Drall\Models\Queue;

use Drall\Models\RawCommand;

/**
 * A Drall Queue.
 */
class Queue {

  /**
   * The version of Drall used to generate the queue.
   *
   * @var string
   */
  protected string $version;

  /**
   * The command to be executed.
   *
   * @var \Drall\Models\RawCommand
   */
  protected RawCommand $command;

  /**
   * The primary placeholder in the command.
   *
   * @var string
   *
   * @example
   * The placeholder 'site' is used for site aliases.
   */
  protected string $placeholder;

  /**
   * Items in the queue.
   *
   * @var \Drall\Models\Queue\Item[]
   */
  protected array $items;

  /**
   * Creates a Drall Queue Data object.
   *
   * @param string $version
   *   Drall version.
   * @param \Drall\Models\RawCommand $command
   *   The command to execute.
   * @param string $placeholder
   *   The placeholder that's present in the command.
   */
  public function __construct(string $version, RawCommand $command, string $placeholder) {
    $this->version = $version;
    $this->command = $command;
    $this->placeholder = $placeholder;
    $this->items = [];
  }

  /**
   * Creates a Queue object from an array.
   *
   * Usually this array comes from the contents of a .drallq.json file.
   *
   * @param array $data
   *   Queue data.
   *
   * @return static
   *   A Queue.
   */
  public static function fromArray(array $data): static {
    $result = new static($data['version'], new RawCommand($data['command']), $data['placeholder']);

    foreach ($data['items'] as $item) {
      $result->push(Item::fromArray($item));
    }

    return $result;
  }

  /**
   * Returns an array representation of the Queue.
   *
   * @return array
   *   Queue data.
   */
  public function asArray(): array {
    return [
      'version' => $this->getVersion(),
      'command' => (string) $this->getCommand(),
      'placeholder' => $this->getPlaceholder(),
      'items' => array_map(fn ($i) => $i->asArray(), $this->items),
    ];
  }

  public function getVersion(): string {
    return $this->version;
  }

  /**
   * Returns the progress of the queue's execution in percentage.
   *
   * @return int
   *   Progress percentage.
   */
  public function getProgress(): int {
    if (empty($this->items)) {
      return 0;
    }

    $done = 0;
    foreach ($this->items as $item) {
      if ($item->getStatus() === ItemStatus::DONE) {
        $done++;
      }
    }

    return floor($done / count($this->items) * 100);
  }

  public function getCommand(): RawCommand {
    return new RawCommand($this->command);
  }

  public function getPlaceholder(): string {
    return $this->placeholder;
  }

  /**
   * Pushes a new item to the end of the queue.
   *
   * @param Item $item
   *   An item.
   */
  public function push(Item $item): void {
    if (isset($this->items[$item->getId()])) {
      throw new \BadMethodCallException('Cannot add duplicate item: ' . $item->getId());
    }

    $this->items[$item->getId()] = $item;
  }

  /**
   * Returns the next item to be processed.
   *
   * The item returned is marked as "running". Also, if no "pending" items exist
   * in the queue, then NULL is returned.
   *
   * @return Item|null
   *   The next pending item, if any.
   */
  public function next(): ?Item {
    // Since the pending items are always towards the beginning, if the first
    // item is not pending, then we have no more pending items.
    $item = array_values($this->items)[0];
    if ($item->getStatus() !== ItemStatus::PENDING) {
      return NULL;
    }

    // Move the item to the end since it is not "pending" anymore.
    $item->setStatus(ItemStatus::RUNNING);
    unset($this->items[$item->getId()]);
    $this->items[$item->getId()] = $item;

    return $item;
  }

  /**
   * Marks the item as "done".
   *
   * @param Item $item
   *   The item.
   */
  public function markAsDone(Item $item): void {
    $item->setStatus(ItemStatus::DONE);
    $this->items[$item->getId()] = $item;
  }

}
