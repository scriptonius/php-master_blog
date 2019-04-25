<?php

namespace NTSchool\Phpblog\Core\Exceptions;


use Throwable;

class Fatal extends BaseException
{
    /**
     * Fatal constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        $this->dest .= '/fatal';
        parent::__construct($message, $code, $previous);
        // mail
    }
}