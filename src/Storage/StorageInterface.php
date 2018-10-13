<?php

declare(strict_types=1);

namespace PHPSess\Storage;

/**
 * Interface StorageInterface
 *
 * @package PHPSess\Storage
 * @author  Ayrton Fidelis <ayrton.vargas33@gmail.com>
 */
interface StorageInterface
{

    /**
     * Saves the encrypted session data to the storage.
     *
     * @throws \PHPSess\Exception\UnableToSaveException
     * @param  string $sessionIdentifier The string used to identify the session data.
     * @param  string $sessionData       The encrypted session data.
     * @return void
     */
    public function save(string $sessionIdentifier, string $sessionData): void;

    /**
     * Fetches the encrypted session data based on the session identifier.
     *
     * @throws \PHPSess\Exception\SessionNotFoundException
     * @throws \PHPSess\Exception\UnableToFetchException
     * @param  string $sessionIdentifier The session identifier
     * @return string The encrypted session data
     */
    public function get(string $sessionIdentifier): string;

    /**
     * Checks if a session with the given identifier exists in the storage.
     *
     * @param  string $sessionIdentifier The session identifier.
     * @return boolean Whether the session exists or not.
     */
    public function sessionExists(string $sessionIdentifier): bool;

    /**
     * Remove this session from the storage.
     *
     * @throws \PHPSess\Exception\SessionNotFoundException
     * @throws \PHPSess\Exception\UnableToDeleteException
     * @param  string $sessionIdentifier The session identifier.
     * @return void
     */
    public function destroy(string $sessionIdentifier): void;

    /**
     * Removes the session older than the specified time from the storage.
     *
     * @throws \PHPSess\Exception\UnableToDeleteException
     * @param  int $maxLife The maximum time (in microseconds) that a session file must be kept.
     * @return void
     */
    public function clearOld(int $maxLife): void;
}
