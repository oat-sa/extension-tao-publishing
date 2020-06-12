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

namespace oat\taoPublishing\test\unit\model\tasks;

use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTaskInterface;
use oat\taoPublishing\model\tasks\BackupQtiTestPackageFactory;
use oat\generis\test\TestCase;

class BackupQtiTestPackageFactoryTest extends TestCase
{
    /** @var BackupQtiTestPackageFactory */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new BackupQtiTestPackageFactory();
    }

    public function testCreateTask()
    {
        $expectedTask = $this->createMock(CallbackTaskInterface::class);
        $deliveryUri = 'DUMMY_DELIVERY_URI';

        $queueDispatcherMock = $this->createMock(QueueDispatcherInterface::class);
        $queueDispatcherMock->expects(self::once())
            ->method('createTask')
            ->willReturn($expectedTask);
        $serviceLocatorMock = $this->getServiceLocatorMock(
            [
                QueueDispatcherInterface::SERVICE_ID => $queueDispatcherMock
            ]
        );
        $this->subject->setServiceLocator($serviceLocatorMock);

        $result = $this->subject->createTask($deliveryUri);
        self::assertSame($expectedTask, $result, 'Factory must return correct task.');
    }
}

