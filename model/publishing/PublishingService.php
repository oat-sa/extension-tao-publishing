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

namespace oat\taoPublishing\model\publishing;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\log\LoggerAwareTrait;
use oat\oatbox\task\Queue;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoPublishing\model\DeployTestEnvironments;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\SyncDeliveryEnvironments;

/**
 * Class PublishingService
 * @package oat\taoPublishing\model\publishing
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 */
class PublishingService extends ConfigurableService
{
    use LoggerAwareTrait;
    use OntologyAwareTrait;

    const SERVICE_ID = 'taoPublishing/PublishingService';
    const ORIGIN_DELIVERY_ID_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#OriginDeliveryID';
    const ORIGIN_TEST_ID_FIELD = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#OriginTestID';

    const DELIVERY_FIELDS = 'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformDeliveryFields';

    /**
     * @return array|mixed
     */
    public function getEnvironments()
    {
        $publishingClass = PlatformService::singleton()->getRootClass();
        $environments = $publishingClass->getInstances(true);
        return $environments;
    }

    public function publishDelivery(\core_kernel_classes_Resource $delivery)
    {
        $environments = $this->getEnvironments();

        $testProperty = $this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN);
        /** @var \core_kernel_classes_Resource $test */
        $test = $delivery->getOnePropertyValue($testProperty);

        $report = \common_report_Report::createSuccess();
        /** @var Queue $queue */
        $queue = $this->getServiceManager()->get(Queue::SERVICE_ID);
        foreach ($environments as $env) {
            $task = $queue->createTask(new DeployTestEnvironments(), [$test->getUri(), $env->getUri(), $delivery->getUri()]);
            $report->add($task->getReport());
        }
        return $report;
    }

    public function syncDelivery(\core_kernel_classes_Resource $delivery)
    {
        $environments = $this->getEnvironments();
        $report = \common_report_Report::createSuccess();
        /** @var Queue $queue */
        $queue = $this->getServiceManager()->get(Queue::SERVICE_ID);
        foreach ($environments as $env) {
            $task = $queue->createTask(new SyncDeliveryEnvironments(), [$delivery->getUri(), $env->getUri()]);
            $report->add($task->getReport());
        }
        return $report;
    }

}