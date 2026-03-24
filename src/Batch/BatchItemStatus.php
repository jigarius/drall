<?php

namespace Drall\Batch;

enum BatchItemStatus: string {

  case Queued = 'q';

  case Processing = 'p';

  case Finished = 'f';

}
