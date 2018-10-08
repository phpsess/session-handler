<?php

namespace Ssess\Storage;

/**
 * Interface StorageInterface
 *
 * @package Ssess\Storage
 * @author Ayrton Fidelis <ayrton.vargas33@gmail.com>
 */
interface StorageInterface {

    /**
     * Saves the encrypted session data to the storage.
     *
     * @throws \Ssess\Exception\UnableToSaveException
     * @param string $session_identifier The string used to identify the session data.
     * @param string $session_data The encrypted session data.
     * @return void
     */
    public function save($session_identifier, $session_data);

    /**
     * Fetches the encrypted session data based on the session identifier.
     *
     * @throws \Ssess\Exception\SessionNotFoundException
     * @throws \Ssess\Exception\UnableToFetchException
     * @param string $session_identifier The session identifier
     * @return string The encrypted session data
     */
    public function get($session_identifier);

    /**
     * Checks if a session with the given identifier exists in the storage.
     *
     * @param string $session_identifier The session identifier.
     * @return boolean Whether the session exists or not.
     */
    public function sessionExists($session_identifier);

    /**
     * Remove this session from the storage.
     *
     * @throws \Ssess\Exception\SessionNotFoundException
     * @throws \Ssess\Exception\UnableToDeleteException
     * @param string $session_identifier The session identifier.
     * @return void
     */
    public function destroy($session_identifier);

    /**
     * Removes the session older than the specified time from the storage.
     *
     * @throws \Ssess\Exception\UnableToDeleteException
     * @param int $max_life The maximum time (in seconds) that a session file must be kept.
     * @return void
     */
    public function clearOld($max_life);
}