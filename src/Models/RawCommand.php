<?php

namespace Drall\Models;

class RawCommand {

  protected string $command;

  public function __construct(string $command) {
    $this->command = $command;
  }

  public function __toString() {
    return $this->command;
  }

  /**
   * Whether the command contains a given placeholder.
   *
   * @param string $name
   *   Name of the placeholder. E.g. "site" searches for "@@site".
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function hasPlaceholder(string $name): bool {
    return preg_match("/(@@$name)\b/", $this->command);
  }

  /**
   * Build an executable command with placeholders replaced.
   *
   * @param array $values
   *   Associative array of values with placeholders as keys.
   *
   * @return string
   *   Command with @@placeholders replaced with real values.
   */
  public function with(array $values = []): string {
    $search = [];
    $replace = [];

    foreach ($values as $k => $v) {
      $search[] = "/(@@$k)\b/";
      $replace[] = $v;
    }

    return preg_replace($search, $replace, $this->command);
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
