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
     * @param  string $appKey              Defines the App Key.
     * @param  string $hashAlgorithm       Defines the algorithm used to create hashes.
     * @param  string $encryptionAlgorithm Defines the algorithm to encrypt/decrypt data.
     */
    public function __construct(string $appKey, string $hashAlgorithm = 'sha512', string $encryptionAlgorithm = 'aes128')
    {
        $this->hashAlgorithm = $hashAlgorithm;
        $this->encryptionAlgorithm = $encryptionAlgorithm;

        if (!extension_loaded('openssl')) {
            throw new OpenSSLNotLoadedException();
        }

        $hashAlgorithms = openssl_get_md_methods(true);
        if (!in_array($hashAlgorithm, $hashAlgorithms)) {
            throw new UnknownHashAlgorithmException();
        }

        $encryptionAlgorithms = openssl_get_cipher_methods(true);
        if (!in_array($encryptionAlgorithm, $encryptionAlgorithms)) {
            throw new UnknownEncryptionAlgorithmException();
        }

        $this->appKey = (string) openssl_digest($appKey, $this->hashAlgorithm);
    }

    /**
     * Makes a session identifier based on the session id.
     *
     * @param  string $sessionId The session id.
     * @return string The session identifier.
     */
    public function makeSessionIdentifier(string $sessionId): string
    {
        return (string) openssl_digest($sessionId . $this->appKey, $this->hashAlgorithm);
    }

    /**
     * Encrypts the session data.
     *
     * @param  string $sessionId   The session id.
     * @param  string $sessionData The session data.
     * @return string The encrypted session data.
     */
    public function encryptSessionData(string $sessionId, string $sessionData): string
    {
        $ivLength = (int) openssl_cipher_iv_length($this->encryptionAlgorithm);
        $initVector = (string) openssl_random_pseudo_bytes($ivLength);

        $encryptionKey = $this->getEncryptionKey($sessionId);
        $encryptedData = openssl_encrypt($sessionData, $this->encryptionAlgorithm, $encryptionKey, 0, $initVector);

        return (string) json_encode(
            [
            'data' => $encryptedData,
            'initVector' => base64_encode($initVector)
            ]
        );
    }

    /**
     * Decrypts the session data.
     *
     * @throws UnableToDecryptException
     * @param  string $sessionId   The session id.
     * @param  string $sessionData The encrypted session data.
     * @return string The decrypted session data.
     */
    public function decryptSessionData(string $sessionId, string $sessionData): string
    {
        if (!$sessionData) {
            return '';
        }

        $encryptedData = json_decode($sessionData);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new UnableToDecryptException();
        }

        $initVector = base64_decode($encryptedData->initVector, true);
        if ($initVector === false) {
            throw new UnableToDecryptException();
        }

        $encryptionKey = $this->getEncryptionKey($sessionId);

        $decryptedData = @openssl_decrypt($encryptedData->data, $this->encryptionAlgorithm, $encryptionKey, 0, $initVector);
        if ($decryptedData === false) {
            throw new UnableToDecryptException();
        }

        return $decryptedData;
    }

    /**
     * Calculates the key to be used in the session encryption.
     *
     * @param  string $sessionId Id of the session
     * @return string Encryption key
     */
    private function getEncryptionKey(string $sessionId): string
    {
        return (string) openssl_digest($this->appKey . $sessionId, $this->hashAlgorithm);
    }
}
