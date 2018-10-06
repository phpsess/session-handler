<?php

namespace Ssess;

/*
 * TODO Implement timestamp based session (http://php.net/manual/en/features.session.security.management.php#features.session.security.management.session-data-deletion)
 * TODO Implement session locking (http://php.net/manual/en/features.session.security.management.php#features.session.security.management.session-locking)
 * TODO Allow user to specify hash algorithm
 * TODO Allow user to specify encryption algorithm
 * TODO Allow user to specify APP name so that session files from different applications never gets mixed
 * TODO Make tests
 * TODO Instruct users to set the session.use_strict_mode ini config
 * TODO Show what a session file looks like with and without encryption in README
 * TODO Option to lock session to IP
 * TODO Option to lock session to User Agent
 * TODO Option to lock session to Host
 * TODO Option to create session cookie with mutating random name
 * TODO Create a way to change encryption/hash algorithms over time without loosing previous sessions, to allow incremental security upgrades
 */

/**
 * Manages the session in a secure way.
 *
 * Features:
 * - Encrypts the session data so that not even the server admin can read.
 * - Rejects arbitrary session ids, generating a new one when a non-existent id is provided.
 *
 * @package Ssess
 * @author Ayrton Fidelis <ayrton.vargas33@gmail.com>
 */
class Ssess implements \SessionHandlerInterface
{
    /**
     * @var string $savePath The full path where the session files are stored
     */
    private $savePath;

    /**
     * @var string $appKey The hashed key of the app. This is only part of the key used to encrypt the session data.
     */
    private $appKey;

    /**
     * @var string $encryptionAlgorithm The algorithm used to encrypt the session data. For a list of available algorithms, use openssl_get_cipher_methods().
     */
    private $encryptionAlgorithm;

    /**
     * @var string $hashAlgorithm The algorithm used to hash the keys and the session file name. For a list of available algorithms, use openssl_get_md_methods().
     */
    private $hashAlgorithm;

    /**
     * Ssess constructor.
     *
     * It computes the app_key hash and calls the function that handles the strict mode.
     *
     * @param string $app_key The encryption key of the app. The hash of it will be used as part of the encryption key.
     * @param string $hash_algorithm The algorithm used to hash. For a list of available algorithms, use openssl_get_md_methods().
     * @param string $encryption_algorithm The algorithm used for encryption. For a list of available algorithms, use openssl_get_cipher_methods().
     */
    public function __construct($app_key, $hash_algorithm = 'sha512', $encryption_algorithm = 'aes128')
    {
        $this->hashAlgorithm = $hash_algorithm;
        $this->encryptionAlgorithm = $encryption_algorithm;
        $this->appKey = openssl_digest($app_key, $this->hashAlgorithm);
        $this->handleStrict();
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

        $save_path = session_save_path();
        if (!is_dir($save_path)) {
            return;
        }

        $file_name = $this->getFileName($session_id);
        if (file_exists("$save_path/$file_name")) {
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
        $this->savePath = $save_path;
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777);
        }

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
        $file_name = $this->getFileName($session_id);

        $content = @file_get_contents("$this->savePath/$file_name");

        if (!$content) {
            return '';
        }

        return $this->decrypt($session_id, $content);
    }

    /**
     * Encrypts the session data and saves to the file;
     *
     * @param string $session_id Id of the session
     * @param string $session_data Unencrypted session data
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        $file_name = $this->getFileName($session_id);

        $content = $this->encrypt($session_id, $session_data);

        return file_put_contents("$this->savePath/$file_name", $content) !== false;
    }

    /**
     * Destroys the session file.
     *
     * @param string $session_id Id of the session
     * @return bool
     */
    public function destroy($session_id)
    {
        $file_name = $this->getFileName($session_id);

        $file = "$this->savePath/$file_name";
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    /**
     * Removes the files of expired sessions.
     *
     * (GC stands for Garbage Collector)
     *
     * @param int $maxlifetime The maximum time (in seconds) that a session file must be kept.
     * @return bool
     */
    public function gc($maxlifetime)
    {
        foreach (glob("$this->savePath/ssess_*") as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }

        return true;
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
     * Gets the name of the session file
     *
     * @param string $session_id Id of the session
     * @return string Name of the file (without the path)
     */
    private function getFileName($session_id)
    {
        return 'ssess_'.openssl_digest($session_id, $this->hashAlgorithm);
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