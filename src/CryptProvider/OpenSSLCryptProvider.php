<?php

namespace Ssess\CryptProvider;

class OpenSSLCryptProvider implements CryptProviderInterface
{

    private $appKey;

    private $hashAlgorithm;

    private $encryptionAlgorithm;

    public function __construct($app_key, $hash_algorithm = 'sha512', $encryption_algorithm = 'aes128')
    {
        $this->hashAlgorithm = $hash_algorithm;
        $this->encryptionAlgorithm = $encryption_algorithm;
        $this->appKey = openssl_digest($app_key, $this->hashAlgorithm);
    }

    /**
     * Makes a session identifier based on the session id.
     *
     * @param string $session_id The session id.
     * @return string The session identifier.
     */
    public function makeSessionIdentifier($session_id)
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
    public function encryptSessionData($session_id, $session_data)
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
     * @param string $session_id The session id.
     * @param string $session_data The encrypted session data.
     * @return string The decrypted session data.
     */
    public function decryptSessionData($session_id, $session_data)
    {
        $encrypted_data = json_decode($session_data);

        if (!$encrypted_data) {
            return '';
        }

        $iv = base64_decode($encrypted_data->iv);
        $encryption_key = $this->getEncryptionKey($session_id);

        return openssl_decrypt($encrypted_data->data, $this->encryptionAlgorithm, $encryption_key, 0, $iv);
    }

    /**
     * Calculates the key to be used in the session encryption.
     *
     * @param string $session_id Id of the session
     * @return string Encryption key
     */
    private function getEncryptionKey($session_id)
    {
        return openssl_digest($this->appKey.$session_id, $this->hashAlgorithm);
    }
}