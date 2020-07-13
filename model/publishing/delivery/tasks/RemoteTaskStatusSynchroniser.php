<?php

namespace oat\taoPublishing\model\publishing\delivery\tasks;

use Exception;
use common_report_Report as Report;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\action\Action;
use oat\oatbox\event\EventManager;
use oat\oatbox\log\LoggerService;
use oat\tao\model\taskQueue\Task\RemoteTaskSynchroniserInterface;
use oat\tao\model\taskQueue\TaskLog\CategorizedStatus;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\event\RemoteDeliveryCreatedEvent;
use oat\taoPublishing\model\publishing\exception\PublishingFailedException;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use GuzzleHttp\Psr7\Request;

/**
 * Class RemoteTaskStatusSynchroniser
 * @package oat\taoPublishing\model\publishing\delivery\tasks
 */
class RemoteTaskStatusSynchroniser implements Action,ServiceLocatorAwareInterface, RemoteTaskSynchroniserInterface
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;

    private $status;

    private $remoteTaskId;
    private $remoteEnvironmentId;

    private $remoteDeliveryId = null;

    public function getRemoteStatus()
    {
        return $this->status;
    }

    /**
     * @param $params
     * @return Report
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     */
    public function __invoke($params)
    {
        if (count($params) != 4) {
            throw new \common_exception_MissingParameter();
        }
        list ($remoteTaskId, $envId, $deliveryUri, $testUri) = $params;
        $this->remoteTaskId = $remoteTaskId;
        $this->remoteEnvironmentId = $envId;

        try {
            $this->status = $this->fetchRemoteTaskStatus();
            if (!$this->isTaskFinished($this->status)) {
                return Report::createInfo(__('Remote task is still running.'));
            }

            $remoteTaskData = $this->getRemoteTaskDetails();
            $report = new Report(Report::TYPE_SUCCESS, __('Remote task was completed.'), $remoteTaskData);

            $remoteReport = Report::jsonUnserialize($remoteTaskData['report']);
            if (!$remoteReport instanceof Report) {
                $report->add(Report::createFailure(__('Remote task does not have a report.')));
            }
            $report->add($this->prepareRemoteTaskExecutionReport($remoteReport));

            /** @var EventManager $eventManager */
            $remoteDeliveryId = $this->getRemoteDeliveryId($remoteReport);
            $this->triggerRemoteDeliveryCreatedEvent($remoteDeliveryId, $deliveryUri, $testUri);
        } catch (Exception $e) {
            $this->getLoggerService()->logError($e->getMessage(), [$e->__toString()]);
            $report = Report::createFailure(__('Checking remote task status failed.'));
        }


        return $report;
    }

    private function fetchRemoteTaskStatus(): string
    {
        $response = $this->callRemoteApi('/tao/TaskQueue/getStatus');

        return $response['data'];
    }

    /**
     * @return mixed
     * @throws PublishingFailedException
     */
    private function getRemoteTaskDetails()
    {
        $response = $this->callRemoteApi('/tao/TaskQueue/get');

        return $response['data'];
    }

    private function isTaskFinished(string $status): bool
    {
        return in_array(
            $status,
            [
                CategorizedStatus::STATUS_COMPLETED,
                CategorizedStatus::STATUS_CANCELLED,
                CategorizedStatus::STATUS_FAILED,
                CategorizedStatus::STATUS_ARCHIVED
            ]
        );
    }

    private function prepareRemoteTaskExecutionReport(Report $remoteTaskReport): Report
    {
        if ($remoteTaskReport->containsError() || !$remoteTaskReport->hasChildren()) {
            $report = $this->prepareErrorReport($remoteTaskReport);
        } else {
            $report = $this->prepareSuccessReport($remoteTaskReport);
        }

        return $report;
    }

    private function prepareErrorReport(Report $remoteTaskReport): Report
    {
        $message = __('Delivery compilation or remote environment failed. ');
        $remoteErrorReports = $remoteTaskReport->getErrors(true);
        if (count($remoteErrorReports) > 0) {
            $remoteErrorReport = current($remoteErrorReports);
            $message .= sprintf(__('Reason: %s.'), $remoteErrorReport->getMessage());
        }

        return new Report(Report::TYPE_ERROR, $message);
    }

    private function prepareSuccessReport(Report $remoteTaskReport): Report
    {
        $remoteDeliveryId = $this->getRemoteDeliveryId($remoteTaskReport);
        $message = $remoteDeliveryId === null
            ? __('Report does not contain remote delivery ID.')
            : sprintf(__("Remote delivery ID: %s."), $remoteDeliveryId);

        return new Report(Report::TYPE_INFO, $message);
    }

    private function getRemoteDeliveryId(Report $remoteTaskReport): ?string
    {
        if ($this->remoteDeliveryId === null) {
            $deliveryCompilationReport = current($remoteTaskReport->getChildren());
            $reportData = $deliveryCompilationReport->getData();

            $this->remoteDeliveryId = $reportData['delivery-uri'] ?? null;
        }

        return $this->remoteDeliveryId;
    }

    /**
     * @param string $url
     * @return array
     * @throws PublishingFailedException
     */
    private function callRemoteApi(string $url): array
    {
        $request = new Request('GET', trim($url, '/') . '?' . http_build_query(['id' => $this->remoteTaskId]));
        $response = $this->getServiceLocator()->get(PlatformService::class)->callApi(
            $this->remoteEnvironmentId,
            $request
        );

        if ($response->getStatusCode() !== 200) {
            throw new PublishingFailedException(__('Call to remote environment failed.'));
        }

        $responseBody = json_decode($response->getBody()->getContents(), true);
        if ($responseBody['success'] !== true || !isset($responseBody['data'])) {
            new PublishingFailedException(__('API request execution failed on remote server.'));
        }

        return $responseBody;
    }

    /**
     * @param string|null $remoteDeliveryId
     * @param string $deliveryUri
     * @param string $testUri
     */
    private function triggerRemoteDeliveryCreatedEvent(?string $remoteDeliveryId, string $deliveryUri, string $testUri): void
    {
        if ($remoteDeliveryId !== null) {
            $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
            $eventManager->trigger(new RemoteDeliveryCreatedEvent($deliveryUri, $testUri, $remoteDeliveryId));
        }
    }

    /**
     * @return LoggerService
     */
    private function getLoggerService(): LoggerService
    {
        return $this->getServiceLocator()->get(LoggerService::SERVICE_ID);
    }
}
