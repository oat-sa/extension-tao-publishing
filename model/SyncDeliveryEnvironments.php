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
namespace oat\taoPublishing\model;

use oat\oatbox\action\Action;
use oat\taoPublishing\model\publishing\PublishingService;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use oat\generis\model\OntologyAwareTrait;
use GuzzleHttp\Psr7\Request;

/**
 * Sync Delivery with last changes
 */
class SyncDeliveryEnvironments implements Action,ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;
    
    /**
     * 
     * @param array $params
     */
    public function __invoke($params) {

        if (count($params) < 2) {
            throw new \common_exception_MissingParameter();
        }
        $delivery = $this->getResource(array_shift($params));
        $envId = array_shift($params);
        $env = $this->getResource($envId);
        
        \common_Logger::i('Deploying '.$delivery->getLabel().' to '.$env->getLabel());
        $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Deployed %s to %s', $delivery->getLabel(), $env->getLabel()));
        $subReport = $this->updateDelivery($env, $delivery);
        $report->add($subReport);
        
        return $report;
    }

    /**
     * @param \core_kernel_classes_Resource $env
     * @param \core_kernel_classes_Resource $delivery
     * @return \common_report_Report
     */
    protected function updateDelivery(\core_kernel_classes_Resource $env, \core_kernel_classes_Resource $delivery)
    {
        \common_Logger::d('Sync Delivery '.$delivery->getUri().' for deployment');
        $envId = $env->getUri();
        $remoteDeliveryUri = $this->findRemoteDelivery($envId, $delivery);
        $request = new Request('POST', '/taoDeliveryRdf/RestDelivery/update');
        $request = $request->withBody(
            \GuzzleHttp\Psr7\stream_for(http_build_query([
                    'delivery' => $remoteDeliveryUri,
                    'delivery-params' => json_encode($this->getPropertiesForUpdating($env, $delivery))
                ]
            )));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');

        \common_Logger::d('Requesting updating of Delivery '.$delivery->getUri());
        $response = PlatformService::singleton()->callApi($envId, $request);
        if ($response->getStatusCode() == 200) {
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);
            $deliveryId = $data['data']['delivery'];
            $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Delivery has been updated as %s', $delivery->getUri()));
            $report->setData($deliveryId);
            return $report;
        } else {
            return new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Failed to updated %s', $delivery->getUri()));
        }
    }

    /**
     * @param $envId
     * @param \core_kernel_classes_Resource $delivery
     * @return \common_report_Report
     */
    protected function findRemoteDelivery($envId, \core_kernel_classes_Resource $delivery)
    {

        $OriginDeliveryField = \tao_helpers_Uri::encode(PublishingService::ORIGIN_DELIVERY_ID_FIELD);
        $deliveryUri = \tao_helpers_Uri::encode($delivery->getUri());
        $request = new Request('GET', '/taoDeliveryRdf/RestDelivery/search?'.$OriginDeliveryField.'='.$deliveryUri);
        \common_Logger::d('Requesting finding of Delivery.');
        $response = PlatformService::singleton()->callApi($envId, $request);
        if ($response->getStatusCode() == 200) {
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);
            $deliveryId = $data['data']['delivery'];
            return $deliveryId;
        } else {
            return new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Failed to finding delivery.'));
        }
    }

    /**
     * @param \core_kernel_classes_Resource $env
     * @param \core_kernel_classes_Resource $delivery
     * @return array
     */
    protected function getPropertiesForUpdating(\core_kernel_classes_Resource $env, \core_kernel_classes_Resource $delivery)
    {
        $deliveryProperties = $env->getPropertyValues($this->getProperty(PublishingService::DELIVERY_FIELDS));
        $propertiesForUpdating = [];
        foreach ($deliveryProperties as $deliveryProperty) {
            $value = $delivery->getOnePropertyValue($this->getProperty($deliveryProperty));
            if ($value instanceof \core_kernel_classes_Resource) {
                $value = $value->getUri();
            } else {
                $value = (string) $value;
            }
            $propertiesForUpdating[$deliveryProperty] = $value;
        }
        return $propertiesForUpdating;
    }
}
