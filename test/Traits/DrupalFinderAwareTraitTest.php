<?php

use Drall\TestCase;
use Drall\Traits\DrupalFinderAwareTrait;
use DrupalFinder\DrupalFinder;

/**
 * @covers \Drall\Traits\DrupalFinderAwareTrait
 */
class DrupalFinderAwareTraitTest extends TestCase {

  public function testDrupalFinder() {
    $subject = $this->getMockForTrait(DrupalFinderAwareTrait::class);
    $drupalFinder = new DrupalFinder(__DIR__);
    $subject->setDrupalFinder($drupalFinder);

    $this->assertSame($drupalFinder, $subject->drupalFinder());
  }

  public function testDrupalFinderNotSet() {
    $this->expectException(\BadMethodCallException::class);
    $subject = $this->getMockForTrait(DrupalFinderAwareTrait::class);
    $subject->drupalFinder();
  }

}
