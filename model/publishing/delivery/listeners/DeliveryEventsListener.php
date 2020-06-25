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

namespace oat\taoPublishing\model\publishing\delivery\listeners;

use Throwable;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\taoDeliveryRdf\model\event\DeliveryCreatedEvent;
use oat\taoPublishing\model\publishing\test\TestBackupService;

class DeliveryEventsListener extends ConfigurableService
{
    use OntologyAwareTrait;

    public function backupQtiTestPackage(DeliveryCreatedEvent $event): void
    {
        try {
            /** @var TestBackupService $testBackupService */
            $testBackupService = $this->getServiceLocator()->get(TestBackupService::class);
            $file = $testBackupService->backupDeliveryTestPackage(
                $event->getDeliveryUri(),
                $event->getOriginTestUri()
            );
            $this->storeQtiTestBackupPath($event->getDeliveryUri(), $file->getPrefix());
        } catch (Throwable $e) {
            $this->logError('Backup was not created for delivery: ' . $event->getDeliveryUri());
        }
    }

    private function storeQtiTestBackupPath(string $deliveryUri, string $path): bool
    {
        $delivery = $this->getResource($deliveryUri);

        return $delivery->setPropertyValue($this->getProperty(TestBackupService::PROPERTY_QTI_TEST_BACKUP_PATH), $path);
    }
}
