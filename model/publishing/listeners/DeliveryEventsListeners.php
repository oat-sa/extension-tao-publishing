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

namespace oat\taoPublishing\model\publishing\listeners;

use oat\oatbox\service\ServiceManager;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoPublishing\model\publishing\PublishingService;

/**
 * Class DeliveryEventsListeners
 * @package oat\taoDeliveryRdf\model\publishing
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 */
class DeliveryEventsListeners
{

    public static function createdDeliveryEvent(DeliveryCreatedEvent $event)
    {
        $delivery = new \core_kernel_classes_Resource($event->getDeliveryUri());
        try {
            /** @var PublishingService $publishService */
            $publishService = ServiceManager::getServiceManager()->get(PublishingService::SERVICE_ID);
            $report = $publishService->publishDelivery($delivery);

        } catch (\Exception $e) {
            $report = new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Delivery cannot be published. Please contact your administrator'));
        }

        return $report;

    }

    public static function updatedDeliveryEvent(DeliveryUpdatedEvent $event)
    {
        $delivery = new \core_kernel_classes_Resource($event->getDeliveryUri());
        try {
            /** @var PublishingService $publishService */
            $publishService = ServiceManager::getServiceManager()->get(PublishingService::SERVICE_ID);
            $report = $publishService->syncDelivery($delivery);

        } catch (\Exception $e) {
            $report = new \common_report_Report(\common_report_Report::TYPE_ERROR, __('Delivery cannot be updated. Please contact your administrator'));
        }

        return $report;

    }

}
