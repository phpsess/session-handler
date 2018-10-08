<?php

declare(strict_types=1);

use Ssess\CryptProvider\OpenSSLCryptProvider;

use PHPUnit\Framework\TestCase;

final class OpenSSLCryptProviderTest extends TestCase
{

    public function testIdentifierDifferentFromSid ()
    {
        $crypt_provider = new OpenSSLCryptProvider('appKey');

        $session_id = 'test_id';

        $identifier = $crypt_provider->makeSessionIdentifier($session_id);

        $this->assertNotEquals($session_id, $identifier);
    }

    public function testEncryptedDataDifferentFromData ()
    {
        $crypt_provider = new OpenSSLCryptProvider('appKey');

        $session_id = 'test_id';

        $data = 'test_data';

        $encrypted_data = $crypt_provider->encryptSessionData($session_id, $data);

        $this->assertNotEquals($data, $encrypted_data);
    }

    public function testCanDecryptEncryptedData ()
    {
        $crypt_provider = new OpenSSLCryptProvider('appKey');

        $session_id = 'test_id';

        $data = 'test_data';

        $encrypted_data = $crypt_provider->encryptSessionData($session_id, $data);

        $decrypted_data = $crypt_provider->decryptSessionData($session_id, $encrypted_data);

        $this->assertEquals($data, $decrypted_data);
    }

    public function testCantDecryptWithWrongSessionId ()
    {
        $crypt_provider = new OpenSSLCryptProvider('appKey');

        $data = 'test_data';

        $encrypted_data = $crypt_provider->encryptSessionData('original_session_id', $data);

        $decrypted_data = $crypt_provider->decryptSessionData('wrong_session_id', $encrypted_data);

        $this->assertEquals($decrypted_data, '');
    }

    public function testCanDecryptWithNewInstance ()
    {
        $app_key = 'appKey';

        $crypt_provider = new OpenSSLCryptProvider($app_key);

        $session_id = 'test_id';

        $data = 'test_data';

        $encrypted_data = $crypt_provider->encryptSessionData($session_id, $data);

        $new_crypt_provider = new OpenSSLCryptProvider($app_key);

        $decrypted_data = $new_crypt_provider->decryptSessionData($session_id, $encrypted_data);

        $this->assertEquals($data, $decrypted_data);
    }

    public function testCantDecryptWithWrongKey ()
    {
        $crypt_provider = new OpenSSLCryptProvider('original_key');

        $session_id = 'test_id';

        $data = 'test_data';

        $encrypted_data = $crypt_provider->encryptSessionData($session_id, $data);

        $new_crypt_provider = new OpenSSLCryptProvider('wrong_key');

        $decrypted_data = $new_crypt_provider->decryptSessionData($session_id, $encrypted_data);

        $this->assertEquals($decrypted_data, '');
    }
}