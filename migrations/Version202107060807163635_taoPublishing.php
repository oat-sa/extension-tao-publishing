<?php

declare(strict_types=1);

namespace oat\taoPublishing\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoPublishing\model\publishing\delivery\PublishingClassDeliveryService;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202107060807163635_taoPublishing extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Register PublishingClassDeliveryService';
    }

    public function up(Schema $schema): void
    {
        $this->getServiceManager()->register(
            PublishingClassDeliveryService::SERVICE_ID,
            new PublishingClassDeliveryService([
                PublishingClassDeliveryService::OPTION_MAX_RESOURCE => 50
            ])
        );

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
