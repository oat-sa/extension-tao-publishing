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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *               
 * 
 */
namespace oat\taoPublishing\model;

use oat\oatbox\service\ConfigurableService;
use oat\oatbox\action\Action;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use oat\generis\model\OntologyAwareTrait;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\MultipartStream;

/**
 * Deploys a test 
 */
class DeployTest implements Action,ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;
    use OntologyAwareTrait;
    
    /**
     * 
     * @param array $params
     */
    public function __invoke($params) {

        if (count($params) != 2) {
            throw new \common_exception_MissingParameter();
        }
        $test = $this->getResource(array_shift($params));
        $envId = array_shift($params);
        $env = $this->getResource($envId);
        
        \common_Logger::i('Deploying '.$test->getLabel().' to '.$env->getLabel());
        $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Deployed %s to %s', $test->getLabel(), $env->getLabel()));
        
        $subReport = $this->sendTest($envId, $test);
        $report->add($subReport);
        if ($subReport->getType() == \common_report_Report::TYPE_SUCCESS) {
            $remoteTest = $subReport->getData();
            $subReport = $this->compileTest($envId, $remoteTest);
            $report->add($subReport);
            if ($subReport->getType() == \common_report_Report::TYPE_SUCCESS) {
                $remoteDelivery = $subReport->getData();
                $subReport = $this->getLtiLink($envId, $remoteDelivery);
                $report->add($subReport);
            }
        }
        
        return $report;
    }
    
    protected function sendTest($envId, $test) {
        $exportFile = \tao_helpers_File::createTempDir().'export.zip';
        $exporter = new \taoQtiTest_models_classes_export_TestExport();
        \common_Logger::d('Exporting Test '.$test->getUri().' for deployment');
        $exporter->exportTests(array($test), $exportFile);
        
        $body = new MultipartStream([[
            'name'     => 'qtiPackage',
            'contents' => fopen($exportFile, 'r')
        ]]);
        $request = new Request('POST', 'taoQtiTest/RestQtiTests/import');
        $request = $request->withBody($body);
        
        \common_Logger::d('Sending Test '.$test->getUri());
        $response = PlatformService::singleton()->callApi($envId, $request);
        if ($response->getStatusCode() == 200) {
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);
            $tests = $data['data'];
            if (count($tests) != 1) {
                throw new \common_exception_InconsistentData('Expected 1 test, received '.count($tests));
            }
            $testEntry = reset($tests);
            $testId = $testEntry['testId'];
            $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Deployed %s as %s', $test->getLabel(), $testId));
            $report->setData($testId);
            return $report;
        } else {
            return new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Unable to deploy $1%s, server response: $2%s', $test->getLabel(), $response->getStatusCode()));
        }
    }
    
    protected function compileTest($envId, $remoteTest) {
        $request = new Request('POST', '/taoDeliveryRdf/RestDelivery/generate');
        $request = $request->withBody(\GuzzleHttp\Psr7\stream_for(http_build_query(array('test' => $remoteTest))));
        $request = $request->withHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        \common_Logger::d('Requesting comilation of Test '.$remoteTest);
        $response = PlatformService::singleton()->callApi($envId, $request);
        if ($response->getStatusCode() == 200) {
            $content = $response->getBody()->getContents();
            $data = json_decode($content, true);
            $deliveryId = $data['data']['delivery'];
            $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Test has been compiled as %s', $deliveryId));
            $report->setData($deliveryId);
            return $report;
        } else {
            return new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Failed to compile %s', $remoteTest));
        }
    }
    
    protected function getLtiLink($envId, $remoteDelivery) {
        $request = new Request('GET', 'ltiDeliveryProvider/DeliveryRestService/getUrl?deliveryId='.urlencode($remoteDelivery));
    
        \common_Logger::d('Requesting LTI link for delivery '.$remoteDelivery);
        $response = PlatformService::singleton()->callApi($envId, $request);
        if ($response->getStatusCode() == 200) {
            $content = $response->getBody()->getContents();
            \common_Logger::i('Response: '.PHP_EOL.$content);
            $data = json_decode($content, true);
            if (is_array($data)) {
                $url = $data['data'];
                $report = new \common_report_Report(\common_report_Report::TYPE_SUCCESS, __('Delivery available: %s', $url));
                $report->setData($url);
                return $report;
            }
        }
        return new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Delivery not available as LTI'));
    }
}
