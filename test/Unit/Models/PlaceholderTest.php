<?php

namespace Unit\Models;

use Drall\Models\Placeholder;
use Drall\TestCase;

/**
 * @covers \Drall\Models\Placeholder
 */
class PlaceholderTest extends TestCase {

  protected SitesFile $subject;

  public function testSearch() {
    $this->assertEquals(
      [Placeholder::Site],
      Placeholder::search('drush @@site.local st')
    );

    $this->assertEquals(
      [Placeholder::Directory],
      Placeholder::search('drush --uri=@@uri uli')
    );
  }

  public function testWithUnrecognizedPlaceholder() {
    $this->assertEquals(
      [],
      Placeholder::search('drush --foo=@@bar st')
    );
  }

  public function testReplace() {
    $this->assertEquals(
      'drush @self.local st',
      Placeholder::replace(['@@site' => '@self'], 'drush @@site.local st')
    );

    $this->assertEquals(
      'drush --uri=example.com uli',
      Placeholder::replace(['@@uri' => 'example.com'], 'drush --uri=@@uri uli')
    );
  }

  public function testReplaceWithUnrecognizedPlaceholder() {
    $this->assertEquals(
      'drush --foo=@@bar st',
      Placeholder::replace(['@@bar' => 'bar'], 'drush --foo=@@bar st')
    );
  }

  public function testReplaceWithWordBoundary() {
    $this->assertEquals(
      'Current site: @@urinal',
      Placeholder::replace(['@@uri' => 'example.com'], 'Current site: @@urinal')
    );
  }

}
