<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */
declare(strict_types=1);

namespace oat\taoPublishing\test\unit\model\publishing\delivery;

use core_kernel_classes_Class;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use League\Flysystem\FileNotFoundException;
use oat\generis\model\data\Ontology;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\filesystem\Directory;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use oat\taoPublishing\model\publishing\delivery\RemoteDeliveryPublisher;
use oat\taoPublishing\model\publishing\exception\PublishingFailedException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

class RemoteDeliveryPublisherTest extends TestCase
{
    /** @var RemoteDeliveryPublisher */
    private $subject;

    /** @var Ontology|MockObject */
    private $ontologyMock;

    /** @var FileSystemService|MockObject */
    private $fileSystemServiceMock;

    /** @var PlatformService|MockObject */
    private $platformServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ontologyMock = $this->createMock(Ontology::class);
        $this->fileSystemServiceMock = $this->createMock(FileSystemService::class);
        $this->platformServiceMock = $this->createMock(PlatformService::class);
        $publishingDeliveryServiceMock = $this->createMock(PublishingDeliveryService::class);

        $loggerMock = $this->createMock(LoggerInterface::class);
        $serviceLocatorMock = $this->getServiceLocatorMock(
            [
                FileSystemService::SERVICE_ID => $this->fileSystemServiceMock,
                PlatformService::class => $this->platformServiceMock,
                PublishingDeliveryService::SERVICE_ID => $publishingDeliveryServiceMock,
            ]
        );



        $this->subject = $this->getMockBuilder(RemoteDeliveryPublisher::class)
            ->onlyMethods(['getParentLabels'])
            ->getMock();
        $this->subject
            ->expects(self::atMost(1))
            ->method('getParentLabels')
            ->willReturn([]);
        $this->subject->setModel($this->ontologyMock);
        $this->subject->setLogger($loggerMock);
        $this->subject->setServiceLocator($serviceLocatorMock);
    }

    /**
     * @return core_kernel_classes_Resource|MockObject
     */
    private function getResourceMock(string $uri, string $label, bool $resourceExists): core_kernel_classes_Resource
    {
        $resourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $resourceMock->method('getUri')
            ->willReturn($uri);
        $resourceMock->method('getLabel')
            ->willReturn($label);
        $resourceMock->method('exists')
            ->willReturn($resourceExists);
        $resourceMock->method('getPropertiesValues')
            ->willReturn([]);

        return $resourceMock;
    }

    public function testPublishReturnRemoteTaskId(): void
    {
        $expectedRemoteTaskId = 'DUMMY_REMOTE_TASK_ID';

        $delivery = $this->getResourceMock('DUMMY_DELIVERY_URI', 'DUMMY_DELIVERY_LABEL', true);
        $environment = $this->getResourceMock('DUMMY_ENV_URI', 'DUMMY_ENV_LABEL', true);
        $test = $this->getResourceMock('DUMMY_TEST_URI', 'DUMMY_TEST_LABEL', true);

        // Mock request data preparation
        $this->mockCorrectRequestData($delivery);

        // Mock API call response
        $successResponse = sprintf('{"success":true, "data":{"reference_id":"%s"}}', $expectedRemoteTaskId);
        $responseMock = $this->getApiResponseMock(200, $successResponse);
        $this->platformServiceMock
            ->method('callApi')
            ->willReturn($responseMock);

        $remoteTaskId = $this->subject->publish($delivery, $environment, $test);
        self::assertSame($expectedRemoteTaskId, $remoteTaskId, "Method must return remote task ID.");
    }

    public function testPublish_WhenDeliveryDontExist_ThenThrowsException(): void {
        $delivery = $this->getResourceMock('DUMMY_DELIVERY_URI', 'DUMMY_DELIVERY_LABEL', false);
        $environment = $this->getResourceMock('DUMMY_ENV_URI', 'DUMMY_ENV_LABEL', true);
        $test = $this->getResourceMock('DUMMY_TEST_URI', 'DUMMY_TEST_LABEL', true);

        self::expectException(PublishingFailedException::class);

        $this->subject->publish($delivery, $environment, $test);
    }

    public function testPublish_WhenEnvironmentDontExist_ThenThrowsException(): void {
        $delivery = $this->getResourceMock('DUMMY_DELIVERY_URI', 'DUMMY_DELIVERY_LABEL', true);
        $environment = $this->getResourceMock('DUMMY_ENV_URI', 'DUMMY_ENV_LABEL', false);
        $test = $this->getResourceMock('DUMMY_TEST_URI', 'DUMMY_TEST_LABEL', true);

        self::expectException(PublishingFailedException::class);

        $this->subject->publish($delivery, $environment, $test);
    }

    public function testPublish_WhenQtiTestBackupDontExist_ThenThrowsException(): void {
        $delivery = $this->getResourceMock('DUMMY_DELIVERY_URI', 'DUMMY_DELIVERY_LABEL', true);
        $environment = $this->getResourceMock('DUMMY_ENV_URI', 'DUMMY_ENV_LABEL', true);
        $test = $this->getResourceMock('DUMMY_TEST_URI', 'DUMMY_TEST_LABEL', true);

        // Mock invalid request data preparation
        $backupPropertyMock = $this->createMock(core_kernel_classes_Property::class);
        $this->ontologyMock
            ->method('getProperty')
            ->willReturn($backupPropertyMock);
        $delivery->method('getOnePropertyValue')
            ->willReturn('DUMMY_PACKAGE_PATH');

        $fileMock = $this->getBackupFileMock();
        $fileMock->method('readPsrStream')
            ->willThrowException(new FileNotFoundException('DUMMY_PATH'));

        self::expectException(PublishingFailedException::class);

        $this->subject->publish($delivery, $environment, $test);
    }

    public function testPublish_WhenConnectionFailed_ThenThrowsException(): void {
        $delivery = $this->getResourceMock('DUMMY_DELIVERY_URI', 'DUMMY_DELIVERY_LABEL', true);
        $environment = $this->getResourceMock('DUMMY_ENV_URI', 'DUMMY_ENV_LABEL', true);
        $test = $this->getResourceMock('DUMMY_TEST_URI', 'DUMMY_TEST_LABEL', true);

        // Mock request data preparation
        $this->mockCorrectRequestData($delivery);

        // Mock API call response
        $requestMock = $this->createMock(RequestInterface::class);
        $this->platformServiceMock
            ->method('callApi')
            ->willThrowException(new ConnectException('DUMMY_MESSAGE', $requestMock));

        self::expectException(PublishingFailedException::class);

        $this->subject->publish($delivery, $environment, $test);
    }

    public function testPublish_WhenBadRequest_ThenThrowsException(): void {
        $delivery = $this->getResourceMock('DUMMY_DELIVERY_URI', 'DUMMY_DELIVERY_LABEL', true);
        $environment = $this->getResourceMock('DUMMY_ENV_URI', 'DUMMY_ENV_LABEL', true);
        $test = $this->getResourceMock('DUMMY_TEST_URI', 'DUMMY_TEST_LABEL', true);

        // Mock request data preparation
        $this->mockCorrectRequestData($delivery);

        // Mock API call response
        $requestMock = $this->createMock(RequestInterface::class);
        $responseMock = $this->getApiResponseMock(400, '');
        $this->platformServiceMock
            ->method('callApi')
            ->willThrowException(new ClientException('DUMMY_MESSAGE', $requestMock, $responseMock));

        self::expectException(PublishingFailedException::class);

        $this->subject->publish($delivery, $environment, $test);
    }

    /**
     * @param int $statusCode
     * @param string $responseBody
     *
     * @dataProvider dataProviderUnsuccessfulApiResponse
     */
    public function testPublish_WhenResponseUnsuccessful_ThenThrowsException(
        int $statusCode,
        string $responseBody
    ): void {
        $delivery = $this->getResourceMock('DUMMY_DELIVERY_URI', 'DUMMY_DELIVERY_LABEL', true);
        $environment = $this->getResourceMock('DUMMY_ENV_URI', 'DUMMY_ENV_LABEL', true);
        $test = $this->getResourceMock('DUMMY_TEST_URI', 'DUMMY_TEST_LABEL', true);

        // Mock request data preparation
        $this->mockCorrectRequestData($delivery);

        // Mock API call response
        $responseMock = $this->getApiResponseMock($statusCode, $responseBody);
        $this->platformServiceMock
            ->method('callApi')
            ->willReturn($responseMock);

        self::expectException(PublishingFailedException::class);

        $this->subject->publish($delivery, $environment, $test);
    }

    /**
     * @return File|MockObject
     */
    private function getBackupFileMock(): File
    {

        $fileMock = $this->createMock(File::class);
        $directoryMock = $this->createMock(Directory::class);
        $directoryMock->method('getFile')
            ->willReturn($fileMock);
        $this->fileSystemServiceMock
            ->method('getDirectory')
            ->willReturn($directoryMock);

        return $fileMock;
    }

    /**
     * @param int $statusCode
     * @param string $responseBody
     * @return ResponseInterface| MockObject
     */
    private function getApiResponseMock(int $statusCode, string $responseBody): ResponseInterface
    {
        $responseBodyMock = $this->createMock(StreamInterface::class);
        $responseBodyMock->method('getContents')
            ->willReturn($responseBody);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')
            ->willReturn(200);
        $responseMock->method('getBody')
            ->willReturn($responseBodyMock);

        return $responseMock;
    }

    /**
     * @return array
     */
    public function dataProviderUnsuccessfulApiResponse(): array
    {
        return [
            'Unsuccessful status code' => [
                'statusCode' => 500,
                'responseBody' => '{"success":false, "errorMsg": "DUMMY ERROR MESSAGE"}',
            ],
            'Status code OK, unsuccessful response' => [
                'statusCode' => 200,
                'responseBody' => '{"success":false, "errorMsg": "DUMMY ERROR MESSAGE"}',
            ],
            'Status code OK, successful response, no remote task ID' => [
                'statusCode' => 200,
                'responseBody' => '{"success":false, "data": {}}',
            ]
        ];
    }

    /**
     * @param $delivery
     */
    private function mockCorrectRequestData($delivery): void
    {
        $backupPropertyMock = $this->createMock(core_kernel_classes_Property::class);
        $this->ontologyMock
            ->method('getProperty')
            ->willReturn($backupPropertyMock);
        $delivery->method('getOnePropertyValue')
            ->willReturn('DUMMY_PACKAGE_PATH');

        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->method('isReadable')
            ->willReturn(true);
        $fileMock = $this->getBackupFileMock();
        $fileMock->method('readPsrStream')
            ->willReturn($streamMock);

        $deliveryClassMock = $this->createMock(core_kernel_classes_Class::class);
        $delivery->method('getTypes')
            ->willReturn([$deliveryClassMock]);
    }
}

