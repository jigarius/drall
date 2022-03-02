<?php

use Drall\TestCase;
use Drall\Traits\SiteDetectorAwareTrait;
use Drall\Services\SiteDetector;
use DrupalFinder\DrupalFinder;
use Consolidation\SiteAlias\SiteAliasManager;

/**
 * @covers \Drall\Traits\SiteDetectorAwareTrait
 */
class SiteDetectorAwareTraitTest extends TestCase {

  public function testSiteDetector() {
    $drupalFinder = new DrupalFinder();
    $siteAliasManager = new SiteAliasManager();
    $siteDetector = new SiteDetector($drupalFinder, $siteAliasManager);

    $subject = $this->getMockForTrait(SiteDetectorAwareTrait::class);
    $subject->setSiteDetector($siteDetector);

    $this->assertSame($siteDetector, $subject->siteDetector());
  }

  public function testSiteDetectorNotSet() {
    $this->expectException(\BadMethodCallException::class);
    $subject = $this->getMockForTrait(SiteDetectorAwareTrait::class);
    $subject->siteDetector();
  }

}
