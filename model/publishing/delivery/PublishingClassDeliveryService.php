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

namespace oat\taoPublishing\model\publishing\delivery;

use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use core_kernel_persistence_Exception;
use Laminas\ServiceManager\ServiceLocatorAwareTrait;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\TaskInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoPublishing\model\publishing\delivery\tasks\RemoteDeliveryPublishingTask;
use oat\taoPublishing\model\publishing\environment\EnvironmentResourceValidatorTrait;
use oat\taoPublishing\model\publishing\exception\ExceedingMaxResourceAmountException;
use oat\taoPublishing\model\publishing\exception\PublishingInvalidArgumentException;

class PublishingClassDeliveryService extends ConfigurableService
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;
    use EnvironmentResourceValidatorTrait;

    public const OPTION_MAX_RESOURCE = 'maxResource';

    /**
     * @return TaskInterface[]
     * @throws PublishingInvalidArgumentException
     * @throws core_kernel_persistence_Exception
     */
    public function publish(core_kernel_classes_Class $class, array $environments): array
    {
        $tasks = [];
        foreach ($environments as $environment) {
            $envResource = $this->getResource($environment);
            $this->validateEnvironment($envResource);
            $newTasks = $this->publishClassDeliveries($class, $envResource);
            $tasks = array_merge($tasks, $newTasks);
        }

        return $tasks;
    }

    /**
     * @return TaskInterface[]
     * @throws ExceedingMaxResourceAmountException
     * @throws core_kernel_persistence_Exception
     */
    private function publishClassDeliveries(
        core_kernel_classes_Class $class,
        core_kernel_classes_Resource $environment
    ): array {
        $tasks = [];

        $resourceCollection = $class->getInstanceCollection();

        if ($this->getOption(self::OPTION_MAX_RESOURCE) < count($resourceCollection)) {
            throw new ExceedingMaxResourceAmountException(
                sprintf(
                    'You are not allowed publish class that contains more then %s resources',
                    (string) $this->getOption(self::OPTION_MAX_RESOURCE)
                )
            );
        }

        foreach ($resourceCollection as $instance) {
            $deliveryResource = $this->getResource($instance['subject']);
            $testResource = $deliveryResource->getOnePropertyValue(
                $this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN)
            );

            $tasks[] = $this->getQueueDispatcher()
                ->createTask(
                    new RemoteDeliveryPublishingTask(),
                    [
                        $testResource->getUri(),
                        $environment->getUri(),
                        $deliveryResource->getUri()
                    ],
                    sprintf(
                        'Publishing delivery "%s" to remote environment "%s"',
                        $deliveryResource->getLabel(),
                        $environment->getLabel()
                    )
                );
        }

        return $tasks;
    }

    private function getQueueDispatcher(): QueueDispatcherInterface
    {
        return $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
    }
}
