<?php

namespace Unit\Services;

use Consolidation\SiteAlias\SiteAliasFileDiscovery;
use Consolidation\SiteAlias\SiteAliasFileLoader;
use Consolidation\SiteAlias\SiteAliasManager;
use Consolidation\SiteAlias\Util\YamlDataFileLoader;
use Drall\Services\SiteDetector;
use Drall\TestCase;
use DrupalFinder\DrupalFinder;

/**
 * @covers \Drall\Services\SiteDetector
 */
class SiteDetectorTest extends TestCase {

  protected SiteDetector $subject;

  protected function setUp(): void {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot($this->drupalDir());

    $siteAliasFileLoader = new SiteAliasFileLoader(
      new SiteAliasFileDiscovery(["{$this->drupalDir()}/drush/sites"])
    );
    $siteAliasFileLoader->addLoader('yml', new YamlDataFileLoader());
    $siteAliasManager = new SiteAliasManager($siteAliasFileLoader, $this->drupalDir());
    $siteAliasManager->addSearchLocation('drush/sites');

    $this->subject = new SiteDetector($drupalFinder, $siteAliasManager);
  }

  public function testGetSiteDirNames() {
    $this->assertEquals(
      ['default', 'donnie', 'leo', 'mikey', 'ralph'],
      $this->subject->getSiteDirNames()
    );
  }

  public function testGetSiteDirNamesWithGroup() {
    $this->assertEquals(
      ['donnie', 'leo'],
      $this->subject->getSiteDirNames('bluish')
    );
  }

  public function testGetSiteDirNamesWithFilter() {
    $this->assertEquals(
      ['leo', 'ralph'],
      $this->subject->getSiteDirNames(NULL, 'leo||ralph')
    );
  }

  public function testGetSiteDirNamesWithNoDrupal() {
    $this->subject = new SiteDetector(new DrupalFinder(), new SiteAliasManager());

    $this->assertEquals([], $this->subject->getSiteDirNames());
  }

  public function testGetSiteKeys() {
    $this->assertEquals(
      [
        'tmnt.com',
        'cowabunga.com',
        'tmnt.drall.local',
        'donatello.com',
        '8080.donatello.com',
        'donnie.drall.local',
        'leonardo.com',
        'leo.drall.local',
        'michelangelo.com',
        'mikey.drall.local',
        'raphael.com',
        'ralph.drall.local',
      ],
      $this->subject->getSiteKeys()
    );

    $this->assertEquals(
      [
        'michelangelo.com',
        'mikey.drall.local',
        'raphael.com',
        'ralph.drall.local',
      ],
      $this->subject->getSiteKeys('reddish')
    );
  }

  public function testGetSiteKeysWithFilter() {
    $this->assertEquals(
      ['cowabunga.com'],
      $this->subject->getSiteKeys(NULL, 'cowabunga')
    );
  }

  public function testGetUniqueSiteKeys() {
    $this->assertEquals(
      [
        'tmnt.drall.local',
        'donnie.drall.local',
        'leo.drall.local',
        'mikey.drall.local',
        'ralph.drall.local',
      ],
      $this->subject->getSiteKeys(NULL, NULL, TRUE)
    );
  }

  public function testGetUniqueSiteKeysWithFilter() {
    $this->assertEquals(
      ['leo.drall.local', 'ralph.drall.local'],
      $this->subject->getSiteKeys(NULL, 'leo||ralph', TRUE)
    );
  }

  public function testGetSiteKeysWithNoDrupal() {
    $this->subject = new SiteDetector(new DrupalFinder(), new SiteAliasManager());

    $this->assertEquals([], $this->subject->getSiteKeys());
  }

  public function testGetSiteAliases() {
    $this->assertEquals(
      [
        '@donnie.local',
        '@leo.local',
        '@mikey.local',
        '@ralph.local',
        '@tmnt.local',
      ],
      $this->subject->getSiteAliases()
    );
  }

  public function testGetSiteAliasesWithGroup() {
    $this->assertEquals(
      ['@donnie.local', '@leo.local'],
      $this->subject->getSiteAliases('bluish')
    );
  }

  public function getGetSiteAliasesWithFilter() {
    $this->assertEquals(
      ['@leo.local', '@ralph.local'],
      $this->subject->getSiteAliases(NULL, 'leo||ralph')
    );
  }

  public function testGetSiteAliasNames() {
    $this->assertEquals(
      ['@donnie', '@leo', '@mikey', '@ralph', '@tmnt'],
      $this->subject->getSiteAliasNames()
    );
  }

  public function testGetSiteAliasNamesWithGroup() {
    $this->assertEquals(
      ['@donnie', '@leo'],
      $this->subject->getSiteAliasNames('bluish')
    );
  }

  public function testGetSiteAliasNamesWithFilter() {
    $this->assertEquals(
      ['@leo', '@ralph'],
      $this->subject->getSiteAliasNames(NULL, 'leo||ralph')
    );
  }

  public function testGetSiteAliasNamesWithNothingToFilter() {
    $this->assertEquals(
      [],
      $this->subject->getSiteAliasNames('unknown', 'leo||ralph')
    );
  }

  public function testGetDrushPath() {
    $this->assertEquals(
      '/opt/drupal/vendor/bin/drush',
      $this->subject->getDrushPath()
    );
  }

  /**
   * Drush path is "drush" when a Drupal installation is not found.
   */
  public function testGetDrushPathWithoutDrupal() {
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot('/');
    $subject = new SiteDetector($drupalFinder, new SiteAliasManager());

    $this->assertEquals(
      'drush',
      $subject->getDrushPath()
    );
  }

}
