<?php

namespace Drall\Trait;

trait SignalAwareTrait {

  /**
   * Register a signal listener.
   *
   * @param int $signo
   *   Signal Number.
   * @param callable $handler
   *   A callable event listener.
   *
   * @return bool
   *   True if the listener was registered.
   */
  protected function registerSignalListener(int $signo, callable $handler): bool {
    if (!extension_loaded('pcntl')) {
      return FALSE;
    }

    declare(ticks = 1);
    \pcntl_signal($signo, $handler);

    return TRUE;
  }

  /**
   * Register a listener for SIGINT.
   *
   * @param callable $handler
   *   A callable event listener.
   *
   * @return bool
   *   True if the listener was registered.
   */
  protected function registerInterruptionListener(callable $handler): bool {
    if (!defined('SIGINT')) {
      return FALSE;
    }

    return self::registerSignalListener(SIGINT, $handler);
  }

}
