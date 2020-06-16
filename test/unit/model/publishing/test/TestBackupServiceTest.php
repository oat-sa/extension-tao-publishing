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

    /** @var QtiPackageExporter|MockObject */
    private $qtiPackageExporterMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->qtiPackageExporterMock = $this->createMock(QtiPackageExporter::class);
        $serviceLocatorMock = $this->getServiceLocatorMock(
            [
                QtiPackageExporter::SERVICE_ID => $this->qtiPackageExporterMock,
                LoggerService::SERVICE_ID => $this->createMock(LoggerService::class)
            ]
        );

        $this->subject = new TestBackupService();
        $this->subject->setServiceLocator($serviceLocatorMock);
    }

    public function testBackupDeliveryTestPackage_ThrowsExceptionWhenBackupFailed(): void
    {
        $deliveryUri = 'DUMMY_DELIVERY_URI';
        $testUri = 'DUMMY_TEST_URI';

        $this->qtiPackageExporterMock->method('exportQtiTestPackageToFile')
            ->willThrowException(new Exception('DUMMY EXCEPTION.'));

        $this->expectException(common_Exception::class);
        $this->subject->backupDeliveryTestPackage($deliveryUri, $testUri);
    }

    public function testBackupDeliveryTestPackageReturnsFile()
    {
        $deliveryUri = 'DUMMY_DELIVERY_URI';
        $expectedFile = $this->createMock(File::class);
        $expectedFileSystem = 'publishing';
        $expectedFilePath = md5($deliveryUri) . DIRECTORY_SEPARATOR . 'qti_test_export.zip';
        $testUri = 'DUMMY_TEST_URI';

        $this->qtiPackageExporterMock->method('exportQtiTestPackageToFile')
            ->with(
                $testUri,
                $expectedFileSystem,
                $expectedFilePath
            )
            ->willReturn($expectedFile);

        $result = $this->subject->backupDeliveryTestPackage($deliveryUri, $testUri);
        self::assertSame(
            $expectedFile,
            $result,
            'Backup method must return correct File object in case of successful backup.'
        );
    }
}

