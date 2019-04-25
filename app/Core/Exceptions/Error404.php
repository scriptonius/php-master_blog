<?php

namespace NTSchool\Phpblog\Core\Exceptions;


use Throwable;

class Error404 extends BaseException
{
    /**
     * Error404 constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $this->dest .= '/err404';
        parent::__construct($message, $code, $previous);
    }
}