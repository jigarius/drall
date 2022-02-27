<?php

namespace Drall\Traits;

use Drall\Services\SiteDetector;

/**
 * Inflection trait for the site detector service.
 */
trait SiteDetectorAwareTrait {

  protected SiteDetector $siteDetector;

  /**
   * Sets a Site detector.
   */
  public function setSiteDetector(SiteDetector $siteDetector) {
    $this->siteDetector = $siteDetector;
  }

  /**
   * Get a site detector.
   *
   * @return SiteDetector
   *   A site detector.
   */
  public function siteDetector(): SiteDetector {
    if (!$this->hasSiteDetector()) {
      throw new \BadMethodCallException(
        'A site detecetor instance must first be assigned'
      );
    }

    return $this->siteDetector;
  }

  /**
   * @inheritdoc
   */
  protected function hasSiteDetector() {
    return isset($this->siteDetector);
  }

}
