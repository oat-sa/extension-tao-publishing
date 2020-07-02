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

namespace oat\taoPublishing\model\routing;

use oat\tao\model\routing\AbstractApiRoute;
use oat\tao\model\routing\RouterException;
use Psr\Http\Message\ServerRequestInterface;
use ResolverException;

class ApiRoute extends AbstractApiRoute
{
    public const REST_CONTROLLER_PREFIX = 'oat\\taoPublishing\\controller\\api\\';

    public const DEFAULT_API_ACTION = 'index';

    /**
     * @inheritdoc
     * @return string
     */
    public static function getControllerPrefix()
    {
        return self::REST_CONTROLLER_PREFIX;
    }

    /**
     * Default controller action is index, unless the URL matches another public action in the controller
     * @param ServerRequestInterface $request
     * @return string|null
     * @throws ResolverException
     */
    public function resolve(ServerRequestInterface $request)
    {
        $relativeUrl = \tao_helpers_Request::getRelativeUrl($request->getRequestTarget());
        try {
            $controllerName = $this->getController($relativeUrl);
        } catch (RouterException $e) {
            return null;
        }

        $action = self::DEFAULT_API_ACTION;
        $parts = explode('/', $relativeUrl);
        if (isset($parts[3]) && is_callable([$controllerName, $parts[3]])) {
            $action = $parts[3];
        }

        return $controllerName . '@' . $action;
    }
}
