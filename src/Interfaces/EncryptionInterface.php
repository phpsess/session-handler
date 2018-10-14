<?php

declare(strict_types=1);

namespace PHPSess\Interfaces;

/**
 * Interface for encryption/hashing drivers
 *
 * @package PHPSess\Interfaces
 * @author  Ayrton Fidelis <ayrton.vargas33@gmail.com>
 */
interface EncryptionInterface
{

    /**
     * EncryptionInterface constructor.
     *
     * @param  string $appKey The app-key that will make part of the encryption key and identifier hash.
     */
    public function __construct(string $appKey);

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
     * @throws \PHPSess\Exception\UnableToDecryptException
     * @param  string $sessionId   The session id.
     * @param  string $sessionData The encrypted session data.
     * @return string The decrypted session data.
     */
    public function decryptSessionData(string $sessionId, string $sessionData): string ;
}
