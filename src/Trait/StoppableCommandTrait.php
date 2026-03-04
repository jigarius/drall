<?php

namespace Drall\Trait;

use Psr\Log\LoggerAwareTrait;

/**
 * Inflection trait for the site detector service.
 */
trait StoppableCommandTrait {

  use LoggerAwareTrait;

  /**
   * Whether the command has been requested to stop.
   */
  private bool $isStopped = FALSE;

  /**
   * Whether the PCNTL extension is enabled.
   *
   * If the said extension is enabled, then the StoppableCommand should be avoided.
   *
   * @return bool
   *   True or False.
   */
  public static function isPcntlEnabled(): bool {
    if (extension_loaded('pcntl') && function_exists('pcntl_signal')) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Gets the path to a drall.stop file.
   *
   * @return string
   *   Path to drall.stop.
   */
  private function getStopFilePath(): string {
    return sys_get_temp_dir() . '/drall.stop';
  }

  /**
   * Whether execution has been stopped using the "stop" command.
   *
   * Some servers that don't have the PCNTL extension, thereby disallowing
   * users from gracefully exiting Drall. For such cases, a graceful exit can
   * be requested by creating a file at tmp/sites/default/files/drall.stop.
   *
   * @return bool
   *   True or False.
   */
  private function isStopped(): bool {
    // In case we already know that a stop was requested.
    if ($this->isStopped) {
      return $this->isStopped;
    }

    if (!is_file($this->getStopFilePath())) {
      return FALSE;
    }

    // Stop commands that started before the "stop" was made.
    $stopTimestamp = file_get_contents($this->getStopFilePath());
    if ($_SERVER['REQUEST_TIME'] >= $stopTimestamp) {
      return FALSE;
    }

    $this->logger->warning("A stop was requested using '{command}'.", ['command' => 'drall stop']);
    return $this->isStopped = TRUE;
  }

  /**
   * Request the command to stop using the drall.stop file.
   */
  protected function stop(): void {
    $now = new \DateTime();
    if (FALSE === file_put_contents($this->getStopFilePath(), $now->getTimestamp())) {
      throw new \RuntimeException("Failed to write: {$this->getStopFilePath()}");
    }

    $this->logger->notice("A stop was requested at {datetime}.", ['datetime' => $this->formatDateTime($now)]);
    $this->logger->debug("Touched file: {file}", ['file' => $this->getStopFilePath()]);
  }

}
