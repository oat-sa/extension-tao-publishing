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
use oat\tao\model\auth\AbstractAuthService;
use oat\tao\model\auth\BasicAuthType;
use oat\tao\model\search\Search;
use oat\tao\model\search\strategy\GenerisSearch;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoDeliveryRdf\model\ContainerRuntime;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoPublishing\model\publishing\delivery\listeners\DeliveryEventsListeners;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use oat\taoPublishing\model\publishing\PublishingAuthService;
use oat\taoPublishing\model\publishing\PublishingService;
use oat\taoPublishing\scripts\update\v0_6_0\UpdateAuthFieldAction;

/**
 * Class Updater
 * @package oat\taoProctoring\scripts\update
 */
class Updater extends common_ext_ExtensionUpdater
{

    /**
     * @param $initialVersion
     * @return string|void
     * @throws \common_Exception
     */
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
            $this->setVersion('0.3.0');
        }

        if ($this->isVersion('0.3.0')) {
            OntologyUpdater::syncModels();
            $this->setVersion('0.4.0');
        }

        if ($this->isVersion('0.4.0')) {
            $this->getServiceManager()->register(PublishingService::SERVICE_ID, new PublishingService());

            $publishingDeliveryService = new PublishingDeliveryService();
            $deliveryFieldsOptions[PublishingService::OPTIONS_FIELDS] = [];
            $deliveryFieldsOptions[PublishingService::OPTIONS_EXCLUDED_FIELDS] = [
                DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY,
                ContainerRuntime::PROPERTY_CONTAINER,
                DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME,
                DeliveryAssemblyService::PROPERTY_DELIVERY_TIME,
                DeliveryAssemblyService::PROPERTY_ORIGIN,
                PublishingDeliveryService::ORIGIN_DELIVERY_ID_FIELD,
            ];
            $publishingDeliveryService->setOptions($deliveryFieldsOptions);
            $this->getServiceManager()->register(PublishingDeliveryService::SERVICE_ID, $publishingDeliveryService);
            $this->setVersion('0.4.1');
        }

        if ($this->isVersion('0.4.1')) {
            $service = $this->getServiceManager()->get(PublishingDeliveryService::SERVICE_ID);
            $deliveryExcludedFieldsOptions = $service->getOption(PublishingService::OPTIONS_EXCLUDED_FIELDS);

            // Using strings for ignoring taoClientRestricted in require
            $deliveryExcludedFieldsOptions[] = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#RestrictBrowserUsage';
            $deliveryExcludedFieldsOptions[] = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#RestrictOSUsage';
            $service->setOption(PublishingService::OPTIONS_EXCLUDED_FIELDS, $deliveryExcludedFieldsOptions);
            $this->getServiceManager()->register(PublishingDeliveryService::SERVICE_ID, $service);
            $this->setVersion('0.4.2');
        }

        if ($this->isVersion('0.4.2')) {
            OntologyUpdater::syncModels();
            $this->setVersion('0.4.3');
        }

        $this->skip('0.4.3', '0.5.0');

        if ($this->isVersion('0.5.0')) {
            OntologyUpdater::syncModels();
            $this->setVersion('0.5.1');
        }

        $this->skip('0.5.1', '0.5.3');

        if ($this->isVersion('0.5.3')) {
            // new ontology
            OntologyUpdater::syncModels();

            // new authentication fields for the publishing
            $updFieldAction = new UpdateAuthFieldAction();
            $updFieldAction([]);

            // new publishing authentication service
            $service = new PublishingAuthService([
                AbstractAuthService::OPTION_DEFAULT_TYPE => new BasicAuthType(),
                AbstractAuthService::OPTION_TYPES => [
                    new BasicAuthType(),
                ]
            ]);

            $this->getServiceManager()->register(PublishingAuthService::SERVICE_ID, $service);

            $this->setVersion('0.6.0');
        }

        $this->skip('0.6.0', '1.0.0');

        if ($this->isVersion('1.0.0')) {
            $searchService = $this->getServiceManager()->get(Search::SERVICE_ID);
            if ($searchService instanceof GenerisSearch) {
                $newSearchService = new \oat\taoPublishing\model\search\GenerisSearch($searchService->getOptions());
                $this->getServiceManager()->register(Search::SERVICE_ID, $newSearchService);
            }
            OntologyUpdater::syncModels();
            $this->setVersion('1.1.0');
        }

        if ($this->isVersion('1.1.0')) {
            OntologyUpdater::syncModels();
            $this->setVersion('1.2.0');
        }

        $this->skip('1.2.0', '2.1.2');

        if ($this->isVersion('2.1.2')) {
            // Unregister remote publishing event listeners
            $eventManager = $this->getServiceManager()->get(EventManager::SERVICE_ID);
            $eventManager->detach(
                DeliveryCreatedEvent::class,
                [
                    'oat\\taoPublishing\\model\\publishing\\delivery\\listeners\\DeliveryEventsListeners',
                    'createdDeliveryEvent'
                ]
            );
            $eventManager->detach(
                DeliveryUpdatedEvent::class,
                [
                    'oat\\taoPublishing\\model\\publishing\\delivery\\listeners\\DeliveryEventsListeners',
                    'updatedDeliveryEvent'
                ]
            );
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);

            // Remove related configs in DeliveryFactory
            $deliveryFactory = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
            $deliveryFactoryOptions = $deliveryFactory->getOptions();
            unset($deliveryFactoryOptions[DeliveryFactory::OPTION_INITIAL_PROPERTIES]);
            unset($deliveryFactoryOptions[DeliveryFactory::OPTION_INITIAL_PROPERTIES_MAP]);
            $deliveryFactory->setOptions($deliveryFactoryOptions);
            $this->getServiceManager()->register(DeliveryFactory::SERVICE_ID, $deliveryFactory);

            $publishingDeliveryService = $this->getServiceManager()->get(PublishingDeliveryService::SERVICE_ID);
            $deliveryExcludedFieldsOptions = $publishingDeliveryService->getOption(PublishingService::OPTIONS_EXCLUDED_FIELDS);
            if (isset($deliveryExcludedFieldsOptions['http://www.tao.lu/Ontologies/TAOPublisher.rdf#RemoteSync'])) {
                unset($deliveryExcludedFieldsOptions['http://www.tao.lu/Ontologies/TAOPublisher.rdf#RemoteSync']);
            }

            OntologyUpdater::syncModels();
            $this->setVersion('3.0.0');
        }
    }
}
