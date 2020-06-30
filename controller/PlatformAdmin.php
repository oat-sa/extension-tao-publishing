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
 * Copyright (c) 2016 (original work) Open Assessment Technologies SA;
 *
 *
 */

namespace oat\taoPublishing\controller;

use common_ext_ExtensionsManager;
use oat\generis\model\OntologyAwareTrait;
use oat\tao\model\auth\AbstractAuthType;
use oat\tao\model\auth\BasicAuth;
use oat\tao\model\oauth\DataStore;
use oat\taoPublishing\model\PlatformService;
use oat\taoPublishing\model\publishing\PublishingAuthService;
use oat\taoPublishing\model\publishing\PublishingService;

/**
 * Sample controller
 *
 * @author Open Assessment Technologies SA
 * @package taoPublishing
 * @license GPL-2.0
 *
 */
class PlatformAdmin extends \tao_actions_SaSModule
{

    use OntologyAwareTrait;

    public function __construct()
    {
        parent::__construct();
        $this->service = $this->getClassService();
    }

    /**
     * @param $instance
     * @throws \Exception
     * @throws \tao_models_classes_dataBinding_GenerisFormDataBindingException
     */
    public function saveInstance($instance = null)
    {
        if(!\tao_helpers_Request::isAjax()){
            throw new \Exception("wrong request mode");
        }

        /** @var PublishingService $publishingService */
        $publishingService = $this->getServiceLocator()->get(PublishingService::SERVICE_ID);
        $clazz = $this->getCurrentClass();
        $myFormContainer = new \tao_actions_form_Instance($clazz, $instance);

        $myForm = $myFormContainer->getForm();

        if($myForm->isSubmited()){
            if($myForm->isValid()){
                /** @var PublishingAuthService $publishingAuthService */
                $publishingAuthService = $this->getServiceLocator()->get(PublishingAuthService::SERVICE_ID);
                // For treeBox component we need to add slashes for rdf value before saving
                $values = $publishingService->addSlashes($myForm->getValues());

                /** @var AbstractAuthType $authType */
                $authType = $publishingAuthService->getAuthType(
                    $this->getResource($this->getRequest()->getParameter(\tao_helpers_Uri::encode(PlatformService::PROPERTY_AUTH_TYPE)))
                );

                // according to the auth type we need to add properties for the authenticator
                $values[PlatformService::PROPERTY_AUTH_TYPE] = $authType->getAuthClass()->getUri();
                foreach ($authType->getAuthProperties() as $authProperty) {
                    $values[$authProperty->getUri()]
                         = $this->getPostParameter(\tao_helpers_Uri::encode($authProperty->getUri()));
                }

                $message = __('Undefined Instance can not be saved');
                if (!$instance) {
                    $this->createInstance(array($clazz), $values);
                    $message = __('Instance created');
                } elseif ($instance instanceof \core_kernel_classes_Resource) {
                    // save properties
                    $binder = new \tao_models_classes_dataBinding_GenerisFormDataBinder($instance);
                    $binder->bind($values);
                    $message = __('Instance saved');
                }

                $this->setData('message', $message);
                $this->setData('reload', true);
            }
        }

        $this->setData('myForm', $myForm->render());
        $this->setView('form.tpl', 'tao');
    }

    public function editInstance()
    {
        $this->setData('formTitle', __('Edit Instance'));
        $this->saveInstance($this->getCurrentInstance());
    }

    public function addInstanceForm()
    {
        $this->setData('formTitle', __('Create instance'));
        $this->saveInstance();
    }

    /**
     * @throws \common_Exception
     * @throws \core_kernel_persistence_Exception
     * @throws \tao_models_classes_MissingRequestParameterException
     */
    public function authTpl()
    {
        /** @var PublishingAuthService $publishingAuthService */
        $publishingAuthService = $this->getServiceLocator()->get(PublishingAuthService::SERVICE_ID);

        /** @var AbstractAuthType $authType */
        $authType = null;
        if ($this->hasRequestParameter('uri') && $this->getRequestParameter('uri')) {
            $instance = $this->getCurrentInstance();
            $authType = $publishingAuthService->getAuthType(
                $instance->getOnePropertyValue($this->getProperty(PlatformService::PROPERTY_AUTH_TYPE))
            );

            $authType->setInstance($instance);
        } else {
            $authType = $publishingAuthService->getAuthType();
        }

        $this->setData('authType', $authType);
        $this->setData('allowedTypes', $publishingAuthService->getTypes());
        $this->setView('auth/form.tpl');

        $this->returnJson([
            'data' => $this->getRenderer()->render(),
            'success' => true,
        ]);

        // prevent further render
        $this->renderer = null;
    }

    /**
     * (non-PHPdoc)
     *
     * @see tao_actions_RdfController::getClassService()
     */
    public function getClassService()
    {
        return PlatformService::singleton();
    }
}