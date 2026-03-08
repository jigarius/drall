<?php

namespace Drall\Batch;

final class MemoryBatch extends BatchBase {

  protected function save(): void {
    // Nothing to save. State lives in memory only.
  }

}
