<?php


namespace oat\taoPublishing\model\publishing\event;


use oat\tao\model\webhooks\WebhookSerializableEventInterface;
use oat\taoDeliveryRdf\model\event\AbstractDeliveryEvent;

/**
 * Class RemotePublishingDeliveryCreatedEvent
 * @package oat\taoPublishing\model\publishing\event
 */
class RemotePublishingDeliveryCreatedEvent extends AbstractDeliveryEvent implements WebhookSerializableEventInterface
{
    /**
     * @var string
     */
    private $testUri;
    /**
     * @var string
     */
    private $remotePublishedDeliveryId;

    /**
     * RemotePublishingDeliveryCreatedEvent constructor.
     * @param string $deliveryUri
     * @param string $testUri
     * @param string $remotePublishedDeliveryId
     */
    public function __construct(string $deliveryUri,string $testUri, string $remotePublishedDeliveryId)
    {
        $this->testUri = $testUri;
        $this->deliveryUri = $deliveryUri;
        $this->remotePublishedDeliveryId =$remotePublishedDeliveryId;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return self::class;
    }

    /**
     * @return string|void
     */
    public function getWebhookEventName()
    {
        return $this->getName();
    }

    public function serializeForWebhook()
    {
        return [
            'deliveryId' => $this->deliveryUri,
            'testId' => $this->testUri,
            'remotePublishedDeliveryId' => $this->remotePublishedDeliveryId,
        ];
    }

    public function jsonSerialize()
    {
        return [
            'delivery' => $this->deliveryUri,
        ];
    }
}