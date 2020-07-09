<?php

namespace oat\taoPublishing\model\publishing\delivery\tasks;

use common_report_Report;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\action\Action;
use oat\tao\model\taskQueue\Task\RemoteTaskSynchroniserInterface;
use oat\taoPublishing\model\PlatformService;
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
        if (count($params) != 2) {
            throw new \common_exception_MissingParameter();
        }
        $remoteTaskId = array_shift($params);
        $envId = array_shift($params);

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
            $deliveryCompilationReport = current($remoteTaskReport->getChildren());
            $reportData = $deliveryCompilationReport->getData();
            $message = isset($reportData['delivery-uri'])
                ? __(sprintf("Remote delivery ID: %", $reportData['delivery-uri']))
                : __('Report does not contain remote delivery ID.');

            $report = new common_report_Report(common_report_Report::TYPE_INFO, $message);
        }

        return $report;
    }
}
