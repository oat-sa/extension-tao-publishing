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

namespace oat\taoPublishing\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\oatbox\reporting\Report;
use oat\tao\model\search\Search;
use oat\tao\model\search\SearchProxy;
use oat\tao\model\search\strategy\GenerisSearch as OriginalGenerisSearch;
use oat\taoPublishing\model\search\GenerisSearch as TaoPublishingGenerisSearch;

class UnRegisterGenerisSearch extends InstallAction
{
    public function __invoke($params)
    {
        /** @var SearchProxy $searchProxy */
        $searchProxy = $this->getServiceManager()->get(SearchProxy::SERVICE_ID);

        /** @var OriginalGenerisSearch $generisSearch */
        $generisSearch = $searchProxy->getOption(SearchProxy::OPTION_DEFAULT_SEARCH_CLASS);

        if ($generisSearch instanceof TaoPublishingGenerisSearch) {
            $searchProxy->setOption(
                SearchProxy::OPTION_DEFAULT_SEARCH_CLASS,
                new OriginalGenerisSearch($generisSearch->getOptions())
            );

            $this->getServiceManager()->register(Search::SERVICE_ID, $searchProxy);
        }

        return new Report(Report::TYPE_SUCCESS, OriginalGenerisSearch::class . ' registered');
    }
}
