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
 * Copyright (c) 2021 (original work) Open Assessment Technologies SA;
 */

declare(strict_types=1);

namespace oat\taoPublishing\scripts\tools;

use Exception;
use oat\oatbox\event\EventManager;
use oat\oatbox\extension\script\ScriptAction;
use oat\oatbox\reporting\Report;
use oat\oatbox\service\ConfigurableService;
use oat\oatbox\service\ServiceManagerAwareTrait;
use oat\tao\model\webhooks\configEntity\Webhook;
use oat\tao\model\webhooks\WebhookEventsService;
use oat\tao\model\webhooks\WebhookRegistryManager;
use oat\tao\model\webhooks\WebhookRegistryManagerInterface;
use oat\taoPublishing\model\publishing\event\RemoteDeliveryCreatedEvent;

class RegisterPublishedDeliveryWebhook extends ScriptAction
{
    use ServiceManagerAwareTrait;

    private const MAX_RETRY = 5;
    private const DEFAULT_HTTP_METHOD = 'GET';

    private const ACCEPTED_HTTP_METHOD = [
        'POST',
        self::DEFAULT_HTTP_METHOD
    ];

    /** @var Report */
    private $report;

    protected function provideOptions(): array
    {
        return [
            'simpleRosterUrl' => [
                'prefix' => 'u',
                'longPrefix' => 'url',
                'flag' => false,
                'description' => 'Will register endpoint to inform about new deliveries published',
                'required' => true
            ],
            'httpMethod' => [
                'prefix' => 'm',
                'description' => 'Determine what type of http method use',
                'flag' => false,
                'required' => false
            ]
        ];
    }

    protected function provideDescription(): string
    {
        return 'Script will register webhook with a defined url';
    }

    protected function run(): Report
    {
        $this->report = Report::createInfo('Registering webhook');

        /** @var ConfigurableService $eventManager */
        $eventManager = $this->getServiceLocator()->get(EventManager::class);
        /** @var ConfigurableService $webhookEventsService */
        $webhookEventsService = $this->getServiceLocator()->get(WebhookEventsService::class);

        try {
            $webhookEventsService->registerEvent(RemoteDeliveryCreatedEvent::class);
            $this->getServiceManager()->register(WebhookEventsService::SERVICE_ID, $webhookEventsService);
            $this->getServiceManager()->register(EventManager::SERVICE_ID, $eventManager);
        } catch (Exception $exception) {
            $this->report->add(
                Report::createError('Registering failed')
            );
        }

        $this->getWebhookRegistryManager()->addWebhookConfig(
            $this->createWebHook(),
            RemoteDeliveryCreatedEvent::class
        );

        return $this->report;
    }

    private function getWebhookRegistryManager(): WebhookRegistryManagerInterface
    {
        return $this->getServiceLocator()->get(WebhookRegistryManager::class);
    }

    private function getHttpMethod(): string
    {
        $method = $this->getOption('httpMethod') ?? self::DEFAULT_HTTP_METHOD;
        if (!in_array($method, self::ACCEPTED_HTTP_METHOD, true)) {
            $this->report->add(
                Report::createWarning(sprintf("Used illegal method %s. Falling back to default method", $method))
            );
            return self::DEFAULT_HTTP_METHOD;
        }

        return $method;
    }

    private function createWebHook(): Webhook
    {
        return new Webhook(
            'remoteDeliveryWebHook',
            $this->getOption('simpleRosterUrl'),
            $this->getHttpMethod(),
            self::MAX_RETRY,
            null,
            false
        );
    }
}
