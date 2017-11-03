<?php

namespace oat\taoPublishing\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoDeliveryRdf\model\DeliveryPublishing;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;

class UpdateConfigDeliveryPublishingService extends InstallAction
{
    public function __invoke($params)
    {
        $service = $this->getServiceManager()->get(DeliveryPublishing::SERVICE_ID);
        $publishingOptions = $service->getOption(DeliveryPublishing::OPTION_PUBLISH_OPTIONS);
        $publishingOptions[DeliveryPublishing::OPTION_PUBLISH_OPTIONS_ELEMENTS] = [
            PublishingDeliveryService::DELIVERY_REMOTE_SYNC_FIELD => [
                'description' => _('Publish to remote environments'),
                'value' => PublishingDeliveryService::DELIVERY_REMOTE_SYNC_COMPILE_ENABLED
            ]
        ];
        $service->setOption(DeliveryPublishing::OPTION_PUBLISH_OPTIONS, $publishingOptions);
        $this->registerService(DeliveryPublishing::SERVICE_ID, $service);
    }
}