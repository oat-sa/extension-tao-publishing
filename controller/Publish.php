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

declare(strict_types=1);

namespace oat\taoPublishing\controller;

use core_kernel_classes_Resource;
use oat\taoDeliveryRdf\model\NoTestsException;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoPublishing\view\form\PublishForm;
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
class Publish extends \tao_actions_SaSModule {

    use OntologyAwareTrait;

    public function wizard(): void
    {
        try {
            $formContainer = new WizardForm(['test' => $this->getRequestParameter('id')]);
            $form = $formContainer->getForm();

            if ($form->isValid() && $form->isSubmited()) {
                $test = $this->getResource($form->getValue('test'));
                $env = $this->getResource($form->getValue('environment'));

                $queue = $this->getQueueService();
                $queue->createTask(new DeployTest(), [$test->getUri(), $env->getUri()]);
                $this->returnReport(
                    \common_report_Report::createSuccess('The deployment of your test has been scheduled')
                );

                return;
            }

            $this->setData('myForm', $form->render());
            $this->setData('formTitle', __('Publish to a delivery environment'));
            $this->setView('form.tpl', 'tao');

        } catch (NoTestsException $e) {
            $this->setView('DeliveryMgmt/wizard_error.tpl');
        }
    }

    public function listPublicationTargets(): void
    {
        $this->defaultData();

        $formContainer = new PublishForm(['class' => $this->getCurrentClass()]);
        $form = $formContainer->getForm();

        $this->setData('form', $form->render());
        $this->setData('publicationTargets', $this->publicationTargets());
        $this->setData('formTitle', __('Properties'));
        $this->setData('label', $this->getCurrentInstance()->getLabel());

        $this->setView('TaoPublishing/publishDelivery.tpl');
    }

    /**
     * @return array
     */
    private function publicationTargets(): array
    {
        $publicationTargets = $this->getPublishingService()->getEnvironments();

        $targets = [];

        /** @var core_kernel_classes_Resource $publicationTarget */
        foreach ($publicationTargets as $publicationTarget) {
            $targets[] = [
                'uriResource' => $publicationTarget->getUri(),
                'label' => $publicationTarget->getLabel(),
            ];
        }

        return $targets;
    }

    private function getPublishingService(): PublishingService
    {
        return $this->getServiceLocator()->get(PublishingService::SERVICE_ID);
    }

    private function getQueueService(): Queue
    {
        return $this->getServiceLocator()->get(Queue::CONFIG_ID);
    }
}
