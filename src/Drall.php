<?php

namespace Drall;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class Drall extends Application {

  const NAME = 'Drall';

  /**
   * Creates a Phpake Application instance.
   */
  public function __construct() {
    parent::__construct();

    $this->setName(self::NAME);
    $this->setVersion('0.0.0');

    $this->input = new ArgvInput();
    $this->output = new ConsoleOutput();
    $this->configureIO($this->input, $this->output);
  }

}
