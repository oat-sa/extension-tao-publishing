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

namespace oat\taoPublishing\model\tasks;

use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTaskInterface;

class BackupQtiTestPackageFactory extends ConfigurableService
{
    /**
     * @param string $deliveryUri
     * @return CallbackTaskInterface
     */
    public function createTask(string $deliveryUri): CallbackTaskInterface
    {
        $task = new BackupQtiTestPackage();
        $parameters = [
            BackupQtiTestPackage::PARAM_DELIVERY_URI => $deliveryUri
        ];

        /** @var QueueDispatcherInterface $queueDispatcher */
        $queueDispatcher = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);

        return $queueDispatcher->createTask(
            $task,
            $parameters,
            __(sprintf('Backup QTI test package for delivery %s', $deliveryUri))
        );
    }
}
