<?php

use Drall\Model\SiteDetectorOptions;
use Drall\TestCase;

class SiteDetectorOptionsTest extends TestCase {

  /**
   * @covers \Drall\Model\SiteDetectorOptions::fromArray()
   */
  public function testCreateFromArray(): void {
    $subject = SiteDetectorOptions::fromArray([
      'group' => 'bluish',
      'filter' => 'tnmt',
    ]);

    $this->assertEquals('bluish', $subject->getGroup());
    $this->assertEquals('tnmt', $subject->getFilter());
  }

  /**
   * @covers \Drall\Model\SiteDetectorOptions::setGroup()
   * @covers \Drall\Model\SiteDetectorOptions::getGroup()
   */
  public function testGroup(): void {
    $subject = new SiteDetectorOptions();
    $subject->setGroup('bluish');
    $this->assertEquals('bluish', $subject->getGroup());
  }

  /**
   * @covers \Drall\Model\SiteDetectorOptions::setFilter()
   * @covers \Drall\Model\SiteDetectorOptions::getFilter()
   */
  public function testFilter(): void {
    $subject = new SiteDetectorOptions();
    $subject->setFilter('tnmt');
    $this->assertEquals('tnmt', $subject->getFilter());
  }

  /**
   * @covers \Drall\Model\SiteDetectorOptions::setOffset()
   * @covers \Drall\Model\SiteDetectorOptions::getOffset()
   */
  public function testOffset(): void {
    $subject = new SiteDetectorOptions();
    $subject->setOffset(2);
    $this->assertEquals(2, $subject->getOffset());
  }

  /**
   * @covers \Drall\Model\SiteDetectorOptions::setLimit()
   * @covers \Drall\Model\SiteDetectorOptions::getLimit()
   */
  public function testLimit(): void {
    $subject = new SiteDetectorOptions();
    $subject->setLimit(3);
    $this->assertEquals(3, $subject->getLimit());

    // Negative limit throws an exception.
    $this->expectException(\ValueError::class);
    $this->expectExceptionMessage('Limit must be greater than zero.');
    $subject->setLimit(-5);
  }

}
