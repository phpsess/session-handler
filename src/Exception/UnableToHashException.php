<?php

declare(strict_types=1);

namespace Ssess\Exception;

class UnableToHashException extends \RuntimeException
{

    protected $message = 'The encryption driver was unable to hash the data.';
}
