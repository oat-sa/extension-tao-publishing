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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 */
declare(strict_types=1);

namespace oat\taoPublishing\controller;

use common_exception_Error;
use common_ext_ExtensionException;
use common_report_Report;
use core_kernel_classes_Resource;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoPublishing\view\form\PublishForm;
use tao_actions_SaSModule;
use tao_models_classes_MissingRequestParameterException;

class Publication extends tao_actions_SaSModule
{
    /**
     * @throws common_ext_ExtensionException
     * @throws tao_models_classes_MissingRequestParameterException
     * @throws common_exception_Error
     */
    public function publishDelivery(): void
    {
        $formContainer = new PublishForm(['class' => $this->getCurrentClass(), 'instance' => $this->getCurrentInstance()]);
        $form = $formContainer->getForm();

        $this->defaultData();

        $this->setData('form', $form->render());
        $this->setData('publicationTargets', $this->getPublicationTargets());
        $this->setData('formTitle', __('Properties'));
        $this->setData('label', $this->getCurrentInstance()->getLabel());

        $this->setView('TaoPublishing/publishDelivery.tpl');

        if ($form->isValid() && $form->isSubmited()) {
            $request = $this->getPsrRequest();
            $report = $this->getPublishingDeliveryService()->publishDelivery(
                new core_kernel_classes_Resource($request->getParsedBody()['uri'])
            );

            $errors = $report->getErrors();
            if (count($errors) > 0) {
                $this->displayPublicationErrors($errors);

                return;
            }

            $this->displayFeedBackMessage(__('Publication Task was created successfully'));
        }
    }

    private function getPublicationTargets(): array
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

    private function displayFeedBackMessage(string $message, bool $isSuccess = true): void
    {
        $this->setData($isSuccess ? 'message' : 'errorMessage', $message);
        $this->setData('reload', true);
    }

    private function displayPublicationErrors(array $errors): void
    {
        $joinedMessages = array_reduce(
            $errors,
            function (?string $joinedMessages, common_report_Report $report): string {
                return $joinedMessages . ', ' . $report->getMessage();
            }
        );
        $this->displayFeedBackMessage(
            __(
                sprintf(
                    "Fail to create the Publication Task: %s",
                    trim($joinedMessages, ', ')
                )
            ),
            false
        );
    }

    private function getPublishingService(): PublishingService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(PublishingService::SERVICE_ID);
    }

    private function getPublishingDeliveryService(): PublishingDeliveryService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(PublishingDeliveryService::SERVICE_ID);
    }
}