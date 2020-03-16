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
 *
 */

namespace oat\taoPublishing\model\deliveryRdfClient\entity;

class Delivery
{
    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $originDeliveryId;

    /**
     * @var string|null
     */
    private $deliveryClassLabel;

    public function __construct(string $label, string $originDeliveryId, ?string $deliveryClassLabel)
    {
        $this->label = $label;
        $this->originDeliveryId = $originDeliveryId;
        $this->deliveryClassLabel = $deliveryClassLabel;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getOriginDeliveryId(): string
    {
        return $this->originDeliveryId;
    }

    public function getDeliveryClassLabel(): ?string
    {
        return $this->deliveryClassLabel;
    }

    public function setDeliveryClassLabel(string $deliveryClassLabel): self
    {
        $this->deliveryClassLabel = $deliveryClassLabel;

        return $this;
    }
}