<?php

declare(strict_types=1);

namespace PHPSess\Tests;

use PHPSess\Storage\FileStorage;
use PHPSess\Exception\SessionNotFoundException;
use PHPSess\Exception\DirectoryNotWritableException;
use PHPSess\Exception\DirectoryNotReadableException;
use PHPSess\Exception\UnableToSaveException;
use PHPSess\Exception\UnableToDeleteException;
use PHPSess\Exception\UnableToCreateDirectoryException;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

/**
 * @runTestsInSeparateProcesses
 */
final class FileStorageTest extends TestCase
{

    public function setUp()
    {
        $path = vfsStream::setup('root', 0777, ['session' => []])->url();

        try {
            $reflection = new \ReflectionClass(self::class);
        } catch (\Exception $exception) {
            $this->fail('Not able to determine the test class name');
            return;
        }

        $class_name = $reflection->getShortName();

        $test_name = $this->getName();

        $session_path = "$path/session/$class_name-$test_name";

        ini_set('session.save_path', $session_path);

        parent::setUp();
    }

    public function testUnwritableDirectory()
    {
        $session_path = session_save_path();

        mkdir($session_path, 0444);

        $this->expectException(DirectoryNotWritableException::class);

        new FileStorage();
    }

    public function testUnreadableDirectory()
    {
        $session_path = session_save_path();

        mkdir($session_path, 0222);

        $this->expectException(DirectoryNotReadableException::class);

        new FileStorage();
    }

    public function testUnableToSave()
    {
        $session_path = session_save_path();

        $file_storage = new FileStorage();

        chmod($session_path, 0444);

        $this->expectException(UnableToSaveException::class);

        $file_storage->save('aSessionIdentifier', 'someData');
    }

    public function testUnableToDestroy()
    {
        $session_path = session_save_path();

        $identifier = 'aSessionIdentifier';

        $file_storage = new FileStorage();

        $file_storage->save($identifier, 'someData');

        chmod($session_path, 0555);

        $this->expectException(UnableToDeleteException::class);

        $file_storage->destroy($identifier);
    }

    public function testSaveThenGet()
    {
        $file_storage = new FileStorage();

        $identifier = $this->getName();

        $data = 'test_data';

        $file_storage->save($identifier, $data);

        $saved_data = $file_storage->get($identifier);

        $this->assertEquals($data, $saved_data);
    }

    public function testGetWithDifferentInstance()
    {
        $file_storage = new FileStorage();

        $identifier = $this->getName();

        $data = 'test_data';

        $file_storage->save($identifier, $data);

        $new_file_storage = new FileStorage();

        $saved_data = $new_file_storage->get($identifier);

        $this->assertEquals($data, $saved_data);
    }

    public function testGetInexistent()
    {
        $file_storage = new FileStorage();

        $identifier = $this->getName();

        $this->expectException(SessionNotFoundException::class);

        $file_storage->get($identifier);
    }

    public function testExists()
    {
        $file_storage = new FileStorage();

        $identifier = $this->getName();

        $exists = $file_storage->sessionExists($identifier);

        $this->assertFalse($exists);

        $file_storage->save($identifier, 'test');

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);
    }

    public function testDestroy()
    {
        $file_storage = new FileStorage();

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
        $file_storage = new FileStorage();

        $identifier = $this->getName();

        $file_storage->save($identifier, 'test');

        usleep(1000); // 1 millisecond

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);

        $file_storage->clearOld(10);

        $exists = $file_storage->sessionExists($identifier);

        $this->assertFalse($exists);
    }

    public function testDoNotClearNew()
    {
        $file_storage = new FileStorage();

        $identifier = $this->getName();

        $file_storage->save($identifier, 'test');

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);

        $file_storage->clearOld(1000000); // one second

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);
    }

    public function testCantFigureOutPath()
    {
        ini_set('session.save_path', '');

        $this->expectException(UnableToCreateDirectoryException::class);

        new FileStorage();
    }

    public function testNoPermissionToCreatePath()
    {
        $path = ini_get('session.save_path');

        $forbiddenPath = "$path/forbidden";

        mkdir($forbiddenPath, 0444);

        $sessionPath = "$forbiddenPath/sessions";

        $this->expectException(UnableToCreateDirectoryException::class);

        new FileStorage($sessionPath);
    }

    public function testNoPermissionToClear()
    {
        $path = ini_get('session.save_path');

        $fileStorage = new FileStorage();

        chmod($path, 0111);

        $this->expectException(UnableToDeleteException::class);

        $fileStorage->clearOld(0);
    }
}
