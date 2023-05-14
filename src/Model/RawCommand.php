<?php

namespace Drall\Model;

class RawCommand {

  protected string $command;

  public function __construct(string $command) {
    $this->command = $command;
  }

  public function __toString() {
    return $this->command;
  }

  /**
   * Extracts a Drall sub-command from $argv, ignoring parts that are only for Drall.
   *
   * @param array $argv
   *   An $argv array.
   *
   * @return self
   *   The command without Drall elements.
   *
   * @example
   * Input: ['/path/to/drall', 'exs', 'drush', '--drall-option=foo', 'st', '--fields=site']
   * Output: 'drush st --fields=site'
   */
  public static function fromArgv(array $argv): self {
    // Ignore the script name and the word "exec".
    $parts = array_slice($argv, 2);
    // Ignore options with --drall namespace.
    $parts = array_filter($parts, fn($w) => !str_starts_with($w, '--drall-'));

    return new RawCommand(implode(' ', $parts));
  }

}
