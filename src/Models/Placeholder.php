<?php

namespace Drall\Models;

enum Placeholder: string {

  /**
   * Represents the first part of the site's Drush alias.
   */
  case Site = 'site';

  /**
   * Represents the site's directory under "DRUPAL/sites/".
   *
   * @todo Rename @@uri to @@dir.
   */
  case Directory = 'uri';

  public function token(): string {
    return "@@$this->value";
  }

}
