<?php

namespace Ssess\Storage;

use Ssess\Exception\SessionNotFoundException;
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
     * @param string $file_path The absolute path to the session files directory. If not set, defaults to INI session.save_path.
     * @param string $file_prefix The prefix used in the session file name.
     */
    public function __construct($file_path = NULL, $file_prefix = 'ssess_')
    {
        $this->filePath = $file_path ? $file_path : ini_get('session.save_path');

        if (!file_exists($this->filePath)) {
            mkdir($this->filePath, 0777);
        }

        $this->filePrefix = $file_prefix;
    }

    public function save($session_identifier, $session_data)
    {
        $file_name = $this->getFileName($session_identifier);

        if (file_put_contents($file_name, $session_data) === false) {
            throw new UnableToSaveException();
        }
    }

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

        return $contents;
    }

    public function sessionExists($session_identifier)
    {
        $file_name = $this->getFileName($session_identifier);

        return file_exists($file_name);
    }

    public function destroy($session_identifier)
    {
        if (!$this->sessionExists($session_identifier)) {
            throw new SessionNotFoundException();
        }

        $file_name = $this->getFileName($session_identifier);

        if (!unlink($file_name)) {
            throw new UnableToDeleteException();
        }
    }

    public function clearOld($max_life)
    {
        $files = glob("$this->filePath/$this->filePrefix*");
        $has_error = false;
        foreach ($files as $file) {
            if (filemtime($file) + $max_life > time()) {
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