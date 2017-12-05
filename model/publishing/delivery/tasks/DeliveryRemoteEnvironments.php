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

use oat\oatbox\action\Action;
use oat\taoDeliveryRdf\controller\RestTest;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use oat\taoTaskQueue\model\QueueDispatcher;
use oat\taoTaskQueue\model\TaskLogActionTrait;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use oat\generis\model\OntologyAwareTrait;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\MultipartStream;

/**
 * Deploys a test to environments
 */
class DeliveryRemoteEnvironments implements Action,ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;

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

        $url = \tao_helpers_Uri::getPath(\tao_helpers_Uri::url('getStatus', 'RestDelivery', 'taoDeliveryRdf'));
        $request = new Request('GET', trim($url, '/').'?'.http_build_query(['id' => $taskId]));
        $response = PlatformService::singleton()->callApi($envId, $request);

        if ($response->getStatusCode() == 200) {
            $body = json_decode($response->getBody()->getContents(), true);
            if ($body['success'] == true) {
                $data = isset($body['data']) ? $body['data'] : [];
                $status = $data['status_code'];
                $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Remote env return status '), $status);
            } else {
                $report = new \common_report_Report(\common_report_Report::TYPE_INFO, __('Recheck is failed'));
            }

        } else {
            $report = new \common_report_Report(\common_report_Report::TYPE_INFO, __('Recheck is failed'));
        }
        return $report;

    }

}
