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
use GuzzleHttp\Psr7\MultipartStream;

/**
 * Deploys a test to environments
 */
class DeployTestEnvironments implements Action,ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;
    
    /**
     * 
     * @param array $params
     */
    public function __invoke($params) {

        if (count($params) != 3) {
            throw new \common_exception_MissingParameter();
        }
        $test = $this->getResource(array_shift($params));
        $envId = array_shift($params);
        $env = $this->getResource($envId);
        $deliveryId = array_shift($params);

        \common_Logger::i('Deploying '.$test->getLabel().' to '.$env->getLabel());
        $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Deployed %s to %s', $test->getLabel(), $env->getLabel()));
        
        $subReport = $this->compileTest($envId, $test, $deliveryId);
        $report->add($subReport);
        return $report;
    }

    /**
     * @param $envId
     * @param $test
     * @param $deliveryId
     * @return \common_report_Report
     */
    protected function compileTest($envId, \core_kernel_classes_Resource $test, $deliveryId) {

        \common_Logger::d('Exporting Test '.$test->getUri().' for deployment');
        $exporter = new \taoQtiTest_models_classes_export_TestExport();
        $exportReport = $exporter->export([
            'filename' => \League\Flysystem\Util::normalizePath($test->getLabel()),
            'instances' => $test->getUri(),
        ], \tao_helpers_File::createTempDir());
        $packagePath = $exportReport->getData();
        $body = new MultipartStream([[
            'name'     => 'testPackage',
            'contents' => fopen($packagePath, 'rb'),
        ], [
            'name'     => 'importerId',
            'contents' => 'taoQtiTest',
        ], [
            'name'     => 'delivery-params',
            'contents' => json_encode([
                PublishingService::ORIGIN_DELIVERY_ID_FIELD => $deliveryId
            ])
        ]]);

        $url = \tao_helpers_Uri::getPath(\tao_helpers_Uri::url('compileDeferred', 'RestTest', 'taoDeliveryRdf'));
        $request = new Request('POST', trim($url, '/'));
        $request = $request->withBody($body);
        
        \common_Logger::d('Requesting comilation of Test '.$test);
        $response = PlatformService::singleton()->callApi($envId, $request);
        if ($response->getStatusCode() == 200) {
            $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Test has been compiled as %s', $deliveryId));
            $report->setData($deliveryId);
            return $report;
        } else {
            return new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Failed to compile %s', $test));
        }
    }
}
