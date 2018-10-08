<?php

namespace Ssess\CryptProvider;

interface CryptProviderInterface {

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