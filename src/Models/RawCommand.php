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

  public function getPlaceholders(): array {
    $command = $this->command;
    $result = array_filter(Placeholder::cases(), function($placeholder) use ($command) {
      return str_contains($command, $placeholder->token());
    });
    return array_unique($result);
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
