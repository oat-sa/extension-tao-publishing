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
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\filesystem\File;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\import\service\RdsResourceNotFoundException;
use oat\taoQtiTest\helpers\QtiPackageExporter;

class TestBackupService extends ConfigurableService
{
    use OntologyAwareTrait;

    public const FILESYSTEM_ID = 'publishing';

    public const PROPERTY_QTI_TEST_BACKUP_PATH = 'http://www.tao.lu/Ontologies/TAOPublishing.rdf#QtiTestBackupPath';

    /**
     * @param string $deliveryUri
     * @param string $testUri
     * @return File
     * @throws RdsResourceNotFoundException
     * @throws common_Exception
     */
    public function backupDeliveryTestPackage(string $deliveryUri, string $testUri): File
    {
        try {
            /** @var QtiPackageExporter $packageExporter */
            $packageExporter = $this->getServiceLocator()->get(QtiPackageExporter::SERVICE_ID);

            return $packageExporter->exportQtiTestPackageToFile($testUri, self::FILESYSTEM_ID, $this->prepareBackupFilePath($deliveryUri));
        } catch (Exception $e) {
            $this->logError($e->getMessage());
            throw new common_Exception(sprintf('Backup of origin QTI test failed for delivery %s', $deliveryUri));
        }
    }

    private function prepareBackupFilePath(string $deliveryUri): string
    {
        $folderName = md5($deliveryUri);

        return $folderName . DIRECTORY_SEPARATOR . QtiPackageExporter::QTI_PACKAGE_FILENAME . '.zip';
    }
}
