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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoPublishing\test\unit\model\publishing\delivery;

use core_kernel_classes_Class;
use core_kernel_classes_Container;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTask;
use oat\tao\model\taskQueue\Task\CallbackTaskInterface;
use oat\taoPublishing\model\publishing\delivery\PublishingClassDeliveryService;
use oat\taoPublishing\model\publishing\exception\ExceedingMaxResourceAmountException;
use oat\taoPublishing\model\publishing\exception\PublishingInvalidArgumentException;

class PublishingClassDeliveryServiceTest extends TestCase
{
    /** @var PublishingClassDeliveryService */
    private $subject;

    /** @var QueueDispatcherInterface|MockObject */
    private $queueDispatcherMock;

    /** @var core_kernel_classes_Class|MockObject */
    private $classMock;

    /** @var core_kernel_classes_Resource|MockObject */
    private $resourceMock;

    /** @var Ontology|MockObject */
    private $ontologyMock;

    /** @var core_kernel_classes_Property|MockObject */
    private $propertyMock;

    /** @var CallbackTask|MockObject */
    private $taskMock;

    /** @var core_kernel_classes_Container|MockObject */
    private $containerMock;

    public function setUp(): void
    {
        $this->queueDispatcherMock = $this->createMock(QueueDispatcherInterface::class);
        $this->classMock = $this->createMock(core_kernel_classes_Class::class);
        $this->resourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $this->ontologyMock = $this->createMock(Ontology::class);
        $this->propertyMock = $this->createMock(core_kernel_classes_Property::class);
        $this->containerMock = $this->createMock(core_kernel_classes_Container::class);
        $this->taskMock = $this->createMock(CallbackTaskInterface::class);

        $this->subject = new PublishingClassDeliveryService(
            [
                PublishingClassDeliveryService::OPTION_MAX_RESOURCE => 2
            ]
        );
        $this->subject->setServiceLocator(
            $this->getServiceLocatorMock(
                [
                    QueueDispatcherInterface::SERVICE_ID => $this->queueDispatcherMock
                ]
            )
        );
        $this->subject->setModel($this->ontologyMock);
        $this->ontologyMock
            ->method('getResource')
            ->willReturn($this->resourceMock);
    }

    public function testPublish(): void
    {
        $this->resourceMock
            ->method('exists')
            ->willReturn(true);

        $this->resourceMock
            ->method('getOnePropertyValue')
            ->willReturnOnConsecutiveCalls(
                true,
                $this->resourceMock
            );

        $this->resourceMock
            ->method('getUri')
            ->willReturn('someResourceUri');

        $this->ontologyMock
            ->method('getProperty')
            ->willReturn($this->propertyMock);

        $this->classMock
            ->method('getInstanceCollection')
            ->willReturn(
                [
                    [
                        'subject' => 'someResourceUri'
                    ]
                ]
            );

        $this->queueDispatcherMock
            ->method('createTask')
            ->willReturn($this->taskMock);

        $results = $this->subject->publish($this->classMock, ['envUri']);
        $this->assertInstanceOf(CallbackTaskInterface::class, reset($results));
    }

    public function testWrongEnvironments()
    {
        $this->expectException(PublishingInvalidArgumentException::class);

        $this->resourceMock
            ->method('exists')
            ->willReturn(false);


        $this->resourceMock
            ->method('getOnePropertyValue')
            ->willReturn(false);


        $this->subject->publish($this->classMock, ['envUri']);
    }

    public function testTooManyResources()
    {
        $this->expectException(ExceedingMaxResourceAmountException::class);

        $this->resourceMock
            ->method('exists')
            ->willReturn(true);

        $this->resourceMock
            ->method('getOnePropertyValue')
            ->willReturnOnConsecutiveCalls(
                true,
                $this->resourceMock
            );

        $this->resourceMock
            ->method('getUri')
            ->willReturn('someResourceUri');

        $this->ontologyMock
            ->method('getProperty')
            ->willReturn($this->propertyMock);

        $this->classMock
            ->method('getInstanceCollection')
            ->willReturn(
                [
                    [
                        'subject' => 'someResourceUri'
                    ],
                    [
                        'subject' => 'someResourceUri2'
                    ],
                    [
                        'subject' => 'someResourceUri3'
                    ],
                    [
                        'subject' => 'someResourceUri4'
                    ],
                ]
            );

        $this->subject->publish($this->classMock, ['envUri']);
    }
}
