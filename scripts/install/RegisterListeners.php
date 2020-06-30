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
 *
 */
declare(strict_types=1);

namespace oat\taoPublishing\scripts\install;

use common_Exception;
use common_report_Report;
use oat\oatbox\event\EventManager;
use oat\oatbox\extension\InstallAction;
use oat\oatbox\service\exception\InvalidServiceManagerException;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoPublishing\model\publishing\delivery\listeners\DeliveryTestSaverListener;

class RegisterListeners extends InstallAction
{
    /**
     * @param mixed $params
     *
     * @return common_report_Report
     *
     * @throws common_Exception
     * @throws InvalidServiceManagerException
     */
    public function __invoke($params): common_report_Report
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);

        $eventManager->attach(
            DeliveryCreatedEvent::class,
            new DeliveryTestSaverListener()
        );

        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

        return new common_report_Report(common_report_Report::TYPE_SUCCESS, 'Publisher delivery events registered');
    }
}