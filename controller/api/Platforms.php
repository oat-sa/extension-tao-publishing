<?php
/**
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; under version 2
 *  of the License (non-upgradable).
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 *
 *
 */

declare(strict_types=1);

namespace oat\taoPublishing\controller\api;

use common_exception_RestApi;
use Exception;
use oat\taoPublishing\model\entity\Platform;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\CrudPlatformsService;
use tao_actions_CommonRestModule;
use tao_actions_RestController;

/**
 * @OA\Info(title="Platform API", version="0.1")
 */
class Platforms extends tao_actions_CommonRestModule
{
    public function __construct()
    {
        parent::__construct();
        $this->service = CrudPlatformsService::singleton();
    }

    public function index()
    {
        $request = $this->getPsrRequest();
        if ($request->getMethod() !== 'GET') {
            return $this->returnFailure(new \common_exception_BadRequest($request->getUri()->getPath()));
        }

        parent::index();
    }

    /**
     * @OA\Get(
     *     path="/taoPublishing/api/platforms",
     *     tags={"platforms"},
     *     summary="Index of platforms",
     *     description="Index of platforms",
     *     @OA\Response(
     *         response="200",
     *         description="Platform data",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="success",
     *                     type="boolean",
     *                     description="`false` on failure, `true` on success",
     *                 ),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         ref="#/components/schemas/Platform",
     *                     ),
     *                 ),
     *                 example= {
     *                     "success": true,
     *                     "data": {
     *                         {
     *                             "uri": "http://sample/first.rdf#i1536680377163170",
     *                             "label": "Sample label",
     *                             "authType": "http://www.tao.lu/Ontologies/TAO.rdf#BasicAuthConsumer",
     *                             "rootUri": "http://ROOT/URI",
     *                             "boxId": "1",
     *                             "isPublishingEnabled": true,
     *                         }
     *                     }
     *                 }
     *             )
     *         ),
     *     ),
     * )
     */

    /**
     * @return array
     */
    protected function getParametersAliases()
    {
        return array_merge(parent::getParametersAliases(), [
            'rootUrl' => PlatformService::PROPERTY_ROOT_URL,
            'authType' => PlatformService::PROPERTY_AUTH_TYPE,
            'boxId' => PlatformService::PROPERTY_SENDING_BOX_ID,
            'isPublishingEnabled' => PlatformService::PROPERTY_IS_PUBLISHING_ENABLED,
        ]);
    }
}
