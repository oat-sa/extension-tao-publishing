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

namespace oat\taoPublishing\model\publishing\delivery\listeners;

use oat\oatbox\service\ServiceManager;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoPublishing\model\publishing\delivery\DeliveryTestService;

/**
 * Class DeliveryEventsListeners
 * @package oat\taoDeliveryRdf\model\publishing
 * @author Aleksej Tikhanovich, <aleksej@taotesting.com>
 */
class DeliveryTestSaverListener
{
    /**
     * @param DeliveryCreatedEvent $event
     *
     * @return \common_report_Report
     */
    public function __invoke(DeliveryCreatedEvent $event): \common_report_Report
    {
        $report = \common_report_Report::createSuccess();
        try {
            $this->getTestService()->exportTest(new \core_kernel_classes_Resource($event->getDeliveryUri()));
        } catch (\Exception $e) {
            $report = new \common_report_Report(
                \common_report_Report::TYPE_ERROR,
                __('Delivery cannot be published. Please contact your administrator')
            );
        }

        return $report;
    }

    public function getTestService(): DeliveryTestService {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceManager::getServiceManager()->get(DeliveryTestService::SERVICE_ID);
    }
}