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
        $request = $this->getPsrRequest();

        \common_report_Report::createSuccess();

        try {
            $this->getPublishDeliveryRequestValidator()->validate($request);
        } catch (InvalidRequestException $exception) {
            $message = __($exception->getMessage());
            $this->displayFeedBackMessage($message);
        }

        $this->getPublishingDeliveryService()->publishDelivery(
            new \core_kernel_classes_Resource($request->getParsedBody()['uri'])
        );

        $this->displayFeedBackMessage(__('Publication Task was created successfully'));
    }

    /**
     * @param string $message
     */
    public function displayFeedBackMessage(string $message): void
    {
        $this->setData('message', $message);
        $this->setData('reload', true);
    }

    private function getPublishDeliveryRequestValidator(): PublishDeliveryRequestValidator {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(PublishDeliveryRequestValidator::SERVICE_ID);
    }

    private function getPublishingDeliveryService(): PublishingDeliveryService {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return  $this->getServiceLocator()->get(PublishingDeliveryService::SERVICE_ID);
    }
}