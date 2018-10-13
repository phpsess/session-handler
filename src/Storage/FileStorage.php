<?php

declare(strict_types=1);

namespace PHPSess\Storage;

use PHPSess\Exception\DirectoryNotReadableException;
use PHPSess\Exception\DirectoryNotWritableException;
use PHPSess\Exception\SessionNotFoundException;
use PHPSess\Exception\UnableToCreateDirectoryException;
use PHPSess\Exception\UnableToDeleteException;
use PHPSess\Exception\UnableToFetchException;
use PHPSess\Exception\UnableToSaveException;

/**
 * Uses the filesystem to store the session data.
 *
 * @package PHPSess\Storage
 * @author  Ayrton Fidelis <ayrton.vargas33@gmail.com>
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
     * @param  string|null $filePath   The absolute path to the session files directory. If not set, defaults to INI session.save_path.
     * @param  string      $filePrefix The prefix used in the session file name.
     */
    public function __construct(?string $filePath = null, string $filePrefix = 'ssess_')
    {
        if (!$filePath) {
            $filePath = ini_get('session.save_path');
        }

        if (!$filePath) {
            throw new UnableToCreateDirectoryException();
        }

        if (!file_exists($filePath) && !@mkdir($filePath, 0777)) {
            throw new UnableToCreateDirectoryException();
        }

        if (!is_readable($filePath)) {
            throw new DirectoryNotReadableException();
        }

        if (!is_writable($filePath)) {
            throw new DirectoryNotWritableException();
        }

        $this->filePath = $filePath;
        $this->filePrefix = $filePrefix;
    }

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
        $fileName = $this->getFileName($sessionIdentifier);

        $contents = json_encode([
            'data' => $sessionData,
            'time' => microtime(true)
        ]);

        if (@file_put_contents($fileName, $contents) === false) {
            throw new UnableToSaveException();
        }
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
        $fileName = $this->getFileName($sessionIdentifier);

        if (!$this->sessionExists($sessionIdentifier)) {
            throw new SessionNotFoundException();
        }

        try {
            $contents = (string) file_get_contents($fileName);
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
     * @param  string $sessionIdentifier The session identifier.
     * @return boolean Whether the session exists or not.
     */
    public function sessionExists(string $sessionIdentifier): bool
    {
        $fileName = $this->getFileName($sessionIdentifier);

        clearstatcache(true, $fileName);

        return file_exists($fileName);
    }

    /**
     * Remove this session from the storage.
     *
     * @throws \PHPSess\Exception\SessionNotFoundException
     * @throws \PHPSess\Exception\UnableToDeleteException
     * @param  string $sessionIdentifier The session identifier.
     * @return void
     */
    public function destroy(string $sessionIdentifier): void
    {
        if (!$this->sessionExists($sessionIdentifier)) {
            throw new SessionNotFoundException();
        }

        $fileName = $this->getFileName($sessionIdentifier);

        if (!@unlink($fileName)) {
            throw new UnableToDeleteException();
        }

        clearstatcache(true, $fileName);
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
        $files = @scandir($this->filePath);

        if ($files === false) {
            throw new UnableToDeleteException();
        }

        $limitTime = microtime(true) - $maxLife / 1000000;

        $hasError = false;
        foreach ($files as $file) {
            $fullPath = "$this->filePath/$file";

            if (!$this->shouldBeCleared($fullPath, $file, $this->filePrefix, $limitTime)) {
                continue;
            }

            if (!@unlink("$this->filePath/$file")) {
                $hasError = true;
            }

            clearstatcache(true, $fullPath);
        }

        if ($hasError) {
            throw new UnableToDeleteException();
        }
    }

    /**
     * Checks whether a file should be removed by clearOld or not
     *
     * @param  string $fullPath  The absolute path to the file
     * @param  string $fileName  Only the name of the file
     * @param  string $prefix    The prefix of the session files
     * @param  float  $limitTime The maximum timestamp (in microseconds) a file can be kept
     * @return bool If the file should be cleared or not
     */
    private function shouldBeCleared(string $fullPath, string $fileName, string $prefix, float $limitTime): bool
    {
        if (strpos($fileName, $prefix) !== 0) {
            return false;
        }

        clearstatcache(true, $fullPath);

        if (!is_file($fullPath)) {
            return false;
        }

        $contents = @file_get_contents($fullPath);
        if ($contents === false) {
            throw new UnableToDeleteException();
        }

        $content = json_decode($contents);

        if ($content->time > $limitTime) {
            return false;
        }

        return true;
    }

    /**
     * Mounts the absolute file name.
     *
     * @param  string $sessionIdentifier The session identifier
     * @return string The absolute file name.
     */
    private function getFileName(string $sessionIdentifier): string
    {
        return $this->filePath . '/' . $this->filePrefix . $sessionIdentifier;
    }
}
