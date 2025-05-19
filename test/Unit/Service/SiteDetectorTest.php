<?php

use Consolidation\SiteAlias\SiteAliasFileDiscovery;
use Consolidation\SiteAlias\SiteAliasFileLoader;
use Consolidation\SiteAlias\SiteAliasManager;
use Consolidation\SiteAlias\Util\YamlDataFileLoader;
use Drall\Model\SiteDetectorOptions;
use Drall\Service\SiteDetector;
use Drall\TestCase;

/**
 * @covers \Drall\Service\SiteDetector
 */
class SiteDetectorTest extends TestCase {

  protected SiteDetector $subject;

  protected function setUp(): void {
    $siteAliasFileLoader = new SiteAliasFileLoader(
      new SiteAliasFileDiscovery([static::PATH_DRUPAL . "/drush/sites"])
    );
    $siteAliasFileLoader->addLoader('yml', new YamlDataFileLoader());
    $siteAliasManager = new SiteAliasManager($siteAliasFileLoader, static::PATH_DRUPAL);
    $siteAliasManager->addSearchLocation('drush/sites');

    $this->subject = new SiteDetector($this->createDrupalFinderStub(), $siteAliasManager);
  }

  public function testGetSiteDirNames() {
    $this->assertEquals(
      ['default', 'donnie', 'leo', 'mikey', 'ralph'],
      $this->subject->getSiteDirNames()
    );
  }

  public function testGetSiteDirNamesWithGroup() {
    $options = new SiteDetectorOptions();
    $options->setGroup('bluish');
    $this->assertEquals(
      ['donnie', 'leo'],
      $this->subject->getSiteDirNames($options),
    );
  }

  public function testGetSiteDirNamesWithFilter() {
    $options = new SiteDetectorOptions();
    $options->setFilter('leo||ralph');
    $this->assertEquals(
      ['leo', 'ralph'],
      $this->subject->getSiteDirNames($options)
    );
  }

  public function testGetSiteDirNamesWithRange() {
    $options = new SiteDetectorOptions();
    $options->setOffset(2)->setLimit(2);
    $this->assertEquals(
      ['leo', 'mikey'],
      $this->subject->getSiteDirNames($options),
    );
  }

  public function testGetSiteDirNamesWithNoDrupal() {
    $subject = new SiteDetector(
      $this->createDrupalFinderStub(static::PATH_NO_DRUPAL),
    );
    $this->expectException(\RuntimeException::class);
    $subject->getSiteDirNames();
  }

  public function testGetSiteDirNamesWithEmptyDrupal() {
    $subject = new SiteDetector(
      $this->createDrupalFinderStub(static::PATH_EMPTY_DRUPAL),
    );
    $this->assertEquals([], $subject->getSiteDirNames());
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

    $options = new SiteDetectorOptions();
    $options->setGroup('reddish');
    $this->assertEquals(
      [
        'michelangelo.com',
        'mikey.drall.local',
        'raphael.com',
        'ralph.drall.local',
      ],
      $this->subject->getSiteKeys($options)
    );
  }

  public function testGetSiteKeysWithGroup() {
    $options = new SiteDetectorOptions();
    $options->setGroup('bluish');
    $this->assertEquals(
      [
        'donatello.com',
        '8080.donatello.com',
        'donnie.drall.local',
        'leonardo.com',
        'leo.drall.local',
      ],
      $this->subject->getSiteKeys($options)
    );
  }

  public function testGetSiteKeysWithFilter() {
    $options = new SiteDetectorOptions();
    $options->setFilter('cowabunga');
    $this->assertEquals(
      ['cowabunga.com'],
      $this->subject->getSiteKeys($options)
    );
  }

  public function testGetSiteKeysWithRange() {
    $options = new SiteDetectorOptions();
    $options->setOffset(2)->setLimit(1);
    $this->assertEquals(
      ['tmnt.drall.local'],
      $this->subject->getSiteKeys($options)
    );
  }

  public function testGetUniqueSiteKeys() {
    $options = new SiteDetectorOptions();
    $this->assertEquals(
      [
        'tmnt.drall.local',
        'donnie.drall.local',
        'leo.drall.local',
        'mikey.drall.local',
        'ralph.drall.local',
      ],
      $this->subject->getSiteKeys($options, TRUE)
    );
  }

  public function testGetUniqueSiteKeysWithFilter() {
    $options = new SiteDetectorOptions();
    $options->setFilter('leo||ralph');
    $this->assertEquals(
      ['leo.drall.local', 'ralph.drall.local'],
      $this->subject->getSiteKeys($options, TRUE)
    );
  }

  public function testGetSiteKeysWithNoDrupal() {
    $subject = new SiteDetector(
      $this->createDrupalFinderStub(static::PATH_NO_DRUPAL),
    );
    $this->expectException(\RuntimeException::class);
    $subject->getSiteKeys();
  }

  public function testGetSiteKeysWithEmptyDrupal() {
    $subject = new SiteDetector(
      $this->createDrupalFinderStub(static::PATH_EMPTY_DRUPAL),
    );
    $this->assertEquals([], $subject->getSiteKeys());
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
    $options = new SiteDetectorOptions();
    $options->setGroup('bluish');
    $this->assertEquals(
      ['@donnie.local', '@leo.local'],
      $this->subject->getSiteAliases($options)
    );
  }

  public function getGetSiteAliasesWithFilter() {
    $this->assertEquals(
      ['@leo.local', '@ralph.local'],
      $this->subject->getSiteAliases(NULL, 'leo||ralph')
    );
  }

  public function getGetSiteAliasesWithRange() {
    $options = new SiteDetectorOptions();
    $options->setOffset(2)->setLimit(2);
    $this->assertEquals(
      ['@leo.local', '@ralph.local'],
      $this->subject->getSiteAliases($options),
    );
  }

  public function testGetSiteAliasNames() {
    $this->assertEquals(
      ['@donnie', '@leo', '@mikey', '@ralph', '@tmnt'],
      $this->subject->getSiteAliasNames()
    );
  }

  public function testGetSiteAliasNamesWithGroup() {
    $options = new SiteDetectorOptions();
    $options->setGroup('bluish');
    $this->assertEquals(
      ['@donnie', '@leo'],
      $this->subject->getSiteAliasNames($options)
    );
  }

  public function testGetSiteAliasNamesWithFilter() {
    $options = new SiteDetectorOptions();
    $options->setFilter('leo||ralph');
    $this->assertEquals(
      ['@leo', '@ralph'],
      $this->subject->getSiteAliasNames($options)
    );
  }

  public function testGetSiteAliasNamesWithRange() {
    $options = new SiteDetectorOptions();
    $options->setOffset(2)->setLimit(2);
    $this->assertEquals(
      ['@mikey', '@ralph'],
      $this->subject->getSiteAliasNames($options)
    );
  }

  public function testGetSiteAliasNamesWithNothingToFilter() {
    $options = new SiteDetectorOptions();
    $options->setGroup('unknown')
      ->setFilter('leo||ralph');
    $this->assertEquals(
      [],
      $this->subject->getSiteAliasNames($options)
    );
  }

  public function testGetDrushPath() {
    $this->assertEquals(
      '/opt/drupal/vendor/bin/drush',
      $this->subject->getDrushPath()
    );
  }

  public function testDetectFromDirectory(): void {
    $this->assertEquals([
      'default' => 'default',
      'donnie' => 'donnie',
      'leo' => 'leo',
      'mikey' => 'mikey',
      'ralph' => 'ralph',
    ], SiteDetector::detectFromDirectory(static::PATH_DRUPAL . '/web/sites'));
  }

  public function testDetectFromIncorrectDirectory(): void {
    $this->assertEquals([], SiteDetector::detectFromDirectory('/foo'));
  }

}
