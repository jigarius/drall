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

    $this->assertEquals(
      ['donnie', 'leo'],
      $this->subject->getSiteDirNames('bluish')
    );

    $this->assertEquals(
      ['mikey', 'ralph'],
      $this->subject->getSiteDirNames('reddish')
    );
  }

  public function testGetSiteDirNamesWithNoDrupal() {
    $this->subject = new SiteDetector(new DrupalFinder(), new SiteAliasManager());

    $this->assertEquals([], $this->subject->getSiteDirNames('reddish'));
  }

  public function testGetSiteAliasNames() {
    $this->assertEquals(
      ['@donnie', '@leo', '@mikey', '@ralph', '@tmnt'],
      $this->subject->getSiteAliasNames()
    );

    $this->assertEquals(
      ['@donnie', '@leo'],
      $this->subject->getSiteAliasNames('bluish')
    );

    $this->assertEquals(
      ['@mikey', '@ralph'],
      $this->subject->getSiteAliasNames('reddish')
    );
  }

  public function testGetDrushPath() {
    $this->assertEquals(
      '/opt/drupal/vendor/bin/drush',
      $this->subject->getDrushPath()
    );
  }

}
