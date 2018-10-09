<?php

declare(strict_types=1);

namespace Ssess\CryptProvider;

interface CryptProviderInterface {

    /**
     * CryptProviderInterface constructor.
     *
     * @throws \Ssess\Exception\UnknownEncryptionAlgorithmException
     * @throws \Ssess\Exception\UnknownHashAlgorithmException
     * @param string $app_key Defines the App Key.
     * @param string|null $hash_algorithm Defines the algorithm used to create hashes.
     * @param string|null $encryption_algorithm Defines the algorithm to encrypt/decrypt data.
     */
    public function __construct(string $app_key, ?string $hash_algorithm, ?string $encryption_algorithm);

    /**
     * Makes a session identifier based on the session id.
     *
     * @param string $session_id The session id.
     * @return string The session identifier.
     */
    public function makeSessionIdentifier(string $session_id): string ;

    /**
     * Encrypts the session data.
     *
     * @param string $session_id The session id.
     * @param string $session_data The session data.
     * @return string The encrypted session data.
     */
    public function encryptSessionData(string $session_id, string $session_data): string ;

    /**
     * Decrypts the session data.
     *
     * @throws \Ssess\Exception\UnableToDecryptException
     * @param string $session_id The session id.
     * @param string $session_data The encrypted session data.
     * @return string The decrypted session data.
     */
    public function decryptSessionData(string $session_id, string $session_data): string ;
}