<?php

namespace Drall\Models\Queue;

class File {

  protected string $path;

  public function __construct(string $path) {
    $this->path = $path;
  }

  public function getPath(): string {
    return $this->path;
  }

  public function read(): Queue {
    if (!is_file($this->path)) {
      throw new \RuntimeException("File not found: $this->path");
    }

    $data = json_decode(file_get_contents($this->path), TRUE);
    if (!$data) {
      throw new \RuntimeException("File is not valid JSON: $this->path");
    }

    return Queue::fromArray($data);
  }

  public function write(Queue $data): void {
    if (!file_put_contents($this->path, json_encode($data->asArray(), JSON_PRETTY_PRINT))) {
      throw new \RuntimeException("File not writable: $this->path");
    }
  }

}
