<?php

namespace Ssess\Storage;

use Ssess\Exception\SessionNotFoundException;

/**
 * Uses an array to mock the session data. May be useful in tests.
 *
 * @package Ssess\Storage
 * @author Ayrton Fidelis <ayrton.vargas33@gmail.com>
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
     * @throws \Ssess\Exception\UnableToSaveException
     * @param string $session_identifier The string used to identify the session data.
     * @param string $session_data The encrypted session data.
     * @return void
     */
    public function save($session_identifier, $session_data)
    {
        self::$files[$session_identifier] = array(
            'data' => $session_data,
            'time' => microtime(true)
        );
    }

    /**
     * Fetches the encrypted session data based on the session identifier.
     *
     * @throws \Ssess\Exception\SessionNotFoundException
     * @throws \Ssess\Exception\UnableToFetchException
     * @param string $session_identifier The session identifier
     * @return string The encrypted session data
     */
    public function get($session_identifier)
    {
        if (!$this->sessionExists($session_identifier)) {
            throw new SessionNotFoundException();
        }

        return self::$files[$session_identifier]['data'];
    }

    /**
     * Checks if a session with the given identifier exists in the storage.
     *
     * @param string $session_identifier The session identifier.
     * @return boolean Whether the session exists or not.
     */
    public function sessionExists($session_identifier)
    {
        return isset(self::$files[$session_identifier]);
    }

    /**
     * Remove this session from the storage.
     *
     * @throws \Ssess\Exception\SessionNotFoundException
     * @throws \Ssess\Exception\UnableToDeleteException
     * @param string $session_identifier The session identifier.
     * @return void
     */
    public function destroy($session_identifier)
    {
        unset(self::$files[$session_identifier]);
    }

    /**
     * Removes the session older than the specified time from the storage.
     *
     * @throws \Ssess\Exception\UnableToDeleteException
     * @param float $max_life The maximum time (in milliseconds) that a session file must be kept.
     * @return void
     */
    public function clearOld($max_life)
    {
        foreach (self::$files as &$file) {
            if ($file['time'] + $max_life < microtime(true)) {
                $file = null;
            }
        }

        self::$files = array_filter(self::$files);
    }

}