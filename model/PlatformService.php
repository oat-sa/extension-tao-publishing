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
    
    const PROPERTY_AUTHENTICATION = 'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformAuth';
    
    const PROPERTY_ROOT_URL = 'http://www.tao.lu/Ontologies/TAO.rdf#TaoPlatformUrl';
    
    /**
     * return the group top level class
     *
     * @access public
     * @author Joel Bout, <joel.bout@tudor.lu>
     * @return core_kernel_classes_Class
     */
    public function getRootClass()
    {
        return new \core_kernel_classes_Class(self::CLASS_URI);
    }

    /**
     * 
     * @param unknown $platformId
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function callApi($platformId, RequestInterface $request)
    {
        $platform = $this->getResource($platformId);
        $rootUrl = $platform->getUniquePropertyValue($this->getProperty(self::PROPERTY_ROOT_URL));
        $auth = (string)$platform->getUniquePropertyValue($this->getProperty(self::PROPERTY_AUTHENTICATION));
        list($user,$password) = explode(':', $auth, 2);
        
        $relUrl = $request->getUri()->__toString();
        $absUrl = $rootUrl.ltrim($relUrl,'/');
        $request = $request->withUri(new Uri($absUrl)); 
        
        $client = new Client();
        $response = $client->send($request, ['auth' => [$user, $password], 'verify' => false]);
        return $response;
    }
}
