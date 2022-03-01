<?php

namespace Drall\Commands;

use Drall\Traits\SiteDetectorAwareTrait;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;

abstract class BaseCommand extends Command {

  use LoggerAwareTrait;
  use SiteDetectorAwareTrait;

}
