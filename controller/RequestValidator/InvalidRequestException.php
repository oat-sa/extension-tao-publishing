<?php declare(strict_types=1);

namespace oat\taoPublishing\controller\RequestValidator;

use RuntimeException;
use Throwable;

class InvalidRequestException extends RuntimeException
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
        parent::__construct(
            sprintf("The provided request is invalid. Message %s", $message),
            $code,
            $previous
        );
    }
}