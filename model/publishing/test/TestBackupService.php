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

namespace oat\taoPublishing\model\publishing\test;

use Exception;
use common_Exception;
use core_kernel_classes_Resource;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\import\service\RdsResourceNotFoundException;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;
use oat\taoQtiTest\helpers\QtiPackageExporter;

class TestBackupService extends ConfigurableService
{
    use OntologyAwareTrait;

    public const FILESYSTEM_ID = 'publishing';

    public const PROPERTY_QTI_TEST_BACKUP_PATH = 'http://www.tao.lu/Ontologies/TAOPublishing.rdf#QtiTestBackupPath';

    /**
     * @param string $deliveryUri
     * @return File
     * @throws RdsResourceNotFoundException
     * @throws common_Exception
     */
    public function backupDeliveryTestPackage(string $deliveryUri): File
    {
        try {
            $test = $this->getTestResource($deliveryUri);
            /** @var QtiPackageExporter $packageExporter */
            $packageExporter = $this->getServiceLocator()->get(QtiPackageExporter::SERVICE_ID);

            return $packageExporter->exportQtiTestPackageToFile($test->getUri(), self::FILESYSTEM_ID, $this->prepareBackupFilePath($deliveryUri));
        } catch (RdsResourceNotFoundException $e) {
            $this->logError($e->getMessage());
            throw $e;
        } catch (Exception $e) {
            $this->logError($e->getMessage());
            throw new common_Exception(sprintf('Backup of origin QTI test failed for delivery %s', $deliveryUri));
        }
    }

    /**
     * @param string $deliveryUri
     * @return string
     */
    private function prepareBackupFilePath(string $deliveryUri): string
    {
        $folderName = md5($deliveryUri);

        return $folderName . DIRECTORY_SEPARATOR . QtiPackageExporter::QTI_PACKAGE_FILENAME . '.zip';
    }

    /**
     * @param string $deliveryUri
     * @return core_kernel_classes_Resource
     * @throws RdsResourceNotFoundException
     * @throws \core_kernel_persistence_Exception
     */
    private function getTestResource(string $deliveryUri): core_kernel_classes_Resource
    {
        $delivery = $this->getResource($deliveryUri);
        /** @var core_kernel_classes_Resource $test */
        $test = $delivery->getOnePropertyValue($this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN));
        if (!$test->exists()) {
            throw new RdsResourceNotFoundException("Origin test not found for delivery: {$deliveryUri}");
        }

        return $test;
    }
}
