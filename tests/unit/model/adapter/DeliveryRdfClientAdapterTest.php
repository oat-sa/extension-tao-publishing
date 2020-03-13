<?php

namespace oat\taoPublishing\model\adapter;

use oat\taoPublishing\model\PlatformService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class DeliveryRdfClientAdapterTest extends TestCase
{
    /**
     * @var DeliveryRdfClientAdapter
     */
    private $sut;

    /**
     * @var PlatformService|MockObject
     */
    private $platformService;

    protected function setUp(): void
    {
        $this->platformService = $this->createMock(PlatformService::class);
        $this->sut = new DeliveryRdfClientAdapter(
            $this->platformService,
            'test'
        );
    }

    public function testSendSuccessRequest(): void
    {
        $request = $this->createMock(RequestInterface::class);

        $this->platformService->expects($this->once())
            ->method('callApi')
            ->with('test', $request)
            ->willReturn(
                $this->createMock(ResponseInterface::class)
            );

        $this->assertInstanceOf(ResponseInterface::class, $this->sut->sendRequest($request));
    }

    public function testSendRequestWithACommonError(): void
    {
        $this->expectException(RequestException::class);

        $request = $this->createMock(RequestInterface::class);

        $this->platformService->expects($this->once())
            ->method('callApi')
            ->with('test', $request)
            ->willThrowException(
                new \common_Exception()
            );
        $this->sut->sendRequest($request);
    }

    public function testSendRequestWithACoreKernelError(): void
    {
        $this->expectException(RequestException::class);

        $request = $this->createMock(RequestInterface::class);

        $this->platformService->expects($this->once())
            ->method('callApi')
            ->with('test', $request)
            ->willThrowException(
                $this->createMock(\core_kernel_classes_EmptyProperty::class)
            );

        $this->sut->sendRequest($request);
    }
}
