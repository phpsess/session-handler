<?php

declare(strict_types=1);

namespace Ssess\CryptProvider;

interface CryptProviderInterface
{

    /**
     * CryptProviderInterface constructor.
     *
     * @throws \Ssess\Exception\UnknownEncryptionAlgorithmException
     * @throws \Ssess\Exception\UnknownHashAlgorithmException
     * @param  string $appKey              Defines the App Key.
     * @param  string $hashAlgorithm       Defines the algorithm used to create hashes.
     * @param  string $encryptionAlgorithm Defines the algorithm to encrypt/decrypt data.
     */
    public function __construct(string $appKey, string $hashAlgorithm, string $encryptionAlgorithm);

    /**
     * Makes a session identifier based on the session id.
     *
     * @param  string $sessionId The session id.
     * @return string The session identifier.
     */
    public function makeSessionIdentifier(string $sessionId): string ;

    /**
     * Encrypts the session data.
     *
     * @param  string $sessionId   The session id.
     * @param  string $sessionData The session data.
     * @return string The encrypted session data.
     */
    public function encryptSessionData(string $sessionId, string $sessionData): string ;

    /**
     * Decrypts the session data.
     *
     * @throws \Ssess\Exception\UnableToDecryptException
     * @param  string $sessionId   The session id.
     * @param  string $sessionData The encrypted session data.
     * @return string The decrypted session data.
     */
    public function decryptSessionData(string $sessionId, string $sessionData): string ;
}
