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
 *
 */

namespace oat\taoPublishing\scripts\update;

use common_ext_ExtensionUpdater;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoDeliveryRdf\model\ContainerRuntime;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoPublishing\model\publishing\delivery\listeners\DeliveryEventsListeners;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use oat\taoPublishing\model\publishing\PublishingService;

/**
 * Class Updater
 * @package oat\taoProctoring\scripts\update
 */
class Updater extends common_ext_ExtensionUpdater
{

    public function update($initialVersion)
    {
        if ($this->isVersion('0.1')) {
            OntologyUpdater::syncModels();

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

            $service = new PublishingService();
            $this->getServiceManager()->register(PublishingService::SERVICE_ID, $service);

            $this->setVersion('0.2.0');
        }

        if ($this->isVersion('0.2.0')) {
            OntologyUpdater::syncModels();

            $publishingService = $this->getServiceManager()->get(PublishingService::SERVICE_ID);

            $actionOptions = $publishingService->hasOption(PublishingService::OPTIONS_ACTIONS)
                ? $publishingService->getOption(PublishingService::OPTIONS_ACTIONS)
                : [];

            $actionOptions = array_merge($actionOptions, [
                DeliveryCreatedEvent::class,
                DeliveryUpdatedEvent::class
            ]);
            $publishingService->setOption(PublishingService::OPTIONS_ACTIONS, $actionOptions);
            $this->getServiceManager()->register(PublishingService::SERVICE_ID, $publishingService);

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
            $this->getServiceManager()->register(PublishingDeliveryService::SERVICE_ID, $publishingDeliveryService);

            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);

            $eventManager->detach(
                'oat\\taoDeliveryRdf\\model\\event\\DeliveryCreatedEvent',
                ['oat\\taoPublishing\\model\\publishing\\listeners\\DeliveryEventsListeners', 'createdDeliveryEvent']
            );
            $eventManager->detach(
                'oat\\taoDeliveryRdf\\model\\event\\DeliveryUpdatedEvent',
                ['oat\\taoPublishing\\model\\publishing\\listeners\\DeliveryEventsListeners', 'updatedDeliveryEvent']
            );
            $eventManager->attach(
                DeliveryCreatedEvent::class,
                [DeliveryEventsListeners::class, 'createdDeliveryEvent']
            );
            $eventManager->attach(
                DeliveryUpdatedEvent::class,
                [DeliveryEventsListeners::class, 'updatedDeliveryEvent']
            );

            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            $this->setVersion('0.3.0');
        }
    }
}
