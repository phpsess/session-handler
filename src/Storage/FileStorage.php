<?php

declare(strict_types=1);

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
     * @param string|null $file_path The absolute path to the session files directory. If not set, defaults to INI session.save_path.
     * @param string $file_prefix The prefix used in the session file name.
     */
    public function __construct(?string $file_path = NULL, string $file_prefix = 'ssess_')
    {
        if (!$file_path) {
            $file_path = ini_get('session.save_path');
        }

        if ($file_path === false) {
            throw new UnableToCreateDirectoryException();
        }

        $this->filePath = $file_path;

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
    public function save(string $session_identifier, string $session_data): void
    {
        $file_name = $this->getFileName($session_identifier);

        $contents = json_encode(array(
            'data' => $session_data,
            'time' => microtime(true)
        ));

        if (@file_put_contents($file_name, $contents) === false) {
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
    public function get(string $session_identifier): string
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
    public function sessionExists(string $session_identifier): bool
    {
        $file_name = $this->getFileName($session_identifier);

        clearstatcache(true, $file_name);

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
    public function destroy(string $session_identifier): void
    {
        if (!$this->sessionExists($session_identifier)) {
            throw new SessionNotFoundException();
        }

        $file_name = $this->getFileName($session_identifier);

        if (!@unlink($file_name)) {
            throw new UnableToDeleteException();
        }

        clearstatcache(true, $file_name);
    }

    /**
     * Removes the session older than the specified time from the storage.
     *
     * @throws \Ssess\Exception\UnableToDeleteException
     * @param int $max_life The maximum time (in microseconds) that a session file must be kept.
     * @return void
     */
    public function clearOld(int $max_life): void
    {
        $files = scandir($this->filePath);

        if ($files === false) {
            throw new UnableToDeleteException();
        }

        $limit_time = microtime(true) - $max_life / 1000000;

        $has_error = false;
        foreach ($files as $file) {
            $full_path = "$this->filePath/$file";

            if (!$this->shouldBeCleared($full_path, $file, $this->filePrefix, $limit_time)) {
                continue;
            }

            if (!@unlink("$this->filePath/$file")) {
                $has_error = true;
            }

            clearstatcache(true, $full_path);
        }

        if ($has_error) {
            throw new UnableToDeleteException();
        }
    }

    /**
     * Checks whether a file should be removed by clearOld or not
     *
     * @param string $full_path The absolute path to the file
     * @param string $file_name Only the name of the file
     * @param string $prefix The prefix of the session files
     * @param float $limit_time The maximum timestamp (in microseconds) a file can be kept
     * @return bool If the file should be cleared or not
     */
    private function shouldBeCleared(string $full_path, string $file_name, string $prefix, float $limit_time): bool
    {
        if (strpos($file_name, $prefix) !== 0) {
            return false;
        }

        clearstatcache(true, $full_path);

        if (!is_file($full_path)) {
            return false;
        }

        $contents = @file_get_contents($full_path);
        if ($contents === false) {
            throw new UnableToDeleteException();
        }

        $content = json_decode($contents);

        if ($content->time > $limit_time) {
            return false;
        }

        return true;
    }

    /**
     * Mounts the absolute file name.
     *
     * @param string $session_identifier The session identifier
     * @return string The absolute file name.
     */
    private function getFileName(string $session_identifier): string
    {
        return $this->filePath . '/' . $this->filePrefix . $session_identifier;
    }

}