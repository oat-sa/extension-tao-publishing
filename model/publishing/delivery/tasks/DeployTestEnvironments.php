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

use Exception;
use common_exception_Error;
use common_exception_MissingParameter;
use common_report_Report;
use core_kernel_classes_Resource;
use oat\oatbox\action\Action;
use oat\oatbox\filesystem\File;
use oat\oatbox\filesystem\FileSystemService;
use oat\oatbox\log\LoggerService;
use oat\tao\model\taskQueue\QueueDispatcherInterface;
use oat\tao\model\taskQueue\Task\ChildTaskAwareInterface;
use oat\tao\model\taskQueue\Task\ChildTaskAwareTrait;
use oat\tao\model\taskQueue\Task\TaskAwareInterface;
use oat\tao\model\taskQueue\Task\TaskAwareTrait;
use oat\taoDeliveryRdf\controller\RestTest;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use oat\taoPublishing\model\publishing\test\TestBackupService;
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
     * @return common_report_Report
     * @throws common_exception_Error
     * @throws common_exception_MissingParameter
     */
    public function __invoke($params) {

        if (count($params) != 3) {
            throw new common_exception_MissingParameter();
        }
        list($testId, $environmentId, $deliveryId) = $params;
        $test = $this->getResource($testId);
        $env = $this->getResource($environmentId);
        $delivery = $this->getResource($deliveryId);

        $this->getLoggerService()->logInfo('Deploying ' . $delivery->getLabel().' to '.$env->getLabel());
        $report = new common_report_Report(
            common_report_Report::TYPE_SUCCESS,
            __(
                'Requested publishing of delivery "%s" to "%s"',
                $delivery->getLabel(),
                $env->getLabel()
            )
        );
        
        $subReport = $this->compileTest($env, $test, $delivery);
        $report->add($subReport);

        return $report;
    }

    /**
     * @param core_kernel_classes_Resource $env
     * @param core_kernel_classes_Resource $test
     * @param core_kernel_classes_Resource $delivery
     * @return common_report_Report
     */
    protected function compileTest(core_kernel_classes_Resource $env, core_kernel_classes_Resource $test, core_kernel_classes_Resource $delivery) {
        try {
            $requestData = $this->prepareRequestData($delivery);
            $message = sprintf(
                'Requesting remote publishing of Delivery "%s" to environment "%s"',
                $delivery->getLabel(),
                $env->getLabel()
            );
            $this->getLoggerService()->logDebug($message);

            $response = $this->callRemotePublishingApi($env, $requestData);
            if ($response->getStatusCode() == 200) {
                $responseData = json_decode($response->getBody()->getContents(), true);
                if (isset($responseData['success']) && $responseData['success']) {
                    $remoteTaskId = $responseData['data']['reference_id'];

                    /** @var QueueDispatcherInterface $queueDispatcher reference_id*/
                    $queueDispatcher = $this->getServiceLocator()->get(QueueDispatcherInterface::SERVICE_ID);
                    $queueDispatcher->createTask(
                        new RemoteTaskStatusSynchroniser(),
                        [$remoteTaskId, $env->getUri(), $delivery->getUri(), $test->getUri()],
                        __('Status of publishing delivery "%s" on remote environment "%s"', $delivery->getLabel(), $env->getLabel()),
                        $this->getTask()
                    );
                }

                $report = new common_report_Report(
                    common_report_Report::TYPE_SUCCESS,
                    __(
                        'Publishing of delivery "%s" on remote environment "%s" was successfully requested.',
                        $delivery->getLabel(),
                        $env->getLabel()
                    )
                );
                $report->setData($delivery->getUri());
            } else {
                $report = new common_report_Report(
                    common_report_Report::TYPE_ERROR,
                    __(
                        'Publishing request to remote environment failed with message: %s',
                        $response->getBody()->getContents()
                    )
                );
            }
        } catch (Exception $e) {
            $report = new common_report_Report(
                common_report_Report::TYPE_ERROR,
                __('Remote publishing failed: %s', $e->getMessage())
            );
        } finally {
            return $report;
        }

    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @return File
     * @throws \core_kernel_persistence_Exception
     */
    private function getQtiTestPackageFile(core_kernel_classes_Resource $delivery): File
    {
        $packagePath = $delivery->getOnePropertyValue($this->getProperty(TestBackupService::PROPERTY_QTI_TEST_BACKUP_PATH));
        $packagePath = (string)  $packagePath;
        
        return $this->getServiceLocator()
            ->get(FileSystemService::SERVICE_ID)
            ->getDirectory(TestBackupService::FILESYSTEM_ID)
            ->getFile($packagePath);
    }

    /**
     * @return LoggerService
     */
    private function getLoggerService(): LoggerService
    {
        return $this->getServiceLocator()->get(LoggerService::SERVICE_ID);
    }

    /**
     * @param core_kernel_classes_Resource $delivery
     * @return array
     * @throws \core_kernel_persistence_Exception
     */
    private function prepareRequestData(core_kernel_classes_Resource $delivery): array
    {
        $qtiPackageFile = $this->getQtiTestPackageFile($delivery);
        $streamData = [
            [
                'name' => RestTest::REST_FILE_NAME,
                'contents' => $qtiPackageFile->readStream(),
            ],
            [
                'name' => RestTest::REST_IMPORTER_ID,
                'contents' => 'taoQtiTest',
            ],
            [
                'name' => RestTest::REST_DELIVERY_PARAMS,
                'contents' => json_encode(
                    [
                        PublishingDeliveryService::ORIGIN_DELIVERY_ID_FIELD => $delivery->getUri()
                    ]
                )
            ]
        ];

        $deliveryClass = current($delivery->getTypes());
        if ($deliveryClass->getUri() != DeliveryAssemblyService::CLASS_URI) {
            $streamData[] = [
                'name' => RestTest::REST_DELIVERY_CLASS_LABEL,
                'contents' => $deliveryClass->getLabel()
            ];
        }
        return $streamData;
    }

    /**
     * @param core_kernel_classes_Resource $env
     * @param array $requestData
     * @return mixed
     */
    private function callRemotePublishingApi(core_kernel_classes_Resource $env, array $requestData)
    {
        $body = new MultipartStream($requestData);
        $request = new Request('POST', '/taoDeliveryRdf/RestTest/compileDeferred');
        $request = $request->withBody($body);

        return $this->getServiceLocator()->get(PlatformService::class)->callApi($env->getUri(), $request);
    }
}
