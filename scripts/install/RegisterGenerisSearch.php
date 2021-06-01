<?php

namespace oat\taoPublishing\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\tao\model\search\Search;
use oat\tao\model\search\SearchProxy;
use oat\tao\model\search\strategy\GenerisSearch as OriginalGenerisSearch;
use oat\taoPublishing\model\search\GenerisSearch;

/**
 * Class RegisterGenerisSearch
 * @package oat\taoPublishing\scripts\install
 */
class RegisterGenerisSearch extends InstallAction
{
    public function __invoke($params)
    {
        /** @var SearchProxy $searchProxy */
        $searchProxy = $this->getServiceManager()->get(SearchProxy::SERVICE_ID);

        /** @var OriginalGenerisSearch $generisSearch */
        $generisSearch = $searchProxy->getOption(SearchProxy::OPTION_DEFAULT_SEARCH_CLASS);

        if ($generisSearch instanceof OriginalGenerisSearch) {
            $searchProxy->setOption(SearchProxy::OPTION_DEFAULT_SEARCH_CLASS, new GenerisSearch($generisSearch->getOptions()));

            $this->getServiceManager()->register(Search::SERVICE_ID, $searchProxy);
        }

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Generis search service registered');
    }
}