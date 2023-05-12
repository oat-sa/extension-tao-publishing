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

namespace oat\taoPublishing\model\publishing\event;

use oat\tao\model\webhooks\configEntity\WebhookInterface;
use oat\tao\model\webhooks\WebhookConditionalEventInterface;
use oat\tao\model\webhooks\WebhookSerializableEventInterface;
use oat\taoDeliveryRdf\model\event\AbstractDeliveryEvent;

/**
 * Class RemoteDeliveryCreatedEvent
 */
class RemoteDeliveryCreatedEvent extends AbstractDeliveryEvent implements WebhookSerializableEventInterface, WebhookConditionalEventInterface
{
    /**
     * @var string
     */
    private $testUri;
    /**
     * @var string
     */
    private $remoteDeliveryUri;

    /** @var string|null */
    private $alias;

    /** @var string|null */
    private ?string $webhookId;

    public function __construct(
        string $deliveryUri,
        string $testUri,
        string $remoteDeliveryUri,
        string $alias = null,
        ?string $webhookId = null
    ) {
        $this->deliveryUri = $deliveryUri;
        $this->testUri = $testUri;
        $this->remoteDeliveryUri = $remoteDeliveryUri;
        $this->alias = $alias;
        $this->webhookId = $webhookId;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::class;
    }

    /**
     * @return string
     */
    public function getWebhookEventName()
    {
        return $this->getName();
    }

    public function serializeForWebhook()
    {
        return $this->jsonSerialize();
    }

    public function jsonSerialize()
    {
        return [
            'deliveryId' => $this->deliveryUri,
            'testId' => $this->testUri,
            'remoteDeliveryId' => $this->remoteDeliveryUri,
            'alias' => $this->alias,
            'webhookId' => $this->webhookId
        ];
    }

    public function isSatisfiedBy(WebhookInterface $webhook): bool
    {
        return $this->webhookId === $webhook->getId();
    }
}
