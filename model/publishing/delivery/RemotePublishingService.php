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

namespace oat\taoPublishing\model\publishing\delivery;

use Throwable;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTaskInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoPublishing\model\publishing\delivery\tasks\DeployTestEnvironments;
use oat\taoPublishing\model\publishing\exception\PublishingFailedException;

class RemotePublishingService extends ConfigurableService
{
    use OntologyAwareTrait;

    /** @var string */
    private $deliveryUri;

    /** @var string */
    private $testUri;

    /** @var QueueDispatcherInterface */
    private $queueDispatcher;

    /**
     * @param string $deliveryUri
     * @param array $environments
     * @return CallbackTaskInterface[]
     * @throws PublishingFailedException
     */
    public function publishDeliveryToEnvironments(string $deliveryUri, array $environments): array
    {
        $this->deliveryUri = $deliveryUri;
        $this->testUri = $this->getOriginTestUri($deliveryUri);
        $this->queueDispatcher = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
        $deliveryLabel = $this->getResource($deliveryUri)->getLabel();

        $tasks = [];
        foreach ($environments as $environmentUri) {
            try {
                $tasks[] = $this->publishToEnvironment($environmentUri, $deliveryLabel);
            } catch (Throwable $e) {
                $this->logError(
                    sprintf(
                        '[REMOTE_PUBLISHING] Publishing of delivery %s to remote environment %s failed. Error:  %s',
                        $deliveryUri,
                        $environmentUri,
                        $e->getMessage()
                    )
                );
            }
        }

        return $tasks;
    }

    private function getOriginTestUri(string $deliveryUri): string
    {
        $testProperty = $this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN);
        $deliveryResource = $this->getResource($deliveryUri);
        $testResource = $deliveryResource->getOnePropertyValue($testProperty);
        if (!$testResource instanceof \core_kernel_classes_Resource) {
            throw new PublishingFailedException("Origin test property not found for delivery: {$deliveryUri}");
        }

        return $testResource->getUri();
    }

    private function publishToEnvironment(string $environmentUri, string $deliveryLabel): CallbackTaskInterface
    {
        $params = [
            $this->testUri,
            $environmentUri,
            $this->deliveryUri
        ];
        $environmentLabel = $this->getResource($environmentUri)->getLabel();
        $message = __("Publishing %s to remote env %s", $deliveryLabel, $environmentLabel);

        return $this->queueDispatcher->createTask(new DeployTestEnvironments(), $params, $message);
    }
}
