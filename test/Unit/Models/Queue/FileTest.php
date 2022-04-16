<?php

namespace Drall\Models\Queue;

use Drall\Models\RawCommand;
use Drall\TestCase;

/**
 * @covers \Drall\Models\Queue\File
 */
class FileTest extends TestCase {

  public function testGetPath() {
    $qPath = "{$this->fixturesDir()}/drallq.json";
    $qFile = new File($qPath);
    $this->assertEquals($qPath, $qFile->getPath());
  }

  public function testRead() {
    $qPath = "{$this->fixturesDir()}/drallq.json";
    $qFile = new File($qPath);
    $this->assertInstanceOf(Queue::class, $qFile->read());
  }

  /**
   * Read fails if queue file doesn't exist.
   */
  public function testReadWithNonExistentFile() {
    $qPath = '/tmp/non-existent.json';
    $qFile = new File($qPath);
    $this->expectException(\RuntimeException::class);
    $qFile->read();
  }

  /**
   * Read fails if queue file doesn't contain valid JSON.
   */
  public function testReadWithNonJSONFile() {
    $qPath = $this->createTempFile('This is obviously not valid JSON.');
    $qFile = new File($qPath);
    $this->expectException(\RuntimeException::class);
    $qFile->read();
  }

  public function testWrite() {
    $qData = new Queue(
      'a.b.c',
      new RawCommand('cat sites/@@uri/settings.php'),
      'uri'
    );

    $item1 = new Item('shredder', ItemStatus::DONE);
    $qData->push($item1);

    $item2 = new Item('krang', ItemStatus::RUNNING);
    $qData->push($item2);

    $item3 = new Item('beebop', ItemStatus::PENDING);
    $qData->push($item3);

    $qPath = $this->createTempFilePath();
    $qFile = new File($qPath);
    $qFile->write($qData);

    $this->assertEquals(
      [
        'version' => 'a.b.c',
        'command' => 'cat sites/@@uri/settings.php',
        'placeholder' => 'uri',
        'items' => [
          $item1->getId() => $item1->asArray(),
          $item2->getId() => $item2->asArray(),
          $item3->getId() => $item3->asArray(),
        ],
      ],
      json_decode(file_get_contents($qFile->getPath()), TRUE)
    );
  }

}
