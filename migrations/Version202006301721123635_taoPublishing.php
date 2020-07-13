<?php

declare(strict_types=1);

namespace oat\taoPublishing\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\tao\scripts\update\OntologyUpdater;
use oat\taoDeliveryRdf\model\DeliveryFactory;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoDeliveryRdf\model\event\DeliveryUpdatedEvent;
use oat\taoPublishing\model\publishing\PublishingService;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202006301721123635_taoPublishing extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes remote publishing rdf property, UI interfaces and configs.';
    }

    public function up(Schema $schema): void
    {
        OntologyUpdater::syncModels();

        $this->deleteActionsConfigs();
        $this->deleteInitialPropertiesDeliveryFactoryConfigs();
        $this->unregisterRemotePublishingEventListeners();
    }

    public function down(Schema $schema): void
    {
        OntologyUpdater::syncModels();
        $this->setActionsConfigs();
        $this->setInitialPropertiesDeliveryFactoryConfigs();
        $this->registerRemotePublishingEventListeners();
    }

    private function deleteActionsConfigs(): void
    {
        $publishingService = $this->getServiceLocator()->get(PublishingService::SERVICE_ID);
        $options = $publishingService->getOptions();
        if (isset($options['actions'])) {
            unset($options['actions']);
        }
        $publishingService->setOptions($options);
        $this->getServiceManager()->register(PublishingService::SERVICE_ID, $publishingService);
    }

    private function deleteInitialPropertiesDeliveryFactoryConfigs(): void
    {
        $deliveryFactory = $this->getServiceLocator()->get(DeliveryFactory::SERVICE_ID);
        $options = $deliveryFactory->getOptions();

        $initialProperties = $options['initialProperties'] ?? [];
        $initialProperties = array_values(array_diff($initialProperties, ['http://www.tao.lu/Ontologies/TAOPublisher.rdf#RemoteSync']));
        $options['initialProperties'] = $initialProperties;

        if (isset($options['initialPropertiesMap']['remote-publish'])) {
            unset($options['initialPropertiesMap']['remote-publish']);
        }

        $deliveryFactory->setOptions($options);
        $this->getServiceManager()->register(DeliveryFactory::SERVICE_ID, $deliveryFactory);
    }

    private function unregisterRemotePublishingEventListeners(): void
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
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
    }

    private function setActionsConfigs(): void
    {
        $publishingService = $this->getServiceLocator()->get(PublishingService::SERVICE_ID);
        $actionsConfig = $publishingService->getOption('actions', []);
        $actionsConfig = array_merge(
            $actionsConfig,
            [
                'oat\\taoDeliveryRdf\\model\\event\\DeliveryUpdatedEvent',
                'oat\\taoDeliveryRdf\\model\\event\\DeliveryCreatedEvent'
            ]
        );
        $publishingService->setOption('actions', $actionsConfig);
        $this->getServiceManager()->register(PublishingService::SERVICE_ID, $publishingService);
    }

    private function setInitialPropertiesDeliveryFactoryConfigs(): void
    {
        $deliveryFactory = $this->getServiceLocator()->get(DeliveryFactory::SERVICE_ID);
        $options = $deliveryFactory->getOptions();
        $options['initialProperties'][] = 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#RemoteSync';
        $options['initialPropertiesMap']['remote-publish'] = [
            'uri' => 'http://www.tao.lu/Ontologies/TAOPublisher.rdf#RemoteSync',
            'values' => [
                'true' => 'http://www.tao.lu/Ontologies/TAODelivery.rdf#ComplyEnabled'
            ]
        ];
        $deliveryFactory->setOptions($options);
        $this->getServiceManager()->register(DeliveryFactory::SERVICE_ID, $deliveryFactory);
    }

    private function registerRemotePublishingEventListeners(): void
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->getServiceLocator()->get(EventManager::SERVICE_ID);
        $eventManager->attach(
            DeliveryCreatedEvent::class,
            [
                'oat\\taoPublishing\\model\\publishing\\delivery\\listeners\\DeliveryEventsListeners',
                'createdDeliveryEvent'
            ]
        );
        $eventManager->attach(
            DeliveryUpdatedEvent::class,
            [
                'oat\\taoPublishing\\model\\publishing\\delivery\\listeners\\DeliveryEventsListeners',
                'updatedDeliveryEvent'
            ]
        );
        $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
    }
}
