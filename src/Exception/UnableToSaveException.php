<?php

namespace Ssess\Exception;

class UnableToSaveException extends \RuntimeException
{

    protected $message = 'The session storage driver was unable to save the session.';

}