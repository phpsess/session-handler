<?php

namespace Ssess;

use Ssess\Exception\UseStrictModeDisabledException;
use Ssess\Exception\UseCookiesDisabledException;
use Ssess\Exception\UseOnlyCookiesDisabledException;
use Ssess\Exception\UseTransSidEnabledException;
use Ssess\Storage\StorageInterface;

/**
 * Handles the session in a secure way.
 *
 * @todo Implement timestamp based session (http://php.net/manual/en/features.session.security.management.php#features.session.security.management.session-data-deletion)
 * @todo Implement session locking (http://php.net/manual/en/features.session.security.management.php#features.session.security.management.session-locking)
 * @todo Allow user to specify APP name so that session files from different applications never gets mixed
 * @todo Option to lock session to IP
 * @todo Option to lock session to User Agent
 * @todo Option to lock session to Host
 * @todo Option to create session cookie with mutating random name
 * @todo Create a way to change encryption/hash algorithms over time without loosing previous sessions, to allow incremental security upgrades
 * @todo Provide a storage driver interface (such as file, mysqli, pdo, redis, memcached, etc)
 * @todo Specify a better session directory and files permissions
 *
 * @package Ssess
 * @author Ayrton Fidelis <ayrton.vargas33@gmail.com>
 */
class Ssess implements \SessionHandlerInterface
{
    /**
     * @var string $appKey The hashed key of the app. This is only part of the key used to encrypt the session data.
     */
    private $appKey;

    /**
     * @var string $encryptionAlgorithm The algorithm used to encrypt the session data. For a list of available algorithms, use openssl_get_cipher_methods().
     */
    private $encryptionAlgorithm;

    /**
     * @var string $hashAlgorithm The algorithm used to hash the keys and the session identifier. For a list of available algorithms, use openssl_get_md_methods().
     */
    private $hashAlgorithm;

    /**
     * @var boolean $warnInsecureSettings Whether the handler should warn about insecure settings or not.
     */
    public static $warnInsecureSettings = true;

    /**
     * @var StorageInterface $storageDriver The driver used to store the session data.
     */
    private $storageDriver;

    /**
     * Ssess constructor.
     *
     * It computes the app_key hash and calls the function that handles the strict mode.
     *
     * @param string $app_key The encryption key of the app. The hash of it will be used as part of the encryption key.
     * @param string $hash_algorithm The algorithm used to hash. For a list of available algorithms, use openssl_get_md_methods().
     * @param string $encryption_algorithm The algorithm used for encryption. For a list of available algorithms, use openssl_get_cipher_methods().
     * @param StorageInterface $storage The driver used to store the session data.
     */
    public function __construct($app_key, $hash_algorithm = 'sha512', $encryption_algorithm = 'aes128', $storage = NULL)
    {
        $this->hashAlgorithm = $hash_algorithm;
        $this->encryptionAlgorithm = $encryption_algorithm;
        $this->appKey = openssl_digest($app_key, $this->hashAlgorithm);
        $this->storageDriver = $storage ? $storage : new Storage\FileStorage();

        $this->warnInsecureSettings();
        $this->handleStrict();
    }

    /**
     * Throws exceptions when insecure INI settings are detected.
     */
    private function warnInsecureSettings()
    {
        if (!self::$warnInsecureSettings) {
            return;
        }

        if (!ini_get('session.use_cookies')) {
            throw new UseCookiesDisabledException();
        }

        if (!ini_get('session.use_only_cookies')) {
            throw new UseOnlyCookiesDisabledException();
        }

        if (ini_get('session.use_trans_sid')) {
            throw new UseTransSidEnabledException();
        }

        if (!ini_get('session.use_strict_mode')) {
            throw new UseStrictModeDisabledException();
        }
    }

    /**
     * Rejects arbitrary session ids.
     *
     * @see http://php.net/manual/en/features.session.security.management.php#features.session.security.management.non-adaptive-session Why this security measure is important.
     */
    private function handleStrict()
    {
        if (!ini_get('session.use_strict_mode') || headers_sent()) {
            return;
        }

        $cookie_name = session_name();
        if (empty($_COOKIE[$cookie_name])) {
            return;
        }

        $session_id = $_COOKIE[$cookie_name];

        $identifier = $this->getSessionIdentifier($session_id);

        if ($this->storageDriver->sessionExists($identifier)) {
            return;
        }

        $new_session_id = session_create_id();
        session_id($new_session_id);
    }

    /**
     * Opens the session.
     *
     * @param string $save_path The path where the session files will be saved.
     * @param string $name The name of the session
     * @return bool
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * Closes the session
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * Return the decrypted (but still serialized) data of the session.
     *
     * @param string $session_id Id of the session
     * @return string Decrypted session data (still serialized)
     */
    public function read($session_id)
    {
        $identifier = $this->getSessionIdentifier($session_id);

        $content = $this->storageDriver->get($identifier);

        if (!$content) {
            return '';
        }

        return $this->decrypt($session_id, $content);
    }

    /**
     * Encrypts the session data and saves to the storage;
     *
     * @param string $session_id Id of the session
     * @param string $session_data Unencrypted session data
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        $identifier = $this->getSessionIdentifier($session_id);

        $content = $this->encrypt($session_id, $session_data);

        return $this->storageDriver->save($identifier, $content);
    }

    /**
     * Destroys the session.
     *
     * @param string $session_id Id of the session
     * @return bool
     */
    public function destroy($session_id)
    {
        $identifier = $this->getSessionIdentifier($session_id);

        return $this->storageDriver->destroy($identifier);
    }

    /**
     * Removes the expired sessions from the storage.
     *
     * (GC stands for Garbage Collector)
     *
     * @param int $max_life The maximum time (in seconds) that a session must be kept.
     * @return bool
     */
    public function gc($max_life)
    {
        return $this->storageDriver->clearOld($max_life);
    }

    /**
     * Encrypts the session data.
     *
     * @param string $session_id Id of the session
     * @param string $session_data Serialized data to be encrypted.
     * @return string
     */
    private function encrypt($session_id, $session_data)
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
     * @param string $session_id Id of the session
     * @param string $content Encrypted session data
     * @return string Decrypted session data
     */
    private function decrypt($session_id, $content)
    {
        $encrypted_data = json_decode($content);

        if (!$encrypted_data) {
            return '';
        }

        $iv = base64_decode($encrypted_data->iv);
        $encryption_key = $this->getEncryptionKey($session_id);

        return openssl_decrypt($encrypted_data->data, $this->encryptionAlgorithm, $encryption_key, 0, $iv);
    }

    /**
     * Gets the string used to identify the stored session data.
     *
     * @param string $session_id Id of the session
     * @return string Session identifier
     */
    private function getSessionIdentifier($session_id)
    {
        return openssl_digest($session_id.$this->appKey, $this->hashAlgorithm);
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