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

namespace oat\taoPublishing\model\deliveryRdfClient\resource\restTest;

use oat\taoPublishing\model\deliveryRdfClient\entity\CompileDeferredResult;
use oat\taoPublishing\model\deliveryRdfClient\entity\Delivery;
use oat\taoPublishing\model\deliveryRdfClient\entity\TestPackage;
use oat\taoPublishing\model\deliveryRdfClient\factory\CompileDeferredRequestFactory;
use oat\taoPublishing\model\deliveryRdfClient\factory\CompileDeferredResultFactory;
use Psr\Http\Client\ClientInterface;
use Throwable;

class CompileDeferredAction
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var CompileDeferredRequestFactory
     */
    private $requestFactory;

    /**
     * @var CompileDeferredResultFactory
     */
    private $resultFactory;

    public function __construct(
        ClientInterface $client,
        CompileDeferredRequestFactory $requestFactory,
        CompileDeferredResultFactory $resultFactory
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @param TestPackage $testPackage
     * @param Delivery $delivery
     * @param string $importerId
     *
     * @return CompileDeferredResult
     *
     * @throws CompileDeferredFailureException
     */
    public function compileDeferred(TestPackage $testPackage, Delivery $delivery, string $importerId)
    {
        try {
            $response = $this->client->sendRequest(
                $this->requestFactory->make($testPackage, $delivery, $importerId)
            );
        } catch (Throwable $e) {
            throw new CompileDeferredFailureException('Fail when try to send the Request.', 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            $error = json_decode($response->getBody()->getContents(), true);

            throw new CompileDeferredFailureException($error['errorMsg'], $error['errorCode']);
        }

        return $this->resultFactory->make($response);
    }
}