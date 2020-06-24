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
use oat\taoPublishing\scripts\install\RegisterDeliveryEventsListener;
use oat\taoPublishing\scripts\install\RegisterPublishingFileSystem;
use oat\taoPublishing\scripts\update\v0_6_0\UpdateAuthFieldAction;

/**
 * Class Updater
 * @package oat\taoProctoring\scripts\update
 * @deprecated use migrations instead. See https://github.com/oat-sa/generis/wiki/Tao-Update-Process
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

        if ($this->isVersion('0.3.0')) {
            OntologyUpdater::syncModels();

            $deliveryFactoryService = $this->getServiceManager()->get(DeliveryFactory::SERVICE_ID);
            $publishingOptions = $deliveryFactoryService->getOptions();
            $publishingOptions[DeliveryFactory::OPTION_INITIAL_PROPERTIES][] = PublishingDeliveryService::DELIVERY_REMOTE_SYNC_FIELD;
            $publishingOptions[DeliveryFactory::OPTION_INITIAL_PROPERTIES_MAP] = [
                PublishingDeliveryService::DELIVERY_REMOTE_SYNC_REST_OPTION => [
                    DeliveryFactory::OPTION_INITIAL_PROPERTIES_MAP_URI => PublishingDeliveryService::DELIVERY_REMOTE_SYNC_FIELD,
                    DeliveryFactory::OPTION_INITIAL_PROPERTIES_MAP_VALUES => [
                        'true' => PublishingDeliveryService::DELIVERY_REMOTE_SYNC_COMPILE_ENABLED
                    ]
                ]
            ];
            $deliveryFactoryService->setOptions($publishingOptions);
            $this->getServiceManager()->register(DeliveryFactory::SERVICE_ID, $deliveryFactoryService);

            $publishingDeliveryService = $this->getServiceManager()->get(PublishingDeliveryService::SERVICE_ID);
            $deliveryFieldsOptions = $publishingDeliveryService->getOption(PublishingService::OPTIONS_EXCLUDED_FIELDS);
            $deliveryFieldsOptions[] = PublishingDeliveryService::DELIVERY_REMOTE_SYNC_FIELD;

            $publishingDeliveryService->setOptions($deliveryFieldsOptions);
            $this->getServiceManager()->register(PublishingDeliveryService::SERVICE_ID, $publishingDeliveryService);

            $this->setVersion('0.4.0');
        }

        if ($this->isVersion('0.4.0')) {
            $service = new PublishingService();
            $service->setOption(PublishingService::OPTIONS_ACTIONS, [
                DeliveryCreatedEvent::class,
                DeliveryUpdatedEvent::class
            ]);
            $this->getServiceManager()->register(PublishingService::SERVICE_ID, $service);

            $publishingDeliveryService = new PublishingDeliveryService();
            $deliveryFieldsOptions[PublishingService::OPTIONS_FIELDS] = [];
            $deliveryFieldsOptions[PublishingService::OPTIONS_EXCLUDED_FIELDS] = [
                DeliveryAssemblyService::PROPERTY_DELIVERY_DIRECTORY,
                ContainerRuntime::PROPERTY_CONTAINER,
                DeliveryAssemblyService::PROPERTY_DELIVERY_RUNTIME,
                DeliveryAssemblyService::PROPERTY_DELIVERY_TIME,
                DeliveryAssemblyService::PROPERTY_ORIGIN,
                PublishingDeliveryService::ORIGIN_DELIVERY_ID_FIELD,
                PublishingDeliveryService::DELIVERY_REMOTE_SYNC_FIELD
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
            $this->runExtensionScript(RegisterPublishingFileSystem::class);
            $this->runExtensionScript(RegisterDeliveryEventsListener::class);
            $this->setVersion('2.2.0');
        }

        $this->skip('2.2.0', '2.2.1');
        
        //Updater files are deprecated. Please use migrations.
        //See: https://github.com/oat-sa/generis/wiki/Tao-Update-Process

        $this->setVersion($this->getExtension()->getManifest()->getVersion());
    }
}
