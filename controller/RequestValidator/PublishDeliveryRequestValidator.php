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
 */

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

        if (!filter_var($body[self::DELIVERY_URI], FILTER_VALIDATE_URL)) {
            throw new InvalidRequestException("URI Must be a valid URL");
        }
    }
}