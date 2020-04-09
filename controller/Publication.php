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
use common_exception_NotFound;
use common_report_Report;
use core_kernel_classes_Resource;
use core_kernel_persistence_Exception;
use oat\taoPublishing\controller\RequestValidator\InvalidRequestException;
use oat\taoPublishing\controller\RequestValidator\PublishDeliveryRequestValidator;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use tao_actions_SaSModule;

class Publication extends tao_actions_SaSModule
{
    /**
     * @throws common_exception_Error
     * @throws common_exception_NotFound
     * @throws core_kernel_persistence_Exception
     */
    public function publishDelivery(): void
    {
        //@TODO need to redirect to listPublicationTarget action

        $request = $this->getPsrRequest();

        try {
            $this->getPublishDeliveryRequestValidator()->validate($request);
        } catch (InvalidRequestException $exception) {
            $this->displayFeedBackMessage(__($exception->getMessage()), false);

            return;
        }

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

    private function getPublishDeliveryRequestValidator(): PublishDeliveryRequestValidator
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(PublishDeliveryRequestValidator::SERVICE_ID);
    }

    private function getPublishingDeliveryService(): PublishingDeliveryService
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(PublishingDeliveryService::SERVICE_ID);
    }
}