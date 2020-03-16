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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *               
 * 
 */
namespace oat\taoPublishing\model\publishing\delivery\tasks;

use oat\generis\model\OntologyRdfs;
use oat\oatbox\action\Action;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\ChildTaskAwareInterface;
use oat\tao\model\taskQueue\Task\ChildTaskAwareTrait;
use oat\tao\model\taskQueue\Task\TaskAwareInterface;
use oat\tao\model\taskQueue\Task\TaskAwareTrait;
use oat\taoDeliveryRdf\controller\RestTest;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoPublishing\model\adapter\DeliveryRdfClientAdapter;
use oat\taoPublishing\model\deliveryRdfClient\DeliveryRdfFacade;
use oat\taoPublishing\model\deliveryRdfClient\entity\Delivery;
use oat\taoPublishing\model\deliveryRdfClient\entity\TestPackage;
use oat\taoPublishing\model\deliveryRdfClient\resource\restTest\exception\CompileDeferredFailureException;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use oat\generis\model\OntologyAwareTrait;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\MultipartStream;

/**
 * Deploys a test to environments
 */
class DeployTestEnvironments implements Action, ServiceLocatorAwareInterface, ChildTaskAwareInterface, TaskAwareInterface
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;
    use TaskAwareTrait;
    use ChildTaskAwareTrait;

    /**
     * @param array  $params
     * @return \common_report_Report
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     */
    public function __invoke($params) {

        if (count($params) != 3) {
            throw new \common_exception_MissingParameter();
        }
        $test = $this->getResource(array_shift($params));
        $envId = array_shift($params);
        $env = $this->getResource($envId);
        $deliveryId = array_shift($params);
        $delivery = $this->getResource($deliveryId);
        \common_Logger::i('Deploying '.$test->getLabel().' to '.$env->getLabel());
        $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Deployed %s to %s', $test->getLabel(), $env->getLabel()));
        
        $subReport = $this->compileTest($env, $test, $delivery);
        $report->add($subReport);
        return $report;
    }

    /**
     * @param \core_kernel_classes_Resource $env
     * @param \core_kernel_classes_Resource $test
     * @param \core_kernel_classes_Resource $delivery
     * @return \common_report_Report
     */
    protected function compileTest(\core_kernel_classes_Resource $env, \core_kernel_classes_Resource $test, \core_kernel_classes_Resource $delivery) {
        try {
            \common_Logger::d('Exporting Test '.$test->getUri().' for deployment');

            $exporter = new \taoQtiTest_models_classes_export_TestExport();
            $exportReport = $exporter->export([
                'filename' => \League\Flysystem\Util::normalizePath($test->getLabel()),
                'instances' => $test->getUri(),
            ], \tao_helpers_File::createTempDir());
            $packagePath = $exportReport->getData();
            if(is_array($packagePath) && isset($packagePath['path'])){
                $packagePath = $packagePath['path'];
            }

            \common_Logger::d('Requesting compilation of Test '.$test);

            $deliveryClient = new Delivery($delivery->getLabel(), $delivery->getUri(), null);

            $deliveryClass = current($delivery->getTypes());
            if ($deliveryClass->getUri() != DeliveryAssemblyService::CLASS_URI) {
                $deliveryClient->setDeliveryClassLabel($deliveryClass->getLabel());
            }

            $deliveryRdfFacade = new DeliveryRdfFacade(
                new DeliveryRdfClientAdapter(PlatformService::singleton(), $env->getUri())
            );

            try {
                $compileDeferredResult = $deliveryRdfFacade->getRestTestResource()->compileDeferred(
                    new TestPackage($packagePath),
                    $deliveryClient,
                    'taoQtiTest'
                );
            } catch (CompileDeferredFailureException $e) {
                return new \common_report_Report(
                    \common_report_Report::TYPE_ERROR,
                    __(
                        'Failed to compile %s with message: %s',
                        $test,
                        $e->getMessage()
                    )
                );
            }

            $taskId = $compileDeferredResult->getReferenceId();

            /** @var QueueDispatcherInterface $queueDispatcher reference_id*/
            $queueDispatcher = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);

            $queueDispatcher->createTask(
                new RemoteTaskStatusSynchroniser(),
                [$taskId, $env->getUri()],
                __('Remote status synchronisation for %s from %s', $delivery->getLabel(), $env->getLabel()),
                $this->getTask()
            );
            $this->addChildId($taskId);

            $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Test has been compiled as %s', $delivery->getUri()));
            $report->setData($delivery->getUri());
            return $report;
        } catch (\Exception $e) {
            return new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Failed to compile %s with message: %s', $test, $e->getMessage()));
        }
    }
}
