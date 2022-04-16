<?php

namespace Drall\Models;

use Drall\TestCase;

/**
 * @covers Drall\Models\Lock
 */
class LockTest extends TestCase {

  public function testGetPath() {
    $path = $this->createTempFilePath() . '.lock';
    $lock = new Lock($path);
    $this->assertEquals($path, $lock->getPath());
  }

  public function testIsLocked() {
    $lock = new Lock($this->createTempFilePath() . '.lock');
    $this->assertFalse($lock->isLocked());
    $lock->acquire();
    $this->assertTrue($lock->isLocked());
  }

  public function testAcquire() {
    $lock = new Lock($this->createTempFilePath() . '.lock');
    $this->assertTrue($lock->acquire());
    $this->assertFileExists($lock->getPath());
    $this->assertFalse($lock->acquire());
  }

  public function testRelease() {
    $lock = new Lock($this->createTempFilePath() . '.lock');
    $this->assertTrue($lock->acquire());
    $this->assertTrue($lock->isLocked());
    $lock->release();
    $this->assertFileDoesNotExist($lock->getPath());
    $this->assertFalse($lock->isLocked());
  }

}
