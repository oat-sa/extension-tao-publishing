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

use common_exception_Error;
use core_kernel_classes_Resource;
use core_kernel_classes_Property;
use oat\generis\model\data\Ontology;
use oat\generis\test\MockObject;
use oat\oatbox\log\LoggerService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTaskInterface;
use oat\taoPublishing\model\publishing\delivery\RemotePublishingService;
use oat\generis\test\TestCase;
use oat\taoPublishing\model\publishing\exception\PublishingFailedException;
use oat\taoPublishing\model\publishing\exception\PublishingInvalidArgumentException;

class RemotePublishingServiceTest extends TestCase
{
    /** RemotePublishingService */
    private $subject;

    /** @var Ontology|MockObject */
    private $ontologyMock;

    /** @var LoggerService */
    private $loggerMock;

    /** @var QueueDispatcherInterface|MockObject */
    private $queueDispatcherMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ontologyMock =  $this->getOntologyMock();
        $this->loggerMock = $this->createMock(LoggerService::class);
        $this->queueDispatcherMock = $this->createMock(QueueDispatcherInterface::class);
        $serviceLocatorMock = $this->getServiceLocatorMock(
            [
                QueueDispatcherInterface::SERVICE_ID => $this->queueDispatcherMock,
                LoggerService::SERVICE_ID => $this->loggerMock
            ]
        );

        $this->subject = new RemotePublishingService();
        $this->subject->setModel($this->ontologyMock);
        $this->subject->setServiceLocator($serviceLocatorMock);
    }

    public function testPublishDeliveryToEnvironments_ReturnsListOfCreatedTasks(): void
    {
        $deliveryUri = 'FAKE_DELIVERY_URI';
        $testUri = 'FAKE_TEST_URI';
        $environments = ['FAKE_ENVIRONMENT_URI_1', 'FAKE_ENVIRONMENT_URI_2'];
        $expectedTasksAmount = 2;

        $testResourceMock = $this->getTestResourceMock($testUri);
        $deliveryExists = true;
        $deliveryResourceMock = $this->getDeliveryResourceMock($testResourceMock, $deliveryExists);

        $environmentExists = true;
        $publishingEnabled = true;
        $environmentMock1 = $this->getRemoteEnvironmentMock($environmentExists, $publishingEnabled);
        $environmentMock2 = $this->getRemoteEnvironmentMock($environmentExists, $publishingEnabled);

        $this->ontologyMock
            ->method('getResource')
            ->willReturnOnConsecutiveCalls($deliveryResourceMock, $environmentMock1, $environmentMock2);

        $task1 = $this->createMock(CallbackTaskInterface::class);
        $task2 = $this->createMock(CallbackTaskInterface::class);
        $this->queueDispatcherMock
            ->method('createTask')
            ->willReturnOnConsecutiveCalls($task1, $task2);

        $tasks = $this->subject->publishDeliveryToEnvironments($deliveryUri, $environments);

        self::assertCount($expectedTasksAmount, $tasks, 'Method must return expected number of created tasks.');
        self::assertInstanceOf(CallbackTaskInterface::class, $tasks[0]);
        self::assertInstanceOf(CallbackTaskInterface::class, $tasks[1]);
    }

    public function testPublishDeliveryToEnvironments_WhenDeliveryDoesNotExist_ThrowsException(): void
    {
        $deliveryUri = 'FAKE_DELIVERY_URI';
        $environments = ['FAKE_ENVIRONMENT_URI'];

        $originTestProperty = null;
        $deliveryExists = false;
        $deliveryResourceMock = $this->getDeliveryResourceMock($originTestProperty, $deliveryExists);
        $this->ontologyMock
            ->method('getResource')
            ->with($deliveryUri)
            ->willReturn($deliveryResourceMock);

        self::expectException(PublishingInvalidArgumentException::class);

        $this->subject->publishDeliveryToEnvironments($deliveryUri, $environments);
    }

    public function testPublishDeliveryToEnvironments_WhenDeliveryDoesNotHaveOriginTest_ThrowsException(): void
    {
        $deliveryUri = 'FAKE_DELIVERY_URI';
        $environments = ['FAKE_ENVIRONMENT_URI'];

        $originTestProperty = null;
        $deliveryExists = true;
        $deliveryResourceMock = $this->getDeliveryResourceMock($originTestProperty, $deliveryExists);
        $this->ontologyMock
            ->method('getResource')
            ->with($deliveryUri)
            ->willReturn($deliveryResourceMock);

        self::expectException(PublishingFailedException::class);

        $this->subject->publishDeliveryToEnvironments($deliveryUri, $environments);
    }

    public function testPublishDeliveryToEnvironments_WhenPublicationToEnvFailed_LogsError(): void
    {
        $deliveryUri = 'FAKE_DELIVERY_URI';
        $testUri = 'FAKE_TEST_URI';
        $environments = ['FAKE_ENVIRONMENT_URI'];

        $testResourceMock = $this->getTestResourceMock($testUri);
        $deliveryExists = true;
        $deliveryResourceMock = $this->getDeliveryResourceMock($testResourceMock, $deliveryExists);
        $environmentMock = $this->getRemoteEnvironmentMock(true, true);

        $this->ontologyMock
            ->method('getResource')
            ->withConsecutive([$deliveryUri], ['FAKE_ENVIRONMENT_URI'])
            ->willReturnOnConsecutiveCalls($deliveryResourceMock, $environmentMock);

        $this->queueDispatcherMock
            ->method('createTask')
            ->willThrowException(new common_exception_Error('DUMMY DISPATCHER EXCEPTION MESSAGE'));

        $this->loggerMock->expects(self::once())
            ->method('error');

        $tasks = $this->subject->publishDeliveryToEnvironments($deliveryUri, $environments);
        self::assertCount(0, $tasks, 'Method must return correct number of created tasks.');
    }

    public function testPublishDeliveryToEnvironments_EnvironmentDoesNotExist_ThrowsException(): void
    {
        $deliveryUri = 'FAKE_DELIVERY_URI';
        $testUri = 'FAKE_TEST_URI';
        $environments = ['FAKE_ENVIRONMENT_URI_1', 'FAKE_ENVIRONMENT_URI_2'];

        $testResourceMock = $this->getTestResourceMock($testUri);
        $deliveryExists = true;
        $deliveryResourceMock = $this->getDeliveryResourceMock($testResourceMock, $deliveryExists);

        $environmentExists = true;
        $publishingEnabled = true;
        $environmentMock1 = $this->getRemoteEnvironmentMock($environmentExists, $publishingEnabled);
        $environmentMock2 = $this->getRemoteEnvironmentMock(false, $publishingEnabled);

        $this->ontologyMock
            ->method('getResource')
            ->willReturnOnConsecutiveCalls($deliveryResourceMock, $environmentMock1, $environmentMock2);

        self::expectException(PublishingInvalidArgumentException::class);

        $this->subject->publishDeliveryToEnvironments($deliveryUri, $environments);
    }

    public function testPublishDeliveryToEnvironments_PublishingToEnvironmentDisabled_ThrowsException(): void
    {
        $deliveryUri = 'FAKE_DELIVERY_URI';
        $testUri = 'FAKE_TEST_URI';
        $environments = ['FAKE_ENVIRONMENT_URI_1', 'FAKE_ENVIRONMENT_URI_2'];

        $testResourceMock = $this->getTestResourceMock($testUri);
        $deliveryExists = true;
        $deliveryResourceMock = $this->getDeliveryResourceMock($testResourceMock, $deliveryExists);

        $environmentExists = true;
        $publishingEnabled = true;
        $environmentMock1 = $this->getRemoteEnvironmentMock($environmentExists, $publishingEnabled);
        $environmentMock2 = $this->getRemoteEnvironmentMock($environmentExists, false);

        $this->ontologyMock
            ->method('getResource')
            ->willReturnOnConsecutiveCalls($deliveryResourceMock, $environmentMock1, $environmentMock2);

        self::expectException(PublishingInvalidArgumentException::class);

        $this->subject->publishDeliveryToEnvironments($deliveryUri, $environments);
    }

    /**
     * @return Ontology|MockObject
     */
    private function getOntologyMock(): Ontology
    {
        $propertyOriginMock = $this->createMock(core_kernel_classes_Property::class);
        $ontologyMock = $this->createMock(Ontology::class);
        $ontologyMock
            ->method('getProperty')
            ->willReturn($propertyOriginMock);

        return $ontologyMock;
    }

    /**
     * @param string $testUri
     * @return core_kernel_classes_Resource|MockObject
     */
    private function getTestResourceMock(string $testUri): core_kernel_classes_Resource
    {
        $testResourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $testResourceMock->method('getUri')
            ->willReturn($testUri);

        return $testResourceMock;
    }

    /**
     * @param mixed $expectedOriginTestValue
     * @param bool $resourceExists
     * @return core_kernel_classes_Resource|MockObject
     */
    private function getDeliveryResourceMock($expectedOriginTestValue, bool $resourceExists): core_kernel_classes_Resource
    {
        $deliveryResourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $deliveryResourceMock->method('getOnePropertyValue')
            ->willReturn($expectedOriginTestValue);
        $deliveryResourceMock->method('exists')
            ->willReturn($resourceExists);

        return $deliveryResourceMock;
    }

    /**
     * @param bool $resourceExists
     * @param bool $remotePublishingEnabled
     * @return core_kernel_classes_Resource|MockObject
     */
    private function getRemoteEnvironmentMock(bool $resourceExists, bool $remotePublishingEnabled): core_kernel_classes_Resource
    {
        $environmentMock = $this->createMock(core_kernel_classes_Resource::class);
        $environmentMock->method('exists')
            ->willReturn($resourceExists);
        $environmentMock->method('getOnePropertyValue')
            ->willReturn($remotePublishingEnabled);

        return $environmentMock;
    }
}

