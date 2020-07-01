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

namespace oat\taoPublishing\model\entity;

use core_kernel_classes_Resource;
use JsonSerializable;
use oat\taoPublishing\model\PlatformService;
use oat\generis\model\OntologyRdfs;

/**
 * @OA\Schema()
 */
class Platform implements JsonSerializable
{
    /**
     * @var string
     * @OA\Property(
     *     description="Platform URI",
     *     type="string",
     * )
     */
    private $uri;

    /**
     * @var string
     * @OA\Property(
     *     description="Platform label",
     *     type="string",
     * )
     */
    private $label;

    /**
     * @var string
     * @OA\Property(
     *     description="Platform root url",
     *     type="string",
     * )
     */
    private $rootUrl;

    /**
     * @var string
     * @OA\Property(
     *     description="Platform box id",
     *     type="string",
     * )
     */
    private $boxId;

    /**
     * @var string
     * @OA\Property(
     *     description="Platform authentication type",
     *     type="string",
     * )
     */
    private $authType;

    /**
     * @var bool
     * @OA\Property(
     *     description="Is publishing enabled",
     *     type="boolean",
     * )
     */
    private $isPublishingEnabled;

    public function __construct(core_kernel_classes_Resource $resource)
    {
        $this->uri = $resource->getUri();
        $values = $resource->getPropertiesValues([
            OntologyRdfs::RDFS_LABEL,
            PlatformService::PROPERTY_ROOT_URL,
            PlatformService::PROPERTY_SENDING_BOX_ID,
            PlatformService::PROPERTY_AUTH_TYPE,
            PlatformService::PROPERTY_IS_PUBLISHING_ENABLED,
        ]);
        $this->label = (string) reset($values[OntologyRdfs::RDFS_LABEL]);
        $this->rootUrl = (string) reset($values[PlatformService::PROPERTY_ROOT_URL]);
        $this->boxId = (string) reset($values[PlatformService::PROPERTY_SENDING_BOX_ID]);
        $this->isPublishingEnabled = (bool) reset($values[PlatformService::PROPERTY_IS_PUBLISHING_ENABLED]);
        $authType = reset($values[PlatformService::PROPERTY_AUTH_TYPE]);
        $this->authType = $authType instanceof core_kernel_classes_Resource ? $authType->getUri() : (string) $authType;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getRootUrl(): string
    {
        return $this->rootUrl;
    }

    public function getBoxId(): string
    {
        return $this->boxId;
    }

    public function getAuthType(): string
    {
        return $this->authType;
    }

    public function isPublishingEnabled(): bool
    {
        return $this->isPublishingEnabled;
    }

    public function jsonSerialize(): array
    {
        return [
            'uri' => $this->getUri(),
            'label' => $this->getLabel(),
            'rootUrl' => $this->getRootUrl(),
            'boxId' => $this->getBoxId(),
            'authType' => $this->getAuthType(),
            'isPublishingEnabled' => $this->isPublishingEnabled(),
        ];
    }
}
