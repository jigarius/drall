<?php

namespace Drall\Models;

/**
 * Implements locking mechanism with a lock file.
 */
class Lock {

  /**
   * Path to the lock file.
   *
   * @var string
   */
  protected string $path;

  /**
   * Prepare for locking with a file at $path.
   *
   * @param string $path
   *   The /path/to/lock file.
   */
  public function __construct(string $path) {
    $this->path = $path;
  }

  /**
   * Get the path to the underlying lock file.
   *
   * @return string
   *   Path to a lock file. E.g. /tmp/foo.lock.
   */
  public function getPath(): string {
    return $this->path;
  }

  /**
   * Acquires a lock.
   *
   * This is done by creating a file in the lock path.
   *
   * @param bool $retry
   *   Retry until success.
   *
   * @return bool
   *   TRUE on success, FALSE otherwise.
   */
  public function acquire(bool $retry = FALSE): bool {
    while (TRUE) {
      // With the "x" mode, the file is opened with a lock.
      // If the file already exists, then fopen() fails, and we retry.
      $fh = @fopen($this->path, 'x');
      if ($fh) {
        fwrite($fh, getmypid() . ' @ ' . date('Y-m-d H:i:s.u'));
        fclose($fh);
        return TRUE;
      }

      if (!$retry) {
        break;
      }

      usleep(190000);
    }

    return FALSE;
  }

  /**
   * Releases the lock, if any.
   */
  public function release(): void {
    unlink($this->path);
  }

  /**
   * Whether the lock is currently in place.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function isLocked(): bool {
    return file_exists($this->path);
  }

}
