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

namespace oat\taoPublishing\model\publishing\delivery\tasks;

use Throwable;
use common_report_Report as Report;
use core_kernel_classes_Resource;
use common_exception_Error;
use common_exception_MissingParameter;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\action\Action;
use oat\oatbox\log\LoggerAwareTrait;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\ChildTaskAwareInterface;
use oat\tao\model\taskQueue\Task\ChildTaskAwareTrait;
use oat\tao\model\taskQueue\Task\TaskAwareInterface;
use oat\tao\model\taskQueue\Task\TaskAwareTrait;
use oat\taoPublishing\model\publishing\delivery\RemoteDeliveryPublisher;
use oat\taoPublishing\model\publishing\exception\PublishingFailedException;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class RemoteDeliveryPublishingTask implements Action, ServiceLocatorAwareInterface, ChildTaskAwareInterface, TaskAwareInterface
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;
    use TaskAwareTrait;
    use ChildTaskAwareTrait;
    use LoggerAwareTrait;

    /** @var core_kernel_classes_Resource */
    private $test;

    /** @var core_kernel_classes_Resource */
    private $delivery;

    /** @var core_kernel_classes_Resource */
    private $environment;

    /**
     * @param array  $params
     * @return Report
     * @throws common_exception_Error
     * @throws common_exception_MissingParameter
     */
    public function __invoke($params) {

        if (!is_array($params) || count($params) != 3) {
            throw new common_exception_MissingParameter();
        }
        $this->instantiateInputResources($params);

        return $this->publishToRemoteEnvironment();
    }

    /**
     * @param array $params
     */
    private function instantiateInputResources(array $params): void
    {
        list($testId, $environmentId, $deliveryId) = $params;
        $this->test = $this->getResource($testId);
        $this->delivery = $this->getResource($deliveryId);
        $this->environment = $this->getResource($environmentId);
    }

    /**
     * @return Report
     */
    protected function publishToRemoteEnvironment(): Report
    {
        $message = sprintf(__('Requesting publishing of delivery "%s" to "%s".'), $this->delivery->getLabel(), $this->environment->getLabel());
        $this->logInfo($message);
        $taskReport = new Report(Report::TYPE_INFO, $message);

        try {
            /** @var RemoteDeliveryPublisher $publisherService */
            $publisherService = $this->getServiceLocator()->get(RemoteDeliveryPublisher::class);
            $remoteTaskId = $publisherService->publish($this->delivery, $this->environment, $this->test);
            $this->createRemoteTaskStatusSynchroniser($remoteTaskId);

            $reportMassage = sprintf(
                __('Publishing of delivery "%s" on remote environment "%s" was successfully requested.'),
                $this->delivery->getLabel(),
                $this->environment->getLabel()
            );
            $executionReport = Report::createSuccess($reportMassage);
        } catch (PublishingFailedException $e) {
            $this->logError($e->getMessage(), [$e->__toString()]);
            $executionReport = Report::createFailure(__('Remote publishing failed: %s', $e->getMessage()));
        } catch (Throwable $e) {
            $this->logError($e->getMessage(), [$e->__toString()]);
            $executionReport = Report::createFailure(__('Remote publishing failed.'));
        } finally {
            $executionReport->setData($this->delivery->getUri());
            $taskReport->add($executionReport);
        }

        return $taskReport;
    }

    /**
     * @param $remoteTaskId
     */
    private function createRemoteTaskStatusSynchroniser(string $remoteTaskId): void
    {
        /** @var QueueDispatcherInterface $queueDispatcher reference_id */
        $queueDispatcher = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
        $taskTitle = sprintf(
            __('Status of publishing delivery "%s" on remote environment "%s"'),
            $this->delivery->getLabel(),
            $this->environment->getLabel()
        );

        $task = $queueDispatcher->createTask(
            new RemoteTaskStatusSynchroniser(),
            [$remoteTaskId, $this->environment->getUri(), $this->delivery->getUri(), $this->test->getUri()],
            $taskTitle,
            $this->getTask()
        );

        $this->addChildId($task->getId());
    }
}
