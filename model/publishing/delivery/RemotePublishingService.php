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
 * Copyright (c) 2020-2021 (original work) Open Assessment Technologies SA ;
 */
declare(strict_types=1);

namespace oat\taoPublishing\model\publishing\delivery;

use oat\taoPublishing\model\publishing\delivery\tasks\RemoteDeliveryPublishingTask;
use oat\taoPublishing\model\publishing\environment\EnvironmentResourceValidatorTrait;
use oat\taoPublishing\model\publishing\exception\PublishingInvalidArgumentException;
use Throwable;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTaskInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoPublishing\model\publishing\exception\PublishingFailedException;

class RemotePublishingService extends ConfigurableService
{
    use OntologyAwareTrait;
    use EnvironmentResourceValidatorTrait;

    /** @var string */
    private $deliveryUri;

    /** @var core_kernel_classes_Resource */
    private $deliveryResource;

    /** @var string */
    private $testUri;

    /** @var QueueDispatcherInterface */
    private $queueDispatcher;

    /**
     * @param string $deliveryUri
     * @param array $environments
     * @return CallbackTaskInterface[]
     * @throws PublishingFailedException
     * @throws PublishingInvalidArgumentException
     */
    public function publishDeliveryToEnvironments(string $deliveryUri, array $environments): array
    {
        $tasks = [];
        $this->deliveryUri = $deliveryUri;
        $this->deliveryResource = $this->getDeliveryResource($deliveryUri);
        $this->testUri = $this->getOriginTestUri();
        $this->queueDispatcher = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
        $environments = $this->collectAndValidateEnvironmentResources($environments);
        foreach ($environments as $environment) {
            try {
                $tasks[] = $this->publishToEnvironment($environment);
            } catch (Throwable $e) {
                $this->logError(
                    sprintf(
                        '[REMOTE_PUBLISHING] Publishing of delivery %s to remote environment %s failed. Error:  %s',
                        $deliveryUri,
                        $environment->getUri(),
                        $e->getMessage()
                    )
                );
            }
        }

        return $tasks;
    }

    private function getDeliveryResource(string $deliveryUri): core_kernel_classes_Resource
    {
        $deliveryResource = $this->getResource($deliveryUri);
        if (!$deliveryResource->exists()) {
            throw new PublishingInvalidArgumentException(
                sprintf("Delivery resource with URI '%s' does not exist.", $deliveryUri)
            );
        }

        return $deliveryResource;
    }

    private function getOriginTestUri(): string
    {
        $testProperty = $this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN);
        $testResource = $this->deliveryResource->getOnePropertyValue($testProperty);
        if (!$testResource instanceof core_kernel_classes_Resource) {
            throw new PublishingFailedException(
                "Origin test property not found for delivery: " . $this->deliveryResource->getUri()
            );
        }

        return $testResource->getUri();
    }

    /**
     * @throws PublishingInvalidArgumentException
     */
    private function collectAndValidateEnvironmentResources(array $environments): array
    {
        $results = [];
        foreach ($environments as $environmentUri) {
            $environmentResource = $this->getResource($environmentUri);
            $this->validateEnvironment($environmentResource);
            $results[] = $environmentResource;
        }

        return $results;
    }

    private function publishToEnvironment(core_kernel_classes_Resource $environment): CallbackTaskInterface
    {
        $params = [
            $this->testUri,
            $environment->getUri(),
            $this->deliveryUri
        ];
        $message = __(
            'Publishing delivery "%s" to remote environment "%s"',
            $this->deliveryResource->getLabel(),
            $environment->getLabel()
        );

        return $this->queueDispatcher->createTask(new RemoteDeliveryPublishingTask(), $params, $message);
    }
}
