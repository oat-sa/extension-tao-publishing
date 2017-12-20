<?php

namespace oat\taoPublishing\model\publishing\delivery\tasks;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\action\Action;
use oat\taoPublishing\model\PlatformService;
use oat\taoTaskQueue\model\Task\RemoteTaskSynchroniserInterface;
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
     * @return \common_report_Report
     * @throws \common_exception_MissingParameter
     */
    public function __invoke($params)
    {
        if (count($params) != 2) {
            throw new \common_exception_MissingParameter();
        }
        $taskId = array_shift($params);
        $envId = array_shift($params);

        $url = '/taoTaskQueue/TaskQueueRestApi/getStatus';
        $request = new Request('GET', trim($url, '/').'?'.http_build_query(['id' => $taskId]));
        $response = PlatformService::singleton()->callApi($envId, $request);

        if ($response->getStatusCode() == 200) {
            $body = json_decode($response->getBody()->getContents(), true);
            if ($body['success'] == true && isset($body['data'])) {
                $this->status = $body['data'];
                $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Status from remote successfully received.'), $this->status);
            } else {
                $report = new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Recheck is failed'));
            }

        } else {
            $report = new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Recheck is failed'));
        }
        return $report;
    }
}
