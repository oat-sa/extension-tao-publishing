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

namespace oat\taoPublishing\test\unit\model\publishing\event;

use oat\generis\test\TestCase;
use oat\tao\model\webhooks\configEntity\Webhook;
use oat\tao\model\webhooks\configEntity\WebhookInterface;
use oat\taoPublishing\model\publishing\event\RemoteDeliveryCreatedEvent;

class RemoteDeliveryCreatedEventTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $deliveryUri = 'DUMMY_DELIVERY_URI';
        $testUri = 'DUMMY_TEST_URI';
        $remoteDeliveryUri = 'DUMMY_REMOTE_DELIVERY_URI';
        $alias = 'DUMMY_ALIAS';

        $event = new RemoteDeliveryCreatedEvent($deliveryUri, $testUri, $remoteDeliveryUri, $alias);
        $serialisedEvent = $event->jsonSerialize();

        self::assertIsArray($serialisedEvent, 'Serialization method must return an array.');
        self::assertArrayHasKey('deliveryId', $serialisedEvent, 'Serialised event must contain delivery ID.');
        self::assertArrayHasKey('testId', $serialisedEvent, 'Serialised event must contain test ID.');
        self::assertArrayHasKey('remoteDeliveryId', $serialisedEvent, 'Serialised event must contain remote delivery ID.');
        self::assertArrayHasKey('alias', $serialisedEvent, 'Serialised event must contain alias.');
    }

    public function testIsSatisfiedByTrue(): void
    {
        $deliveryUri = 'DUMMY_DELIVERY_URI';
        $testUri = 'DUMMY_TEST_URI';
        $remoteDeliveryUri = 'DUMMY_REMOTE_DELIVERY_URI';
        $alias = 'DUMMY_ALIAS';
        $webhookId = 'existed_webhook_id';

        $event = new RemoteDeliveryCreatedEvent($deliveryUri, $testUri, $remoteDeliveryUri, $alias, $webhookId);

        $webhook = new Webhook('existed_webhook_id', 'url', 'POST', 1);

        self::assertTrue($event->isSatisfiedBy($webhook));
    }

    public function testIsSatisfiedByFalse(): void
    {
        $deliveryUri = 'DUMMY_DELIVERY_URI';
        $testUri = 'DUMMY_TEST_URI';
        $remoteDeliveryUri = 'DUMMY_REMOTE_DELIVERY_URI';
        $alias = 'DUMMY_ALIAS';
        $webhookId = 'existed_webhook_id';

        $event = new RemoteDeliveryCreatedEvent($deliveryUri, $testUri, $remoteDeliveryUri, $alias, $webhookId);

        $webhook = new Webhook('not_existed_webhook_id', 'url', 'POST', 1);

        self::assertFalse($event->isSatisfiedBy($webhook));
    }
}

