<?php

namespace Drall\Integration\Command;

use Drall\TestCase;
use Symfony\Component\Process\Process;

/**
 * @testdox Base Command
 * @covers \Drall\Command\BaseCommand
 */
class BaseCommandTest extends TestCase {

  /**
   * @testdox Detects --group.
   */
  public function testWithGroup(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --group=bluish --dry-run -vv -- ./vendor/bin/drush st',
      static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertOutputStartsWith(<<<EOT
[info] Using group: bluish
EOT, $process1->getOutput());

    // Short form.
    $process1 = Process::fromShellCommandline(
      'drall exec -g bluish --dry-run -vv -- ./vendor/bin/drush st',
      static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertOutputStartsWith(<<<EOT
[info] Using group: bluish
EOT, $process1->getOutput());
  }

  /**
   * @testdox Detects --filter.
   */
  public function testWithFilter(): void {
    $process1 = Process::fromShellCommandline(
      'drall exec --filter=leo --dry-run -vv -- ./vendor/bin/drush st',
      static::PATH_DRUPAL,
    );
    $process1->run();
    $this->assertOutputStartsWith(<<<EOT
[info] Using filter: leo
EOT, $process1->getOutput());

    // Short form.
    $process2 = Process::fromShellCommandline(
      'drall exec -f leo --dry-run -vv -- ./vendor/bin/drush st',
      static::PATH_DRUPAL,
    );
    $process2->run();
    $this->assertOutputStartsWith(<<<EOT
[info] Using filter: leo
EOT, $process2->getOutput());
  }

}
