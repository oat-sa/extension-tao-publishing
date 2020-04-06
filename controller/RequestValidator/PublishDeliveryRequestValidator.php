<?php declare(strict_types=1);

namespace oat\taoPublishing\controller\RequestValidator;

use oat\oatbox\service\ConfigurableService;
use Psr\Http\Message\ServerRequestInterface;

class PublishDeliveryRequestValidator extends ConfigurableService
{
    private const DELIVERY_URI = 'uri';
    public const SERVICE_ID = self::class;

    /**
     * @param ServerRequestInterface $request
     *
     * @throws InvalidRequestException
     */
    public function validate(ServerRequestInterface $request): void {
        $body = $request->getParsedBody();

        if (!isset($body[self::DELIVERY_URI])) {
            throw new InvalidRequestException("Undefined URI");
        }

        if (filter_var($body[self::DELIVERY_URI], FILTER_VALIDATE_URL)) {
            throw new InvalidRequestException("URI Must be a valid URL");
        }
    }
}