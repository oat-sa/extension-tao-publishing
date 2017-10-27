<?php

namespace oat\taoPublishing\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoDeliveryRdf\model\ContainerRuntime;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use oat\taoPublishing\model\publishing\PublishingService;

class RegisterPublishingService extends InstallAction
{
    public function __invoke($params)
    {
        $service = new PublishingService();
        $service->setOption(PublishingService::OPTIONS_ACTIONS, [
            'DeliveryCreatedEvent',
            'DeliveryUpdatedEvent'
        ]);
        $this->registerService(PublishingService::SERVICE_ID, $service);

        $publishingDeliveryService = new PublishingDeliveryService();
        $deliveryFieldsOptions[PublishingService::OPTIONS_FIELDS] = [];
        $deliveryFieldsOptions[PublishingService::OPTIONS_EXCLUDED_FIELDS] = [
            DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY,
            ContainerRuntime::PROPERTY_CONTAINER,
            DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME,
            DeliveryAssemblyService::PROPERTY_DELIVERY_TIME,
            DeliveryAssemblyService::PROPERTY_ORIGIN,
            PublishingDeliveryService::ORIGIN_DELIVERY_ID_FIELD
        ];
        $publishingDeliveryService->setOptions($deliveryFieldsOptions);
        $this->registerService(PublishingDeliveryService::SERVICE_ID, $publishingDeliveryService);
    }
}