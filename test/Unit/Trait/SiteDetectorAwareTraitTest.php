<?php

use Consolidation\SiteAlias\SiteAliasManager;
use Drall\Service\SiteDetector;
use Drall\TestCase;
use Drall\Trait\SiteDetectorAwareTrait;
use DrupalFinder\DrupalFinder;

/**
 * @covers \Drall\Trait\SiteDetectorAwareTrait
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
