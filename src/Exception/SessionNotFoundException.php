<?php

declare(strict_types=1);

namespace Ssess\Exception;

class SessionNotFoundException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to find the session.';
}
