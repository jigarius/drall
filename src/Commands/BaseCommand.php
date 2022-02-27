<?php

namespace Drall\Commands;

use Drall\Traits\SiteDetectorAwareTrait;
use Symfony\Component\Console\Command\Command;

abstract class BaseCommand extends Command {

  use SiteDetectorAwareTrait;

}
