<?php

declare(strict_types=1);

namespace Ssess\Exception;

class UnableToSaveException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to save the session.';
}
