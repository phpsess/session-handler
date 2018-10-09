<?php

namespace Ssess\Storage;

use Ssess\Exception\DirectoryNotReadableException;
use Ssess\Exception\DirectoryNotWritableException;
use Ssess\Exception\SessionNotFoundException;
use Ssess\Exception\UnableToCreateDirectoryException;
use Ssess\Exception\UnableToDeleteException;
use Ssess\Exception\UnableToFetchException;
use Ssess\Exception\UnableToSaveException;

/**
 * Uses the filesystem to store the session data.
 *
 * @package Ssess\Storage
 * @author Ayrton Fidelis <ayrton.vargas33@gmail.com>
 */
class FileStorage implements StorageInterface
{

    /**
     * @var string $filePrefix The prefix used in the session file name.
     */
    private $filePrefix;

    /**
     * @var string $filePath The absolute path where the session files are saved.
     */
    private $filePath;

    /**
     * FileStorage constructor.
     *
     * @throws DirectoryNotReadableException
     * @throws DirectoryNotWritableException
     * @throws UnableToCreateDirectoryException
     * @param string $file_path The absolute path to the session files directory. If not set, defaults to INI session.save_path.
     * @param string $file_prefix The prefix used in the session file name.
     */
    public function __construct($file_path = NULL, $file_prefix = 'ssess_')
    {
        $this->filePath = $file_path ? $file_path : ini_get('session.save_path');

        if (!file_exists($this->filePath)) {
            if (!mkdir($this->filePath, 0777)) {
                throw new UnableToCreateDirectoryException();
            }
        }

        if (!is_readable($this->filePath)) {
            throw new DirectoryNotReadableException();
        }

        if (!is_writable($this->filePath)) {
            throw new DirectoryNotWritableException();
        }

        $this->filePrefix = $file_prefix;
    }

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
        $file_name = $this->getFileName($session_identifier);

        $contents = json_encode(array(
            'data' => $session_data,
            'time' => microtime(true)
        ));

        if (file_put_contents($file_name, $contents) === false) {
            throw new UnableToSaveException();
        }
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
        $file_name = $this->getFileName($session_identifier);

        if (!$this->sessionExists($session_identifier)) {
            throw new SessionNotFoundException();
        }

        try {
            $contents = (string) file_get_contents($file_name);
        } catch (\Exception $e) {
            throw new UnableToFetchException();
        }

        $data = json_decode($contents);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($data->data)) {
            throw new UnableToFetchException();
        }

        return $data->data;
    }

    /**
     * Checks if a session with the given identifier exists in the storage.
     *
     * @param string $session_identifier The session identifier.
     * @return boolean Whether the session exists or not.
     */
    public function sessionExists($session_identifier)
    {
        $file_name = $this->getFileName($session_identifier);

        clearstatcache($file_name);

        return file_exists($file_name);
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
        if (!$this->sessionExists($session_identifier)) {
            throw new SessionNotFoundException();
        }

        $file_name = $this->getFileName($session_identifier);

        if (!unlink($file_name)) {
            throw new UnableToDeleteException();
        }

        clearstatcache($file_name);
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
        $files = glob("$this->filePath/$this->filePrefix*");
        $has_error = false;
        foreach ($files as $file) {
            $content = json_decode(file_get_contents($file));

            if ($content->time + $max_life > microtime(true)) {
                continue;
            }

            if (!unlink($file)) {
                $has_error = true;
            }
        }

        if ($has_error) {
            throw new UnableToDeleteException();
        }
    }

    /**
     * Mounts the absolute file name.
     *
     * @param string $session_identifier The session identifier
     * @return string The absolute file name.
     */
    private function getFileName($session_identifier)
    {
        return $this->filePath . '/' . $this->filePrefix . $session_identifier;
    }

}