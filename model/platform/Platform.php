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

namespace oat\taoPublishing\model\platform;

use oat\taoPublishing\model\PlatformService;

/**
 * @OA\Schema()
 */
class Platform implements \JsonSerializable
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
     * @var bool
     * @OA\Property(
     *     description="Is platform enabled",
     *     type="boolean",
     * )
     */
    private $isEnabled;

    public function __construct(\core_kernel_classes_Resource $resource)
    {
        $this->uri = $resource->getUri();
        $this->label = $resource->getLabel();
        $this->rootUrl = (string) $resource->getOnePropertyValue(
            new \core_kernel_classes_Property(PlatformService::PROPERTY_ROOT_URL)
        );
        $this->boxId = (string) $resource->getOnePropertyValue(
            new \core_kernel_classes_Property(PlatformService::PROPERTY_SENDING_BOX_ID)
        );
        $this->isEnabled = (bool) $resource->getOnePropertyValue(
            new \core_kernel_classes_Property(PlatformService::PROPERTY_IS_ENABLED)
        );
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

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function jsonSerialize(): array
    {
        return [
            'uri' => $this->getUri(),
            'label' => $this->getLabel(),
            'rootUrl' => $this->getRootUrl(),
            'boxId' => $this->getBoxId(),
            'isEnabled' => $this->isEnabled(),
        ];
    }
}
