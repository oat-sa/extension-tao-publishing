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

namespace oat\taoPublishing\test\unit\model\publishing\test;

use Exception;
use common_Exception;
use core_kernel_classes_Property;
use core_kernel_classes_Resource;
use oat\generis\model\data\Ontology;
use oat\generis\test\MockObject;
use oat\oatbox\filesystem\File;
use oat\oatbox\log\LoggerService;
use oat\tao\model\import\service\RdsResourceNotFoundException;
use oat\taoPublishing\model\publishing\test\TestBackupService;
use oat\generis\test\TestCase;
use oat\taoQtiTest\helpers\QtiPackageExporter;

class TestBackupServiceTest extends TestCase
{
    /** @var TestBackupService */
    private $subject;

    /** @var Ontology|MockObject */
    private $ontologyMock;

    /** @var QtiPackageExporter|MockObject */
    private $qtiPackageExporterMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ontologyMock = $this->createMock(Ontology::class);
        $this->qtiPackageExporterMock = $this->createMock(QtiPackageExporter::class);
        $serviceLocatorMock = $this->getServiceLocatorMock(
            [
                Ontology::SERVICE_ID => $this->ontologyMock,
                QtiPackageExporter::SERVICE_ID => $this->qtiPackageExporterMock,
                LoggerService::SERVICE_ID => $this->createMock(LoggerService::class)
            ]
        );

        $this->subject = new TestBackupService();
        $this->subject->setServiceLocator($serviceLocatorMock);
    }

    public function testBackupDeliveryTestPackage_ThrowsExceptionWhenDestDoesNotExist(): void
    {
        $deliveryUri = 'DUMMY_DELIVERY_URI';
        $testMock = $this->getTestResource($deliveryUri);
        $testMock->method('exists')
            ->willReturn(false);

        $this->expectException(RdsResourceNotFoundException::class);
        $this->subject->backupDeliveryTestPackage($deliveryUri);
    }

    public function testBackupDeliveryTestPackage_ThrowsExceptionWhenBackupFailed(): void
    {
        $deliveryUri = 'DUMMY_DELIVERY_URI';
        $testMock = $this->getTestResource($deliveryUri);
        $testMock->method('exists')
            ->willReturn(true);
        $testMock->method('getUri')
            ->willReturn('DUMMY_TEST_URI');

        $this->qtiPackageExporterMock->method('exportQtiTestPackageToFile')
            ->willThrowException(new Exception('DUMMY EXCEPTION.'));

        $this->expectException(common_Exception::class);
        $this->subject->backupDeliveryTestPackage($deliveryUri);
    }

    public function testBackupDeliveryTestPackageReturnsFile()
    {
        $deliveryUri = 'DUMMY_DELIVERY_URI';
        $expectedFile = $this->createMock(File::class);
        $expectedFileSystem = 'publishing';
        $expectedFilePath = md5($deliveryUri) . DIRECTORY_SEPARATOR . 'qti_test_export.zip';
        $expectedTestUri = 'DUMMY_TEST_URI';

        $testMock = $this->getTestResource($deliveryUri);
        $testMock->method('exists')
            ->willReturn(true);
        $testMock->method('getUri')
            ->willReturn($expectedTestUri);

        $this->qtiPackageExporterMock->method('exportQtiTestPackageToFile')
            ->with(
                $expectedTestUri,
                $expectedFileSystem,
                $expectedFilePath
            )
            ->willReturn($expectedFile);

        $result = $this->subject->backupDeliveryTestPackage($deliveryUri);
        self::assertSame(
            $expectedFile,
            $result,
            'Backup method must return correct File object in case of successful backup.'
        );
    }

    /**
     * @return core_kernel_classes_Resource|MockObject
     */
    private function getTestResource(): core_kernel_classes_Resource
    {
        $propertyMock = $this->createMock(core_kernel_classes_Property::class);
        $testMock = $this->createMock(core_kernel_classes_Resource::class);

        $deliveryMock = $this->createMock(core_kernel_classes_Resource::class);
        $deliveryMock->method('getOnePropertyValue')
            ->willReturn($testMock);

        $this->ontologyMock->method('getResource')
            ->willReturn($deliveryMock);
        $this->ontologyMock->method('getProperty')
            ->willReturn($propertyMock);

        return $testMock;
    }
}

