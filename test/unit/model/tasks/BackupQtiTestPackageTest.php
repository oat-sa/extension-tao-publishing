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

namespace oat\taoPublishing\test\unit\model\tasks;

use Exception;
use common_exception_MissingParameter;
use common_report_Report;
use oat\generis\test\MockObject;
use oat\oatbox\filesystem\File;
use oat\oatbox\log\LoggerService;
use oat\taoPublishing\model\publishing\test\TestBackupService;
use oat\taoPublishing\model\tasks\BackupQtiTestPackage;
use oat\generis\test\TestCase;

class BackupQtiTestPackageTest extends TestCase
{
    /** BackupQtiTestPackage */
    private $subject;

    /** @var TestBackupService|MockObject */
    private $testBackupServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testBackupServiceMock = $this->createMock(TestBackupService::class);
        $serviceLocatorMock = $this->getServiceLocatorMock(
            [
                TestBackupService::class => $this->testBackupServiceMock,
                LoggerService::SERVICE_ID => $this->createMock(LoggerService::class),
            ]
        );

        $this->subject = new BackupQtiTestPackage();
        $this->subject->setServiceLocator($serviceLocatorMock);
    }

    public function testInvoke_ThrowsExceptionWhenDeliveryUriParameterIsMissing(): void
    {
        $params = [];

        $this->expectException(common_exception_MissingParameter::class);
        $this->subject->__invoke($params);
    }

    public function testInvoke_ReturnsFailureReportWhenTestBackupFails(): void
    {
        $params = ['deliveryUri' => 'DUMMY_DELIVERY_URI'];
        $this->testBackupServiceMock
            ->method('backupDeliveryTestPackage')
            ->willThrowException(new Exception('FAKE_EXCEPTION'));

        $result = $this->subject->__invoke($params);

        self::assertInstanceOf(common_report_Report::class, $result, 'Method must return a report object.');
        self::assertTrue($result->containsError(), 'Report object must contain errors when backup failed.');
    }

    public function testInvokeReturnsSuccessfulReport(): void
    {
        $params = ['deliveryUri' => 'DUMMY_DELIVERY_URI'];

        $fileMock = $this->createMock(File::class);
        $fileMock->method('getPrefix')
            ->willReturn('FAKE/FILE/PATH.ext');
        $this->testBackupServiceMock
            ->method('backupDeliveryTestPackage')
            ->willReturn($fileMock);

        $result = $this->subject->__invoke($params);

        self::assertInstanceOf(common_report_Report::class, $result, 'Method must return a report object.');
        self::assertFalse($result->containsError(), 'Report object should not contain errors when backup failed.');
    }
}

