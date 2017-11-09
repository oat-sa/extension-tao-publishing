<?php

namespace oat\taoPublishing\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\DeliveryPublishing;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;

class UpdateConfigDeliveryFactoryService extends InstallAction
{
    public function __invoke($params)
    {
        $deliveryFactoryService = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
        $publishingOptions = $deliveryFactoryService->getOptions();
        $publishingOptions[DeliveryFactory::OPTION_INITIAL_PROPERTIES][] = PublishingDeliveryService::DELIVERY_REMOTE_SYNC_FIELD;
        $publishingOptions[DeliveryFactory::OPTION_INITIAL_PROPERTIES_MAP][] = [PublishingDeliveryService::DELIVERY_REMOTE_SYNC_REST_OPTION => PublishingDeliveryService::DELIVERY_REMOTE_SYNC_FIELD];
        $deliveryFactoryService->setOptions($publishingOptions);
        $this->getServiceManager()->register(DeliveryFactory::SERVICE_ID, $deliveryFactoryService);
    }
}