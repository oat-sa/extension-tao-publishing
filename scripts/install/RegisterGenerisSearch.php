<?php

namespace oat\taoPublishing\scripts\install;

use oat\oatbox\extension\InstallAction;
use oat\tao\model\search\Search;
use oat\tao\model\search\strategy\GenerisSearch;

/**
 * Class RegisterGenerisSearch
 * @package oat\taoPublishing\scripts\install
 */
class RegisterGenerisSearch extends InstallAction
{
    public function __invoke($params)
    {
        $searchService = $this->getServiceLocator()->get(Search::SERVICE_ID);
        if ($searchService instanceof GenerisSearch) {
            $newSearchService = new \oat\taoPublishing\model\search\GenerisSearch($searchService->getOptions());
            $this->getServiceManager()->register(Search::SERVICE_ID, $newSearchService);
        }

        return new \common_report_Report(\common_report_Report::TYPE_SUCCESS, 'Generis search service registered');
    }
}