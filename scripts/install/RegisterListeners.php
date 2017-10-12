<?php

namespace oat\taoPublishing\scripts\install;

use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoPublishing\model\publishing\listeners\DeliveryEventsListeners;

/**
 * Class RegisterListeners
 * @package oat\taoPublishing\scripts\install
 */
class RegisterListeners extends InstallAction
{
    public function __invoke($params)
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);

        $eventManager->attach(
            DeliveryCreatedEvent::class,
            [DeliveryEventsListeners::class, 'createdDeliveryEvent']
        );

        $eventManager->attach(
            DeliveryUpdatedEvent::class,
            [DeliveryEventsListeners::class, 'updatedDeliveryEvent']
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Publisher delivery events registered');
    }
}