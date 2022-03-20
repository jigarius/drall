<?php

namespace Drall\Commands;

use Drall\Traits\SiteDetectorAwareTrait;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

abstract class BaseCommand extends Command {

  use LoggerAwareTrait;
  use SiteDetectorAwareTrait;

  protected function configure() {
    $this->addOption(
      'root',
      NULL,
      InputOption::VALUE_OPTIONAL,
      'Drupal root or Composer root.'
    );
  }

}
