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

namespace oat\taoPublishing\model\deliveryRdfClient\factory;

use oat\taoPublishing\model\deliveryRdfClient\entity\CompileDeferredResult;
use Psr\Http\Message\ResponseInterface;

class CompileDeferredResultFactory
{
    public function make(ResponseInterface $response): CompileDeferredResult
    {
        $responseRaw = json_decode($response->getBody()->getContents(), true);

        return new CompileDeferredResult($responseRaw['data']['reference_id']);
    }
}