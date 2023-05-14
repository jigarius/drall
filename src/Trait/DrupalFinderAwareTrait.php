<?php

namespace Drall\Trait;

use DrupalFinder\DrupalFinder;

/**
 * Inflection trait for Drupal finder.
 */
trait DrupalFinderAwareTrait {

  protected ?DrupalFinder $drupalFinder;

  /**
   * Sets a Drupal Finder.
   */
  public function setDrupalFinder(DrupalFinder $drupalFinder) {
    $this->drupalFinder = $drupalFinder;
  }

  /**
   * Get a Drupal finder.
   *
   * @return \DrupalFinder\DrupalFinder
   *   A Drupal Finder.
   *
   * @throws \BadMethodCallException
   */
  public function drupalFinder(): DrupalFinder {
    if (!$this->hasDrupalFinder()) {
      throw new \BadMethodCallException(
        'A Drupal Finder instance must first be assigned'
      );
    }

    return $this->drupalFinder;
  }

  /**
   * Whether the instance has a Drupal Finder attached.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  protected function hasDrupalFinder(): bool {
    return isset($this->drupalFinder);
  }

}
