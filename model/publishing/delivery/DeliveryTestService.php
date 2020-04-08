<?php declare(strict_types=1);
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
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoPublishing\model\publishing\delivery;

use core_kernel_classes_Property;
use League\Flysystem\FileExistsException;
use oat\generis\model\OntologyAwareTrait;
use oat\oatbox\service\ConfigurableService;
use oat\tao\model\service\ServiceFileStorage;
use oat\taoDeliveryRdf\model\DeliveryAssemblyService;

class DeliveryTestService extends ConfigurableService
{
    use OntologyAwareTrait;

    public const SERVICE_ID = self::class;
    private const TEST_DIRECTORY_ID_DELIVERY_PROPERTY = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#TestFilePathID';
    private const TEST_FILE_NAME_DELIVERY_PROPERTY = 'http://www.tao.lu/Ontologies/TAODelivery.rdf#TestFileName';

    /**
     * @param \core_kernel_classes_Resource $delivery
     *
     * @throws FailToSaveTestException
     */
    public function exportTest(\core_kernel_classes_Resource $delivery): void
    {
        $testProperty = $this->getProperty(DeliveryAssemblyService::PROPERTY_ORIGIN);

        try {
            /** @var \core_kernel_classes_Resource $test */
            $test = $delivery->getOnePropertyValue($testProperty);
        } catch (\core_kernel_persistence_Exception $exception) {
            throw new FailToSaveTestException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $result = $this->export($test);

        $this->saveTestFilePathId($delivery, $result['pathId']);
        $this->saveTestFileName($delivery, $result['fileName']);
    }

    /**
     * @return resource
     *
     * @throws FailToGetTestException
     */
    public function getTestStream(\core_kernel_classes_Resource $delivery)
    {
        $filePathIdProperty = $this->getProperty(self::TEST_DIRECTORY_ID_DELIVERY_PROPERTY);
        $fileNameProperty = $this->getProperty(self::TEST_FILE_NAME_DELIVERY_PROPERTY);

        try {
            $filePathId = $delivery->getOnePropertyValue($filePathIdProperty);
            $fileName = $delivery->getOnePropertyValue($fileNameProperty);
        } catch (\core_kernel_persistence_Exception $exception) {
            throw new FailToGetTestException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $stream = $this->getFileStorageService()
            ->getDirectoryById(trim((string)$filePathId))
            ->getFile((string)$fileName)
            ->readStream();

        if (!$stream) {
            throw new FailToGetTestException(sprintf('Fail to read the file %s', $filePathIdProperty));
        }

        return $stream;
    }

    private function getFileStorageService(): ServiceFileStorage
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->getServiceLocator()->get(ServiceFileStorage::SERVICE_ID);
    }

    /**
     * @param \core_kernel_classes_Resource $test
     *
     * @throws FailToSaveTestException
     *
     * @return \common_report_Report
     */
    private function export(\core_kernel_classes_Resource $test): array
    {
        $this->getLogger()->debug(sprintf('Exporting Test %s for deployment', $test->getUri()));

        $exporter = new \taoQtiTest_models_classes_export_TestExport();

        try {
            $report = $exporter->export(
                [
                    'filename' => \League\Flysystem\Util::normalizePath($test->getLabel()),
                    'instances' => $test->getUri(),
                ],
                \tao_helpers_File::createTempDir()
            );

            $reportData = $report->getData();
            if (!isset($reportData['path'])) {
                throw new FailToSaveTestException('Exported Path does not exist.');
            }

            $filePath = $reportData['path'];
            $fileName = basename($reportData['path']);
            $privateDirectory = $this->getFileStorageService()->spawnDirectory();

            $privateDirectory->getFile($fileName)
                ->write(fopen($filePath, 'rb'));

            return [
                'pathId' => $privateDirectory->getId(),
                'fileName' => $fileName
            ];
        } catch (\common_exception_Error | \common_Exception| FileExistsException $exception) {
            throw new FailToSaveTestException($exception->getMessage(), 0, $exception);
        }
    }

    private function saveTestFilePathId(\core_kernel_classes_Resource $delivery, string $pathId): void
    {
        $propertyFilePath = new core_kernel_classes_Property(
            self::TEST_DIRECTORY_ID_DELIVERY_PROPERTY
        );
        $delivery->setPropertyValue($propertyFilePath, $pathId);
    }
    private function saveTestFileName(\core_kernel_classes_Resource $delivery, string $filename): void
    {
        $propertyFilePath = new core_kernel_classes_Property(
            self::TEST_FILE_NAME_DELIVERY_PROPERTY
        );
        $delivery->setPropertyValue($propertyFilePath, $filename);
    }
}