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

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use oat\generis\model\OntologyRdfs;
use oat\taoPublishing\model\deliveryRdfClient\entity\Delivery;
use oat\taoPublishing\model\deliveryRdfClient\entity\TestPackage;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;
use Psr\Http\Message\RequestInterface;

class CompileDeferredRequestFactory
{
    const HTTP_METHOD = 'POST';
    const HTTP_PATH = '/taoDeliveryRdf/RestTest/compileDeferred';

    const PARAMETER_IMPORTER_ID = 'importerId';
    const PARAMETER_FILE_NAME = 'testPackage';
    const PARAMETER_DELIVERY_PARAMS = 'delivery-params';
    const PARAMETER_DELIVERY_CLASS_LABEL = 'delivery-class-label';

    public function make(TestPackage $testPackage, Delivery $delivery, string $importerId): RequestInterface
    {
        $streamData = [
            [
                'name' => self::PARAMETER_FILE_NAME,
                'contents' => fopen($testPackage->getFileName(), 'rb'),
            ],
            [
                'name' => self::PARAMETER_IMPORTER_ID,
                'contents' => $importerId,
            ],
            [
                'name' => self::PARAMETER_DELIVERY_PARAMS,
                'contents' => json_encode(
                    [
                        PublishingDeliveryService::ORIGIN_DELIVERY_ID_FIELD => $delivery->getOriginDeliveryId(),
                        OntologyRdfs::RDFS_LABEL => $delivery->getLabel(),
                    ]
                )
            ]
        ];

        $deliveryClassLabel = $delivery->getDeliveryClassLabel();

        if (!empty($deliveryClassLabel)) {
            $streamData[] = [
                'name' => self::PARAMETER_DELIVERY_CLASS_LABEL,
                'contents' => $deliveryClassLabel
            ];
        }

        return (new Request(self::HTTP_METHOD, self::HTTP_PATH))
            ->withBody(new MultipartStream($streamData));
    }
}