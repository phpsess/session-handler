<?php

declare(strict_types=1);

namespace PHPSess\Tests;

use PHPSess\Storage\MockStorage;

use PHPUnit\Framework\TestCase;
use PHPSess\Exception\SessionNotFoundException;

final class MockStorageTest extends TestCase
{

    public function testSaveThenGet()
    {
        $file_storage = new MockStorage();

        $identifier = $this->getName();

        $data = 'test_data';

        $file_storage->save($identifier, $data);

        $saved_data = $file_storage->get($identifier);

        $this->assertEquals($data, $saved_data);
    }

    public function testGetWithDifferentInstance()
    {
        $file_storage = new MockStorage();

        $identifier = $this->getName();

        $data = 'test_data';

        $file_storage->save($identifier, $data);

        $new_file_storage = new MockStorage();

        $saved_data = $new_file_storage->get($identifier);

        $this->assertEquals($data, $saved_data);
    }

    public function testGetInexistent()
    {
        $file_storage = new MockStorage();

        $identifier = $this->getName();

        $this->expectException(SessionNotFoundException::class);

        $file_storage->get($identifier);
    }

    public function testExists()
    {
        $file_storage = new MockStorage();

        $identifier = $this->getName();

        $exists = $file_storage->sessionExists($identifier);

        $this->assertFalse($exists);

        $file_storage->save($identifier, 'test');

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);
    }

    public function testDestroy()
    {
        $file_storage = new MockStorage();

        $identifier = $this->getName();

        $file_storage->save($identifier, 'test');

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);

        $file_storage->destroy($identifier);

        $exists = $file_storage->sessionExists($identifier);

        $this->assertFalse($exists);
    }

    public function testClearOld()
    {
        $file_storage = new MockStorage();

        $identifier = $this->getName();

        $file_storage->save($identifier, 'test');

        usleep(1000); // 1 milisecond

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);

        $file_storage->clearOld(10);

        $exists = $file_storage->sessionExists($identifier);

        $this->assertFalse($exists);
    }

    public function testDoNotClearNew()
    {
        $file_storage = new MockStorage();

        $identifier = $this->getName();

        $file_storage->save($identifier, 'test');

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);

        $file_storage->clearOld(1000000); // one second

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);
    }
}
