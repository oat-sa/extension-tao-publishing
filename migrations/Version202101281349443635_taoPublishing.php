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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoPublishing\migrations;

use Doctrine\DBAL\Schema\Schema;
use oat\oatbox\event\EventManager;
use oat\tao\scripts\tools\migrations\AbstractMigration;
use oat\taoDeliveryRdf\model\DataStore\DeliveryMetadataListener;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoPublishing\model\publishing\event\RemoteDeliveryCreatedEvent;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version202101281349443635_taoPublishing extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Registering listener for RemoteDeliveryCreatedEvent';
    }

    public function up(Schema $schema): void
    {
        $eventManager = $this->getEventManger();
        $eventManager->attach(
            RemoteDeliveryCreatedEvent::class,
            [DeliveryMetadataListener::class, 'whenDeliveryIsPublished']
        );
    }

    public function down(Schema $schema): void
    {
        $eventManager = $this->getEventManger();
        $eventManager->detach(
            RemoteDeliveryCreatedEvent::class,
            [DeliveryMetadataListener::class, 'whenDeliveryIsPublished']
        );
    }

    private function getEventManger(): EventManager
    {
        return $this->getServiceManager()->get(EventManager::SERVICE_ID);
    }
}
