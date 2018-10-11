<?php

declare(strict_types=1);

namespace Ssess\Exception;

class UseCookiesDisabledException extends \RuntimeException
{

    protected $message = 'Insecure session config: session.use_cookies should be set to true';
}
