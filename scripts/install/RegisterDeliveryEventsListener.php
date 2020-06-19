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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA ;
 */
declare(strict_types=1);

namespace oat\taoPublishing\scripts\install;

use common_report_Report;
use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoPublishing\model\publishing\delivery\listeners\DeliveryEventsListener;
use oat\taoQtiTest\helpers\QtiPackageExporter;

class RegisterDeliveryEventsListener extends InstallAction
{
    public function __invoke($params): common_report_Report
    {
        $serviceManager = $this->getServiceManager();
        /** @var EventManager $eventManager */
        $eventManager = $serviceManager->get(EventManager::SERVICE_ID);
        $eventManager->attach(
            DeliveryCreatedEvent::class,
            [DeliveryEventsListener::class, 'backupQtiTestPackage']
        );
        $serviceManager->register(EventManager::SERVICE_ID, $eventManager);

        return new common_report_Report(common_report_Report::TYPE_SUCCESS, 'DeliveryEventsListener subscribed to DeliveryCreatedEvent.');
    }
}
