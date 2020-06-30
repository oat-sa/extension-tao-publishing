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
 *  Copyright (c) 2017 (original work) Open Assessment Technologies SA
 */

namespace oat\taoPublishing\model\publishing\delivery;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\log\LoggerAwareTrait;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\CallbackTask;
use oat\tao\model\taskQueue\TaskLog\Entity\EntityInterface;
use oat\tao\model\taskQueue\TaskLogInterface;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoPublishing\model\publishing\delivery\tasks\DeployTestEnvironments;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoPublishing\model\publishing\delivery\tasks\SyncDeliveryEnvironments;

/**
 * Class PublishingDeliveryService
 * @package oat\taoPublishing\model\publishing
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 */
class PublishingDeliveryService extends ConfigurableService
{
    use LoggerAwareTrait;
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoPublishing/PublishingDeliveryService';
    const ORIGIN_DELIVERY_ID_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#OriginDeliveryID';
    const ORIGIN_TEST_ID_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#OriginTestID';
    const DELIVERY_REMOTE_SYNC_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#RemoteSync';
    const DELIVERY_REMOTE_SYNC_COMPILE_ENABLED = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled';

    const DELIVERY_REMOTE_SYNC_REST_OPTION = 'remote-publish';

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @return \common_report_Report
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     * @throws \core_kernel_persistence_Exception
     */
    public function publishDelivery(\core_kernel_classes_Resource $delivery)
    {
        $testProperty = $this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN);
        $environments = $this->getEnvironments();

        /** @var \core_kernel_classes_Resource $test */
        $test = $delivery->getOnePropertyValue($testProperty);
        /** @var QueueDispatcherInterface $queueDispatcher */
        $queueDispatcher = $this->getServiceManager()->get(QueueDispatcherInterface::SERVICE_ID);
        $taskLog = $this->getTaskLogFromDelivery($delivery);

        $report = \common_report_Report::createInfo('Publishing delivery '.$delivery->getUri());
        /** @var \core_kernel_classes_Resource $env */
        foreach ($environments as $env) {
            if ($this->checkActionForEnvironment(DeliveryCreatedEvent::class, $env)) {
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
                $report->add(\common_report_Report::createSuccess($message));
                $this->logNotice($message);
            }
        }
        return $report;
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @return \common_report_Report
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     */
    public function syncDelivery(\core_kernel_classes_Resource $delivery)
    {
        $environments = $this->getEnvironments();
        /** @var QueueDispatcherInterface $queueDispatcher */
        $queueDispatcher = $this->getServiceManager()->get(QueueDispatcherInterface::SERVICE_ID);
        $report = \common_report_Report::createInfo('Updating remote delivery ' . $delivery->getUri());
        foreach ($environments as $env) {
            if ($this->checkActionForEnvironment(DeliveryUpdatedEvent::class, $env)) {
                $task = $queueDispatcher->createTask(new SyncDeliveryEnvironments(), [$delivery->getUri(), $env->getUri()], __('Updating %s to remote env %s', $delivery->getLabel(), $env->getLabel()));
                $message = DeployTestEnvironments::class . "task created; Task id: " . $task->getId();
                $report->add(\common_report_Report::createSuccess($message));
                $this->logNotice($message);
            }
        }
        return $report;
    }

    /**
     * @return array
     */
    public function getSyncFields()
    {
        $deliveryFieldsOptions = $this->getOption(PublishingService::OPTIONS_FIELDS);
        $deliveryExcludedFieldsOptions = $this->hasOption(PublishingService::OPTIONS_EXCLUDED_FIELDS)
            ? $this->getOption(PublishingService::OPTIONS_EXCLUDED_FIELDS)
            : [];
        if (!$deliveryFieldsOptions) {
            $deliveryClass = new \core_kernel_classes_Class(DeliveryAssemblyService::CLASS_URI);
            $deliveryProperties = \tao_helpers_form_GenerisFormFactory::getClassProperties($deliveryClass);
            $defaultProperties = \tao_helpers_form_GenerisFormFactory::getDefaultProperties();
            $deliveryProperties = array_merge($defaultProperties, $deliveryProperties);
            /** @var \core_kernel_classes_Property $deliveryProperty */
            foreach ($deliveryProperties as $deliveryProperty)
            {
                if (!in_array($deliveryProperty->getUri(), $deliveryExcludedFieldsOptions)) {
                    $deliveryFieldsOptions[] = $deliveryProperty->getUri();
                }
            }
        }
        return $deliveryFieldsOptions;
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @return EntityInterface
     * @throws \common_exception_NotFound
     */
    private function getTaskLogFromDelivery(\core_kernel_classes_Resource $delivery)
    {
        try {
            $deliveryCompileTaskProperty = $this->getProperty(DeliveryFactory::PROPERTY_DELIVERY_COMPILE_TASK);
            /** @var \core_kernel_classes_Resource $compileTask */
            $compileTask = $delivery->getOnePropertyValue($deliveryCompileTaskProperty);
            /** @var TaskLogInterface $taskLogService */
            $taskLogService = $this->getServiceManager()->get(TaskLogInterface::SERVICE_ID);
            /** @var EntityInterface $taskLog */
            $taskLog = $taskLogService->getById($compileTask->getUri());
            return $taskLog;
        } catch (\Exception $e) {
            throw new \common_exception_NotFound();
        }
    }

    /**
     * @return array
     */
    protected function getEnvironments()
    {
        /** @var PublishingService $publishService */
        $publishService = $this->getServiceManager()->get(PublishingService::SERVICE_ID);
        $environments = $publishService->getEnvironments();
        return $environments;
    }

    /**
     * @param $action
     * @param \core_kernel_classes_Resource $env
     * @return bool
     */
    protected function checkActionForEnvironment($action, \core_kernel_classes_Resource $env)
    {
        $property = $this->getProperty(PublishingService::PUBLISH_ACTIONS);
        $actionProperties = $env->getPropertyValues($property);
        if ($actionProperties && in_array(addslashes($action), $actionProperties)) {
            return true;
        }
        return false;
    }
}