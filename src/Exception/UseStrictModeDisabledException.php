<?php

declare(strict_types=1);

namespace Ssess\Exception;

class UseStrictModeDisabledException extends \RuntimeException
{

    protected $message = 'Insecure session config: session.use_strict_mode should be set to true';

}