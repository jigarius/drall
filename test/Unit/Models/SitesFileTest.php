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

}
