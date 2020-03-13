<?php

namespace oat\taoPublishing\test\integration\model\deliveryRdfClient;

use oat\taoPublishing\model\adapter\RequestException;
use oat\taoPublishing\model\deliveryRdfClient\DeliveryRdfFacade;
use oat\taoPublishing\model\deliveryRdfClient\entity\CompileDeferredResult;
use oat\taoPublishing\model\deliveryRdfClient\entity\Delivery;
use oat\taoPublishing\model\deliveryRdfClient\entity\TestPackage;
use oat\taoPublishing\model\deliveryRdfClient\resource\RestTest;
use oat\taoPublishing\model\deliveryRdfClient\resource\restTest\CompileDeferredFailureException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class DeliveryRdfFacadeTest extends TestCase
{
    /**
     * @var DeliveryRdfFacade
     */
    private $sut;

    /**
     * @var ClientInterface|MockObject
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);

        $this->sut = new DeliveryRdfFacade(
            $this->client
        );
    }

    public function testGetRestTestResourceShouldAlwaysReturnSameInstance(): void
    {
        $restTestResource = $this->sut->getRestTestResource();

        $this->assertInstanceOf(RestTest::class, $restTestResource);
        $this->assertSame($restTestResource, $this->sut->getRestTestResource());
    }

    public function testSuccessCompileDeferred(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('getContents')->willReturn(
            json_encode(
                [
                    'data' => [
                        'reference_id' => 'testReferenceId'
                    ]
                ]
            )
        );
        $response->method('getBody')->willReturn(
            $stream
        );
        $response->method('getStatusCode')->willReturn(200);

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->willReturn(
                $response
            );

        $restTestResource = $this->sut->getRestTestResource()->compileDeferred(
            new TestPackage('php://stdout'),
            new Delivery('testLabel', 'testOrigin', 'testClassLabel'),
            'testImporter'
        );

        $this->assertInstanceOf(CompileDeferredResult::class, $restTestResource);
        $this->assertEquals('testReferenceId', $restTestResource->getReferenceId());
    }

    public function testFailureCompileDeferred(): void
    {
        $this->expectException(CompileDeferredFailureException::class);

        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('getContents')->willReturn(
            json_encode(
                [
                    'errorMsg' => 'testReferenceId',
                    'errorCode' => 0,
                ]
            )
        );
        $response->method('getBody')->willReturn(
            $stream
        );
        $response->method('getStatusCode')->willReturn(400);

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->willReturn(
                $response
            );

        $delivery =  (new Delivery('testLabel', 'testOrigin', null))
            ->setDeliveryClassLabel('testClassLabel');

        $this->sut->getRestTestResource()->compileDeferred(
            new TestPackage('php://stdout'),
            $delivery,
            'testImporter'
        );
    }


    public function testRequestExceptionCompileDeferred(): void
    {
        $this->expectException(CompileDeferredFailureException::class);

        $this->client->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(new RequestException($this->createMock(RequestInterface::class)));

        $this->sut->getRestTestResource()->compileDeferred(
            new TestPackage('php://stdout'),
            new Delivery('testLabel', 'testOrigin', 'testClassLabel'),
            'testImporter'
        );
    }
}
