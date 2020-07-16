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

namespace oat\taoPublishing\test\unit\model\publishing\delivery;

use common_report_Report;
use common_exception_MissingParameter;
use oat\generis\model\data\Ontology;
use oat\generis\test\MockObject;
use oat\generis\test\TestCase;
use oat\oatbox\event\EventManager;
use oat\oatbox\log\LoggerService;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\delivery\tasks\RemoteTaskStatusSynchroniser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class RemoteTaskStatusSynchroniserTest extends TestCase
{
    /** @var RemoteTaskStatusSynchroniser */
    private $subject;

    /** @var PlatformService|MockObject */
    private $platformServiceMock;

    /** @var EventManager|MockObject */
    private $eventManagerMock;

    /** @var LoggerService|MockObject */
    private $loggerMock;

    /** @var Ontology|MockObject */
    private $ontologyMock;

    private $dataPath = __DIR__ . '/data/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->ontologyMock = $this->createMock(Ontology::class);
        $this->platformServiceMock = $this->createMock(PlatformService::class);
        $this->eventManagerMock = $this->createMock(EventManager::class);
        $this->loggerMock = $this->createMock(LoggerService::class);
        $serviceLocatorMock = $this->getServiceLocatorMock(
            [
                PlatformService::class => $this->platformServiceMock,
                EventManager::SERVICE_ID => $this->eventManagerMock,
                LoggerService::SERVICE_ID => $this->loggerMock
            ]
        );

        $this->subject = new RemoteTaskStatusSynchroniser();
        $this->subject->setServiceLocator($serviceLocatorMock);
        $this->subject->setModel($this->ontologyMock);
    }

    public function testInvoke_WhenRemoteTaskCompleted_ReturnsSuccessReport()
    {
        $params = [
            'DUMMY_REMOTE_TASK_ID',
            'DUMMY_ENVIRONMENT_ID',
            'DUMMY_DELIVERY_ID',
            'DUMMY_TEST_ID'
        ];

        $expectedStatus = '"completed"';
        $statusResponseMock = $this->getResponseMock(200, $expectedStatus);

        $expectedReport = file_get_contents($this->dataPath . 'successful_remote_task_report.json');
        $taskDataResponseMock = $this->getResponseMock(200, $expectedReport);

        $this->platformServiceMock->method('callApi')
            ->willReturnOnConsecutiveCalls(
                $statusResponseMock,
                $taskDataResponseMock
            );

        $report = $this->subject->__invoke($params);

        self::assertInstanceOf(common_report_Report::class, $report, 'Method must return report instance.');
        self::assertFalse($report->containsError(), 'Task report should not contain errors.');
    }

    public function testInvoke_WhenRemoteTaskFailed_ReturnsErrorReport()
    {
        $params = [
            'DUMMY_REMOTE_TASK_ID',
            'DUMMY_ENVIRONMENT_ID',
            'DUMMY_DELIVERY_ID',
            'DUMMY_TEST_ID'
        ];

        $expectedStatus = '"completed"';
        $statusResponseMock = $this->getResponseMock(200, $expectedStatus);

        $expectedReport = file_get_contents($this->dataPath . 'failed_remote_task_report.json');
        $taskDataResponseMock = $this->getResponseMock(200, $expectedReport);

        $this->platformServiceMock->method('callApi')
            ->willReturnOnConsecutiveCalls(
                $statusResponseMock,
                $taskDataResponseMock
            );

        $report = $this->subject->__invoke($params);

        self::assertInstanceOf(common_report_Report::class, $report, 'Method must return report instance.');
        self::assertTrue($report->containsError(), 'Task report must have error reports on failure.');
    }

    public function testInvoke_WhenNotAllRequiredParametersPassed_throwsException()
    {
        $params = [
            'DUMMY_REMOTE_TASK_ID',
            'DUMMY_ENVIRONMENT_ID',
            'DUMMY_DELIVERY_ID'
        ];

        $this->expectException(common_exception_MissingParameter::class);
        $this->subject->__invoke($params);
    }

    public function testInvoke_WhenResponseStatusNotSuccessful_ReturnsErrorReport()
    {
        $params = [
            'DUMMY_REMOTE_TASK_ID',
            'DUMMY_ENVIRONMENT_ID',
            'DUMMY_DELIVERY_ID',
            'DUMMY_TEST_ID'
        ];

        $expectedStatus = '"completed"';
        $statusResponseMock = $this->getResponseMock(400, $expectedStatus);

        $this->platformServiceMock->method('callApi')
            ->willReturn($statusResponseMock);

        $report = $this->subject->__invoke($params);

        self::assertInstanceOf(common_report_Report::class, $report, 'Method must return report instance.');
        self::assertSame(common_report_Report::TYPE_ERROR, $report->getType(),'Task report must have error type when request failed.');
    }

    public function testInvoke_WhenRemoteTaskNotFinished_ReturnsInfoReport()
    {
        $params = [
            'DUMMY_REMOTE_TASK_ID',
            'DUMMY_ENVIRONMENT_ID',
            'DUMMY_DELIVERY_ID',
            'DUMMY_TEST_ID'
        ];

        $expectedStatus = '"in_progress"';
        $statusResponseMock = $this->getResponseMock(200, $expectedStatus);

        $this->platformServiceMock->method('callApi')
            ->willReturn($statusResponseMock);

        $report = $this->subject->__invoke($params);

        self::assertSame('in_progress', $this->subject->getRemoteStatus(), 'Remote task status must be as expected.');
        self::assertInstanceOf(common_report_Report::class, $report, 'Method must return report instance.');
        self::assertSame(common_report_Report::TYPE_INFO, $report->getType(),'Task report must have info type when remote task not finished yet.');
    }

    /**
     * @param int $statusCode
     * @param string $responseData
     * @return ResponseInterface|MockObject
     */
    private function getResponseMock(int $statusCode, string $responseData): ResponseInterface
    {
        $bodyContents = sprintf('{"success":true,"data": %s}', $responseData);
        $bodyMock = $this->createMock(StreamInterface::class);
        $bodyMock->method('getContents')
            ->willReturn($bodyContents);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')
            ->willReturn($statusCode);
        $responseMock->method('getBody')
            ->willReturn($bodyMock);

        return $responseMock;
    }
}

