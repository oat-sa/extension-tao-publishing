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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *               
 * 
 */

namespace oat\taoPublishing\controller;

use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\taoPublishing\model\publishing\exception\PublishingFailedException;
use tao_helpers_Uri;
use core_kernel_classes_Resource;
use GuzzleHttp\Psr7\ServerRequest;
use oat\tao\helpers\UrlHelper;
use oat\taoPublishing\model\publishing\delivery\RemotePublishingService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoDeliveryRdf\model\NoTestsException;
use oat\taoPublishing\view\form\WizardForm;
use oat\generis\model\OntologyAwareTrait;
use oat\taoPublishing\model\DeployTest;
use oat\oatbox\task\Queue;
/**
 * Sample controller
 *
 * @author Open Assessment Technologies SA
 * @package taoPublishing
 * @license GPL-2.0
 *
 */
class Publish extends \tao_actions_CommonModule {

    use OntologyAwareTrait;
    use TaskLogActionTrait;
    
    public function wizard()
    {
            try {
            $formContainer = new WizardForm(array('test' => $this->getRequestParameter('id')));
            $myForm = $formContainer->getForm();
             
            if ($myForm->isValid() && $myForm->isSubmited()) {
                $test = $this->getResource($myForm->getValue('test'));
                $env = $this->getResource($myForm->getValue('environment'));
                
                $queue = $this->getServiceManager()->get(Queue::CONFIG_ID);
                $report = $queue->createTask(new DeployTest(), [$test->getUri(), $env->getUri()]);
                //$this->returnReport($report);
                $this->returnReport(\common_report_Report::createSuccess('The deployement of your test has been scheduled'));
            } else {
                $this->setData('myForm', $myForm->render());
                $this->setData('formTitle', __('Publish to a delivery environment'));
                $this->setView('form.tpl', 'tao');
            }
    
        } catch (NoTestsException $e) {
            $this->setView('DeliveryMgmt/wizard_error.tpl');
        }
    }

    public function selectRemoteEnvironments()
    {
        $selectedDelivery = new core_kernel_classes_Resource(
            tao_helpers_Uri::decode($this->getRequestParameter('uri'))
        );

        $environments = $this->getServiceLocator()
            ->get(PublishingService::SERVICE_ID)
            ->getEnvironments();

        $submitUrl = $this->getServiceLocator()
            ->get(UrlHelper::class)
            ->buildUrl('publishToRemoteEnvironment', 'Publish', 'taoPublishing');

        $this->setData('submit-url', $submitUrl);
        $this->setData('delivery-uri', $selectedDelivery->getUri());
        $this->setData('delivery-label', $selectedDelivery->getLabel());
        $this->setData('remote-environments', array_values($environments));
        $this->setView('PublishToRemote/index.tpl');
    }

    /**
     * @param ServerRequest $request
     * @return mixed
     * @throws PublishingFailedException
     */
    public function publishToRemoteEnvironment(ServerRequest $request)
    {
        $requestData = $request->getParsedBody();
        $deliveryUri = $requestData['delivery-uri'] ?? '';
        $environments = $requestData['remote-environments'] ?? [];

        if (!count($environments)) {
            return $this->returnJson([
                'success'=> false,
                'message' => __('Environment(s) should be selected'),
            ]);
        }

        /** @var RemotePublishingService $remotePublishingService */
        $remotePublishingService = $this->getServiceLocator()->get(RemotePublishingService::class);
        $tasks = $remotePublishingService->publishDeliveryToEnvironments($deliveryUri, $environments);

        $task = array_shift($tasks);
        $self = $this;
        $allTasks = array_map(static function ($task) use ($self) {
            return $self->getTaskLogReturnData($task->getId());
        }, $tasks);

        return $this->returnTaskJson($task, ['allTasks' => $allTasks]);
    }
}
