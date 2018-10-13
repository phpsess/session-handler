<?php

declare(strict_types=1);

namespace PHPSess\Storage;

use PHPSess\Exception\SessionNotFoundException;

/**
 * Uses an array to mock the session data. May be useful in tests.
 *
 * @package PHPSess\Storage
 * @author  Ayrton Fidelis <ayrton.vargas33@gmail.com>
 */
class MockStorage implements StorageInterface
{

    /**
     * @var array $files The array that stores all the session data.
     */
    private static $files = [];

    /**
     * Saves the encrypted session data to the storage.
     *
     * @throws \PHPSess\Exception\UnableToSaveException
     * @param  string $sessionIdentifier The string used to identify the session data.
     * @param  string $sessionData       The encrypted session data.
     * @return void
     */
    public function save(string $sessionIdentifier, string $sessionData): void
    {
        self::$files[$sessionIdentifier] = [
            'data' => $sessionData,
            'time' => microtime(true)
        ];
    }

    /**
     * Fetches the encrypted session data based on the session identifier.
     *
     * @throws \PHPSess\Exception\SessionNotFoundException
     * @throws \PHPSess\Exception\UnableToFetchException
     * @param  string $sessionIdentifier The session identifier
     * @return string The encrypted session data
     */
    public function get(string $sessionIdentifier): string
    {
        if (!$this->sessionExists($sessionIdentifier)) {
            throw new SessionNotFoundException();
        }

        return self::$files[$sessionIdentifier]['data'];
    }

    /**
     * Checks if a session with the given identifier exists in the storage.
     *
     * @param  string $sessionIdentifier The session identifier.
     * @return boolean Whether the session exists or not.
     */
    public function sessionExists(string $sessionIdentifier): bool
    {
        return isset(self::$files[$sessionIdentifier]);
    }

    /**
     * Remove this session from the storage.
     *
     * @throws \PHPSess\Exception\SessionNotFoundException
     * @param  string $sessionIdentifier The session identifier.
     * @return void
     */
    public function destroy(string $sessionIdentifier): void
    {
        if (!isset(self::$files[$sessionIdentifier])) {
            throw new SessionNotFoundException();
        }

        unset(self::$files[$sessionIdentifier]);
    }

    /**
     * Removes the session older than the specified time from the storage.
     *
     * @throws \PHPSess\Exception\UnableToDeleteException
     * @param  int $maxLife The maximum time (in microseconds) that a session file must be kept.
     * @return void
     */
    public function clearOld(int $maxLife): void
    {
        $limit = microtime(true) - $maxLife / 1000000;

        foreach (self::$files as &$file) {
            if ($file['time'] <= $limit) {
                $file = null;
            }
        }

        self::$files = array_filter(self::$files);
    }
}
