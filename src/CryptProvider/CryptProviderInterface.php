<?php

namespace Ssess\CryptProvider;

interface CryptProviderInterface {

    /**
     * CryptProviderInterface constructor.
     *
     * @param string $app_key Defines the App Key.
     * @param string $hash_algorithm Defines the algorithm used to create hashes.
     * @param string $encryption_algorithm Defines the algorithm to encrypt/decrypt data.
     */
    public function __construct($app_key, $hash_algorithm, $encryption_algorithm);

    /**
     * Makes a session identifier based on the session id.
     *
     * @param string $session_id The session id.
     * @return string The session identifier.
     */
    public function makeSessionIdentifier($session_id);

    /**
     * Encrypts the session data.
     *
     * @param string $session_id The session id.
     * @param string $session_data The session data.
     * @return string The encrypted session data.
     */
    public function encryptSessionData($session_id, $session_data);

    /**
     * Decrypts the session data.
     *
     * @param string $session_id The session id.
     * @param string $session_data The encrypted session data.
     * @return string The decrypted session data.
     */
    public function decryptSessionData($session_id, $session_data);
}