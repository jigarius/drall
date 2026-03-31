<?php

namespace Drall\Batch;

final class FileBatch extends BatchBase {

  /**
   * File handle for the lock file.
   *
   * @var resource|null
   */
  private $lockHandle = NULL;

  /**
   * Opens a file-based batch.
   *
   * If the file exists and contains valid batch data, it is loaded.
   * Otherwise, a fresh batch is initialized.
   *
   * @param string $path
   *   Path to the batch JSON file.
   * @param bool $writable
   *   Whether the batch should be writable. Writable mode acquires an
   *   exclusive file lock to prevent concurrent writes. Defaults to FALSE.
   *
   * @throws \RuntimeException
   *   If writable mode is requested but the lock cannot be acquired.
   */
  public function __construct(
    private readonly string $path,
    private readonly bool $writable = FALSE,
  ) {
    if ($this->writable) {
      $this->acquireLock();
    }

    if (!$this->load()) {
      $this->reset();
    }
  }

  /**
   * Releases the file lock on destruction.
   */
  public function __destruct() {
    $this->releaseLock();
  }

  /**
   * Whether the batch was opened in writable mode.
   */
  public function isWritable(): bool {
    return $this->writable;
  }

  /**
   * Loads batch data from the file.
   *
   * @return bool
   *   TRUE if the file was loaded successfully, FALSE otherwise.
   */
  private function load(): bool {
    if (!is_file($this->path)) {
      return FALSE;
    }

    $json = file_get_contents($this->path);
    if ($json === FALSE) {
      return FALSE;
    }

    $json = json_decode($json, TRUE);

    if (!is_array($json)) {
      return FALSE;
    }

    if (($json['version'] ?? NULL) !== static::VERSION) {
      return FALSE;
    }

    $json['startedAt'] = new \DateTimeImmutable($json['startedAt']);
    $json['updatedAt'] = new \DateTimeImmutable($json['updatedAt']);
    $json['started'] = array_map(fn ($a) => BatchItem::fromArray($a), $json['started'] ?? []);
    $json['queued'] = array_map(fn ($a) => BatchItem::fromArray($a), $json['queued'] ?? []);
    $json['finished'] = array_map(fn ($a) => BatchItem::fromArray($a), $json['finished'] ?? []);

    $this->data = $json;

    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \LogicException
   *   If the batch was opened in readonly mode.
   * @throws \RuntimeException
   *   If the file cannot be written.
   */
  protected function save(): void {
    if (!$this->isWritable()) {
      throw new \LogicException("Cannot write to a readonly batch file: $this->path");
    }

    $this->data['updatedAt'] = new \DateTimeImmutable();
    if (FALSE === file_put_contents($this->path, json_encode($this->toArray(), JSON_PRETTY_PRINT))) {
      throw new \RuntimeException("Could not write file: $this->path");
    }
  }

  /**
   * Acquires an exclusive lock on the batch file.
   *
   * @throws \RuntimeException
   *   If the lock file cannot be created or the lock cannot be acquired.
   */
  private function acquireLock(): void {
    $lockPath = $this->path . '.lock';
    $handle = @fopen($lockPath, 'c');
    if ($handle === FALSE) {
      throw new \RuntimeException("Could not create lock file: $lockPath");
    }

    if (!flock($handle, LOCK_EX | LOCK_NB)) {
      fclose($handle);
      throw new \RuntimeException("Could not acquire lock: $lockPath. Is another Drall instance running?");
    }

    $this->lockHandle = $handle;
  }

  /**
   * Releases the exclusive lock and removes the lock file.
   */
  private function releaseLock(): void {
    if ($this->lockHandle === NULL) {
      return;
    }

    flock($this->lockHandle, LOCK_UN);
    fclose($this->lockHandle);
    $this->lockHandle = NULL;

    $lockPath = $this->path . '.lock';
    if (is_file($lockPath)) {
      @unlink($lockPath);
    }
  }

}
