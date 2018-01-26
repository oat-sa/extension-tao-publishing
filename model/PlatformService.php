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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA
 */
namespace oat\taoPublishing\model;
use oat\tao\model\BasicAuth;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
/**
 * Service methods to manage the Platforms
 *
 * @access public
 */
class PlatformService extends \tao_models_classes_ClassService
{
    const CLASS_URI = 'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatform';
    const PROPERTY_AUTH_TYPE = 'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformAuthType';
    const PROPERTY_ROOT_URL = 'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformUrl';
    
    /**
     * return the group top level class
     *
     * @access public
     * @author Joel Bout, <joel.bout@tudor.lu>
     * @return \core_kernel_classes_Class
     */
    public function getRootClass()
    {
        return new \core_kernel_classes_Class(self::CLASS_URI);
    }

    /**
     * @param $platformId
     * @param RequestInterface $request
     * @return mixed|ResponseInterface
     * @throws \common_Exception
     * @throws \core_kernel_classes_EmptyProperty
     */
    public function callApi($platformId, RequestInterface $request)
    {
        $platform = $this->getResource($platformId);
        $rootUrl = $platform->getUniquePropertyValue($this->getProperty(self::PROPERTY_ROOT_URL));
        $rootUrl = rtrim($rootUrl,"/").'/';

        $authType = (string)$platform->getUniquePropertyValue($this->getProperty(self::PROPERTY_AUTH_TYPE));

        if ($authType == BasicAuth::CLASS_BASIC_AUTH) {
            $user = (string)$platform->getUniquePropertyValue($this->getProperty(BasicAuth::LOGIN));
            $password = (string)$platform->getUniquePropertyValue($this->getProperty(BasicAuth::PASSWORD));
        }

        $relUrl = $request->getUri()->__toString();
        $absUrl = $rootUrl.ltrim($relUrl,'/');
        $request = $request->withUri(new Uri($absUrl)); 

        if (!isset($user) || empty($user) || !isset($password) || empty($password)) {
            throw new \common_Exception('Remote Publishing can not be used without credentials');
        }

        $client = new Client();
        $response = $client->send($request, ['auth' => [$user, $password], 'verify' => false]);
        return $response;
    }
}
