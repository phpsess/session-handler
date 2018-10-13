<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class SessionNotFoundException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to find the session.';
}
