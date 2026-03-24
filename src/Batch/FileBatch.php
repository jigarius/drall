<?php

namespace Drall\Batch;

final class FileBatch extends BatchBase {

  public function __construct(
    private readonly string $path,
  ) {
    if (!$this->load()) {
      $this->reset();
    }
  }

  public function load(): bool {
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

  protected function save(): void {
    $this->data['updatedAt'] = new \DateTimeImmutable();
    if (FALSE === file_put_contents($this->path, json_encode($this->toArray(), JSON_PRETTY_PRINT))) {
      throw new \RuntimeException("Could not write file: $this->path");
    }
  }

}
