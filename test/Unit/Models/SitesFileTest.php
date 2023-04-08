<?php

namespace Unit\Models;

use Drall\Models\SitesFile;
use Drall\TestCase;

/**
 * @covers \Drall\Models\SitesFile
 */
class SitesFileTest extends TestCase {

  protected SitesFile $subject;

  public function setUp(): void {
    $this->subject = new SitesFile($this->fixturesDir() . '/sites.valid.php');
  }

  public function testGetPath() {
    $this->assertEquals(
      $this->fixturesDir() . '/sites.valid.php',
      $this->subject->getPath()
    );
  }

  public function testInvalidPath() {
    $this->expectException(\RuntimeException::class);
    new SitesFile($this->fixturesDir() . '/sites.invalid.php');
  }

  public function testSitesNotDefined() {
    $this->expectException(\RuntimeException::class);

    $path = $this->createTempFile('<?php // $sites not defined.');
    new SitesFile($path);
  }

  public function testSitesNotArray() {
    $this->expectException(\RuntimeException::class);

    $path = $this->createTempFile('<?php $sites = TRUE;');
    new SitesFile($path);
  }

  public function testGetDirNames() {
    $this->assertEquals(
      ['default', 'donnie', 'leo', 'mikey', 'ralph'],
      $this->subject->getDirNames()
    );
  }

  public function testGetUris() {
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
      $this->subject->getKeys()
    );
  }

  /**
   * Get unique URIs (the last one) for each site.
   */
  public function testGetUniqueUris() {
    $this->assertEquals(
      [
        'tmnt.drall.local',
        'donnie.drall.local',
        'leo.drall.local',
        'mikey.drall.local',
        'ralph.drall.local',
      ],
      $this->subject->getKeys(TRUE)
    );
  }

}
