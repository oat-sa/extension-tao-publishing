<?php declare(strict_types=1);

namespace oat\taoPublishing\controller;

use oat\taoPublishing\controller\RequestValidator\InvalidRequestException;
use oat\taoPublishing\controller\RequestValidator\PublishDeliveryRequestValidator;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;

class Publication extends \tao_actions_SaSModule
{
    /**
     * @throws \common_exception_Error
     * @throws \common_exception_NotFound
     * @throws \core_kernel_persistence_Exception
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
            new \core_kernel_classes_Resource($request->getParsedBody()['uri'])
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
            function (?string $joinedMessages, \common_report_Report $report): string {
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