<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class UnableToDeleteException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to delete the session.';
}
