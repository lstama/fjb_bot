<?php
namespace Kaskus\Exceptions;

class UnauthorizedException extends KaskusClientException
{

    public function __construct($message)
    {
        parent::__construct($message);
    }
}
