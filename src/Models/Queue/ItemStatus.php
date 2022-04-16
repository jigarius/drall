<?php

namespace Drall\Models\Queue;

/**
 * Item status.
 *
 * @todo Switch to Enum in the future.
 */
class ItemStatus {

  /**
   * Denotes items that need to be processed.
   */
  const PENDING = 'p';

  /**
   * Denotes items that are being processed.
   */
  const RUNNING = 'r';

  /**
   * Denotes items that have been processed.
   */
  const DONE = 'd';

  /**
   * Get all possible item statuses.
   *
   * @return string[]
   *   An array of possible item statuses.
   */
  public static function all(): array {
    return [self::PENDING, self::RUNNING, self::DONE];
  }

}
