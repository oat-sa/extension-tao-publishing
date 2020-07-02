<?php

namespace oat\taoPublishing\model\publishing\delivery\tasks;

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
     * @return \common_report_Report
     * @throws \common_exception_MissingParameter
     */
    public function __invoke($params)
    {
        if (count($params) != 2) {
            throw new \common_exception_MissingParameter();
        }
        $remoteTaskId = array_shift($params);
        $envId = array_shift($params);

        $url = '/tao/TaskQueue/getStatus';
        $request = new Request('GET', trim($url, '/').'?'.http_build_query(['id' => $remoteTaskId]));
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
