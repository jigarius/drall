<?php

namespace Drall\Test\Unit\Model;

use Drall\Model\EnvironmentId;
use Drall\TestCase;

/**
 * @covers \Drall\Model\EnvironmentId
 */
class EnvironmentIdTest extends TestCase {

  public function testIsActive(): void {
    $this->assertFalse(EnvironmentId::Unknown->isActive());
    $this->assertEquals('test', getenv('DRALL_ENVIRONMENT'));
    $this->assertTrue(EnvironmentId::Test->isActive());
  }

}
