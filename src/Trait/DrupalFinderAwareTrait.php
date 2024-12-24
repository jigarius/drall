<?php

namespace Drall\Trait;

use DrupalFinder\DrupalFinderComposerRuntime;

/**
 * Inflection trait for Drupal finder.
 */
trait DrupalFinderAwareTrait {

  protected ?DrupalFinderComposerRuntime $drupalFinder;

  /**
   * Sets a Drupal Finder.
   */
  public function setDrupalFinder(DrupalFinderComposerRuntime $drupalFinder) {
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
  public function drupalFinder(): DrupalFinderComposerRuntime {
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
