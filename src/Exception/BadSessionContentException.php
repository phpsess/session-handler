<?php

declare(strict_types=1);

namespace PHPSess\Exception;

class BadSessionContentException extends \UnexpectedValueException
{

    protected $message = 'The session content either mal-formed or corrupted.';
}
