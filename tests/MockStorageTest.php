<?php

declare(strict_types=1);

use Ssess\Storage\MockStorage;

use PHPUnit\Framework\TestCase;

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

        $file_storage->destroy($identifier);
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

        $file_storage->destroy($identifier);
    }

    public function testExists()
    {
        $file_storage = new MockStorage();

        $identifier = $this->getName();

        $file_storage->destroy($identifier);
        if ($file_storage->sessionExists($identifier)) {
            $this->fail('Cant assure session does not exist');
        }

        $file_storage->save($identifier, 'test');

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);

        $file_storage->destroy($identifier);
    }

    public function testDestroy()
    {
        $file_storage = new MockStorage();

        $identifier = $this->getName();

        $file_storage->save($identifier, 'test');
        if (!$file_storage->sessionExists($identifier)) {
            $this->fail('Cant create session to destroy');
        }

        $file_storage->destroy($identifier);

        $exists = $file_storage->sessionExists($identifier);

        $this->assertFalse($exists);
    }

    public function testClearOld()
    {
        $file_storage = new MockStorage();

        $identifier = $this->getName();

        $file_storage->save($identifier, 'test');

        sleep(2);

        $file_storage->clearOld(1);

        $exists = $file_storage->sessionExists($identifier);

        $this->assertFalse($exists);
    }

    public function testDoNotClearNew()
    {
        $file_storage = new MockStorage();

        $identifier = $this->getName();

        $file_storage->save($identifier, 'test');

        sleep(1);

        $file_storage->clearOld(2);

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);

        $file_storage->destroy($identifier);
    }

}