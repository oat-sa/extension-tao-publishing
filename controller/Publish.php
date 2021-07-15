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
 * Copyright (c) 2016-2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoPublishing\controller;

use Exception;
use common_exception_ClientException;
use HttpRequestException;
use oat\oatbox\reporting\Report;
use oat\tao\model\taskQueue\TaskLogActionTrait;
use oat\taoPublishing\model\entity\Platform;
use oat\taoPublishing\model\publishing\delivery\PublishingClassDeliveryService;
use oat\taoPublishing\model\publishing\exception\PublishingInvalidArgumentException;
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
class Publish extends \tao_actions_CommonModule
{

    use OntologyAwareTrait;
    use TaskLogActionTrait;

    const PARAM_SUBJECT_URI = 'subject-uri';
    const PARAM_REMOTE_ENVIRONMENTS = 'remote-environments';

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
        $requestBody = $this->getPsrRequest()->getParsedBody();

        if (!isset($requestBody['id'])) {
            throw new HttpRequestException('Body has to contain id parameter representing delivery');
        }

        $this->selectEnvironmentsScreen($requestBody['id'], 'publishToRemoteEnvironment');
    }

    public function selectClassRemoteEnvironments()
    {
        $requestBody = $this->getPsrRequest()->getParsedBody();

        if (!isset($requestBody['id']) && !$this->getClass($requestBody['id'])->exists()) {
            throw new HttpRequestException('Class under this id does not exist');
        }

        $resourceLimit = $this->getPublishingClassDeliveryService()
            ->getOption(PublishingClassDeliveryService::OPTION_MAX_RESOURCE);

        $this->selectEnvironmentsScreen($requestBody['id'], 'publishClassToRemoteEnvironment');

        $this->setData(
            'class-content-exceeded',
            $this->getClass($requestBody['id'])->countInstances() > $resourceLimit
        );

        $this->setData('class-content-limit', $resourceLimit);
    }

    private function selectEnvironmentsScreen(string $subjectUri, string $methodName)
    {
        $subjectResource = $this->getResource($subjectUri);

        if (empty($subjectResource->getLabel())) {
            throw new Exception('Resource does not exist');
        }

        $submitUrl = $this->getServiceLocator()
            ->get(UrlHelper::class)
            ->buildUrl($methodName, 'Publish', 'taoPublishing');

        $environments = $this->getEnvironmentsEntities();
        $this->setData('submit-url', $submitUrl);
        $this->setData(self::PARAM_SUBJECT_URI, $subjectResource->getUri());
        $this->setData('subject-label', $subjectResource->getLabel());
        $this->setData('remote-environments', array_values($environments));
        $this->setView('PublishToRemote/index.tpl');
    }

    public function publishClassToRemoteEnvironment(ServerRequest $request)
    {
        $requestData = $request->getParsedBody();
        if (!isset($requestData[self::PARAM_SUBJECT_URI]) || !$this->getClass($requestData[self::PARAM_SUBJECT_URI])->isClass()) {
            throw new Exception('This is not a class');
        }

        if (!count($requestData[self::PARAM_REMOTE_ENVIRONMENTS])) {
            throw new PublishingInvalidArgumentException(__('Environment(s) must be selected.'));
        }

        $tasks = $this->getPublishingClassDeliveryService()
            ->publish(
                $this->getClass($requestData[self::PARAM_SUBJECT_URI]),
                $requestData[self::PARAM_REMOTE_ENVIRONMENTS]
            );

        $report = Report::createInfo('Publishing tasks has been queued');

        foreach ($tasks as $task) {
            $log = $this->getTaskLogReturnData($task->getId());
            $report->add(
                Report::createInfo(
                    sprintf(
                        'Publishing task %s has been %s',
                        $log['id'],
                        $log['status']
                    )
                )
            );
        }

        return $this->returnTaskJson(array_shift($tasks), ['allTasks' => $tasks]);
    }

    /**
     * @param ServerRequest $request
     * @return mixed
     */
    public function publishToRemoteEnvironment(ServerRequest $request)
    {
        try {
            if ($request->getMethod() !== 'POST') {
                throw new PublishingInvalidArgumentException(__('Only POST method is supported.'));
            }
            $requestData = $request->getParsedBody();
            $deliveryUri = $requestData[self::PARAM_SUBJECT_URI] ?? '';
            $environments = $requestData[self::PARAM_REMOTE_ENVIRONMENTS] ?? [];

            if (!count($environments)) {
                throw new PublishingInvalidArgumentException(__('Environment(s) must be selected.'));
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
        } catch (common_exception_ClientException $e) {
            $this->returnJson(
                [
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            );
        } catch (Exception $e) {
            $this->returnJson(
                [
                    'success' => false,
                    'message' => __('Publishing to remote environments failed.'),
                ]
            );
        }

    }

    /**
     * @return Platform[]
     */
    private function getEnvironmentsEntities(): array
    {
        $environments = [];

        /** @var PublishingService $publishingService */
        $publishingService = $this->getServiceLocator()->get(PublishingService::SERVICE_ID);
        foreach ($publishingService->getEnvironments() as $environment) {
            $environments[] = new Platform($environment);
        }

        return $environments;
    }

    public function getPublishingClassDeliveryService(): PublishingClassDeliveryService
    {
        return $this->getServiceLocator()->get(PublishingClassDeliveryService::SERVICE_ID);
    }
}
