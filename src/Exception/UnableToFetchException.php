<?php

declare(strict_types=1);

namespace Ssess\Exception;

class UnableToFetchException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to fetch the session data.';
}
