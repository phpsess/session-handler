<?php

declare(strict_types=1);

namespace PHPSess\Exception;

/**
 * Class DirectoryNotReadableException
 * @package PHPSess\Exception
 * @deprecated Use the generic UnableToFetchException with a custom message instead.
 */
class DirectoryNotReadableException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to read the session directory.';
}
