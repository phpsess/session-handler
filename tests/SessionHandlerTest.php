<?php

declare(strict_types=1);

namespace PHPSess\Tests;

use PHPSess\Exception\UnableToDeleteException;
use PHPSess\Exception\UnableToSaveException;
use PHPSess\SessionHandler;
use PHPSess\Encryption\OpenSSLEncryption;
use PHPSess\Storage\MockStorage;

use PHPSess\Exception\InsecureSettingsException;

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
final class SessionHandlerTest extends TestCase
{

    public function setUp()
    {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        parent::setUp();
    }

    /**
     * @covers \PHPSess\SessionHandler::__construct
     * @covers \PHPSess\SessionHandler::handleStrict
     */
    public function testSessionFixation()
    {
        $arbitrary_session_id = $this->setArbitrarySessionId();

        $this->initSecureSession();

        $current_session_id = session_id();

        $this->assertNotEquals($current_session_id, $arbitrary_session_id);
    }

    /**
     * @covers \PHPSess\SessionHandler::handleStrict
     */
    public function testSessionFixationWhenSidExists()
    {
        $this->initSecureSession();

        $session_id = session_id();

        $_SESSION['password'] = 'password';

        session_write_close();

        $this->setArbitrarySessionId($session_id);

        $this->initSecureSession();

        $current_session_id = session_id();

        $this->assertEquals($session_id, $current_session_id);
    }

    /**
     * @covers \PHPSess\SessionHandler::handleStrict
     */
    public function testIgnoreSessionFixation()
    {
        SessionHandler::$warnInsecureSettings = false;

        ini_set('session.use_strict_mode', '0');

        $arbitrary_session_id = $this->setArbitrarySessionId();

        $this->initSecureSession();

        $current_session_id = session_id();

        $this->assertEquals($arbitrary_session_id, $current_session_id);
    }

    /**
     * @covers \PHPSess\SessionHandler::warnInsecureSettings
     */
    public function testWarnStrictModeDisabled()
    {
        ini_set('session.use_strict_mode', '0');

        $this->expectException(InsecureSettingsException::class);

        $this->initSecureSession();
    }

    /**
     * @covers \PHPSess\SessionHandler::warnInsecureSettings
     */
    public function testWarnUseCookiesDisabled()
    {
        ini_set('session.use_cookies', '0');

        $this->expectException(InsecureSettingsException::class);

        $this->initSecureSession();
    }

    /**
     * @covers \PHPSess\SessionHandler::warnInsecureSettings
     */
    public function testWarnUseOnlyCookiesDisabled()
    {
        ini_set('session.use_only_cookies', '0');

        $this->expectException(InsecureSettingsException::class);

        $this->initSecureSession();
    }

    /**
     * @covers \PHPSess\SessionHandler::warnInsecureSettings
     */
    public function testWarnUseTransSidEnabled()
    {
        ini_set('session.use_trans_sid', '1');

        $this->expectException(InsecureSettingsException::class);

        $this->initSecureSession();
    }

    /**
     * @covers \PHPSess\SessionHandler::warnInsecureSettings
     */
    public function testDisabledWarnInsecureSettings()
    {
        ini_set('session.use_strict_mode', '0');
        ini_set('session.use_cookies', '0');
        ini_set('session.use_only_cookies', '0');
        ini_set('session.use_trans_sid', '1');

        SessionHandler::$warnInsecureSettings = false;

        $exception = null;
        try {
            $this->initSecureSession();
        } catch (\Exception $exception) {
        }

        $this->assertNull($exception);
    }

    public function testAllSecureSettings()
    {
        $exception = null;
        try {
            $this->initSecureSession();
        } catch (\Exception $exception) {
        }

        $this->assertNull($exception);
    }

    /**
     * @covers \PHPSess\SessionHandler::open
     */
    public function testTakeTwoTriesToLock()
    {
        $crypt_provider = new OpenSSLEncryption('appKey');

        $storage = $this->createMock(MockStorage::class);
        $storage->method('lock')->willReturn(false, true);

        $ssess = new SessionHandler($crypt_provider, $storage);

        $opened = $ssess->open('any_path', 'any_name');

        $this->assertTrue($opened);
    }

    /**
     * @covers \PHPSess\SessionHandler::write
     * @covers \PHPSess\SessionHandler::close
     * @covers \PHPSess\SessionHandler::open
     * @covers \PHPSess\SessionHandler::read
     */
    public function testCanWriteReopenAndRead()
    {
        $this->initSecureSession();

        $_SESSION['password'] = 'password';

        session_write_close();

        $this->initSecureSession();

        $this->assertEquals($_SESSION['password'], 'password');
    }

    /**
     * @covers \PHPSess\SessionHandler::read
     */
    public function testCantReadWithWrongAppKey()
    {
        $this->initSecureSession('original-key');

        $_SESSION['password'] = 'password';

        session_write_close();

        $this->initSecureSession('wrong-key');

        $this->assertArrayNotHasKey('password', $_SESSION);
    }

    /**
     * @covers \PHPSess\SessionHandler::read
     */
    public function testGetEmptyData()
    {
        $crypt_provider = new OpenSSLEncryption('appKey');

        $storage = $this->createMock(MockStorage::class);
        $storage->method('sessionExists')->willReturn(true);
        $storage->method('get')->willReturn('');

        $ssess = new SessionHandler($crypt_provider, $storage);

        $identifier = $this->getName();

        $data = $ssess->read($identifier);

        $this->assertSame('', $data);
    }

    /**
     * @covers \PHPSess\SessionHandler::write
     */
    public function testWriteError()
    {
        $crypt_provider = new OpenSSLEncryption('appKey');

        $storage = $this->createMock(MockStorage::class);
        $storage->method('save')->willThrowException(new UnableToSaveException());

        $ssess = new SessionHandler($crypt_provider, $storage);

        $identifier = $this->getName();

        $saved = $ssess->write($identifier, 'testData');

        $this->assertFalse($saved);
    }

    /**
     * @covers \PHPSess\SessionHandler::destroy
     */
    public function testDestroy()
    {
        $ssess = $this->initSecureSession();

        $session_id = session_id();

        $_SESSION['password'] = 'test';

        session_write_close();

        $destroyed = $ssess->destroy($session_id);

        $this->assertTrue($destroyed);

        $ssess = $this->initSecureSession();

        $data = $ssess->read($session_id);

        $this->assertEquals($data, '');
    }

    /**
     * @covers \PHPSess\SessionHandler::destroy
     */
    public function testDestroyInexistentSessionId()
    {
        $ssess = $this->initSecureSession('aSessionId');

        $_SESSION['password'] = 'test';

        session_write_close();

        $destroyed = $ssess->destroy('anotherSessionId');

        $this->assertFalse($destroyed);
    }

    /**
     * @covers \PHPSess\SessionHandler::gc
     */
    public function testGarbageCollector()
    {
        $ssess = $this->initSecureSession();

        $session_id = session_id();

        $_SESSION['password'] = 'test';

        session_write_close();

        sleep(2);

        $ssess->gc(1);

        $new_crypt_provider = $this->initSecureSession();

        $data = $new_crypt_provider->read($session_id);

        $this->assertEquals('', $data);
    }

    /**
     * @covers \PHPSess\SessionHandler::gc
     */
    public function testErrorOnGargabeCollector()
    {
        $crypt_provider = new OpenSSLEncryption('appKey');

        $storage = $this->createMock(MockStorage::class);
        $storage->method('clearOld')->willThrowException(new UnableToDeleteException());

        $ssess = new SessionHandler($crypt_provider, $storage);

        $garbageCollected = $ssess->gc(0);

        $this->assertFalse($garbageCollected);
    }

    private function setArbitrarySessionId($arbitrary_session_id = '')
    {
        if (!$arbitrary_session_id) {
            $arbitrary_session_id = session_create_id();
        }

        $session_name = session_name();
        $_COOKIE[$session_name] = $arbitrary_session_id;

        return $arbitrary_session_id;
    }

    private function initSecureSession($key = 'testKey')
    {
        $crypt_provider = new OpenSSLEncryption($key);
        $storage = new MockStorage();

        $ssess = new SessionHandler($crypt_provider, $storage);

        session_set_save_handler($ssess);

        session_start();

        return $ssess;
    }
}
