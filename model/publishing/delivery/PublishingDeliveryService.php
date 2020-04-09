<?php

/**
 * This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies SA
 */
declare(strict_types=1);

namespace oat\taoPublishing\model\publishing\delivery;

use common_exception_Error;
use common_exception_NotFound;
use common_report_Report;
use core_kernel_classes_Resource;
use core_kernel_persistence_Exception;
use Exception;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTask;
use oat\tao\model\taskQueue\TaskLog\Entity\EntityInterface;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoPublishing\model\publishing\delivery\tasks\DeployTestEnvironments;
use oat\taoPublishing\model\publishing\PublishingService;

class PublishingDeliveryService extends ConfigurableService
{
    use LoggerAwareTrait;
    use OntologyAwareTrait;

    public const SERVICE_ID = 'taoPublishing/PublishingDeliveryService';
    public const ORIGIN_TEST_ID_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#OriginTestID';
    public const DELIVERY_REMOTE_SYNC_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#RemoteSync';
    public const DELIVERY_REMOTE_SYNC_COMPILE_ENABLED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled';

    public const DELIVERY_REMOTE_SYNC_REST_OPTION = 'remote-publish';

    /**
     * @param core_kernel_classes_Resource $delivery
     *
     * @return common_report_Report
     *
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     * @throws core_kernel_persistence_Exception
     */
    public function publishDelivery(core_kernel_classes_Resource $delivery): common_report_Report
    {
        $testProperty = $this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN);
        $environments = $this->getEnvironments();

        /** @var core_kernel_classes_Resource $test */
        $test = $delivery->getOnePropertyValue($testProperty);
        /** @var QueueDispatcherInterface $queueDispatcher */
        $queueDispatcher = $this->getServiceManager()->get(QueueDispatcherInterface::SERVICE_ID);
        $taskLog = $this->getTaskLogFromDelivery($delivery);

        $report = common_report_Report::createInfo('Publishing delivery ' . $delivery->getUri());

        if (empty($environments)) {
            $report->add(common_report_Report::createFailure("There is no publication targets to deploy"));

            return $report;
        }

        /** @var core_kernel_classes_Resource $env */
        foreach ($environments as $env) {
            $callBackTask = new CallbackTask($taskLog->getId(), $taskLog->getOwner());
            $task = $queueDispatcher->createTask(
                new DeployTestEnvironments(),
                [$test->getUri(), $env->getUri(), $delivery->getUri()],
                __(
                    'Publishing %s to remote env %s',
                    $delivery->getLabel(),
                    $env->getLabel()
                ),
                $callBackTask
            );
            $message = DeployTestEnvironments::class . "task created; Task id: " . $task->getId();
            $report->add(common_report_Report::createSuccess($message));
            $this->logNotice($message);
        }
        return $report;
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     *
     * @return EntityInterface
     *
     * @throws common_exception_NotFound
     */
    private function getTaskLogFromDelivery(core_kernel_classes_Resource $delivery): EntityInterface
    {
        try {
            $deliveryCompileTaskProperty = $this->getProperty(DeliveryFactory::PROPERTY_DELIVERY_COMPILE_TASK);
            /** @var core_kernel_classes_Resource $compileTask */
            $compileTask = $delivery->getOnePropertyValue($deliveryCompileTaskProperty);
            /** @var TaskLogInterface $taskLogService */
            $taskLogService = $this->getServiceManager()->get(TaskLogInterface::SERVICE_ID);
            /** @var EntityInterface $taskLog */
            $taskLog = $taskLogService->getById($compileTask->getUri());
            return $taskLog;
        } catch (Exception $e) {
            throw new common_exception_NotFound();
        }
    }

    protected function getEnvironments(): array
    {
        /** @var PublishingService $publishService */
        $publishService = $this->getServiceManager()->get(PublishingService::SERVICE_ID);
        $environments = $publishService->getEnvironments();
        return $environments;
    }
}