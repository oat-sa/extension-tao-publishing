<?php

namespace oat\taoPublishing\test\unit\model\adapter\exception;

use oat\taoPublishing\model\adapter\exception\RequestException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class RequestExceptionTest extends TestCase
{
    public function testGetRequest()
    {
        $request = $this->createMock(RequestInterface::class);
        $exception = new RequestException(
            $request
        );

        $this->assertSame($request, $exception->getRequest());
    }
}
