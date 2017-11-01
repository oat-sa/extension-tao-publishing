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
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoPublishing\model\publishing\delivery\tasks\DeployTestEnvironments;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoPublishing\model\publishing\delivery\tasks\SyncDeliveryEnvironments;
use oat\taoTaskQueue\model\QueueDispatcher;
use oat\taoTaskQueue\model\TaskLogInterface;

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

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @return \common_report_Report|null
     */
    public function publishDelivery(\core_kernel_classes_Resource $delivery)
    {
        $environments = $this->getEnvironments();
        $testProperty = $this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN);
        /** @var \core_kernel_classes_Resource $test */
        $test = $delivery->getOnePropertyValue($testProperty);
        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = $this->getServiceManager()->get(QueueDispatcher::SERVICE_ID);
        $report = \common_report_Report::createInfo('Publishing delivery '.$delivery->getUri());
        foreach ($environments as $env) {
            if ($this->checkActionForEnvironment(DeliveryCreatedEvent::class, $env)) {
                $task = $queueDispatcher->createTask(new DeployTestEnvironments(), [$test->getUri(), $env->getUri(), $delivery->getUri()]);
                $message = DeployTestEnvironments::class . "task created; Task id: " . $task->getId();
                $report->add(\common_report_Report::createSuccess($message));
                $this->logNotice($message);
            }
        }
        return $report;
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @return \common_report_Report|null
     */
    public function syncDelivery(\core_kernel_classes_Resource $delivery)
    {
        $environments = $this->getEnvironments();

        /** @var QueueDispatcher $queueDispatcher */
        $queueDispatcher = $this->getServiceManager()->get(QueueDispatcher::SERVICE_ID);

        $report = \common_report_Report::createInfo('Updating remote delivery ' . $delivery->getUri());
        foreach ($environments as $env) {
            if ($this->checkActionForEnvironment(DeliveryUpdatedEvent::class, $env)) {
                $task = $queueDispatcher->createTask(new SyncDeliveryEnvironments(), [$delivery->getUri(), $env->getUri()]);
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
            $deliveryClass = new \core_kernel_classes_Class(CLASS_COMPILEDDELIVERY);
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