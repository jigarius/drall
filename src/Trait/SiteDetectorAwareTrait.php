<?php

namespace Drall\Trait;

use Drall\Service\SiteDetector;

/**
 * Inflection trait for the site detector service.
 */
trait SiteDetectorAwareTrait {

  protected SiteDetector $siteDetector;

  /**
   * Sets a Site detector.
   *
   * @param \Drall\Service\SiteDetector $siteDetector
   *   A site detector.
   */
  public function setSiteDetector(SiteDetector $siteDetector) {
    $this->siteDetector = $siteDetector;
  }

  /**
   * Get a site detector.
   *
   * @return \Drall\Service\SiteDetector
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
   * Whether the instance has a Site Detector attached.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  protected function hasSiteDetector(): bool {
    return isset($this->siteDetector);
  }

}
