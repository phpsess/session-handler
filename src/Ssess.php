<?php

declare(strict_types=1);

namespace Ssess;

use Ssess\CryptProvider\CryptProviderInterface;
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
     * @var boolean $warnInsecureSettings Whether the handler should warn about insecure settings or not.
     */
    public static $warnInsecureSettings = true;

    /**
     * @var StorageInterface $storageDriver The driver used to store the session data.
     */
    private $storageDriver;

    /**
     * @var CryptProviderInterface $cryptProvider The driver used to deal with encryption/decryption/hashing.
     */
    private $cryptProvider;

    /**
     * Ssess constructor.
     *
     * It computes the app_key hash and calls the function that handles the strict mode.
     *
     * @param CryptProviderInterface $cryptProvider The driver used to deal with encryption/decryption/hashing.
     * @param StorageInterface|null $storage The driver used to store the session data.
     */
    public function __construct(CryptProviderInterface $cryptProvider, ?StorageInterface $storage = NULL)
    {
        $this->cryptProvider = $cryptProvider;
        $this->storageDriver = $storage ? $storage : new Storage\FileStorage();

        $this->warnInsecureSettings();
        $this->handleStrict();
    }

    /**
     * Throws exceptions when insecure INI settings are detected.
     *
     * @throws UseCookiesDisabledException
     * @throws UseOnlyCookiesDisabledException
     * @throws UseTransSidEnabledException
     * @throws UseStrictModeDisabledException
     * @return void
     */
    private function warnInsecureSettings(): void
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
     * @SuppressWarnings(PHPMD.Superglobals)
     *
     * @see http://php.net/manual/en/features.session.security.management.php#features.session.security.management.non-adaptive-session Why this security measure is important.
     * @return void
     */
    private function handleStrict(): void
    {
        if (!ini_get('session.use_strict_mode') || headers_sent()) {
            return;
        }

        $cookieName = session_name();
        if (empty($_COOKIE[$cookieName])) {
            return;
        }

        $sessionId = $_COOKIE[$cookieName];

        $identifier = $this->cryptProvider->makeSessionIdentifier($sessionId);

        if ($this->storageDriver->sessionExists($identifier)) {
            return;
        }

        $newSessionId = session_create_id();
        session_id($newSessionId);
    }

    /**
     * Opens the session.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param string $savePath The path where the session files will be saved.
     * @param string $sessionName The name of the session
     * @return bool
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    /**
     * Closes the session
     *
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Return the decrypted (but still serialized) data of the session.
     *
     * @param string $sessionId Id of the session
     * @return string Decrypted session data (still serialized)
     */
    public function read($sessionId): string
    {
        $identifier = $this->cryptProvider->makeSessionIdentifier($sessionId);

        if (!$this->storageDriver->sessionExists($identifier)) {
            return '';
        }

        $content = $this->storageDriver->get($identifier);
        if (!$content) {
            return '';
        }

        return $this->cryptProvider->decryptSessionData($sessionId, $content);
    }

    /**
     * Encrypts the session data and saves to the storage;
     *
     * @param string $sessionId Id of the session
     * @param string $sessionData Unencrypted session data
     * @return boolean
     */
    public function write($sessionId, $sessionData): bool
    {
        $identifier = $this->cryptProvider->makeSessionIdentifier($sessionId);

        $content = $this->cryptProvider->encryptSessionData($sessionId, $sessionData);

        try {
            $this->storageDriver->save($identifier, $content);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Destroys the session.
     *
     * @param string $sessionId Id of the session
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        $identifier = $this->cryptProvider->makeSessionIdentifier($sessionId);

        try {
            $this->storageDriver->destroy($identifier);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Removes the expired sessions from the storage.
     *
     * (GC stands for Garbage Collector)
     *
     * @SuppressWarnings(PHPMD.ShortMethodName)
     *
     * @param int $maxLife The maximum time (in seconds) that a session must be kept.
     * @return bool
     */
    public function gc($maxLife): bool
    {
        try {
            $this->storageDriver->clearOld($maxLife * 1000000);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}