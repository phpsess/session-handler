<?php

declare(strict_types=1);

namespace Ssess\Exception;

class UnableToGenerateRandomnessException extends \RuntimeException
{

    protected $message = 'The encryption driver was unable to generate random data do securely encrypt the session data.';

}