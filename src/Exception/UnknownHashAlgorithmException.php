<?php

namespace Ssess\Exception;

class UnknownHashAlgorithmException extends \RuntimeException
{

    protected $message = 'The requested hashing algorithm is unknown';

}