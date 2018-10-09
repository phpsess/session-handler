<?php

namespace Ssess\Exception;

class UnknownEncryptionAlgorithmException extends \RuntimeException
{

    protected $message = 'The requested encryption algorithm is unknown';

}