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

use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use Throwable;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\import\service\RdsResourceNotFoundException;
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
            $testResource = $this->getOriginTestResource($event);
            $file = $testBackupService->backupDeliveryTestPackage(
                $event->getDeliveryUri(),
                $testResource->getUri()
            );
            $this->storeQtiTestBackupPath($event->getDeliveryUri(), $file->getPrefix());
        } catch (Throwable $e) {
            $this->logError('Backup was not created for delivery: ' . $event->getDeliveryUri());
        }
    }

    private function getOriginTestResource(DeliveryCreatedEvent  $event): core_kernel_classes_Resource
    {
        $delivery = $this->getResource($event->getDeliveryUri());
        /** @var core_kernel_classes_Resource $test */
        $test = $delivery->getOnePropertyValue($this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN));
        if (!$test->exists()) {
            throw new RdsResourceNotFoundException(
                sprintf("Origin test not found for delivery: %s", $event->getDeliveryUri())
            );
        }

        return $test;
    }

    private function storeQtiTestBackupPath(string $deliveryUri, string $path): bool
    {
        $delivery = $this->getResource($deliveryUri);

        return $delivery->setPropertyValue($this->getProperty(TestBackupService::PROPERTY_QTI_TEST_BACKUP_PATH), $path);
    }
}
