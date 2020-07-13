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
use common_report_Report as Report;
use core_kernel_classes_Resource;
use GuzzleHttp\Exception\ConnectException;
use League\Flysystem\FileNotFoundException;
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
use oat\taoPublishing\model\publishing\exception\PublishingFailedException;
use oat\taoPublishing\model\publishing\test\TestBackupService;
use Psr\Http\Message\ResponseInterface;
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

        if (count($params) != 3) {
            throw new common_exception_MissingParameter();
        }
        $this->instantiateInputResources($params);

        $message = sprintf(__('Requested publishing of delivery "%s" to "%s".'), $this->delivery->getLabel(), $this->environment->getLabel());
        $this->getLoggerService()->logInfo($message);
        $report = new Report(Report::TYPE_SUCCESS, $message);
        
        $subReport = $this->compileTest();
        $report->add($subReport);

        return $report;
    }

    /**
     * @return Report
     */
    protected function compileTest(): Report
    {
        try {
            $requestData = $this->prepareRequestData();
            $response = $this->callRemotePublishingApi($requestData);
            $report = $this->processApiResponse($response);
        } catch (PublishingFailedException $e) {
            $report = new Report(
                Report::TYPE_ERROR,
                __('Remote publishing failed: %s', $e->getMessage())
            );
        } catch (Exception $e) {
            $report = new Report(
                Report::TYPE_ERROR,
                __('Remote publishing failed.')
            );
        } finally {
            $report->setData($this->delivery->getUri());

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
     * @return array
     * @throws \core_kernel_persistence_Exception
     */
    private function prepareRequestData(): array
    {
        try {
            $qtiPackageFile = $this->getQtiTestPackageFile($this->delivery);
            $requestData = [
                [
                    'name' => RestTest::REST_FILE_NAME,
                    'filename' => RestTest::REST_FILE_NAME,
                    'contents' => $qtiPackageFile->readPsrStream(),
                ],
                [
                    'name' => RestTest::REST_IMPORTER_ID,
                    'contents' => 'taoQtiTest',
                ],
                [
                    'name' => RestTest::REST_DELIVERY_PARAMS,
                    'contents' => json_encode(
                        [
                            PublishingDeliveryService::ORIGIN_DELIVERY_ID_FIELD => $this->delivery->getUri()
                        ]
                    )
                ]
            ];

            $deliveryClass = current($this->delivery->getTypes());
            if ($deliveryClass->getUri() != DeliveryAssemblyService::CLASS_URI) {
                $requestData[] = [
                    'name' => RestTest::REST_DELIVERY_CLASS_LABEL,
                    'contents' => $deliveryClass->getLabel()
                ];
            }

            return $requestData;
        } catch (FileNotFoundException $e) {
            $message = __(sprintf('QTI Test backup file not found for delivery "%s"', $this->delivery->getLabel()));
            throw new PublishingFailedException($message);
        }
    }

    /**
     * @param array $requestData
     * @return mixed
     */
    private function callRemotePublishingApi(array $requestData)
    {
        try {
            $body = new MultipartStream($requestData);
            $request = new Request('POST', '/taoDeliveryRdf/RestTest/compileDeferred');
            $request = $request->withBody($body);

            return $this->getServiceLocator()->get(PlatformService::class)->callApi($this->environment->getUri(), $request);
        } catch (ConnectException $e) {
            $message = __('Remote environment "%s" is not reachable.', $this->environment->getLabel());
            throw new PublishingFailedException($message);
        }
    }

    /**
     * @param ResponseInterface $response
     * @return Report
     */
    private function processApiResponse(ResponseInterface $response): Report
    {
        if ($response->getStatusCode() !== 200) {
            return Report::createFailure(__('Request failed with message: %s', $response->getBody()->getContents()));
        }

        $responseData = json_decode($response->getBody()->getContents(), true);
        if (!isset($responseData['success'], $responseData['data']['reference_id']) || $responseData['success'] !== true) {
            return Report::createFailure(__('API request execution failed on remote server.'));
        }

        $remoteTaskId = $responseData['data']['reference_id'];
        $this->createRemoteTaskStatusSynchroniser($remoteTaskId);
        $reportMassage = __(
            'Publishing of delivery "%s" on remote environment "%s" was successfully requested.',
            $this->delivery->getLabel(),
            $this->environment->getLabel()
        );

        return Report::createSuccess($reportMassage);
    }

    /**
     * @param $remoteTaskId
     */
    private function createRemoteTaskStatusSynchroniser($remoteTaskId): void
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

    /**
     * @param $params
     */
    private function instantiateInputResources($params): void
    {
        list($testId, $environmentId, $deliveryId) = $params;
        $this->test = $this->getResource($testId);
        $this->delivery = $this->getResource($deliveryId);
        $this->environment = $this->getResource($environmentId);
    }
}
