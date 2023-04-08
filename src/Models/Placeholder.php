<?php

namespace Drall\Models;

enum Placeholder: string {

  // Represents the first part of the site's Drush alias.
  case Site = '@@site';

  // Represents the site's directory under "DRUPAL/sites/".
  // @todo Rename to @@dir.
  case Directory = '@@dir';

  // Represents keys in the $sites array.
  case Key = '@@key';

  // Represents the site's unique URI as deduced from keys in $sites.
  case UniqueKey = '@@ukey';

  private function getRegExp(): string {
    return "/($this->value)\b/";
  }

  /**
   * Detect all valid placeholders present in a string.
   *
   * @param string $haystack
   *   A string.
   *
   * @return \Drall\Models\Placeholder[]
   *   All placeholders that were found (if any).
   */
  public static function search(string $haystack): array {
    $result = array_filter(self::cases(), function ($p) use ($haystack) {
      return preg_match($p->getRegExp(), $haystack);
    });

    return array_values($result);
  }

  /**
   * Replace placeholders with values in a given string.
   *
   * @param array $data
   *   An array with @@placeholder as keys and replacements as values.
   * @param string $subject
   *   The string on which to operate.
   *
   * @return string
   *   The subject string with placeholders replaced with values.
   *
   * @todo Revisit when PHP allows enum as array keys.
   */
  public static function replace(array $data, string $subject): string {
    $search = [];
    $replace = [];

    foreach ($data as $key => $value) {
      if (!$placeholder = Placeholder::tryFrom($key)) {
        continue;
      }

      $search[] = $placeholder->getRegExp();
      $replace[] = $value;
    }

    return preg_replace($search, $replace, $subject);
  }

}
