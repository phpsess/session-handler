<?php

declare(strict_types=1);

use Ssess\Ssess;
use Ssess\CryptProvider\OpenSSLCryptProvider;
use Ssess\Exception\UseStrictModeDisabledException;
use Ssess\Exception\UseCookiesDisabledException;
use Ssess\Exception\UseOnlyCookiesDisabledException;
use Ssess\Exception\UseTransSidEnabledException;

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
final class SsessTest extends TestCase
{

    public function setUp()
    {
        $current_path = getcwd();
        $test_name = $this->getName();

        ini_set('session.save_path', "$current_path/temp_session_$test_name");

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
            unlink($session_file);
        }

        rmdir($session_path);

        parent::tearDown();
    }

    public function testSessionFixation()
    {
        $this->setIniConfigs();

        $arbitrary_session_id = $this->setArbitrarySessionId();

        $this->initSecureSession();

        $current_session_id = session_id();

        $this->assertNotEquals($current_session_id, $arbitrary_session_id);
    }

    public function testSessionFixationWhenSidExists()
    {
        $this->setIniConfigs();

        $this->initSecureSession();

        $session_id = session_id();

        $_SESSION['password'] = 'password';

        session_write_close();

        $this->setArbitrarySessionId($session_id);

        $this->initSecureSession();

        $current_session_id = session_id();

        $this->assertEquals($session_id, $current_session_id);
    }

    public function testWarnStrictModeDisabled()
    {
        $this->setIniConfigs();

        ini_set('session.use_strict_mode', '0');

        $this->expectException(UseStrictModeDisabledException::class);

        $crypt_provider = new OpenSSLCryptProvider('testKey');

        new Ssess($crypt_provider);
    }

    public function testWarnUseCookiesDisabled()
    {
        $this->setIniConfigs();

        ini_set('session.use_cookies', '0');

        $this->expectException(UseCookiesDisabledException::class);

        $crypt_provider = new OpenSSLCryptProvider('testKey');

        new Ssess($crypt_provider);
    }

    public function testWarnUseOnlyCookiesDisabled()
    {
        $this->setIniConfigs();

        ini_set('session.use_only_cookies', '0');

        $this->expectException(UseOnlyCookiesDisabledException::class);

        $crypt_provider = new OpenSSLCryptProvider('testKey');

        new Ssess($crypt_provider);
    }

    public function testWarnUseTransSidEnabled()
    {
        $this->setIniConfigs();

        ini_set('session.use_trans_sid', '1');

        $this->expectException(UseTransSidEnabledException::class);

        $crypt_provider = new OpenSSLCryptProvider('testKey');

        new Ssess($crypt_provider);
    }

    public function testDisabledWarnInsecureSettings()
    {
        $this->setIniConfigs();

        ini_set('session.use_strict_mode', '0');
        ini_set('session.use_cookies', '0');
        ini_set('session.use_only_cookies', '0');
        ini_set('session.use_trans_sid', '1');

        Ssess::$warnInsecureSettings = false;

        $crypt_provider = new OpenSSLCryptProvider('testKey');

        try {
            new Ssess($crypt_provider);
            $did_not_throw_errors = true;
        } catch(Exception $e) {
            $did_not_throw_errors = false;
        }

        $this->assertTrue($did_not_throw_errors);
    }

    public function testIgnoreSessionFixation()
    {
        $this->setIniConfigs();

        Ssess::$warnInsecureSettings = false;

        ini_set('session.use_strict_mode', '0');

        $arbitrary_session_id = $this->setArbitrarySessionId();

        $this->initSecureSession();

        $current_session_id = session_id();

        $this->assertEquals($arbitrary_session_id, $current_session_id);
    }

    public function testCanWriteReopenAndRead()
    {
        $this->setIniConfigs();

        $this->initSecureSession();

        $_SESSION['password'] = 'password';

        session_write_close();

        $this->initSecureSession();

        $this->assertEquals($_SESSION['password'], 'password');
    }

    public function testCantReadWithWrongAppKey()
    {
        $this->setIniConfigs();

        $this->initSecureSession('original-key');

        $_SESSION['password'] = 'password';

        session_write_close();

        $this->initSecureSession('wrong-key');

        $this->assertArrayNotHasKey('password', $_SESSION);
    }

    public function testDestroy()
    {
        $this->setIniConfigs();

        $crypt_provider = $this->initSecureSession();

        $session_id = session_id();

        $_SESSION['password'] = 'test';

        session_write_close();

        $destroyed = $crypt_provider->destroy($session_id);

        $this->assertTrue($destroyed);

        $crypt_provider = $this->initSecureSession();

        $data = $crypt_provider->read($session_id);

        $this->assertEquals($data, '');
    }

    public function testDestroyInexistentSessionId()
    {
        $this->setIniConfigs();

        $crypt_provider = $this->initSecureSession('aSessionId');

        $_SESSION['password'] = 'test';

        session_write_close();

        $destroyed = $crypt_provider->destroy('anotherSessionId');

        $this->assertFalse($destroyed);
    }

    public function testGarbageCollector()
    {
        $this->setIniConfigs();

        $crypt_provider = $this->initSecureSession();

        $session_id = session_id();

        $_SESSION['password'] = 'test';

        session_write_close();

        sleep(2);

        $crypt_provider->gc(1);

        $new_crypt_provider = $this->initSecureSession();

        $data = $new_crypt_provider->read($session_id);

        $this->assertEquals('', $data);
    }

    private function setIniConfigs()
    {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
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
        $crypt_provider = new OpenSSLCryptProvider($key);

        $ssess = new Ssess($crypt_provider);

        session_set_save_handler($ssess);

        session_start();

        return $ssess;
    }
}