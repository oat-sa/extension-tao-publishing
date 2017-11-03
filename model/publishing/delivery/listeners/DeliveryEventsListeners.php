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
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoPublishing\model\publishing\delivery\listeners;

use oat\oatbox\service\ServiceManager;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use oat\taoPublishing\model\publishing\PublishingService;

/**
 * Class DeliveryEventsListeners
 * @package oat\taoDeliveryRdf\model\publishing
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 */
class DeliveryEventsListeners
{

    /**
     * @param DeliveryCreatedEvent $event
     * @return \common_report_Report
     */
    public static function createdDeliveryEvent(DeliveryCreatedEvent $event)
    {
        $delivery = new \core_kernel_classes_Resource($event->getDeliveryUri());
        try {
            self::checkSyncProperty($delivery);
            /** @var PublishingDeliveryService $publishDeliveryService */
            $publishDeliveryService = ServiceManager::getServiceManager()->get(PublishingDeliveryService::SERVICE_ID);
            self::checkActions($event->getName());
            $report = $publishDeliveryService->publishDelivery($delivery);

        } catch (\Exception $e) {
            $report = new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Delivery cannot be published. Please contact your administrator'));
        }

        return $report;

    }

    /**
     * @param DeliveryUpdatedEvent $event
     * @return \common_report_Report
     */
    public static function updatedDeliveryEvent(DeliveryUpdatedEvent $event)
    {
        $delivery = new \core_kernel_classes_Resource($event->getDeliveryUri());
        try {
            self::checkSyncProperty($delivery);
            /** @var PublishingDeliveryService $publishDeliveryService */
            $publishDeliveryService = ServiceManager::getServiceManager()->get(PublishingDeliveryService::SERVICE_ID);
            self::checkActions($event->getName());
            $report = $publishDeliveryService->syncDelivery($delivery);

        } catch (\Exception $e) {
            $report = new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Delivery cannot be updated. Please contact your administrator'));
        }

        return $report;

    }

    /**
     * @param $name
     * @throws \common_exception_NotFound
     */
    protected static function checkActions($name)
    {
        /** @var PublishingService $publishService */
        $publishService = ServiceManager::getServiceManager()->get(PublishingService::SERVICE_ID);
        $actions = $publishService->getOption(PublishingService::OPTIONS_ACTIONS);
        if (!in_array($name, $actions)) {
            throw new \common_exception_NotFound();
        }
    }

    /**
     * @param \core_kernel_classes_Resource $delivery
     * @throws \common_exception_NotFound
     */
    protected static function checkSyncProperty(\core_kernel_classes_Resource $delivery)
    {
        $property = new \core_kernel_classes_Property(PublishingDeliveryService::DELIVERY_REMOTE_SYNC_FIELD);
        $sync = $delivery->getPropertyValues($property);
        if (!current($sync)) {
            throw new \common_exception_NotFound();
        }
    }

}
