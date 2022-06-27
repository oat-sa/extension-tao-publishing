<?php

declare(strict_types=1);

namespace oat\taoPublishing\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDeliveryRdf\model\DeliveryContainerService;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use oat\taoPublishing\model\publishing\PublishingService;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202206271510143635_taoPublishing extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Setting the new excluded field for the delivery publishing';
    }

    public function up(Schema $schema): void
    {
        $service = $this->getServiceLocator()->get(PublishingDeliveryService::SERVICE_ID);
        $deliveryExcludedFieldsOptions = $service->getOption(PublishingService::OPTIONS_EXCLUDED_FIELDS);

        if (!array_search(DeliveryContainerService::PROPERTY_EXCLUDED_SUBJECTS, $deliveryExcludedFieldsOptions)) {
            // Using strings for ignoring ExcludedSubjects in require
            $deliveryExcludedFieldsOptions[] = DeliveryContainerService::PROPERTY_EXCLUDED_SUBJECTS;
        }

        $service->setOption(PublishingService::OPTIONS_EXCLUDED_FIELDS, $deliveryExcludedFieldsOptions);
        $this->getServiceManager()->register(PublishingDeliveryService::SERVICE_ID, $service);

    }

    public function down(Schema $schema): void
    {
        $service = $this->getServiceLocator()->get(PublishingDeliveryService::SERVICE_ID);
        $deliveryExcludedFieldsOptions = $service->getOption(PublishingService::OPTIONS_EXCLUDED_FIELDS);

        $to_remove = [DeliveryContainerService::PROPERTY_EXCLUDED_SUBJECTS];
        $result = array_diff($deliveryExcludedFieldsOptions, $to_remove);

        $service->setOption(PublishingService::OPTIONS_EXCLUDED_FIELDS, $result);
        $this->getServiceManager()->register(PublishingDeliveryService::SERVICE_ID, $service);

    }
}
