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
 * Copyright (c) 2018 (original work) Open Assessment Technologies SA;
 *
 *
 */
namespace oat\taoPublishing\model\search;

use oat\generis\model\OntologyRdfs;
use oat\tao\model\search\ResultSet;
use oat\tao\model\search\strategy\GenerisSearch as OriginGenerisSearch;
use oat\generis\model\OntologyAwareTrait;
use oat\taoPublishing\model\publishing\delivery\PublishingDeliveryService;


class GenerisSearch extends OriginGenerisSearch
{
    use OntologyAwareTrait;

    /**
     * (non-PHPdoc)
     * @see \oat\tao\model\search\Search::query()
     */
    public function query($queryString, $type, $start = 0, $count = 10, $order = 'id', $dir = 'DESC') {

        $rootClass = $this->getClass($type);
        $results = $rootClass->searchInstances([
            OntologyRdfs::RDFS_LABEL => $queryString,
            PublishingDeliveryService::ORIGIN_DELIVERY_ID_FIELD => $queryString,
            PublishingDeliveryService::ORIGIN_TEST_ID_FIELD => $queryString,
        ], [
            'recursive' => true,
            'like'      => true,
            'chaining' => 'or',
            'offset'    => $start,
            'limit'     => $count,
        ]);
        $ids = array();
        foreach ($results as $resource) {
            $ids[] = $resource->getUri();
        }

        return new ResultSet($ids, $this->getTotalCount($queryString, $rootClass));
    }

    /**
     * Return total count of corresponded instances
     *
     * @param string $queryString
     * @param \core_kernel_classes_Class $rootClass
     *
     * @return array
     */
    private function getTotalCount($queryString, $rootClass = null )
    {
        return $rootClass->countInstances(
            [
                OntologyRdfs::RDFS_LABEL => $queryString,
                PublishingDeliveryService::ORIGIN_DELIVERY_ID_FIELD => $queryString,
                PublishingDeliveryService::ORIGIN_TEST_ID_FIELD => $queryString,
            ],
            [
                'recursive' => true,
                'like'      => true,
                'chaining' => 'or'
            ]
        );
    }
}
