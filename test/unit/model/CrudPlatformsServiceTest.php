<?php
/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 *
 */

declare(strict_types=1);

namespace oat\taoPublishing\test\unit\model;

use core_kernel_classes_Literal;
use common_exception_InvalidArgumentType;
use common_exception_PreConditionFailure;
use core_kernel_classes_Class;
use core_kernel_classes_Resource;
use oat\generis\test\TestCase;
use oat\taoPublishing\model\CrudPlatformsService;
use oat\taoPublishing\model\entity\Platform;
use oat\taoPublishing\model\PlatformService;
use PHPUnit\Framework\MockObject\MockObject;

class CrudPlatformsServiceTest extends TestCase
{
    /** @var CrudPlatformsService */
    private $subject;

    /** @var PlatformService|MockObject  */
    private $platformServiceMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->platformServiceMock = $this->createMock(PlatformService::class);

        $this->subject = $this->createPartialMock(
            CrudPlatformsService::class,
            ['isInScope', 'getResource', 'getClassService']
        );
        $this->subject->method('getClassService')->willReturn($this->platformServiceMock);
    }

    public function testGet_WhenUriIsNotValid_ThenExceptionIsThrown(): void
    {
        $this->expectException(common_exception_InvalidArgumentType::class);
        $this->subject->get('invaliduri');
    }

    public function testGet_WhenUriIsNotInScope_ThenExceptionIsThrown(): void
    {
        $this->expectException(common_exception_PreConditionFailure::class);
        $this->subject->expects($this->once())->method('isInScope')->willReturn(false);
        $this->subject->get('http://test/first.rdf#i1111111111111111');
    }

    public function testGet_WhenCorrectPlatformUriIsProvided_ThenPlatformEntityReturned(): void
    {
        $platformUri = 'http://test/first.rdf#i1111111111111111';
        $this->subject->expects($this->once())->method('isInScope')->willReturn(true);
        $resourceMock = $this->getPlatformResourceMock($platformUri);
        $this->subject->expects($this->once())
            ->method('getResource')
            ->willReturn($resourceMock);

        $result = $this->subject->get($platformUri);
        $this->assertInstanceOf(Platform::class, $result);
    }

    public function testGetAll_WhenRequested_ThenListOfPlatformEntitiesReturned(): void
    {
        $resources = [
            $this->getPlatformResourceMock('http://test/first.rdf#i1111111111111111'),
            $this->getPlatformResourceMock('http://test/second.rdf#i2222222222222222'),
        ];
        $classMock = $this->createMock(core_kernel_classes_Class::class);
        $classMock->method('getInstances')->willReturn($resources);
        $this->platformServiceMock->method('getRootClass')->willReturn($classMock);

        $result = $this->subject->getAll();
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Platform::class, $result[0]);
    }

    /**
     * @return core_kernel_classes_Resource|MockObject
     */
    private function getPlatformResourceMock(string $uri): core_kernel_classes_Resource
    {
        $resourceMock = $this->createMock(core_kernel_classes_Resource::class);
        $resourceMock->method('getUri')
            ->willReturn($uri);
        $resourceProperties = [
            'http://www.w3.org/2000/01/rdf-schema#label' => [new core_kernel_classes_Literal('DUMMY LABEL')],
            'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformUrl' => [new core_kernel_classes_Literal('DUMMY ROOT URL')],
            'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformSendingBoxId' => [new core_kernel_classes_Literal('DUMMY BOX ID')],
            'http://www.tao.lu/Ontologies/TaoPlatform.rdf#PublishingEnabled' => [new core_kernel_classes_Literal(true)],
            'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformAuthType' => [new core_kernel_classes_Literal('DUMMY AUTH TYPE')],
        ];
        $resourceMock->method('getPropertiesValues')
            ->willReturn($resourceProperties);

        return $resourceMock;
    }
}
