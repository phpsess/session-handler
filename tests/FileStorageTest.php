<?php

declare(strict_types=1);

use Ssess\Storage\FileStorage;
use Ssess\Exception\SessionNotFoundException;
use Ssess\Exception\DirectoryNotWritableException;
use Ssess\Exception\DirectoryNotReadableException;
use Ssess\Exception\UnableToSaveException;
use Ssess\Exception\UnableToDeleteException;

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
final class FileStorageTest extends TestCase
{

    public function setUp()
    {
        $current_path = getcwd();
        $folder = 'temp_session_' . self::class . '_' . $this->getName();

        ini_set('session.save_path', "$current_path/$folder");

        parent::setUp();
    }

    public function tearDown()
    {
        $session_path = session_save_path();

        if (!file_exists($session_path)) {
            return;
        }

        session_write_close();

        $session_files = glob("$session_path/*");

        foreach ($session_files as $session_file) {
            @unlink($session_file);
        }

        @rmdir($session_path);

        parent::tearDown();
    }

    public function testUnwritableDirectory()
    {
        $session_path = session_save_path();

        mkdir($session_path);

        chmod($session_path, 0444);

        $this->expectException(DirectoryNotWritableException::class);

        new FileStorage();

        chmod($session_path, 0777);
    }

    public function testUnreadableDirectory()
    {
        $session_path = session_save_path();

        mkdir($session_path);

        chmod($session_path, 0222);

        $this->expectException(DirectoryNotReadableException::class);

        new FileStorage();

        chmod($session_path, 0777);
    }

    public function testUnableToSave()
    {
        $session_path = session_save_path();

        $file_storage = new FileStorage();

        chmod($session_path, 0444);

        $this->expectException(UnableToSaveException::class);

        $file_storage->save('aSessionIdentifier', 'someData');

        chmod($session_path, 0777);
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

        chmod($session_path, 0777);

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

        if ($file_storage->sessionExists($identifier)) {
            $file_storage->destroy($identifier);
        }

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

        sleep(1);

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);

        $file_storage->clearOld(1);

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

        $file_storage->clearOld(1);

        $exists = $file_storage->sessionExists($identifier);

        $this->assertTrue($exists);
    }

}