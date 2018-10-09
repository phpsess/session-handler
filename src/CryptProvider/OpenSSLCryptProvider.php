<?php

declare(strict_types=1);

namespace Ssess\CryptProvider;

use Ssess\Exception\OpenSSLNotLoadedException;
use Ssess\Exception\UnableToDecryptException;
use Ssess\Exception\UnknownEncryptionAlgorithmException;
use Ssess\Exception\UnknownHashAlgorithmException;

class OpenSSLCryptProvider implements CryptProviderInterface
{

    /**
     * @var string $appKey The hashed app key.
     */
    private $appKey;

    /**
     * @var string $hashAlgorithm The hashing algorithm.
     */
    private $hashAlgorithm;

    /**
     * @var string $encryptionAlgorithm The encryption/decryption algorithm.
     */
    private $encryptionAlgorithm;

    /**
     * CryptProviderInterface constructor.
     *
     * @throws \Ssess\Exception\OpenSSLNotLoadedException
     * @throws \Ssess\Exception\UnknownEncryptionAlgorithmException
     * @throws \Ssess\Exception\UnknownHashAlgorithmException
     * @param string $app_key Defines the App Key.
     * @param string|null $hash_algorithm Defines the algorithm used to create hashes.
     * @param string|null $encryption_algorithm Defines the algorithm to encrypt/decrypt data.
     */
    public function __construct(string $app_key, ?string $hash_algorithm = 'sha512', ?string $encryption_algorithm = 'aes128')
    {
        $this->hashAlgorithm = $hash_algorithm;
        $this->encryptionAlgorithm = $encryption_algorithm;

        if (!extension_loaded('openssl')) {
            throw new OpenSSLNotLoadedException();
        }

        $known_hash_algorithms = openssl_get_md_methods(true);
        if (!in_array($hash_algorithm, $known_hash_algorithms)) {
            throw new UnknownHashAlgorithmException();
        }

        $known_encryption_algorithms = openssl_get_cipher_methods(true);
        if (!in_array($encryption_algorithm, $known_encryption_algorithms)) {
            throw new UnknownEncryptionAlgorithmException();
        }

        $this->appKey = openssl_digest($app_key, $this->hashAlgorithm);
    }

    /**
     * Makes a session identifier based on the session id.
     *
     * @param string $session_id The session id.
     * @return string The session identifier.
     */
    public function makeSessionIdentifier(string $session_id): string
    {
        return openssl_digest($session_id.$this->appKey, $this->hashAlgorithm);
    }

    /**
     * Encrypts the session data.
     *
     * @param string $session_id The session id.
     * @param string $session_data The session data.
     * @return string The encrypted session data.
     */
    public function encryptSessionData(string $session_id, string $session_data): string
    {
        $iv_length = openssl_cipher_iv_length($this->encryptionAlgorithm);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encryption_key = $this->getEncryptionKey($session_id);
        $encrypted_data = openssl_encrypt($session_data, $this->encryptionAlgorithm, $encryption_key, 0, $iv);

        return (string) json_encode([
            'data' => $encrypted_data,
            'iv' => base64_encode($iv)
        ]);
    }

    /**
     * Decrypts the session data.
     *
     * @throws UnableToDecryptException
     * @param string $session_id The session id.
     * @param string $session_data The encrypted session data.
     * @return string The decrypted session data.
     */
    public function decryptSessionData(string $session_id, string $session_data): string
    {
        $encrypted_data = json_decode($session_data);

        if (!$encrypted_data) {
            return '';
        }

        $iv = base64_decode($encrypted_data->iv);
        $encryption_key = $this->getEncryptionKey($session_id);

        $decrypted_data = openssl_decrypt($encrypted_data->data, $this->encryptionAlgorithm, $encryption_key, 0, $iv);
        if ($decrypted_data === false) {
            throw new UnableToDecryptException();
        }

        return $decrypted_data;
    }

    /**
     * Calculates the key to be used in the session encryption.
     *
     * @param string $session_id Id of the session
     * @return string Encryption key
     */
    private function getEncryptionKey(string $session_id): string
    {
        return openssl_digest($this->appKey.$session_id, $this->hashAlgorithm);
    }
}