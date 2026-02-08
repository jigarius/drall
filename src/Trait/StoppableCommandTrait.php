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
    if ($this->isStopped) {
      return $this->isStopped;
    }

    if (!is_file($this->getStopFilePath())) {
      return FALSE;
    }

    // @todo Only stop commands that started before the "stop" file was created.
    $this->logger->warning('A drall.stop file was detected. Stopping.');
    $this->deleteStopFile();
    return $this->isStopped = TRUE;
  }

  /**
   * Create a drall.stop file.
   */
  protected function createStopFile(): void {
    if (!touch($this->getStopFilePath())) {
      throw new \RuntimeException("Failed to touch: {$this->getStopFilePath()}");
    }

    $this->logger->notice("Created file: {file}", ['file' => $this->getStopFilePath()]);
  }

  /**
   * Delete the drall.stop file, if exists.
   */
  protected function deleteStopFile(): void {
    if (!unlink($this->getStopFilePath())) {
      throw new \RuntimeException("Failed to unlink: {$this->getStopFilePath()}");
    }
  }

}
