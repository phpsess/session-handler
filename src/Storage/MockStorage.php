<?php

namespace Ssess\Storage;

/**
 * Uses an array to mock the session data.
 *
 * @package Ssess\Storage
 * @author Ayrton Fidelis <ayrton.vargas33@gmail.com>
 */
class MockStorage implements StorageInterface
{

    private static $files = [];

    public function save($session_identifier, $session_data)
    {
        self::$files[$session_identifier] = array(
            'data' => $session_data,
            'time' => time()
        );

        return true;
    }

    public function get($session_identifier)
    {
        if (!isset(self::$files[$session_identifier])) {
            return '';
        }
        return self::$files[$session_identifier]['data'];
    }

    public function sessionExists($session_identifier)
    {
        return isset(self::$files[$session_identifier]);
    }

    public function destroy($session_identifier)
    {
        unset(self::$files[$session_identifier]);

        return true;
    }

    public function clearOld($max_life)
    {
        foreach (self::$files as &$file) {
            if ($file['time'] + $max_life < time()) {
                $file = null;
            }
        }

        self::$files = array_filter(self::$files);

        return true;
    }

}