<?php

namespace oat\taoPublishing\model\publishing\delivery\tasks;

use common_report_Report;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\action\Action;
use oat\oatbox\event\EventManager;
use oat\tao\model\taskQueue\Task\RemoteTaskSynchroniserInterface;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\event\RemoteDeliveryCreatedEvent;
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

    private $remoteDeliveryId = null;

    public function getRemoteStatus()
    {
        return $this->status;
    }

    /**
     * @param $params
     * @return common_report_Report
     * @throws \common_exception_Error
     * @throws \common_exception_MissingParameter
     */
    public function __invoke($params)
    {
        if (count($params) != 4) {
            throw new \common_exception_MissingParameter();
        }
        list ($remoteTaskId, $envId, $deliveryUri, $testUri) = $params;

        $url = '/tao/TaskQueue/get';
        $request = new Request('GET', trim($url, '/').'?'.http_build_query(['id' => $remoteTaskId]));
        $response = $this->getServiceLocator()->get(PlatformService::class)->callApi($envId, $request);

        $success = 200;
        if ($response->getStatusCode() == $success) {
            $body = json_decode($response->getBody()->getContents(), true);
            if ($body['success'] == true && isset($body['data'])) {
                $remoteTaskData = $body['data'];
                $this->status = $remoteTaskData['status'];
                $remoteReport = common_report_Report::jsonUnserialize($remoteTaskData['report']);
                $report = new common_report_Report(common_report_Report::TYPE_SUCCESS, __('Remote task was completed.'), $remoteTaskData);
                $report->add($this->prepareRemoteTaskExecutionReport($remoteReport));

                /** @var EventManager $eventManager */
                $remoteDeliveryId = $this->getRemoteDeliveryId($remoteReport);
                $this->triggerRemoteDeliveryCreatedEvent($remoteDeliveryId, $deliveryUri, $testUri);
            } else {
                $report = new common_report_Report(common_report_Report::TYPE_ERROR, __('Remote task execution failed.'));
            }

        } else {
            $report = new common_report_Report(common_report_Report::TYPE_ERROR, __('Request to check remote task status failed.'));
        }
        return $report;
    }

    private function prepareRemoteTaskExecutionReport(common_report_Report $remoteTaskReport): common_report_Report
    {
        if ($remoteTaskReport->containsError() || !$remoteTaskReport->hasChildren()) {
            $message = __("Delivery publishing on remote environment failed");
            $report = new common_report_Report(common_report_Report::TYPE_ERROR, $message);
        } else {
            $remoteDeliveryId = $this->getRemoteDeliveryId($remoteTaskReport);
            $message = $remoteDeliveryId === null
                ? __('Report does not contain remote delivery ID.')
                : __(sprintf("Remote delivery ID: %", $remoteDeliveryId));

            $report = new common_report_Report(common_report_Report::TYPE_INFO, $message);
        }

        return $report;
    }

    private function getRemoteDeliveryId(common_report_Report $remoteTaskReport): ?string
    {
        if ($this->remoteDeliveryId === null) {
            $deliveryCompilationReport = current($remoteTaskReport->getChildren());
            $reportData = $deliveryCompilationReport->getData();

            $this->remoteDeliveryId = $reportData['delivery-uri'] ?? null;
        }

        return $this->remoteDeliveryId;
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
}
