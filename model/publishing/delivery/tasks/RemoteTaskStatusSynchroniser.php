<?php

namespace oat\taoPublishing\model\publishing\delivery\tasks;

use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\action\Action;
use oat\oatbox\event\EventManager;
use oat\tao\model\taskQueue\Task\RemoteTaskSynchroniserInterface;
use oat\tao\model\taskQueue\TaskLog\Broker\TaskLogBrokerInterface;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\event\RemotePublishingDeliveryCreatedEvent;
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
        if (count($params) != 4) {
            throw new \common_exception_MissingParameter();
        }
        $taskId = array_shift($params);
        $envId = array_shift($params);
        $testUri = array_shift($params);
        $deliveryUri = array_shift($params);

        $url = '/tao/TaskQueue/get';
        $request = new Request('GET', trim($url, '/').'?'.http_build_query(['id' => $taskId]));
        $response = PlatformService::singleton()->callApi($envId, $request);

        if ($response->getStatusCode() == 200) {
            $body = json_decode($response->getBody()->getContents(), true);
            if ($body['success'] == true && isset($body['data'])) {
                $this->status = $body['data']['status'];
                $importAndCompileTaskReport = \common_report_Report::jsonUnserialize($body['data']['report']);
                $remoteDeliveryId = 'Not found';

                /** @var \common_report_Report $successReport */
                foreach ($importAndCompileTaskReport->getSuccesses(true) as $successReport)
                {
                    \common_Logger::d($successReport->getMessage());
                    if (
                        strpos($successReport->getMessage(), 'QTI Test') !== false &&
                        strpos($successReport->getMessage(), 'successfully published') !== false
                    ) {
                        $remoteDeliveryId = $successReport->getData()['uriResource'];
                        break;
                    }
                }

                $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Status from remote successfully received. with ID:' . $remoteDeliveryId), $this->status);
                $eventManager = $this->getEventManager();
                $eventManager->trigger(new RemotePublishingDeliveryCreatedEvent($deliveryUri, $testUri, $remoteDeliveryId));
            } else {
                $report = new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Recheck is failed'));
            }

        } else {
            $report = new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Recheck is failed'));
        }
        return $report;
    }

    /**
     * @return EventManager
     */
    private function getEventManager(): EventManager
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(EventManager::SERVICE_ID);
    }
}
